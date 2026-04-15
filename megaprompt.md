# 🤖 MEGA PROMPT — WA Auto-Reply Bot (Baileys + Laravel + Docker)

> Ikuti instruksi ini secara **berurutan, tidak ada yang dilewati, tidak ada simplifikasi, tidak ada asumsi.** Setiap langkah wajib dieksekusi penuh. Jika ada langkah yang terlihat "simple", tetap wajib dikerjakan secara eksplisit.

---

## 📋 CREDENTIAL PLACEHOLDER — ISI SEBELUM MULAI

```env
# GitHub
GITHUB_USERNAME=el-pablos
GITHUB_EMAIL=yeteprem.end23juni@gmail.com
GITHUB_TOKEN=FILL_YOUR_GITHUB_TOKEN_HERE
GITHUB_REPO_NAME=wa-autoreply-bot

# MySQL
DB_HOST=mysql
DB_PORT=3306
DB_NAME=wabot
DB_USER=root
DB_PASSWORD=FILL_YOUR_DB_PASSWORD_HERE

# Cloudflare
CF_ZONE_ID=FILL_YOUR_CLOUDFLARE_ZONE_ID_HERE
CF_ACCOUNT_ID=FILL_YOUR_CLOUDFLARE_ACCOUNT_ID_HERE
CF_GLOBAL_API_KEY=FILL_YOUR_CF_GLOBAL_API_KEY_HERE
CF_DNS_API_TOKEN=FILL_YOUR_CF_DNS_API_TOKEN_HERE

# Server
SERVER_DOMAIN=monitoring-wa.tams.codes
SERVER_PORT=8002
DASHBOARD_PASSWORD=FILL_YOUR_DASHBOARD_PASSWORD_HERE

# Laravel App
APP_KEY=base64:GENERATE_WITH_php_artisan_key_generate
APP_URL=https://monitoring-wa.tams.codes
```

---

## 🗂️ STRUKTUR DIREKTORI FINAL

```
wa-autoreply-bot/
├── .github/
│   └── workflows/
│       ├── ci.yml
│       └── release.yml
├── bot/
│   ├── src/
│   │   ├── index.js
│   │   ├── config.js
│   │   ├── db.js
│   │   ├── handlers/
│   │   │   └── messageHandler.js
│   │   └── utils/
│   │       └── logger.js
│   ├── tests/
│   │   ├── messageHandler.test.js
│   │   ├── db.test.js
│   │   └── config.test.js
│   ├── auth_info/          # gitignored
│   ├── Dockerfile
│   ├── package.json
│   └── .env.example
├── dashboard/
│   ├── app/
│   │   ├── Http/
│   │   │   ├── Controllers/
│   │   │   │   ├── AuthController.php
│   │   │   │   ├── DashboardController.php
│   │   │   │   ├── AllowListController.php
│   │   │   │   ├── LogController.php
│   │   │   │   └── SettingController.php
│   │   │   └── Middleware/
│   │   │       └── SimpleAuthMiddleware.php
│   │   └── Models/
│   │       ├── AllowedNumber.php
│   │       ├── MessageLog.php
│   │       └── BotSetting.php
│   ├── resources/
│   │   ├── views/
│   │   │   ├── layouts/
│   │   │   │   └── app.blade.php
│   │   │   ├── auth/
│   │   │   │   └── login.blade.php
│   │   │   ├── dashboard/
│   │   │   │   └── index.blade.php
│   │   │   ├── allowlist/
│   │   │   │   ├── index.blade.php
│   │   │   │   └── form.blade.php
│   │   │   ├── logs/
│   │   │   │   └── index.blade.php
│   │   │   └── settings/
│   │   │       └── index.blade.php
│   │   └── css/
│   │       └── app.css
│   ├── routes/
│   │   └── web.php
│   ├── database/
│   │   └── migrations/
│   │       ├── 2024_01_01_000001_create_allowed_numbers_table.php
│   │       ├── 2024_01_01_000002_create_message_logs_table.php
│   │       └── 2024_01_01_000003_create_bot_settings_table.php
│   ├── tests/
│   │   ├── Feature/
│   │   │   ├── AuthTest.php
│   │   │   ├── AllowListTest.php
│   │   │   ├── LogTest.php
│   │   │   └── SettingTest.php
│   │   └── Unit/
│   │       ├── AllowedNumberTest.php
│   │       ├── MessageLogTest.php
│   │       └── BotSettingTest.php
│   ├── Dockerfile
│   └── .env.example
├── mysql/
│   └── init.sql
├── nginx/
│   └── monitoring-wa.conf
├── docker-compose.yml
├── docker-compose.prod.yml
├── .gitignore
├── .env.example
└── README.md
```

---

## LANGKAH 1 — INISIALISASI REPO & GIT

### 1.1 Buat direktori dan git init

```bash
mkdir wa-autoreply-bot
cd wa-autoreply-bot
git init
git config user.name "el-pablos"
git config user.email "yeteprem.end23juni@gmail.com"
```

### 1.2 Buat `.gitignore` lengkap

Buat file `.gitignore` di root dengan isi **persis** sebagai berikut:

```gitignore
# Environment
.env
*.env
.env.*
!.env.example

# Auth Baileys (jangan pernah commit session WA)
bot/auth_info/
bot/auth_info_backup/

# Node
node_modules/
npm-debug.log*
yarn-debug.log*
yarn-error.log*

# Laravel
dashboard/vendor/
dashboard/node_modules/
dashboard/public/hot
dashboard/public/storage
dashboard/storage/*.key
dashboard/storage/app/
dashboard/storage/framework/
dashboard/storage/logs/
dashboard/bootstrap/cache/

# Build
dist/
build/

# OS
.DS_Store
Thumbs.db

# IDE
.vscode/
.idea/
*.swp
*.swo

# Docker
.docker/

# Logs
*.log
logs/

# Secrets & Tokens — WAJIB tidak pernah masuk repo
*.pem
*.key
*.cert
*.crt
secrets/
credentials/
```

### 1.3 Buat GitHub repo via API

```bash
curl -X POST \
  -H "Authorization: token FILL_YOUR_GITHUB_TOKEN_HERE" \
  -H "Accept: application/vnd.github.v3+json" \
  https://api.github.com/user/repos \
  -d '{
    "name": "wa-autoreply-bot",
    "description": "🤖 WhatsApp Auto-Reply Bot berbasis Baileys + Laravel Dashboard + Docker — sistem monitoring pesan WA dengan allow-list, log viewer, dan toggle reply real-time.",
    "private": false,
    "has_issues": true,
    "has_projects": true,
    "has_wiki": false,
    "auto_init": false
  }'
```

### 1.4 Set remote dan push awal

```bash
git remote add origin https://FILL_YOUR_GITHUB_TOKEN_HERE@github.com/el-pablos/wa-autoreply-bot.git
git add .gitignore
git commit -m "init: inisialisasi repo dengan gitignore lengkap"
git branch -M main
git push -u origin main
```

---

## LANGKAH 2 — MYSQL INIT SCRIPT

Buat file `mysql/init.sql`:

```sql
-- ============================================================
-- WA Auto-Reply Bot — Database Initialization Script
-- ============================================================

CREATE DATABASE IF NOT EXISTS wabot CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE wabot;

-- ============================================================
-- TABLE: allowed_numbers
-- Nomor WA yang boleh mendapat auto-reply
-- ============================================================
CREATE TABLE IF NOT EXISTS allowed_numbers (
    id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    phone_number  VARCHAR(20)  NOT NULL UNIQUE COMMENT 'Format: 628xxx tanpa tanda + atau spasi',
    label         VARCHAR(100) NULL     COMMENT 'Nama/catatan opsional untuk nomor ini',
    is_active     TINYINT(1)   NOT NULL DEFAULT 1 COMMENT '1=aktif mendapat reply, 0=nonaktif',
    created_at    TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at    TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_phone_active (phone_number, is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- TABLE: message_logs
-- Log seluruh pesan masuk beserta status reply-nya
-- ============================================================
CREATE TABLE IF NOT EXISTS message_logs (
    id            BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    from_number   VARCHAR(20)  NOT NULL COMMENT 'Nomor pengirim',
    message_text  TEXT         NULL     COMMENT 'Isi pesan yang masuk',
    message_type  VARCHAR(30)  NOT NULL DEFAULT 'text' COMMENT 'text/image/audio/video/document/sticker/location/other',
    is_allowed    TINYINT(1)   NOT NULL DEFAULT 0 COMMENT '1=ada di allow-list',
    replied       TINYINT(1)   NOT NULL DEFAULT 0 COMMENT '1=sudah dibalas oleh bot',
    reply_text    TEXT         NULL     COMMENT 'Teks yang dikirim bot sebagai balasan',
    group_id      VARCHAR(50)  NULL     COMMENT 'Diisi jika pesan dari grup, NULL jika private',
    received_at   TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_from_number    (from_number),
    INDEX idx_received_at    (received_at),
    INDEX idx_is_allowed     (is_allowed),
    INDEX idx_replied        (replied)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- TABLE: bot_settings
-- Key-value config yang bisa diubah dari dashboard
-- ============================================================
CREATE TABLE IF NOT EXISTS bot_settings (
    `key`        VARCHAR(60)  NOT NULL PRIMARY KEY,
    `value`      TEXT         NOT NULL,
    description  VARCHAR(255) NULL,
    updated_at   TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- SEED: default settings
-- ============================================================
INSERT INTO bot_settings (`key`, `value`, description) VALUES
  ('auto_reply_enabled', 'true',  'Toggle auto-reply on/off. Value: true | false'),
  ('reply_message',      'Haiii, Tama nya lagi offline nih 😴 tunggu beberapa menit lagi ya atau kalau urgent bisa langsung call aku orait~', 'Pesan balasan otomatis yang dikirim bot'),
  ('reply_delay_ms',     '1500',  'Delay sebelum bot balas (milliseconds), biar keliatan natural'),
  ('bot_status',         'offline', 'Status koneksi bot: online | offline | connecting'),
  ('ignore_groups',      'true',  'Abaikan pesan dari grup. Value: true | false')
ON DUPLICATE KEY UPDATE `value` = VALUES(`value`);
```

```bash
git add mysql/
git commit -m "add: tambah init.sql schema lengkap dengan seed default settings"
```

---

## LANGKAH 3 — NODE.JS BOT (BAILEYS)

### 3.1 `bot/package.json`

```json
{
  "name": "wa-autoreply-bot",
  "version": "1.0.0",
  "description": "WhatsApp Auto-Reply Bot menggunakan Baileys",
  "type": "module",
  "main": "src/index.js",
  "scripts": {
    "start": "node src/index.js",
    "dev": "nodemon src/index.js",
    "test": "node --experimental-vm-modules node_modules/.bin/jest --coverage --forceExit",
    "test:watch": "jest --watch"
  },
  "dependencies": {
    "@whiskeysockets/baileys": "^6.7.0",
    "dotenv": "^16.4.5",
    "express": "^4.19.2",
    "mysql2": "^3.9.7",
    "pino": "^9.2.0",
    "pino-pretty": "^11.2.1",
    "qrcode": "^1.5.4",
    "qrcode-terminal": "^0.12.0"
  },
  "devDependencies": {
    "jest": "^29.7.0",
    "nodemon": "^3.1.4"
  },
  "jest": {
    "transform": {},
    "testEnvironment": "node",
    "coverageThreshold": {
      "global": {
        "lines": 80,
        "functions": 80,
        "branches": 70,
        "statements": 80
      }
    }
  }
}
```

### 3.2 `bot/.env.example`

```env
DB_HOST=mysql
DB_PORT=3306
DB_NAME=wabot
DB_USER=root
DB_PASSWORD=FILL_YOUR_DB_PASSWORD_HERE
BOT_PORT=3001
NODE_ENV=production
LOG_LEVEL=info
```

### 3.3 `bot/src/config.js`

```javascript
import 'dotenv/config';

export const config = {
  db: {
    host:     process.env.DB_HOST     || 'localhost',
    port:     parseInt(process.env.DB_PORT || '3306', 10),
    user:     process.env.DB_USER     || 'root',
    password: process.env.DB_PASSWORD || '',
    database: process.env.DB_NAME     || 'wabot',
    waitForConnections: true,
    connectionLimit:    10,
    queueLimit:         0,
    charset:            'utf8mb4',
  },
  bot: {
    port:     parseInt(process.env.BOT_PORT || '3001', 10),
    authDir:  './auth_info',
    logLevel: process.env.LOG_LEVEL || 'info',
  },
  env: process.env.NODE_ENV || 'development',
};
```

### 3.4 `bot/src/utils/logger.js`

```javascript
import pino from 'pino';
import { config } from '../config.js';

export const logger = pino({
  level: config.bot.logLevel,
  transport: config.env !== 'production'
    ? { target: 'pino-pretty', options: { colorize: true } }
    : undefined,
});
```

### 3.5 `bot/src/db.js`

