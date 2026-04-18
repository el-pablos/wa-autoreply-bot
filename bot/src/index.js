import 'dotenv/config';
import makeWASocket, {
  useMultiFileAuthState,
  DisconnectReason,
  fetchLatestBaileysVersion,
} from '@whiskeysockets/baileys';
import { Boom } from '@hapi/boom';
import express from 'express';
import QRCode from 'qrcode';
import { config }                  from './config.js';
import { logger }                  from './utils/logger.js';
import {
  updateBotStatus,
  getPool,
  getSetting,
  getSettingsByKeys,
  verifyApiKey,
  upsertAllowListEntry,
  getMessageLogs,
} from './db.js';
import { routeCommand }            from './handlers/commandHandler.js';
import { handleIncomingMessage }   from './handlers/messageHandler.js';
import { startScheduler, stopScheduler } from './utils/scheduler.js';
import { createInternalApiRouter } from './api/internal.js';
import { createPublicApiRouter } from './api/public.js';

let qrCodeDataURL = null;
let connectionStatus = 'disconnected';
let activeSock = null;

// ─────────────────────────────────────────────
// HTTP Server (untuk QR code & health check)
// ─────────────────────────────────────────────
const app = express();
app.use(express.json({ limit: '1mb' }));

app.use(
  '/internal',
  createInternalApiRouter({
    getSock: () => activeSock,
    logger,
    sharedSecret: process.env.INTERNAL_SECRET,
    db: {
      getSetting,
      getSettingsByKeys,
    },
  }),
);

app.use(
  '/api',
  createPublicApiRouter({
    getSock: () => activeSock,
    logger,
    db: {
      verifyApiKey,
      upsertAllowListEntry,
      getMessageLogs,
    },
  }),
);

app.get('/health', (_req, res) => {
  res.json({ status: 'ok', botStatus: connectionStatus, timestamp: new Date().toISOString() });
});

app.get('/qr', async (_req, res) => {
  if (!qrCodeDataURL) {
    return res.status(404).json({ message: 'QR code belum tersedia atau sudah ter-scan' });
  }
  res.send(`
    <!DOCTYPE html>
    <html lang="id">
    <head>
      <meta charset="UTF-8">
      <meta name="viewport" content="width=device-width, initial-scale=1.0">
      <title>WA Bot QR Code</title>
      <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
          min-height: 100vh;
          display: flex;
          flex-direction: column;
          align-items: center;
          justify-content: center;
          background: #111;
          color: #fff;
          font-family: sans-serif;
          gap: 1.5rem;
        }
        h1 { font-size: 1.5rem; }
        p  { opacity: 0.6; font-size: 0.9rem; }
        img { width: 260px; height: 260px; border-radius: 12px; }
      </style>
    </head>
    <body>
      <h1>📱 Scan QR Code WA Bot</h1>
      <img src="${qrCodeDataURL}" alt="QR Code" />
      <p>Buka WhatsApp → Perangkat Tertaut → Tautkan Perangkat</p>
      <p>Halaman ini auto-refresh setiap 30 detik</p>
      <script>setTimeout(() => location.reload(), 30000);</script>
    </body>
    </html>
  `);
});

app.listen(config.bot.port, () => {
  logger.info(`Bot HTTP server berjalan di port ${config.bot.port}`);
});

// ─────────────────────────────────────────────
// Fungsi koneksi WA (dengan auto-reconnect)
// ─────────────────────────────────────────────
async function connectToWhatsApp() {
  // Tunggu MySQL siap (retry sampai 10x dengan delay 3 detik)
  const pool = getPool();
  let dbReady = false;
  for (let attempt = 1; attempt <= 10; attempt++) {
    try {
      await pool.execute('SELECT 1');
      dbReady = true;
      logger.info('Koneksi MySQL berhasil');
      break;
    } catch (err) {
      logger.warn({ attempt }, `Menunggu MySQL siap... (percobaan ${attempt}/10)`);
      await new Promise(r => setTimeout(r, 3000));
    }
  }

  if (!dbReady) {
    logger.error('MySQL tidak bisa dikoneksikan setelah 10 percobaan. Keluar.');
    process.exit(1);
  }

  await startScheduler();

  const { state, saveCreds } = await useMultiFileAuthState(config.bot.authDir);
  const { version }          = await fetchLatestBaileysVersion();

  logger.info({ version }, 'Menggunakan Baileys versi');

  const sock = makeWASocket({
    version,
    auth:            state,
    printQRInTerminal: true,
    logger:          logger.child({ module: 'baileys' }),
    browser:         ['WA Bot', 'Chrome', '1.0.0'],
  });
  activeSock = sock;

  // Simpan credential setiap update
  sock.ev.on('creds.update', saveCreds);

  // Handle koneksi
  sock.ev.on('connection.update', async (update) => {
    const { connection, lastDisconnect, qr } = update;

    if (qr) {
      try {
        qrCodeDataURL = await QRCode.toDataURL(qr);
        logger.info(`QR Code tersedia di http://localhost:${config.bot.port}/qr`);
      } catch (err) {
        logger.error({ err }, 'Gagal generate QR DataURL');
      }
    }

    if (connection === 'connecting') {
      connectionStatus = 'connecting';
      await updateBotStatus('connecting').catch(() => {});
      logger.info('Menghubungkan ke WhatsApp...');
    }

    if (connection === 'open') {
      connectionStatus = 'online';
      qrCodeDataURL    = null;
      await updateBotStatus('online').catch(() => {});
      logger.info('✅ WhatsApp terhubung!');
    }

    if (connection === 'close') {
      connectionStatus = 'offline';
      activeSock = null;
      await updateBotStatus('offline').catch(() => {});

      const reason = new Boom(lastDisconnect?.error)?.output?.statusCode;
      logger.warn({ reason }, 'Koneksi WhatsApp terputus');

      const shouldReconnect = reason !== DisconnectReason.loggedOut;

      if (shouldReconnect) {
        logger.info('Mencoba reconnect dalam 5 detik...');
        setTimeout(connectToWhatsApp, 5000);
      } else {
        logger.error('Logged out dari WhatsApp. Hapus folder auth_info dan scan ulang QR.');
        process.exit(1);
      }
    }
  });

  // Handle pesan masuk
  sock.ev.on('messages.upsert', async ({ messages, type }) => {
    if (type !== 'notify') return;

    for (const msg of messages) {
      try {
        const commandHandled = await routeCommand(sock, msg);
        if (commandHandled) continue;
        await handleIncomingMessage(sock, msg);
      } catch (err) {
        logger.error({ err, msgId: msg.key?.id }, 'Error saat handle pesan');
      }
    }
  });
}

// Graceful shutdown
process.on('SIGTERM', async () => {
  logger.info('SIGTERM diterima. Menutup koneksi...');
  stopScheduler();
  await updateBotStatus('offline').catch(() => {});
  process.exit(0);
});

process.on('SIGINT', async () => {
  logger.info('SIGINT diterima. Menutup koneksi...');
  stopScheduler();
  await updateBotStatus('offline').catch(() => {});
  process.exit(0);
});

connectToWhatsApp().catch(err => {
  logger.error({ err }, 'Fatal error saat startup');
  process.exit(1);
});