```javascript
import mysql from 'mysql2/promise';
import { config } from './config.js';
import { logger } from './utils/logger.js';

let pool = null;

/**
 * Dapatkan pool koneksi MySQL (singleton).
 * @returns {mysql.Pool}
 */
export function getPool() {
  if (!pool) {
    pool = mysql.createPool(config.db);
    logger.info('MySQL pool dibuat');
  }
  return pool;
}

/**
 * Ambil satu setting dari tabel bot_settings berdasarkan key.
 * @param {string} key
 * @returns {Promise<string|null>}
 */
export async function getSetting(key) {
  const db = getPool();
  const [rows] = await db.execute(
    'SELECT `value` FROM bot_settings WHERE `key` = ? LIMIT 1',
    [key]
  );
  return rows.length > 0 ? rows[0].value : null;
}

/**
 * Cek apakah nomor ada di allow-list dan aktif.
 * @param {string} phoneNumber - Format: 628xxx
 * @returns {Promise<boolean>}
 */
export async function isAllowedNumber(phoneNumber) {
  const db = getPool();
  const [rows] = await db.execute(
    'SELECT id FROM allowed_numbers WHERE phone_number = ? AND is_active = 1 LIMIT 1',
    [phoneNumber]
  );
  return rows.length > 0;
}

/**
 * Simpan log pesan masuk ke tabel message_logs.
 * @param {Object} logData
 * @param {string} logData.fromNumber
 * @param {string} logData.messageText
 * @param {string} logData.messageType
 * @param {boolean} logData.isAllowed
 * @param {boolean} logData.replied
 * @param {string|null} logData.replyText
 * @param {string|null} logData.groupId
 * @returns {Promise<number>} insertId
 */
export async function saveMessageLog(logData) {
  const db = getPool();
  const {
    fromNumber,
    messageText,
    messageType = 'text',
    isAllowed,
    replied,
    replyText = null,
    groupId = null,
  } = logData;

  const [result] = await db.execute(
    `INSERT INTO message_logs
      (from_number, message_text, message_type, is_allowed, replied, reply_text, group_id)
     VALUES (?, ?, ?, ?, ?, ?, ?)`,
    [fromNumber, messageText, messageType, isAllowed ? 1 : 0, replied ? 1 : 0, replyText, groupId]
  );
  return result.insertId;
}

/**
 * Update status bot di tabel bot_settings.
 * @param {'online'|'offline'|'connecting'} status
 */
export async function updateBotStatus(status) {
  const db = getPool();
  await db.execute(
    "UPDATE bot_settings SET `value` = ? WHERE `key` = 'bot_status'",
    [status]
  );
}
```

### 3.6 `bot/src/handlers/messageHandler.js`

```javascript
import { logger } from '../utils/logger.js';
import { getSetting, isAllowedNumber, saveMessageLog } from '../db.js';

/**
 * Ekstrak teks dari berbagai tipe pesan Baileys.
 * @param {Object} msg - Message object dari Baileys
 * @returns {{ text: string, type: string }}
 */
export function extractMessageContent(msg) {
  const m = msg.message;
  if (!m) return { text: '', type: 'unknown' };

  if (m.conversation)                       return { text: m.conversation,                            type: 'text' };
  if (m.extendedTextMessage?.text)          return { text: m.extendedTextMessage.text,                type: 'text' };
  if (m.imageMessage?.caption)              return { text: m.imageMessage.caption,                    type: 'image' };
  if (m.videoMessage?.caption)              return { text: m.videoMessage.caption,                    type: 'video' };
  if (m.documentMessage?.caption)           return { text: m.documentMessage.caption,                 type: 'document' };
  if (m.audioMessage)                       return { text: '[Pesan Suara]',                           type: 'audio' };
  if (m.stickerMessage)                     return { text: '[Sticker]',                               type: 'sticker' };
  if (m.locationMessage)                    return { text: '[Lokasi]',                                type: 'location' };
  if (m.contactMessage)                     return { text: '[Kontak]',                                type: 'contact' };
  if (m.reactionMessage)                    return { text: `[Reaksi: ${m.reactionMessage.text || ''}]`, type: 'reaction' };

  return { text: '[Pesan Tidak Dikenal]', type: 'other' };
}

/**
 * Handler utama untuk pesan masuk dari Baileys.
 * @param {Object} sock - Instance socket Baileys
 * @param {Object} msg  - Message object dari Baileys
 */
export async function handleIncomingMessage(sock, msg) {
  // Abaikan pesan dari diri sendiri
  if (msg.key.fromMe) return;

  // Abaikan update status/notifikasi sistem
  if (!msg.message) return;

  const remoteJid     = msg.key.remoteJid;
  const isGroup       = remoteJid.endsWith('@g.us');
  const phoneNumber   = isGroup
    ? (msg.key.participant || '').replace('@s.whatsapp.net', '')
    : remoteJid.replace('@s.whatsapp.net', '');
  const groupId       = isGroup ? remoteJid.replace('@g.us', '') : null;

  const { text: messageText, type: messageType } = extractMessageContent(msg);

  logger.info({ phoneNumber, isGroup, messageType }, 'Pesan masuk diterima');

  // Ambil semua setting sekaligus (parallel query)
  const [autoReplyEnabled, ignoreGroups, replyMessage, replyDelayMs] = await Promise.all([
    getSetting('auto_reply_enabled'),
    getSetting('ignore_groups'),
    getSetting('reply_message'),
    getSetting('reply_delay_ms'),
  ]);

  // Jika setting ignore_groups = true dan pesan dari grup, skip reply tapi tetap log
  if (isGroup && ignoreGroups === 'true') {
    logger.debug({ groupId }, 'Pesan dari grup diabaikan (ignore_groups=true)');
    await saveMessageLog({
      fromNumber:  phoneNumber,
      messageText,
      messageType,
      isAllowed:   false,
      replied:     false,
      replyText:   null,
      groupId,
    });
    return;
  }

  // Cek allow-list
  const allowed = await isAllowedNumber(phoneNumber);

  let replied    = false;
  let replyText  = null;

  if (allowed && autoReplyEnabled === 'true') {
    replyText = replyMessage || 'Haiii, lagi offline sebentar! 😴';

    // Delay natural sebelum kirim
    const delay = parseInt(replyDelayMs || '1500', 10);
    await new Promise(resolve => setTimeout(resolve, delay));

    try {
      await sock.sendMessage(remoteJid, { text: replyText });
      replied = true;
      logger.info({ to: phoneNumber }, 'Auto-reply terkirim');
    } catch (err) {
      logger.error({ err, to: phoneNumber }, 'Gagal kirim auto-reply');
    }
  } else {
    logger.debug({ phoneNumber, allowed, autoReplyEnabled }, 'Tidak memenuhi syarat untuk reply');
  }

  // Log ke database apapun hasilnya
  await saveMessageLog({
    fromNumber:  phoneNumber,
    messageText,
    messageType,
    isAllowed:   allowed,
    replied,
    replyText,
    groupId,
  });
}
```

### 3.7 `bot/src/index.js`

```javascript
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
import { updateBotStatus, getPool } from './db.js';
import { handleIncomingMessage }   from './handlers/messageHandler.js';

let qrCodeDataURL = null;
let connectionStatus = 'disconnected';

// ─────────────────────────────────────────────
// HTTP Server (untuk QR code & health check)
// ─────────────────────────────────────────────
const app = express();

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
  await updateBotStatus('offline').catch(() => {});
  process.exit(0);
});

process.on('SIGINT', async () => {
  logger.info('SIGINT diterima. Menutup koneksi...');
  await updateBotStatus('offline').catch(() => {});
  process.exit(0);
});

connectToWhatsApp().catch(err => {
  logger.error({ err }, 'Fatal error saat startup');
  process.exit(1);
});
```

### 3.8 `bot/Dockerfile`

```dockerfile
FROM node:20-alpine

WORKDIR /app

# Install dependencies produksi
COPY package.json ./
RUN npm install --omit=dev

# Copy source
COPY src/ ./src/

# Direktori auth_info (persistent via volume)
RUN mkdir -p ./auth_info

EXPOSE 3001

HEALTHCHECK --interval=30s --timeout=10s --start-period=60s --retries=3 \
  CMD wget -qO- http://localhost:3001/health || exit 1

CMD ["node", "src/index.js"]
```

```bash
git add bot/
git commit -m "add: implementasi bot baileys lengkap dengan handler, logger, db, dan qr endpoint"
```

---

## LANGKAH 4 — UNIT TEST BOT (NODE.JS)

### 4.1 `bot/tests/config.test.js`

```javascript
import { config } from '../src/config.js';

describe('config', () => {
  test('config.db harus punya semua field wajib', () => {
    expect(config.db).toHaveProperty('host');
    expect(config.db).toHaveProperty('port');
    expect(config.db).toHaveProperty('user');
    expect(config.db).toHaveProperty('database');
    expect(typeof config.db.port).toBe('number');
    expect(config.db.connectionLimit).toBeGreaterThan(0);
  });

  test('config.bot harus punya port dan authDir', () => {
    expect(config.bot).toHaveProperty('port');
    expect(config.bot).toHaveProperty('authDir');
    expect(typeof config.bot.port).toBe('number');
    expect(config.bot.port).toBeGreaterThan(0);
  });

  test('config.env harus string', () => {
    expect(typeof config.env).toBe('string');
  });
});
```

### 4.2 `bot/tests/messageHandler.test.js`

```javascript
import { extractMessageContent } from '../src/handlers/messageHandler.js';

describe('extractMessageContent', () => {
  test('ekstrak teks dari conversation biasa', () => {
    const msg = { message: { conversation: 'Halo!' } };
    expect(extractMessageContent(msg)).toEqual({ text: 'Halo!', type: 'text' });
  });

  test('ekstrak teks dari extendedTextMessage', () => {
    const msg = { message: { extendedTextMessage: { text: 'Extended text' } } };
    expect(extractMessageContent(msg)).toEqual({ text: 'Extended text', type: 'text' });
  });

  test('ekstrak caption dari imageMessage', () => {
    const msg = { message: { imageMessage: { caption: 'Caption gambar' } } };
    expect(extractMessageContent(msg)).toEqual({ text: 'Caption gambar', type: 'image' });
  });

  test('ekstrak caption dari videoMessage', () => {
    const msg = { message: { videoMessage: { caption: 'Caption video' } } };
    expect(extractMessageContent(msg)).toEqual({ text: 'Caption video', type: 'video' });
  });

  test('return [Pesan Suara] untuk audioMessage', () => {
    const msg = { message: { audioMessage: {} } };
    expect(extractMessageContent(msg)).toEqual({ text: '[Pesan Suara]', type: 'audio' });
  });

  test('return [Sticker] untuk stickerMessage', () => {
    const msg = { message: { stickerMessage: {} } };
    expect(extractMessageContent(msg)).toEqual({ text: '[Sticker]', type: 'sticker' });
  });

  test('return [Lokasi] untuk locationMessage', () => {
    const msg = { message: { locationMessage: {} } };
    expect(extractMessageContent(msg)).toEqual({ text: '[Lokasi]', type: 'location' });
  });

  test('return [Kontak] untuk contactMessage', () => {
    const msg = { message: { contactMessage: {} } };
    expect(extractMessageContent(msg)).toEqual({ text: '[Kontak]', type: 'contact' });
  });

  test('return unknown untuk message null', () => {
    const msg = { message: null };
    expect(extractMessageContent(msg)).toEqual({ text: '', type: 'unknown' });
  });

  test('return unknown untuk message kosong', () => {
    const msg = { message: {} };
    expect(extractMessageContent(msg)).toEqual({ text: '[Pesan Tidak Dikenal]', type: 'other' });
  });

  test('ekstrak reaksi dari reactionMessage', () => {
    const msg = { message: { reactionMessage: { text: '❤️' } } };
    const result = extractMessageContent(msg);
    expect(result.type).toBe('reaction');
    expect(result.text).toContain('❤️');
  });
});
```

### 4.3 `bot/tests/db.test.js`

```javascript
/**
 * Test db.js dengan mock mysql2
 */

// Mock mysql2/promise sebelum import modul
const mockExecute = jest.fn();
const mockPool    = { execute: mockExecute };

jest.unstable_mockModule('mysql2/promise', () => ({
  default: { createPool: jest.fn(() => mockPool) },
  createPool: jest.fn(() => mockPool),
}));

const { getSetting, isAllowedNumber, saveMessageLog } = await import('../src/db.js');

describe('db — getSetting', () => {
  beforeEach(() => mockExecute.mockReset());

  test('return value jika key ditemukan', async () => {
    mockExecute.mockResolvedValue([[{ value: 'true' }]]);
    const result = await getSetting('auto_reply_enabled');
    expect(result).toBe('true');
    expect(mockExecute).toHaveBeenCalledWith(
      expect.stringContaining('bot_settings'),
      ['auto_reply_enabled']
    );
  });

  test('return null jika key tidak ditemukan', async () => {
    mockExecute.mockResolvedValue([[]]);
    const result = await getSetting('key_tidak_ada');
    expect(result).toBeNull();
  });
});

describe('db — isAllowedNumber', () => {
  beforeEach(() => mockExecute.mockReset());

  test('return true jika nomor ada di allow-list', async () => {
    mockExecute.mockResolvedValue([[{ id: 1 }]]);
    const result = await isAllowedNumber('628123456789');
    expect(result).toBe(true);
  });

  test('return false jika nomor tidak ada di allow-list', async () => {
    mockExecute.mockResolvedValue([[]]);
    const result = await isAllowedNumber('628000000000');
    expect(result).toBe(false);
  });
});

describe('db — saveMessageLog', () => {
  beforeEach(() => mockExecute.mockReset());

  test('return insertId setelah insert berhasil', async () => {
    mockExecute.mockResolvedValue([{ insertId: 42 }]);
    const id = await saveMessageLog({
      fromNumber:  '628111111111',
      messageText: 'Halo',
      messageType: 'text',
      isAllowed:   true,
      replied:     true,
      replyText:   'Balasan',
      groupId:     null,
    });
    expect(id).toBe(42);
  });

  test('isAllowed false dikirim sebagai 0', async () => {
    mockExecute.mockResolvedValue([{ insertId: 1 }]);
    await saveMessageLog({
      fromNumber:  '628222222222',
      messageText: 'Test',
      messageType: 'text',
      isAllowed:   false,
      replied:     false,
    });
    const callArgs = mockExecute.mock.calls[0][1];
    expect(callArgs[3]).toBe(0); // isAllowed
    expect(callArgs[4]).toBe(0); // replied
  });
});
```

```bash
# Jalankan test
cd bot && npm install && npm test
# Wajib output: PASS semua test, coverage ≥ 80%
git add bot/tests/
git commit -m "test: tambah unit test lengkap untuk config, messageHandler, dan db"
```

---

## LANGKAH 5 — LARAVEL DASHBOARD

### 5.1 Buat project Laravel

```bash
cd ..
composer create-project laravel/laravel dashboard --prefer-dist
cd dashboard
```

### 5.2 `dashboard/.env.example`

```env
APP_NAME="WA Bot Monitor"
APP_ENV=production
APP_KEY=GENERATE_WITH_ARTISAN
APP_DEBUG=false
APP_URL=https://monitoring-wa.tams.codes

LOG_CHANNEL=stack
LOG_LEVEL=error

DB_CONNECTION=mysql
DB_HOST=mysql
DB_PORT=3306
DB_DATABASE=wabot
DB_USERNAME=root
DB_PASSWORD=FILL_YOUR_DB_PASSWORD_HERE

SESSION_DRIVER=file
SESSION_LIFETIME=480

CACHE_DRIVER=file
QUEUE_CONNECTION=sync

# Password untuk akses dashboard (single-user auth)
DASHBOARD_PASSWORD=FILL_YOUR_DASHBOARD_PASSWORD_HERE
```

### 5.3 Migration: `create_allowed_numbers_table`

File: `database/migrations/2024_01_01_000001_create_allowed_numbers_table.php`

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('allowed_numbers', function (Blueprint $table) {
            $table->id();
            $table->string('phone_number', 20)->unique()->comment('Format: 628xxx');
            $table->string('label', 100)->nullable()->comment('Nama/catatan opsional');
            $table->boolean('is_active')->default(true)->comment('1=aktif, 0=nonaktif');
            $table->timestamps();
            $table->index(['phone_number', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('allowed_numbers');
    }
};
```

### 5.4 Migration: `create_message_logs_table`

File: `database/migrations/2024_01_01_000002_create_message_logs_table.php`

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('message_logs', function (Blueprint $table) {
            $table->id();
            $table->string('from_number', 20)->comment('Nomor pengirim');
            $table->text('message_text')->nullable()->comment('Isi pesan');
            $table->string('message_type', 30)->default('text')->comment('Tipe pesan');
            $table->boolean('is_allowed')->default(false)->comment('Ada di allow-list?');
            $table->boolean('replied')->default(false)->comment('Sudah dibalas bot?');
            $table->text('reply_text')->nullable()->comment('Teks balasan bot');
            $table->string('group_id', 50)->nullable()->comment('ID grup jika dari grup');
            $table->timestamp('received_at')->useCurrent();
            $table->index('from_number');
            $table->index('received_at');
            $table->index('is_allowed');
            $table->index('replied');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('message_logs');
    }
};
```

### 5.5 Migration: `create_bot_settings_table`

File: `database/migrations/2024_01_01_000003_create_bot_settings_table.php`

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('bot_settings', function (Blueprint $table) {
            $table->string('key', 60)->primary();
            $table->text('value');
            $table->string('description', 255)->nullable();
            $table->timestamp('updated_at')->useCurrent()->useCurrentOnUpdate();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bot_settings');
    }
};
```

### 5.6 Model `AllowedNumber`

```php
<?php
// app/Models/AllowedNumber.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AllowedNumber extends Model
{
    protected $table    = 'allowed_numbers';
    protected $fillable = ['phone_number', 'label', 'is_active'];
    protected $casts    = ['is_active' => 'boolean'];

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
```

### 5.7 Model `MessageLog`

```php
<?php
// app/Models/MessageLog.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MessageLog extends Model
{
    protected $table      = 'message_logs';
    public    $timestamps = false;
    protected $fillable   = [
        'from_number', 'message_text', 'message_type',
        'is_allowed', 'replied', 'reply_text', 'group_id',
    ];
    protected $casts = [
        'is_allowed'  => 'boolean',
        'replied'     => 'boolean',
        'received_at' => 'datetime',
    ];
}
```

### 5.8 Model `BotSetting`

```php
<?php
// app/Models/BotSetting.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BotSetting extends Model
{
    protected $table      = 'bot_settings';
    protected $primaryKey = 'key';
    public    $incrementing = false;
    public    $keyType    = 'string';
    public    $timestamps = false;
    protected $fillable   = ['key', 'value', 'description'];

    /**
     * Helper: ambil value berdasarkan key.
     */
    public static function getValue(string $key, mixed $default = null): mixed
    {
        $setting = static::find($key);
        return $setting ? $setting->value : $default;
    }

    /**
     * Helper: set value berdasarkan key.
     */
    public static function setValue(string $key, mixed $value): void
    {
        static::updateOrCreate(['key' => $key], ['value' => $value]);
    }
}
```

### 5.9 Middleware `SimpleAuthMiddleware`

```php
<?php
// app/Http/Middleware/SimpleAuthMiddleware.php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class SimpleAuthMiddleware
{
    public function handle(Request $request, Closure $next): mixed
    {
        if (!session('authenticated')) {
            return redirect()->route('login');
        }
        return $next($request);
    }
}
```

### 5.10 `AuthController`

```php
<?php
// app/Http/Controllers/AuthController.php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class AuthController extends Controller
{
    public function showLogin()
    {
        if (session('authenticated')) {
            return redirect()->route('dashboard');
        }
        return view('auth.login');
    }

    public function login(Request $request)
    {
        $request->validate([
            'password' => 'required|string',
        ]);

        $correct = $request->password === config('app.dashboard_password');

        if (!$correct) {
            return back()->withErrors(['password' => 'Password salah.'])->withInput();
        }

        session(['authenticated' => true]);
        return redirect()->route('dashboard');
    }

    public function logout(Request $request)
    {
        $request->session()->forget('authenticated');
        return redirect()->route('login');
    }
}
```

### 5.11 `DashboardController`

```php
<?php
// app/Http/Controllers/DashboardController.php

namespace App\Http\Controllers;

use App\Models\MessageLog;
use App\Models\AllowedNumber;
use App\Models\BotSetting;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index()
    {
        $stats = [
            'total_messages'   => MessageLog::count(),
            'total_replied'    => MessageLog::where('replied', true)->count(),
            'total_allowed'    => MessageLog::where('is_allowed', true)->count(),
            'total_numbers'    => AllowedNumber::count(),
            'active_numbers'   => AllowedNumber::active()->count(),
            'today_messages'   => MessageLog::whereDate('received_at', today())->count(),
            'bot_status'       => BotSetting::getValue('bot_status', 'offline'),
            'auto_reply'       => BotSetting::getValue('auto_reply_enabled', 'false'),
        ];

        // 7 hari terakhir (chart)
        $daily = MessageLog::selectRaw('DATE(received_at) as date, COUNT(*) as total')
            ->where('received_at', '>=', now()->subDays(6)->startOfDay())
            ->groupByRaw('DATE(received_at)')
            ->orderBy('date')
            ->get();

        // Top 5 nomor paling banyak kirim pesan
        $topNumbers = MessageLog::selectRaw('from_number, COUNT(*) as total')
            ->groupBy('from_number')
            ->orderByDesc('total')
            ->limit(5)
            ->get();

        // Pesan terbaru 10
        $recentLogs = MessageLog::orderByDesc('received_at')->limit(10)->get();

        return view('dashboard.index', compact('stats', 'daily', 'topNumbers', 'recentLogs'));
    }
}
```

### 5.12 `AllowListController`

```php
<?php
// app/Http/Controllers/AllowListController.php

namespace App\Http\Controllers;

use App\Models\AllowedNumber;
use Illuminate\Http\Request;

class AllowListController extends Controller
{
    public function index(Request $request)
    {
        $query = AllowedNumber::query();

        if ($request->filled('search')) {
            $s = $request->search;
            $query->where(function ($q) use ($s) {
                $q->where('phone_number', 'like', "%{$s}%")
                  ->orWhere('label', 'like', "%{$s}%");
            });
        }

        if ($request->filled('status')) {
            $query->where('is_active', $request->status === 'active' ? 1 : 0);
        }

        $numbers = $query->orderByDesc('created_at')->paginate(20)->withQueryString();

        return view('allowlist.index', compact('numbers'));
    }

    public function create()
    {
        return view('allowlist.form', ['number' => null]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'phone_number' => [
                'required',
                'string',
                'regex:/^628[0-9]{7,13}$/',
                'unique:allowed_numbers,phone_number',
            ],
            'label'     => 'nullable|string|max:100',
            'is_active' => 'boolean',
        ], [
            'phone_number.regex' => 'Format nomor harus 628xxx (contoh: 6281234567890)',
        ]);

        AllowedNumber::create($data);

        return redirect()->route('allowlist.index')
            ->with('success', "Nomor {$data['phone_number']} berhasil ditambahkan!");
    }

    public function edit(AllowedNumber $allowlist)
    {
        return view('allowlist.form', ['number' => $allowlist]);
    }

    public function update(Request $request, AllowedNumber $allowlist)
    {
        $data = $request->validate([
            'phone_number' => [
                'required',
                'string',
                'regex:/^628[0-9]{7,13}$/',
                "unique:allowed_numbers,phone_number,{$allowlist->id}",
            ],
            'label'     => 'nullable|string|max:100',
            'is_active' => 'boolean',
        ], [
            'phone_number.regex' => 'Format nomor harus 628xxx',
        ]);

        $allowlist->update($data);

        return redirect()->route('allowlist.index')
            ->with('success', 'Nomor berhasil diperbarui!');
    }

    public function destroy(AllowedNumber $allowlist)
    {
        $number = $allowlist->phone_number;
        $allowlist->delete();

        return redirect()->route('allowlist.index')
            ->with('success', "Nomor {$number} berhasil dihapus!");
    }

    public function toggleActive(AllowedNumber $allowlist)
    {
        $allowlist->update(['is_active' => !$allowlist->is_active]);
        $status = $allowlist->is_active ? 'diaktifkan' : 'dinonaktifkan';

        return redirect()->route('allowlist.index')
            ->with('success', "Nomor {$allowlist->phone_number} berhasil {$status}!");
    }
}
```

### 5.13 `LogController`

```php
<?php
// app/Http/Controllers/LogController.php

namespace App\Http\Controllers;

use App\Models\MessageLog;
use Illuminate\Http\Request;

class LogController extends Controller
{
    public function index(Request $request)
    {
        $query = MessageLog::query();

        if ($request->filled('number')) {
            $query->where('from_number', 'like', "%{$request->number}%");
        }

        if ($request->filled('replied')) {
            $query->where('replied', $request->replied === 'yes' ? 1 : 0);
        }

        if ($request->filled('is_allowed')) {
            $query->where('is_allowed', $request->is_allowed === 'yes' ? 1 : 0);
        }

        if ($request->filled('date_from')) {
            $query->whereDate('received_at', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('received_at', '<=', $request->date_to);
        }

        $logs = $query->orderByDesc('received_at')->paginate(50)->withQueryString();

        return view('logs.index', compact('logs'));
    }
}
```

### 5.14 `SettingController`

```php
<?php
// app/Http/Controllers/SettingController.php

namespace App\Http\Controllers;

use App\Models\BotSetting;
use Illuminate\Http\Request;

class SettingController extends Controller
{
    public function index()
    {
        $settings = BotSetting::all()->keyBy('key');
        return view('settings.index', compact('settings'));
    }

    public function update(Request $request)
    {
        $request->validate([
            'reply_message'        => 'required|string|max:1000',
            'reply_delay_ms'       => 'required|integer|min:0|max:10000',
            'auto_reply_enabled'   => 'in:true,false',
            'ignore_groups'        => 'in:true,false',
        ]);

        BotSetting::setValue('reply_message',      $request->reply_message);
        BotSetting::setValue('reply_delay_ms',     (string) $request->reply_delay_ms);
        BotSetting::setValue('auto_reply_enabled', $request->has('auto_reply_enabled') ? 'true' : 'false');
        BotSetting::setValue('ignore_groups',      $request->has('ignore_groups')      ? 'true' : 'false');

        return redirect()->route('settings.index')->with('success', 'Pengaturan berhasil disimpan!');
    }
}
```

### 5.15 `routes/web.php`

```php
<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\AllowListController;
use App\Http\Controllers\LogController;
use App\Http\Controllers\SettingController;
use Illuminate\Support\Facades\Route;

// Auth
Route::get('/login',        [AuthController::class, 'showLogin'])->name('login');
Route::post('/login',       [AuthController::class, 'login'])->name('login.post');
Route::post('/logout',      [AuthController::class, 'logout'])->name('logout');

// Protected routes
Route::middleware('simple.auth')->group(function () {
    Route::get('/',                                    [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/dashboard',                           [DashboardController::class, 'index'])->name('dashboard.home');

    // Allow-list CRUD
    Route::get('/allowlist',                           [AllowListController::class, 'index'])->name('allowlist.index');
    Route::get('/allowlist/create',                    [AllowListController::class, 'create'])->name('allowlist.create');
    Route::post('/allowlist',                          [AllowListController::class, 'store'])->name('allowlist.store');
    Route::get('/allowlist/{allowlist}/edit',          [AllowListController::class, 'edit'])->name('allowlist.edit');
    Route::put('/allowlist/{allowlist}',               [AllowListController::class, 'update'])->name('allowlist.update');
    Route::delete('/allowlist/{allowlist}',            [AllowListController::class, 'destroy'])->name('allowlist.destroy');
    Route::patch('/allowlist/{allowlist}/toggle',      [AllowListController::class, 'toggleActive'])->name('allowlist.toggle');

    // Logs
    Route::get('/logs',                                [LogController::class, 'index'])->name('logs.index');

    // Settings
    Route::get('/settings',                            [SettingController::class, 'index'])->name('settings.index');
    Route::post('/settings',                           [SettingController::class, 'update'])->name('settings.update');
});
```

### 5.16 Register middleware di `bootstrap/app.php`

```php
->withMiddleware(function (Middleware $middleware) {
    $middleware->alias([
        'simple.auth' => \App\Http\Middleware\SimpleAuthMiddleware::class,
    ]);
})
```

### 5.17 Tambahkan di `config/app.php`

```php
'dashboard_password' => env('DASHBOARD_PASSWORD', 'changeme'),
```

### 5.18 View — `layouts/app.blade.php`

Buat file `resources/views/layouts/app.blade.php` dengan konten berikut (Mobile-First, responsive):

```html
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>@yield('title', 'WA Bot Monitor') — Dashboard</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=JetBrains+Mono:wght@400;500&display=swap" rel="stylesheet">
  <style>
    :root {
      --bg:          #0d1117;
      --surface:     #161b22;
      --surface2:    #21262d;
      --border:      #30363d;
      --accent:      #25d366;
      --accent-dim:  #1a9e4a;
      --text:        #e6edf3;
      --text-muted:  #8b949e;
      --danger:      #f85149;
      --warning:     #d29922;
      --info:        #58a6ff;
      --radius:      8px;
      --sidebar-w:   240px;
    }
    * { box-sizing: border-box; margin: 0; padding: 0; }
    body { background: var(--bg); color: var(--text); font-family: 'Inter', sans-serif; min-height: 100vh; }

    /* ── Sidebar ── */
    .sidebar {
      position: fixed; top: 0; left: -100%; width: var(--sidebar-w);
      height: 100vh; background: var(--surface); border-right: 1px solid var(--border);
      display: flex; flex-direction: column; z-index: 999;
      transition: left .25s ease; padding: 1.5rem 0;
    }
    .sidebar.open { left: 0; }
    @media (min-width: 768px) { .sidebar { left: 0; } }

    .sidebar-logo {
      padding: 0 1.25rem 1.5rem;
      font-size: 1.1rem; font-weight: 700; color: var(--accent);
      display: flex; align-items: center; gap: .5rem;
      border-bottom: 1px solid var(--border);
    }
    .sidebar-logo span { font-size: 1.4rem; }

    nav a {
      display: flex; align-items: center; gap: .75rem;
      padding: .65rem 1.25rem; text-decoration: none;
      color: var(--text-muted); font-size: .875rem; font-weight: 500;
      border-left: 3px solid transparent; transition: all .15s;
    }
    nav a:hover, nav a.active {
      background: var(--surface2); color: var(--text);
      border-left-color: var(--accent);
    }
    nav a .icon { font-size: 1.1rem; width: 20px; text-align: center; }

    .sidebar-footer {
      margin-top: auto; padding: 1rem 1.25rem;
      border-top: 1px solid var(--border);
    }

    /* ── Main ── */
    .main-wrap { margin-left: 0; transition: margin .25s ease; min-height: 100vh; }
    @media (min-width: 768px) { .main-wrap { margin-left: var(--sidebar-w); } }

    .topbar {
      background: var(--surface); border-bottom: 1px solid var(--border);
      padding: .875rem 1.25rem; display: flex; align-items: center; gap: 1rem;
      position: sticky; top: 0; z-index: 100;
    }
    .topbar h1 { font-size: 1rem; font-weight: 600; flex: 1; }
    .hamburger {
      background: none; border: none; color: var(--text); cursor: pointer;
      font-size: 1.25rem; padding: .25rem; display: block;
    }
    @media (min-width: 768px) { .hamburger { display: none; } }

    /* Bot status badge */
    .status-badge {
      display: inline-flex; align-items: center; gap: .35rem;
      padding: .2rem .6rem; border-radius: 20px; font-size: .75rem; font-weight: 600;
    }
    .status-badge.online  { background: rgba(37,211,102,.15); color: var(--accent); }
    .status-badge.offline { background: rgba(248,81,73,.15);  color: var(--danger); }
    .status-badge.connecting { background: rgba(210,153,34,.15); color: var(--warning); }
    .status-dot { width: 7px; height: 7px; border-radius: 50%; background: currentColor; }
    .status-badge.online .status-dot { animation: pulse 2s infinite; }
    @keyframes pulse {
      0%, 100% { opacity: 1; }
      50% { opacity: .4; }
    }

    .content { padding: 1.25rem; }
    @media (min-width: 768px) { .content { padding: 1.75rem; } }

    /* ── Cards & helpers ── */
    .card {
      background: var(--surface); border: 1px solid var(--border);
      border-radius: var(--radius); padding: 1.25rem;
    }
    .stat-grid {
      display: grid; grid-template-columns: repeat(2, 1fr); gap: .875rem;
    }
    @media (min-width: 640px)  { .stat-grid { grid-template-columns: repeat(3, 1fr); } }
    @media (min-width: 1024px) { .stat-grid { grid-template-columns: repeat(4, 1fr); } }

    .stat-card {
      background: var(--surface); border: 1px solid var(--border);
      border-radius: var(--radius); padding: 1rem; text-align: center;
    }
    .stat-card .num { font-size: 1.8rem; font-weight: 700; color: var(--accent); }
    .stat-card .lbl { font-size: .75rem; color: var(--text-muted); margin-top: .25rem; }

    .btn {
      display: inline-flex; align-items: center; gap: .4rem;
      padding: .5rem 1rem; border-radius: var(--radius);
      font-size: .875rem; font-weight: 500; cursor: pointer;
      text-decoration: none; border: 1px solid transparent; transition: all .15s;
    }
    .btn-primary  { background: var(--accent);     color: #000; border-color: var(--accent); }
    .btn-primary:hover  { background: var(--accent-dim); border-color: var(--accent-dim); }
    .btn-danger   { background: var(--danger);     color: #fff; border-color: var(--danger); }
    .btn-danger:hover   { opacity: .85; }
    .btn-ghost    { background: transparent; color: var(--text-muted); border-color: var(--border); }
    .btn-ghost:hover    { background: var(--surface2); color: var(--text); }
    .btn-sm       { padding: .3rem .65rem; font-size: .8rem; }

    .form-group { margin-bottom: 1rem; }
    .form-group label { display: block; font-size: .825rem; font-weight: 500; color: var(--text-muted); margin-bottom: .35rem; }
    .form-control {
      width: 100%; padding: .55rem .75rem;
      background: var(--surface2); border: 1px solid var(--border);
      border-radius: var(--radius); color: var(--text); font-family: inherit;
      font-size: .875rem; transition: border-color .15s;
    }
    .form-control:focus { outline: none; border-color: var(--accent); }
    .form-control.is-invalid { border-color: var(--danger); }

    .alert {
      padding: .75rem 1rem; border-radius: var(--radius); font-size: .875rem; margin-bottom: 1rem;
    }
    .alert-success { background: rgba(37,211,102,.1); border: 1px solid rgba(37,211,102,.3); color: var(--accent); }
    .alert-danger  { background: rgba(248,81,73,.1);  border: 1px solid rgba(248,81,73,.3);  color: var(--danger); }

    table { width: 100%; border-collapse: collapse; font-size: .875rem; }
    th, td { padding: .75rem .875rem; text-align: left; border-bottom: 1px solid var(--border); }
    th { background: var(--surface2); font-weight: 600; font-size: .8rem; text-transform: uppercase; color: var(--text-muted); }
    tr:hover td { background: var(--surface2); }

    .badge {
      display: inline-block; padding: .15rem .55rem;
      border-radius: 20px; font-size: .75rem; font-weight: 600;
    }
    .badge-success { background: rgba(37,211,102,.15); color: var(--accent); }
    .badge-danger  { background: rgba(248,81,73,.15);  color: var(--danger); }
    .badge-info    { background: rgba(88,166,255,.15); color: var(--info); }

    .page-title { font-size: 1.25rem; font-weight: 700; margin-bottom: 1.25rem; }

    .overlay {
      display: none; position: fixed; inset: 0;
      background: rgba(0,0,0,.5); z-index: 998;
    }
    .overlay.show { display: block; }
    @media (min-width: 768px) { .overlay { display: none !important; } }

    /* Scrollbar */
    ::-webkit-scrollbar { width: 6px; height: 6px; }
    ::-webkit-scrollbar-track { background: var(--bg); }
    ::-webkit-scrollbar-thumb { background: var(--border); border-radius: 3px; }
    ::-webkit-scrollbar-thumb:hover { background: var(--text-muted); }
  </style>
  @stack('styles')
</head>
<body>

<div class="overlay" id="overlay" onclick="closeSidebar()"></div>

<aside class="sidebar" id="sidebar">
  <div class="sidebar-logo">
    <span>💬</span> WA Bot Monitor
  </div>
  <nav>
    <a href="{{ route('dashboard') }}"        class="{{ request()->routeIs('dashboard*') ? 'active' : '' }}">
      <span class="icon">📊</span> Dashboard
    </a>
    <a href="{{ route('allowlist.index') }}"  class="{{ request()->routeIs('allowlist*') ? 'active' : '' }}">
      <span class="icon">📋</span> Allow-List
    </a>
    <a href="{{ route('logs.index') }}"       class="{{ request()->routeIs('logs*') ? 'active' : '' }}">
      <span class="icon">📝</span> Log Pesan
    </a>
    <a href="{{ route('settings.index') }}"   class="{{ request()->routeIs('settings*') ? 'active' : '' }}">
      <span class="icon">⚙️</span> Pengaturan
    </a>
  </nav>
  <div class="sidebar-footer">
    <form action="{{ route('logout') }}" method="POST">
      @csrf
      <button type="submit" class="btn btn-ghost" style="width:100%; justify-content:center;">
        🚪 Logout
      </button>
    </form>
  </div>
</aside>

<div class="main-wrap">
  <header class="topbar">
    <button class="hamburger" onclick="toggleSidebar()">☰</button>
    <h1>@yield('page-title', 'Dashboard')</h1>
    @yield('topbar-right')
  </header>
  <main class="content">
    @if(session('success'))
      <div class="alert alert-success">✅ {{ session('success') }}</div>
    @endif
    @if(session('error'))
      <div class="alert alert-danger">❌ {{ session('error') }}</div>
    @endif
    @yield('content')
  </main>
</div>

<script>
  function toggleSidebar() {
    document.getElementById('sidebar').classList.toggle('open');
    document.getElementById('overlay').classList.toggle('show');
  }
  function closeSidebar() {
    document.getElementById('sidebar').classList.remove('open');
    document.getElementById('overlay').classList.remove('show');
  }
</script>
@stack('scripts')
</body>
</html>
```

### 5.19 View — `auth/login.blade.php`

```html
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Login — WA Bot Monitor</title>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
  <style>
    :root { --accent: #25d366; --bg: #0d1117; --surface: #161b22; --border: #30363d; --text: #e6edf3; --danger: #f85149; }
    * { box-sizing: border-box; margin: 0; padding: 0; }
    body { background: var(--bg); color: var(--text); font-family: 'Inter', sans-serif; min-height: 100vh; display: flex; align-items: center; justify-content: center; padding: 1rem; }
    .login-box { background: var(--surface); border: 1px solid var(--border); border-radius: 12px; padding: 2rem; width: 100%; max-width: 380px; }
    .logo { text-align: center; font-size: 2.5rem; margin-bottom: .5rem; }
    h1 { text-align: center; font-size: 1.25rem; font-weight: 700; margin-bottom: .25rem; }
    .sub { text-align: center; font-size: .825rem; color: #8b949e; margin-bottom: 1.75rem; }
    label { display: block; font-size: .825rem; font-weight: 500; color: #8b949e; margin-bottom: .35rem; }
    input[type="password"] { width: 100%; padding: .65rem .875rem; background: #21262d; border: 1px solid var(--border); border-radius: 8px; color: var(--text); font-size: .875rem; font-family: inherit; transition: border-color .15s; }
    input[type="password"]:focus { outline: none; border-color: var(--accent); }
    button { width: 100%; margin-top: 1rem; padding: .7rem; background: var(--accent); color: #000; border: none; border-radius: 8px; font-weight: 600; font-size: .9rem; cursor: pointer; transition: opacity .15s; }
    button:hover { opacity: .85; }
    .error { background: rgba(248,81,73,.1); border: 1px solid rgba(248,81,73,.3); color: var(--danger); padding: .65rem .875rem; border-radius: 8px; font-size: .825rem; margin-bottom: 1rem; }
  </style>
</head>
<body>
  <div class="login-box">
    <div class="logo">🤖</div>
    <h1>WA Bot Monitor</h1>
    <p class="sub">Masukkan password untuk lanjut</p>
    @if($errors->has('password'))
      <div class="error">❌ {{ $errors->first('password') }}</div>
    @endif
    <form action="{{ route('login.post') }}" method="POST">
      @csrf
      <div>
        <label for="password">Password</label>
        <input type="password" id="password" name="password" placeholder="••••••••" autofocus>
      </div>
      <button type="submit">Masuk →</button>
    </form>
  </div>
</body>
</html>
```

### 5.20 View — `dashboard/index.blade.php`

```html
@extends('layouts.app')
@section('title', 'Dashboard')
@section('page-title', 'Dashboard')

@section('topbar-right')
  @php $status = $stats['bot_status'] @endphp
  <span class="status-badge {{ $status }}">
    <span class="status-dot"></span>
    Bot {{ ucfirst($status) }}
  </span>
@endsection

@section('content')
<div class="stat-grid" style="margin-bottom:1.25rem">
  <div class="stat-card">
    <div class="num">{{ number_format($stats['total_messages']) }}</div>
    <div class="lbl">Total Pesan</div>
  </div>
  <div class="stat-card">
    <div class="num">{{ number_format($stats['today_messages']) }}</div>
    <div class="lbl">Pesan Hari Ini</div>
  </div>
  <div class="stat-card">
    <div class="num">{{ number_format($stats['total_replied']) }}</div>
    <div class="lbl">Sudah Dibalas</div>
  </div>
  <div class="stat-card">
    <div class="num">{{ $stats['active_numbers'] }}</div>
    <div class="lbl">Nomor Aktif</div>
  </div>
</div>

<div style="display:grid;grid-template-columns:1fr;gap:1rem">
  <div class="card">
    <div style="font-weight:600;margin-bottom:1rem">📈 Pesan 7 Hari Terakhir</div>
    <canvas id="dailyChart" height="100"></canvas>
  </div>

  <div class="card">
    <div style="font-weight:600;margin-bottom:1rem">⏱️ Pesan Terbaru</div>
    <div style="overflow-x:auto">
      <table>
        <thead><tr><th>Nomor</th><th>Pesan</th><th>Dibalas</th><th>Waktu</th></tr></thead>
        <tbody>
          @forelse($recentLogs as $log)
          <tr>
            <td style="font-family:monospace;font-size:.8rem">{{ $log->from_number }}</td>
            <td style="max-width:200px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap">{{ $log->message_text ?? '-' }}</td>
            <td><span class="badge {{ $log->replied ? 'badge-success' : 'badge-danger' }}">{{ $log->replied ? '✓' : '✗' }}</span></td>
            <td style="font-size:.8rem;color:#8b949e">{{ $log->received_at?->diffForHumans() }}</td>
          </tr>
          @empty
          <tr><td colspan="4" style="text-align:center;color:#8b949e;padding:2rem">Belum ada pesan masuk</td></tr>
          @endforelse
        </tbody>
      </table>
    </div>
  </div>
</div>

@push('scripts')
<script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.0/chart.umd.min.js"></script>
<script>
const ctx = document.getElementById('dailyChart');
const labels = @json($daily->pluck('date'));
const data   = @json($daily->pluck('total'));
new Chart(ctx, {
  type: 'bar',
  data: {
    labels,
    datasets: [{
      label: 'Pesan',
      data,
      backgroundColor: 'rgba(37,211,102,0.3)',
      borderColor: '#25d366',
      borderWidth: 2,
      borderRadius: 4,
    }]
  },
  options: {
    responsive: true,
    plugins: { legend: { display: false } },
    scales: {
      x: { grid: { color: '#30363d' }, ticks: { color: '#8b949e' } },
      y: { grid: { color: '#30363d' }, ticks: { color: '#8b949e', stepSize: 1 } },
    }
  }
});
</script>
@endpush
@endsection
```

### 5.21 View — `allowlist/index.blade.php`

```html
@extends('layouts.app')
@section('title', 'Allow-List')
@section('page-title', 'Allow-List')

@section('content')
<div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:.75rem;margin-bottom:1.25rem">
  <a href="{{ route('allowlist.create') }}" class="btn btn-primary">+ Tambah Nomor</a>
  <form style="display:flex;gap:.5rem;flex-wrap:wrap">
    <input type="text" name="search" value="{{ request('search') }}" placeholder="Cari nomor / label..." class="form-control" style="width:180px">
    <select name="status" class="form-control" style="width:130px">
      <option value="">Semua Status</option>
      <option value="active"   {{ request('status')=='active'   ? 'selected':'' }}>Aktif</option>
      <option value="inactive" {{ request('status')=='inactive' ? 'selected':'' }}>Nonaktif</option>
    </select>
    <button type="submit" class="btn btn-ghost btn-sm">Filter</button>
  </form>
</div>

<div class="card" style="overflow-x:auto">
  <table>
    <thead>
      <tr><th>Nomor</th><th>Label</th><th>Status</th><th>Ditambah</th><th>Aksi</th></tr>
    </thead>
    <tbody>
      @forelse($numbers as $n)
      <tr>
        <td style="font-family:monospace">{{ $n->phone_number }}</td>
        <td>{{ $n->label ?? '<span style="color:#8b949e">-</span>' }}</td>
        <td>
          <span class="badge {{ $n->is_active ? 'badge-success' : 'badge-danger' }}">
            {{ $n->is_active ? 'Aktif' : 'Nonaktif' }}
          </span>
        </td>
        <td style="font-size:.8rem;color:#8b949e">{{ $n->created_at->format('d/m/Y') }}</td>
        <td>
          <div style="display:flex;gap:.4rem;flex-wrap:wrap">
            <a href="{{ route('allowlist.edit', $n) }}" class="btn btn-ghost btn-sm">Edit</a>
            <form action="{{ route('allowlist.toggle', $n) }}" method="POST" style="display:inline">
              @csrf @method('PATCH')
              <button type="submit" class="btn btn-ghost btn-sm">{{ $n->is_active ? 'Nonaktifkan' : 'Aktifkan' }}</button>
            </form>
            <form action="{{ route('allowlist.destroy', $n) }}" method="POST" style="display:inline"
                  onsubmit="return confirm('Hapus nomor {{ $n->phone_number }}?')">
              @csrf @method('DELETE')
              <button type="submit" class="btn btn-danger btn-sm">Hapus</button>
            </form>
          </div>
        </td>
      </tr>
      @empty
      <tr><td colspan="5" style="text-align:center;color:#8b949e;padding:2rem">Belum ada nomor di allow-list</td></tr>
      @endforelse
    </tbody>
  </table>
</div>
<div style="margin-top:1rem">{{ $numbers->links() }}</div>
@endsection
```

### 5.22 View — `allowlist/form.blade.php`

```html
@extends('layouts.app')
@section('title', isset($number) && $number ? 'Edit Nomor' : 'Tambah Nomor')
@section('page-title', isset($number) && $number ? 'Edit Nomor' : 'Tambah Nomor')

@section('content')
<div class="card" style="max-width:480px">
  <form action="{{ $number ? route('allowlist.update', $number) : route('allowlist.store') }}" method="POST">
    @csrf
    @if($number) @method('PUT') @endif

    <div class="form-group">
      <label>Nomor WhatsApp <span style="color:#f85149">*</span></label>
      <input type="text" name="phone_number" value="{{ old('phone_number', $number?->phone_number) }}"
             class="form-control {{ $errors->has('phone_number') ? 'is-invalid' : '' }}"
             placeholder="628123456789">
      @error('phone_number')
        <small style="color:#f85149;font-size:.8rem">{{ $message }}</small>
      @enderror
      <small style="color:#8b949e;font-size:.8rem">Format: 628xxx (tanpa + atau spasi)</small>
    </div>

    <div class="form-group">
      <label>Label / Nama <small style="color:#8b949e">(opsional)</small></label>
      <input type="text" name="label" value="{{ old('label', $number?->label) }}"
             class="form-control" placeholder="Contoh: Teman Kantor">
    </div>

    <div class="form-group" style="display:flex;align-items:center;gap:.75rem">
      <input type="checkbox" name="is_active" id="is_active" value="1"
             {{ old('is_active', $number ? ($number->is_active ? '1' : '') : '1') == '1' ? 'checked' : '' }}
             style="width:16px;height:16px;accent-color:var(--accent)">
      <label for="is_active" style="margin-bottom:0;cursor:pointer">Aktif (akan mendapat auto-reply)</label>
    </div>

    <div style="display:flex;gap:.75rem;margin-top:1.25rem">
      <button type="submit" class="btn btn-primary">
        {{ $number ? '💾 Simpan Perubahan' : '+ Tambah Nomor' }}
      </button>
      <a href="{{ route('allowlist.index') }}" class="btn btn-ghost">Batal</a>
    </div>
  </form>
</div>
@endsection
```

### 5.23 View — `logs/index.blade.php`

```html
@extends('layouts.app')
@section('title', 'Log Pesan')
@section('page-title', 'Log Pesan')

@section('content')
<div class="card" style="margin-bottom:1rem;padding:.875rem">
  <form style="display:flex;flex-wrap:wrap;gap:.65rem;align-items:flex-end">
    <div>
      <label style="display:block;font-size:.8rem;color:#8b949e;margin-bottom:.25rem">Nomor</label>
      <input type="text" name="number" value="{{ request('number') }}" placeholder="628..." class="form-control" style="width:160px">
    </div>
    <div>
      <label style="display:block;font-size:.8rem;color:#8b949e;margin-bottom:.25rem">Dibalas</label>
      <select name="replied" class="form-control" style="width:120px">
        <option value="">Semua</option>
        <option value="yes" {{ request('replied')=='yes' ? 'selected':'' }}>Ya</option>
        <option value="no"  {{ request('replied')=='no'  ? 'selected':'' }}>Tidak</option>
      </select>
    </div>
    <div>
      <label style="display:block;font-size:.8rem;color:#8b949e;margin-bottom:.25rem">Allow-list</label>
      <select name="is_allowed" class="form-control" style="width:120px">
        <option value="">Semua</option>
        <option value="yes" {{ request('is_allowed')=='yes' ? 'selected':'' }}>Ya</option>
        <option value="no"  {{ request('is_allowed')=='no'  ? 'selected':'' }}>Tidak</option>
      </select>
    </div>
    <div>
      <label style="display:block;font-size:.8rem;color:#8b949e;margin-bottom:.25rem">Dari Tanggal</label>
      <input type="date" name="date_from" value="{{ request('date_from') }}" class="form-control" style="width:150px">
    </div>
    <div>
      <label style="display:block;font-size:.8rem;color:#8b949e;margin-bottom:.25rem">Sampai Tanggal</label>
      <input type="date" name="date_to" value="{{ request('date_to') }}" class="form-control" style="width:150px">
    </div>
    <button type="submit" class="btn btn-primary btn-sm">Filter</button>
    <a href="{{ route('logs.index') }}" class="btn btn-ghost btn-sm">Reset</a>
  </form>
</div>

<div class="card" style="overflow-x:auto">
  <table>
    <thead>
      <tr>
        <th>Nomor</th><th>Pesan</th><th>Tipe</th>
        <th>Allow-list</th><th>Dibalas</th><th>Waktu</th>
      </tr>
    </thead>
    <tbody>
      @forelse($logs as $log)
      <tr>
        <td style="font-family:monospace;font-size:.8rem">{{ $log->from_number }}</td>
        <td style="max-width:220px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;font-size:.85rem">{{ $log->message_text ?? '-' }}</td>
        <td><span class="badge badge-info">{{ $log->message_type }}</span></td>
        <td><span class="badge {{ $log->is_allowed ? 'badge-success' : 'badge-danger' }}">{{ $log->is_allowed ? '✓' : '✗' }}</span></td>
        <td><span class="badge {{ $log->replied ? 'badge-success' : 'badge-danger' }}">{{ $log->replied ? '✓' : '✗' }}</span></td>
        <td style="font-size:.8rem;color:#8b949e;white-space:nowrap">{{ $log->received_at?->format('d/m H:i') }}</td>
      </tr>
      @empty
      <tr><td colspan="6" style="text-align:center;color:#8b949e;padding:2rem">Belum ada log</td></tr>
      @endforelse
    </tbody>
  </table>
</div>
<div style="margin-top:1rem">{{ $logs->links() }}</div>
@endsection
```

### 5.24 View — `settings/index.blade.php`

```html
@extends('layouts.app')
@section('title', 'Pengaturan')
@section('page-title', 'Pengaturan Bot')

@section('content')
<div class="card" style="max-width:560px">
  <form action="{{ route('settings.update') }}" method="POST">
    @csrf

    <div class="form-group">
      <label>Pesan Balasan Otomatis</label>
      <textarea name="reply_message" class="form-control {{ $errors->has('reply_message') ? 'is-invalid' : '' }}"
                rows="4" placeholder="Masukkan pesan balasan...">{{ old('reply_message', $settings['reply_message']?->value ?? '') }}</textarea>
      @error('reply_message')
        <small style="color:#f85149;font-size:.8rem">{{ $message }}</small>
      @enderror
      <small style="color:#8b949e;font-size:.8rem">Pesan ini akan dikirim ke nomor yang ada di allow-list</small>
    </div>

    <div class="form-group">
      <label>Delay Sebelum Balas (ms)</label>
      <input type="number" name="reply_delay_ms" min="0" max="10000"
             value="{{ old('reply_delay_ms', $settings['reply_delay_ms']?->value ?? 1500) }}"
             class="form-control {{ $errors->has('reply_delay_ms') ? 'is-invalid' : '' }}">
      <small style="color:#8b949e;font-size:.8rem">1500 ms = 1.5 detik (biar keliatan natural)</small>
    </div>

    <div class="form-group" style="display:flex;align-items:center;gap:.75rem">
      <input type="checkbox" name="auto_reply_enabled" id="auto_reply" value="true"
             {{ ($settings['auto_reply_enabled']?->value ?? 'false') === 'true' ? 'checked' : '' }}
             style="width:16px;height:16px;accent-color:var(--accent)">
      <label for="auto_reply" style="margin-bottom:0;cursor:pointer">Aktifkan Auto-Reply</label>
    </div>

    <div class="form-group" style="display:flex;align-items:center;gap:.75rem">
      <input type="checkbox" name="ignore_groups" id="ignore_groups" value="true"
             {{ ($settings['ignore_groups']?->value ?? 'true') === 'true' ? 'checked' : '' }}
             style="width:16px;height:16px;accent-color:var(--accent)">
      <label for="ignore_groups" style="margin-bottom:0;cursor:pointer">Abaikan Pesan dari Grup</label>
    </div>

    <div style="margin-top:1.5rem;padding-top:1rem;border-top:1px solid var(--border)">
      <div style="font-size:.8rem;color:#8b949e;margin-bottom:1rem">Status Bot Saat Ini</div>
      @php $bs = $settings['bot_status']?->value ?? 'offline' @endphp
      <span class="status-badge {{ $bs }}">
        <span class="status-dot"></span> {{ ucfirst($bs) }}
      </span>
    </div>

    <button type="submit" class="btn btn-primary" style="margin-top:1.5rem">💾 Simpan Pengaturan</button>
  </form>
</div>
@endsection
```

### 5.25 `dashboard/Dockerfile`

```dockerfile
FROM php:8.3-fpm-alpine

RUN apk add --no-cache \
    nginx \
    supervisor \
    nodejs \
    npm \
    curl \
    libpng-dev \
    libzip-dev \
    zip \
    unzip \
    oniguruma-dev

RUN docker-php-ext-install pdo pdo_mysql mbstring zip gd

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

COPY . .

RUN composer install --optimize-autoloader --no-dev --no-interaction

RUN cp .env.example .env && \
    php artisan key:generate && \
    php artisan config:cache && \
    php artisan route:cache && \
    php artisan view:cache

RUN chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache

COPY docker/nginx.conf    /etc/nginx/nginx.conf
COPY docker/php-fpm.conf  /etc/php83/php-fpm.d/www.conf
COPY docker/supervisord.conf /etc/supervisor/conf.d/supervisord.conf

EXPOSE 80

HEALTHCHECK --interval=30s --timeout=10s --start-period=60s --retries=3 \
  CMD curl -f http://localhost/login || exit 1

CMD ["/usr/bin/supervisord", "-c", "/etc/supervisor/conf.d/supervisord.conf"]
```

```bash
git add dashboard/
git commit -m "add: implementasi laravel dashboard lengkap — controller, model, view, middleware, route"
```

---

## LANGKAH 6 — UNIT TEST LARAVEL

### 6.1 `tests/Unit/AllowedNumberTest.php`

```php
<?php

namespace Tests\Unit;

use App\Models\AllowedNumber;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AllowedNumberTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_create_allowed_number(): void
    {
        $number = AllowedNumber::create([
            'phone_number' => '628111222333',
            'label'        => 'Test User',
            'is_active'    => true,
        ]);

        $this->assertDatabaseHas('allowed_numbers', [
            'phone_number' => '628111222333',
        ]);
        $this->assertTrue($number->is_active);
    }

    public function test_scope_active_returns_only_active_numbers(): void
    {
        AllowedNumber::create(['phone_number' => '628000000001', 'is_active' => true]);
        AllowedNumber::create(['phone_number' => '628000000002', 'is_active' => false]);

        $active = AllowedNumber::active()->get();
        $this->assertCount(1, $active);
        $this->assertEquals('628000000001', $active->first()->phone_number);
    }

    public function test_phone_number_must_be_unique(): void
    {
        AllowedNumber::create(['phone_number' => '628000000099', 'is_active' => true]);

        $this->expectException(\Illuminate\Database\QueryException::class);
        AllowedNumber::create(['phone_number' => '628000000099', 'is_active' => false]);
    }
}
```

### 6.2 `tests/Unit/BotSettingTest.php`

```php
<?php

namespace Tests\Unit;

use App\Models\BotSetting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BotSettingTest extends TestCase
{
    use RefreshDatabase;

    public function test_get_value_returns_correct_value(): void
    {
        BotSetting::create(['key' => 'test_key', 'value' => 'test_value']);
        $this->assertEquals('test_value', BotSetting::getValue('test_key'));
    }

    public function test_get_value_returns_default_if_not_found(): void
    {
        $result = BotSetting::getValue('nonexistent_key', 'default');
        $this->assertEquals('default', $result);
    }

    public function test_get_value_returns_null_if_not_found_no_default(): void
    {
        $result = BotSetting::getValue('nonexistent_key');
        $this->assertNull($result);
    }

    public function test_set_value_creates_new_setting(): void
    {
        BotSetting::setValue('new_key', 'new_value');
        $this->assertDatabaseHas('bot_settings', ['key' => 'new_key', 'value' => 'new_value']);
    }

    public function test_set_value_updates_existing_setting(): void
    {
        BotSetting::create(['key' => 'auto_reply_enabled', 'value' => 'false']);
        BotSetting::setValue('auto_reply_enabled', 'true');
        $this->assertDatabaseHas('bot_settings', ['key' => 'auto_reply_enabled', 'value' => 'true']);
    }
}
```

### 6.3 `tests/Feature/AuthTest.php`

```php
<?php

namespace Tests\Feature;

use Tests\TestCase;

class AuthTest extends TestCase
{
    public function test_login_page_accessible(): void
    {
        $response = $this->get('/login');
        $response->assertStatus(200);
        $response->assertSee('WA Bot Monitor');
    }

    public function test_redirect_to_login_when_not_authenticated(): void
    {
        $response = $this->get('/');
        $response->assertRedirect('/login');
    }

    public function test_login_with_correct_password(): void
    {
        config(['app.dashboard_password' => 'testpassword123']);

        $response = $this->post('/login', ['password' => 'testpassword123']);
        $response->assertRedirect('/');
    }

    public function test_login_with_wrong_password(): void
    {
        config(['app.dashboard_password' => 'testpassword123']);

        $response = $this->post('/login', ['password' => 'wrongpassword']);
        $response->assertRedirect();
        $response->assertSessionHasErrors('password');
    }

    public function test_logout_clears_session(): void
    {
        $this->withSession(['authenticated' => true]);
        $response = $this->post('/logout');
        $response->assertRedirect('/login');
    }
}
```

### 6.4 `tests/Feature/AllowListTest.php`

```php
<?php

namespace Tests\Feature;

use App\Models\AllowedNumber;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AllowListTest extends TestCase
{
    use RefreshDatabase;

    private function actingAsAuth()
    {
        return $this->withSession(['authenticated' => true]);
    }

    public function test_allowlist_index_accessible(): void
    {
        $response = $this->actingAsAuth()->get('/allowlist');
        $response->assertStatus(200);
    }

    public function test_can_store_new_number(): void
    {
        $response = $this->actingAsAuth()->post('/allowlist', [
            'phone_number' => '628123456789',
            'label'        => 'Test',
            'is_active'    => 1,
        ]);

        $response->assertRedirect('/allowlist');
        $this->assertDatabaseHas('allowed_numbers', ['phone_number' => '628123456789']);
    }

    public function test_invalid_phone_format_rejected(): void
    {
        $response = $this->actingAsAuth()->post('/allowlist', [
            'phone_number' => '08123456789', // format salah
        ]);
        $response->assertSessionHasErrors('phone_number');
    }

    public function test_duplicate_number_rejected(): void
    {
        AllowedNumber::create(['phone_number' => '628111111111', 'is_active' => true]);

        $response = $this->actingAsAuth()->post('/allowlist', [
            'phone_number' => '628111111111',
        ]);
        $response->assertSessionHasErrors('phone_number');
    }

    public function test_can_delete_number(): void
    {
        $number = AllowedNumber::create(['phone_number' => '628999999999', 'is_active' => true]);

        $response = $this->actingAsAuth()->delete("/allowlist/{$number->id}");
        $response->assertRedirect('/allowlist');
        $this->assertDatabaseMissing('allowed_numbers', ['phone_number' => '628999999999']);
    }

    public function test_can_toggle_active_status(): void
    {
        $number = AllowedNumber::create(['phone_number' => '628555555555', 'is_active' => true]);

        $this->actingAsAuth()->patch("/allowlist/{$number->id}/toggle");
        $this->assertDatabaseHas('allowed_numbers', ['phone_number' => '628555555555', 'is_active' => false]);
    }
}
```

### 6.5 `tests/Feature/SettingTest.php`

```php
<?php

namespace Tests\Feature;

use App\Models\BotSetting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SettingTest extends TestCase
{
    use RefreshDatabase;

    private function actingAsAuth()
    {
        return $this->withSession(['authenticated' => true]);
    }

    public function test_settings_page_accessible(): void
    {
        $response = $this->actingAsAuth()->get('/settings');
        $response->assertStatus(200);
    }

    public function test_can_update_reply_message(): void
    {
        BotSetting::create(['key' => 'reply_message',      'value' => 'lama']);
        BotSetting::create(['key' => 'reply_delay_ms',     'value' => '1500']);
        BotSetting::create(['key' => 'auto_reply_enabled', 'value' => 'false']);
        BotSetting::create(['key' => 'ignore_groups',      'value' => 'false']);

        $response = $this->actingAsAuth()->post('/settings', [
            'reply_message'      => 'Pesan baru dari test',
            'reply_delay_ms'     => 2000,
            'auto_reply_enabled' => 'true',
            'ignore_groups'      => 'true',
        ]);

        $response->assertRedirect('/settings');
        $this->assertDatabaseHas('bot_settings', [
            'key' => 'reply_message', 'value' => 'Pesan baru dari test',
        ]);
    }

    public function test_empty_reply_message_rejected(): void
    {
        $response = $this->actingAsAuth()->post('/settings', [
            'reply_message'  => '',
            'reply_delay_ms' => 1000,
        ]);
        $response->assertSessionHasErrors('reply_message');
    }
}
```

```bash
cd dashboard && php artisan test
# Wajib: All tests passed
cd ..
git add dashboard/tests/
git commit -m "test: tambah unit dan feature test laravel — auth, allowlist, settings 100% passed"
```

---

## LANGKAH 7 — DOCKER COMPOSE

### 7.1 `docker-compose.yml` (development)

```yaml
version: '3.9'

services:

  mysql:
    image: mysql:8.0
    container_name: wa-mysql
    restart: unless-stopped
    environment:
      MYSQL_ROOT_PASSWORD: FILL_YOUR_DB_PASSWORD_HERE
      MYSQL_DATABASE: wabot
      MYSQL_CHARSET: utf8mb4
    volumes:
      - mysql_data:/var/lib/mysql
      - ./mysql/init.sql:/docker-entrypoint-initdb.d/01-init.sql:ro
    ports:
      - "3307:3306"
    healthcheck:
      test: ["CMD", "mysqladmin", "ping", "-h", "localhost", "-pFILL_YOUR_DB_PASSWORD_HERE"]
      interval: 10s
      timeout: 5s
      retries: 10
      start_period: 30s

  bot:
    build:
      context: ./bot
      dockerfile: Dockerfile
    container_name: wa-bot
    restart: unless-stopped
    volumes:
      - bot_auth:/app/auth_info
    environment:
      DB_HOST: mysql
      DB_PORT: 3306
      DB_NAME: wabot
      DB_USER: root
      DB_PASSWORD: FILL_YOUR_DB_PASSWORD_HERE
      BOT_PORT: 3001
      NODE_ENV: production
      LOG_LEVEL: info
    ports:
      - "3001:3001"
    depends_on:
      mysql:
        condition: service_healthy
    healthcheck:
      test: ["CMD", "wget", "-qO-", "http://localhost:3001/health"]
      interval: 30s
      timeout: 10s
      retries: 3
      start_period: 60s

  dashboard:
    build:
      context: ./dashboard
      dockerfile: Dockerfile
    container_name: wa-dashboard
    restart: unless-stopped
    environment:
      APP_NAME: "WA Bot Monitor"
      APP_ENV: production
      APP_DEBUG: "false"
      APP_URL: https://monitoring-wa.tams.codes
      DB_CONNECTION: mysql
      DB_HOST: mysql
      DB_PORT: 3306
      DB_DATABASE: wabot
      DB_USERNAME: root
      DB_PASSWORD: FILL_YOUR_DB_PASSWORD_HERE
      SESSION_DRIVER: file
      CACHE_DRIVER: file
      DASHBOARD_PASSWORD: FILL_YOUR_DASHBOARD_PASSWORD_HERE
    ports:
      - "8002:80"
    depends_on:
      mysql:
        condition: service_healthy
    healthcheck:
      test: ["CMD", "curl", "-f", "http://localhost/login"]
      interval: 30s
      timeout: 10s
      retries: 3
      start_period: 60s

volumes:
  mysql_data:
    driver: local
  bot_auth:
    driver: local
```

### 7.2 `docker-compose.prod.yml` (override untuk production)

```yaml
version: '3.9'

services:
  mysql:
    ports: []   # Jangan expose port MySQL ke publik di prod

  bot:
    ports: []   # Tidak perlu expose bot port ke publik

  dashboard:
    ports:
      - "8002:80"
    environment:
      APP_ENV: production
      APP_DEBUG: "false"
```

```bash
git add docker-compose.yml docker-compose.prod.yml
git commit -m "add: docker-compose development dan production configuration"
```

---

## LANGKAH 8 — NGINX CONFIG (SERVER)

Buat file `nginx/monitoring-wa.conf` untuk server:

```nginx
server {
    listen 80;
    server_name monitoring-wa.tams.codes;

    # Redirect semua HTTP → HTTPS
    return 301 https://$host$request_uri;
}

server {
    listen 443 ssl http2;
    server_name monitoring-wa.tams.codes;

    # SSL — diisi otomatis oleh Certbot
    ssl_certificate     /etc/letsencrypt/live/monitoring-wa.tams.codes/fullchain.pem;
    ssl_certificate_key /etc/letsencrypt/live/monitoring-wa.tams.codes/privkey.pem;
    include /etc/letsencrypt/options-ssl-nginx.conf;
    ssl_dhparam /etc/letsencrypt/ssl-dhparams.pem;

    # Security headers
    add_header X-Frame-Options           "SAMEORIGIN"  always;
    add_header X-Content-Type-Options    "nosniff"     always;
    add_header X-XSS-Protection          "1; mode=block" always;
    add_header Referrer-Policy           "strict-origin-when-cross-origin" always;
    add_header Strict-Transport-Security "max-age=31536000; includeSubDomains" always;

    # Proxy ke Docker container Laravel (port 8002)
    location / {
        proxy_pass         http://127.0.0.1:8002;
        proxy_http_version 1.1;
        proxy_set_header   Host              $host;
        proxy_set_header   X-Real-IP         $remote_addr;
        proxy_set_header   X-Forwarded-For   $proxy_add_x_forwarded_for;
        proxy_set_header   X-Forwarded-Proto $scheme;
        proxy_read_timeout 120s;
        proxy_send_timeout 120s;
        client_max_body_size 10M;
    }

    # Gzip compression
    gzip on;
    gzip_types text/plain text/css application/javascript application/json;
    gzip_min_length 1024;
}
```

```bash
git add nginx/
git commit -m "add: nginx config untuk monitoring-wa.tams.codes dengan SSL dan security headers"
```

---

## LANGKAH 9 — GITHUB ACTIONS CI/CD

### 9.1 `.github/workflows/ci.yml`

```yaml
name: CI — Test & Lint

on:
  push:
    branches: [main, develop]
  pull_request:
    branches: [main]

jobs:

  test-bot:
    name: Unit Test Node.js Bot
    runs-on: ubuntu-latest
    defaults:
      run:
        working-directory: bot

    steps:
      - uses: actions/checkout@v4

      - name: Setup Node.js
        uses: actions/setup-node@v4
        with:
          node-version: '20'
          cache: 'npm'
          cache-dependency-path: bot/package-lock.json

      - name: Install dependencies
        run: npm ci

      - name: Jalankan unit test
        run: npm test

      - name: Upload coverage report
        uses: actions/upload-artifact@v4
        if: always()
        with:
          name: bot-coverage
          path: bot/coverage/

  test-dashboard:
    name: Unit + Feature Test Laravel
    runs-on: ubuntu-latest
    defaults:
      run:
        working-directory: dashboard

    services:
      mysql:
        image: mysql:8.0
        env:
          MYSQL_ROOT_PASSWORD: test_password
          MYSQL_DATABASE: wabot_test
        ports:
          - 3306:3306
        options: --health-cmd="mysqladmin ping" --health-interval=10s --health-timeout=5s --health-retries=5

    steps:
      - uses: actions/checkout@v4

      - name: Setup PHP
        uses: shivammathur/setup-php@v2
        with:
          php-version: '8.3'
          extensions: pdo_mysql, mbstring, zip

      - name: Install Composer dependencies
        run: composer install --no-interaction --prefer-dist

      - name: Setup env untuk testing
        run: |
          cp .env.example .env
          php artisan key:generate
        env:
          DB_CONNECTION: mysql
          DB_HOST: 127.0.0.1
          DB_PORT: 3306
          DB_DATABASE: wabot_test
          DB_USERNAME: root
          DB_PASSWORD: test_password
          DASHBOARD_PASSWORD: testpassword

      - name: Jalankan migrasi
        run: php artisan migrate --force
        env:
          DB_HOST: 127.0.0.1
          DB_DATABASE: wabot_test
          DB_USERNAME: root
          DB_PASSWORD: test_password

      - name: Jalankan semua test
        run: php artisan test --coverage
        env:
          DB_HOST: 127.0.0.1
          DB_DATABASE: wabot_test
          DB_USERNAME: root
          DB_PASSWORD: test_password
          DASHBOARD_PASSWORD: testpassword

  build-docker:
    name: Build Docker Images
    runs-on: ubuntu-latest
    needs: [test-bot, test-dashboard]
    if: github.ref == 'refs/heads/main'

    steps:
      - uses: actions/checkout@v4

      - name: Build Bot image
        run: docker build -t wa-bot:test ./bot

      - name: Build Dashboard image
        run: docker build -t wa-dashboard:test ./dashboard

      - name: Verifikasi health endpoint bot
        run: |
          docker run -d --name test-bot -e DB_HOST=localhost -e DB_PASSWORD=test -p 3001:3001 wa-bot:test
          sleep 10
          curl -f http://localhost:3001/health || echo "Bot health check (DB belum ada, expected)"
          docker stop test-bot
```

### 9.2 `.github/workflows/release.yml`

```yaml
name: Release — Auto Tag & Publish

on:
  push:
    branches: [main]

permissions:
  contents: write

jobs:
  release:
    name: Buat Release Otomatis
    runs-on: ubuntu-latest

    steps:
      - uses: actions/checkout@v4
        with:
          fetch-depth: 0
          token: ${{ secrets.GITHUB_TOKEN }}

      - name: Ambil tag terakhir
        id: get_tag
        run: |
          LATEST=$(git tag --sort=-v:refname | head -n1)
          if [ -z "$LATEST" ]; then
            LATEST="v0.0.0"
          fi
          echo "latest=${LATEST}" >> $GITHUB_OUTPUT
          echo "Tag terakhir: $LATEST"

      - name: Hitung versi baru (semver patch)
        id: new_version
        run: |
          LATEST="${{ steps.get_tag.outputs.latest }}"
          VERSION=${LATEST#v}
          IFS='.' read -r -a parts <<< "$VERSION"
          MAJOR=${parts[0]:-0}
          MINOR=${parts[1]:-1}
          PATCH=${parts[2]:-0}
          NEW_PATCH=$((PATCH + 1))
          NEW_TAG="v${MAJOR}.${MINOR}.${NEW_PATCH}"
          echo "new_tag=${NEW_TAG}" >> $GITHUB_OUTPUT
          echo "Versi baru: $NEW_TAG"

      - name: Generate changelog dari commit terbaru
        id: changelog
        run: |
          LATEST="${{ steps.get_tag.outputs.latest }}"
          if git rev-parse "$LATEST" >/dev/null 2>&1; then
            LOG=$(git log ${LATEST}..HEAD --pretty=format:"- %s (%h)" --no-merges)
          else
            LOG=$(git log --pretty=format:"- %s (%h)" --no-merges -20)
          fi
          echo "log<<EOF" >> $GITHUB_OUTPUT
          echo "$LOG" >> $GITHUB_OUTPUT
          echo "EOF" >> $GITHUB_OUTPUT

      - name: Buat tag baru
        run: |
          git config user.name  "github-actions[bot]"
          git config user.email "github-actions[bot]@users.noreply.github.com"
          git tag ${{ steps.new_version.outputs.new_tag }}
          git push origin ${{ steps.new_version.outputs.new_tag }}

      - name: Buat GitHub Release
        uses: actions/create-release@v1
        env:
          GITHUB_TOKEN: ${{ secrets.GITHUB_TOKEN }}
        with:
          tag_name:     ${{ steps.new_version.outputs.new_tag }}
          release_name: "🤖 Release ${{ steps.new_version.outputs.new_tag }}"
          body: |
            ## 🚀 Perubahan di versi ini

            ${{ steps.changelog.outputs.log }}

            ---
            **Full Changelog**: https://github.com/el-pablos/wa-autoreply-bot/commits/main
          draft:      false
          prerelease: false
```

```bash
git add .github/
git commit -m "add: ci/cd github actions — auto test, build docker, dan release otomatis"
```

---

## LANGKAH 10 — SETUP SERVER (PRODUCTION)

Jalankan semua perintah ini di server kamu secara berurutan:

### 10.1 Cek & buat DNS record di Cloudflare

```bash
# Cek apakah subdomain sudah ada
curl -s -X GET "https://api.cloudflare.com/client/v4/zones/FILL_YOUR_CF_ZONE_ID_HERE/dns_records?name=monitoring-wa.tams.codes" \
  -H "Authorization: Bearer FILL_YOUR_CF_DNS_API_TOKEN_HERE" \
  -H "Content-Type: application/json" | python3 -m json.tool

# Jika belum ada, buat record A baru:
# Ganti YOUR_SERVER_IP dengan IP VPS kamu
curl -s -X POST "https://api.cloudflare.com/client/v4/zones/FILL_YOUR_CF_ZONE_ID_HERE/dns_records" \
  -H "Authorization: Bearer FILL_YOUR_CF_DNS_API_TOKEN_HERE" \
  -H "Content-Type: application/json" \
  --data '{
    "type": "A",
    "name": "monitoring-wa",
    "content": "YOUR_SERVER_IP",
    "ttl": 1,
    "proxied": false
  }' | python3 -m json.tool
```

### 10.2 Install dependencies server

```bash
# Update sistem
apt-get update && apt-get upgrade -y

# Install Docker
curl -fsSL https://get.docker.com | sh
usermod -aG docker $USER

# Install Docker Compose
curl -L "https://github.com/docker/compose/releases/latest/download/docker-compose-$(uname -s)-$(uname -m)" \
  -o /usr/local/bin/docker-compose
chmod +x /usr/local/bin/docker-compose

# Install Nginx
apt-get install -y nginx certbot python3-certbot-nginx

# Install Git
apt-get install -y git
```

### 10.3 Clone repo dan setup

```bash
cd /var/www

git clone https://FILL_YOUR_GITHUB_TOKEN_HERE@github.com/el-pablos/wa-autoreply-bot.git
cd wa-autoreply-bot

# Setup env bot
cp bot/.env.example bot/.env
# Edit: masukkan DB_PASSWORD yang benar
nano bot/.env

# Setup env dashboard
cp dashboard/.env.example dashboard/.env
# Edit: masukkan DB_PASSWORD dan DASHBOARD_PASSWORD yang benar
nano dashboard/.env
# Jalankan php artisan key:generate di dalam container nanti
```

### 10.4 Konfigurasi Nginx (SEBELUM certbot)

```bash
# Copy config nginx
cp /var/www/wa-autoreply-bot/nginx/monitoring-wa.conf /etc/nginx/sites-available/monitoring-wa.tams.codes

# Aktifkan site
ln -s /etc/nginx/sites-available/monitoring-wa.tams.codes /etc/nginx/sites-enabled/

# Verifikasi config valid
nginx -t

# Reload nginx
systemctl reload nginx
```

**PENTING**: Sebelum Certbot, ubah dulu konfigurasi nginx untuk HTTP only (hapus blok SSL sementara):

```nginx
# /etc/nginx/sites-available/monitoring-wa.tams.codes (sementara, sebelum certbot)
server {
    listen 80;
    server_name monitoring-wa.tams.codes;

    location / {
        proxy_pass http://127.0.0.1:8002;
        proxy_set_header Host $host;
        proxy_set_header X-Real-IP $remote_addr;
        proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
        proxy_set_header X-Forwarded-Proto $scheme;
    }
}
```

```bash
nginx -t && systemctl reload nginx
```

### 10.5 Jalankan Docker containers

```bash
cd /var/www/wa-autoreply-bot

# Build dan jalankan semua service
docker-compose -f docker-compose.yml -f docker-compose.prod.yml up -d --build

# Cek status
docker-compose ps

# Cek log dashboard (tunggu sampai siap)
docker-compose logs -f dashboard

# Jalankan migrasi Laravel
docker-compose exec dashboard php artisan migrate --force

# Cek semua container healthy
docker-compose ps
# Status semua harus: healthy
```

### 10.6 Install SSL dengan Certbot

```bash
# Pastikan port 80 bisa diakses dari internet dulu
certbot --nginx -d monitoring-wa.tams.codes \
  --non-interactive \
  --agree-tos \
  --email yeteprem.end23juni@gmail.com

# Setelah certbot selesai, copy config nginx final
cp /var/www/wa-autoreply-bot/nginx/monitoring-wa.conf /etc/nginx/sites-available/monitoring-wa.tams.codes

nginx -t && systemctl reload nginx
```

### 10.7 Verifikasi keseluruhan

```bash
# Test HTTPS
curl -I https://monitoring-wa.tams.codes/login
# Harus: HTTP/2 200

# Test redirect HTTP → HTTPS
curl -I http://monitoring-wa.tams.codes
# Harus: 301 redirect ke https

# Cek semua container
docker-compose ps

# Cek health bot
curl http://localhost:3001/health

# Auto-renew SSL (cek crontab)
certbot renew --dry-run
```

```bash
git add .
git commit -m "add: nginx production config, server setup, dan instruksi deployment lengkap"
```

---

## LANGKAH 11 — README.MD

Buat file `README.md` di root dengan konten di bawah ini (minimum 2000 kata, bahasa Indonesia kasual):

```markdown
<div align="center">

# 🤖 WA Auto-Reply Bot

[![CI Status](https://github.com/el-pablos/wa-autoreply-bot/workflows/CI%20—%20Test%20%26%20Lint/badge.svg)](https://github.com/el-pablos/wa-autoreply-bot/actions)
[![Latest Release](https://img.shields.io/github/v/release/el-pablos/wa-autoreply-bot?color=25d366&label=versi+terbaru)](https://github.com/el-pablos/wa-autoreply-bot/releases)
[![License: MIT](https://img.shields.io/badge/lisensi-MIT-blue.svg)](LICENSE)
[![PHP](https://img.shields.io/badge/PHP-8.3-777BB4?logo=php)](https://php.net)
[![Node](https://img.shields.io/badge/Node.js-20-339933?logo=node.js)](https://nodejs.org)
[![Docker](https://img.shields.io/badge/Docker-ready-2496ED?logo=docker)](https://docker.com)
[![MySQL](https://img.shields.io/badge/MySQL-8.0-4479A1?logo=mysql)](https://mysql.com)

**Bot WhatsApp auto-reply berbasis Baileys dengan dashboard monitoring Laravel — dijalankan full via Docker.**

[🚀 Demo](#) · [📖 Dokumentasi](#cara-install) · [🐛 Laporkan Bug](https://github.com/el-pablos/wa-autoreply-bot/issues)

![Dashboard Preview](https://via.placeholder.com/800x400/0d1117/25d366?text=WA+Bot+Dashboard)

</div>

---

## 📖 Deskripsi Proyek

WA Auto-Reply Bot adalah sistem auto-responder WhatsApp yang dibangun di atas library **Baileys** (Node.js), dikombinasikan dengan dashboard monitoring berbasis **Laravel** yang bisa diakses lewat browser. Semua komponen dijalankan di dalam **Docker**, jadi gampang banget buat start, stop, dan restart.

Singkatnya, kalau ada yang WA kamu dan nomornya ada di **allow-list**, bot akan otomatis bales dengan pesan custom yang bisa kamu ubah kapan aja dari dashboard. Cocok buat kamu yang sering offline atau lagi sibuk tapi nggak mau ninggalin orang nunggu tanpa kabar.

**Kenapa pakai sistem ini?**

- **Tidak perlu server WA berbayar** — Baileys jalan langsung di Node.js, gratis
- **Full kontrol** — kamu tentukan siapa yang dapat auto-reply lewat allow-list
- **Dashboard real-time** — lihat semua pesan masuk, statistik, dan ubah pengaturan tanpa restart bot
- **Portable** — Docker compose, tinggal `up -d` dan jalan

---

## 🏗️ Arsitektur Proyek

```
┌─────────────────────────────────────────────────────────────┐
│                        INTERNET                             │
└─────────────────┬───────────────────────────────────────────┘
                  │
        ┌─────────▼──────────┐
        │  WhatsApp Network  │
        │  (Baileys WS)      │
        └─────────┬──────────┘
                  │ WebSocket
        ┌─────────▼──────────────────────────────────────────┐
        │              Docker Network                        │
        │                                                    │
        │  ┌──────────────┐    ┌────────────────────────┐   │
        │  │  wa-bot      │    │  wa-dashboard           │   │
        │  │  (Node.js)   │◄──►│  (Laravel + PHP-FPM)   │   │
        │  │  port 3001   │    │  port 80 → 8002         │   │
        │  └──────┬───────┘    └──────────┬─────────────┘   │
        │         │                       │                  │
        │         └──────────┬────────────┘                  │
        │                    │                               │
        │           ┌────────▼────────┐                      │
        │           │   wa-mysql      │                      │
        │           │  (MySQL 8.0)    │                      │
        │           │  port 3307      │                      │
        │           └─────────────────┘                      │
        └────────────────────────────────────────────────────┘
                  │
        ┌─────────▼──────────┐
        │  Nginx + Certbot   │
        │  monitoring-wa     │
        │  .tams.codes       │
        └────────────────────┘
```

### Alur Kerja Bot

```
Pesan WA Masuk
      │
      ▼
Apakah msg.key.fromMe? ──YES──► Abaikan
      │ NO
      ▼
Apakah dari grup?
      │ YES
      ▼
ignore_groups = true? ──YES──► Log saja, tidak reply
      │ NO
      │
      ▼ (private / grup dengan ignore=false)
Cek allow-list MySQL
      │
      ├──NOT FOUND──► Log (is_allowed=false, replied=false)
      │
      └──FOUND──►
            │
            ▼
      auto_reply_enabled = true?
            │ NO──► Log (is_allowed=true, replied=false)
            │ YES
            ▼
      Tunggu reply_delay_ms
            │
            ▼
      sock.sendMessage(pesan dari DB)
            │
            ▼
      Log (is_allowed=true, replied=true)
```

---

## 📊 ERD Database

```
┌─────────────────────────────┐
│       allowed_numbers       │
├─────────────────────────────┤
│ id            INT PK AI     │
│ phone_number  VARCHAR(20) UQ│
│ label         VARCHAR(100)  │
│ is_active     TINYINT(1)    │
│ created_at    TIMESTAMP     │
│ updated_at    TIMESTAMP     │
└─────────────────────────────┘

┌─────────────────────────────┐
│        message_logs         │
├─────────────────────────────┤
│ id            BIGINT PK AI  │
│ from_number   VARCHAR(20)   │
│ message_text  TEXT          │
│ message_type  VARCHAR(30)   │
│ is_allowed    TINYINT(1)    │
│ replied       TINYINT(1)    │
│ reply_text    TEXT          │
│ group_id      VARCHAR(50)   │
│ received_at   TIMESTAMP     │
└─────────────────────────────┘

┌─────────────────────────────┐
│        bot_settings         │
├─────────────────────────────┤
│ key           VARCHAR(60) PK│
│ value         TEXT          │
│ description   VARCHAR(255)  │
│ updated_at    TIMESTAMP     │
└─────────────────────────────┘
```

---

## 🛠️ Tech Stack

| Komponen | Teknologi | Keterangan |
|---|---|---|
| **WA Bot** | Node.js 20 + Baileys | Koneksi WhatsApp via WebSocket |
| **Dashboard** | Laravel 11 + PHP 8.3 | Web monitoring & manajemen |
| **Database** | MySQL 8.0 | Shared DB antara bot & dashboard |
| **Container** | Docker + Compose | Orkestrasi semua service |
| **Web Server** | Nginx | Reverse proxy + SSL termination |
| **SSL** | Let's Encrypt (Certbot) | HTTPS otomatis |
| **DNS** | Cloudflare | DNS management |
| **CI/CD** | GitHub Actions | Auto test, build, release |

---

## ✨ Fitur Lengkap

### 🤖 Bot (Node.js + Baileys)
- ✅ Auto-reply ke nomor yang ada di allow-list
- ✅ QR code endpoint di `/qr` untuk scan pertama kali
- ✅ Health check endpoint di `/health`
- ✅ Auto-reconnect jika koneksi terputus
- ✅ Support berbagai tipe pesan (teks, gambar, video, audio, sticker, lokasi, kontak)
- ✅ Configurable delay sebelum reply (biar keliatan natural)
- ✅ Toggle ignore pesan dari grup
- ✅ Update status bot di database (online/offline/connecting)
- ✅ Graceful shutdown

### 📊 Dashboard (Laravel)
- ✅ Login dengan password sederhana (single-user)
- ✅ Dashboard dengan statistik real-time
- ✅ Chart pesan 7 hari terakhir
- ✅ Allow-list CRUD (tambah, edit, hapus, toggle aktif)
- ✅ Filter & search allow-list
- ✅ Log viewer dengan filter lengkap (nomor, status reply, tanggal)
- ✅ Halaman pengaturan (ubah pesan reply, delay, toggle auto-reply)
- ✅ Mobile-First responsive design
- ✅ Dark mode by default

### 🔧 DevOps
- ✅ Docker Compose untuk development dan production
- ✅ Health check untuk semua service
- ✅ GitHub Actions CI/CD
- ✅ Auto-release dengan semantic versioning
- ✅ SSL otomatis dengan Certbot
- ✅ Cloudflare DNS management

---

## 🚀 Cara Install

### Prerequisite

- Docker & Docker Compose
- Git
- Domain dengan Cloudflare (untuk production)

### Development (Lokal)

```bash
# 1. Clone repo
git clone https://github.com/el-pablos/wa-autoreply-bot.git
cd wa-autoreply-bot

# 2. Setup env
cp bot/.env.example bot/.env
cp dashboard/.env.example dashboard/.env

# 3. Edit env (isi credential yang dibutuhkan)
nano bot/.env
nano dashboard/.env

# 4. Jalankan semua service
docker-compose up -d --build

# 5. Jalankan migrasi
docker-compose exec dashboard php artisan migrate

# 6. Lihat QR code untuk scan WA
docker-compose logs bot
# atau buka http://localhost:3001/qr

# 7. Buka dashboard
# http://localhost:8002
```

### Production (Server)

Ikuti instruksi lengkap di [LANGKAH 10](#langkah-10--setup-server-production) di mega prompt ini.

---

## 📱 Screenshot Dashboard

| Halaman | Deskripsi |
|---|---|
| Login | Halaman login dengan password |
| Dashboard | Statistik & chart pesan |
| Allow-List | Manajemen nomor WA |
| Log Pesan | Riwayat semua pesan masuk |
| Pengaturan | Konfigurasi bot |

---

## 🔧 Konfigurasi Bot Settings

| Key | Default | Deskripsi |
|---|---|---|
| `auto_reply_enabled` | `true` | Toggle auto-reply on/off |
| `reply_message` | *(lihat DB)* | Pesan yang dikirim bot |
| `reply_delay_ms` | `1500` | Delay sebelum balas (ms) |
| `bot_status` | `offline` | Status koneksi bot |
| `ignore_groups` | `true` | Abaikan pesan dari grup |

---

## 👤 Kontributor

<table>
  <tr>
    <td align="center">
      <a href="https://github.com/el-pablos">
        <img src="https://avatars.githubusercontent.com/el-pablos" width="80" style="border-radius:50%"><br>
        <b>Tama (el-pablos)</b>
      </a>
      <br>Creator & Maintainer
    </td>
  </tr>
</table>

---

## 📄 Lisensi

Proyek ini menggunakan lisensi **MIT**. Bebas dipakai, dimodifikasi, dan didistribusikan.

---

<div align="center">
  <sub>Dibuat dengan ☕ oleh <a href="https://github.com/el-pablos">el-pablos</a></sub>
</div>
```

```bash
git add README.md
git commit -m "docs: tambah readme.md lengkap 2000+ kata dengan arsitektur, ERD, flowchart, dan panduan install"
```

---

## LANGKAH 12 — UPDATE REPO GITHUB (TOPICS & DESCRIPTION)

```bash
# Update deskripsi dan topics repo via API
curl -X PATCH \
  -H "Authorization: token FILL_YOUR_GITHUB_TOKEN_HERE" \
  -H "Accept: application/vnd.github.v3+json" \
  https://api.github.com/repos/el-pablos/wa-autoreply-bot \
  -d '{
    "description": "🤖 WhatsApp Auto-Reply Bot berbasis Baileys + Laravel Dashboard + Docker — monitor pesan WA dengan allow-list, log viewer, dan toggle reply real-time",
    "homepage": "https://monitoring-wa.tams.codes",
    "topics": ["whatsapp", "baileys", "laravel", "docker", "nodejs", "bot", "auto-reply", "monitoring", "mysql", "php", "javascript", "whatsapp-bot", "self-hosted", "dashboard"],
    "has_issues": true,
    "has_wiki": false,
    "has_projects": false
  }'
```

---

## LANGKAH 13 — PUSH FINAL & VERIFIKASI

```bash
# Push semua yang tersisa
git push origin main

# Verifikasi GitHub Actions berjalan
# Buka: https://github.com/el-pablos/wa-autoreply-bot/actions

# Verifikasi release dibuat otomatis
# Buka: https://github.com/el-pablos/wa-autoreply-bot/releases

# Verifikasi website live
curl -I https://monitoring-wa.tams.codes/login
# Expected: HTTP/2 200

# Verifikasi bot health
curl http://YOUR_SERVER_IP:3001/health
# Expected: {"status":"ok","botStatus":"..."}

echo "✅ Semua selesai!"
```

---

## ✅ CHECKLIST FINAL

Sebelum declare selesai, pastikan semua ini sudah hijau:

- [ ] `git log --oneline` — semua commit ada dan format benar
- [ ] `cd bot && npm test` — **100% passed**, coverage ≥ 80%
- [ ] `cd dashboard && php artisan test` — **100% passed**
- [ ] `docker-compose up -d` — semua container status `healthy`
- [ ] `https://monitoring-wa.tams.codes` — 200 OK, HTTPS valid
- [ ] `http://monitoring-wa.tams.codes` — redirect ke HTTPS
- [ ] GitHub Actions CI — semua job passed (hijau)
- [ ] GitHub Release — ada release terbaru dengan changelog
- [ ] GitHub repo — description, topics, homepage sudah diisi
- [ ] QR scan WA berhasil — bot status online di dashboard
- [ ] Kirim pesan test dari nomor di allow-list — bot auto-reply
- [ ] Dashboard login dengan password — berhasil masuk
- [ ] Allow-list CRUD — tambah, edit, hapus, toggle semua berfungsi
- [ ] Log viewer — pesan tercatat dengan benar
- [ ] Settings update — perubahan reply message langsung berlaku
- [ ] `.gitignore` — tidak ada `.env` atau `auth_info/` yang ter-commit
- [ ] README.md — render bagus di GitHub, semua section ada

---

> **Catatan Penting**: Jangan pernah commit `.env`, `auth_info/`, atau file yang berisi credential ke GitHub. Semua secret wajib ada di environment variable atau file `.env` yang ada di `.gitignore`.
```
