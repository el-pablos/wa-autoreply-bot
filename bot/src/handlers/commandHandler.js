import { logger } from '../utils/logger.js';
import { config } from '../config.js';
import { getSetting, upsertApprovedSession, revokeApprovedSession, getActiveApprovedSessions } from '../db.js';

// ─────────────────────────────────────────────────────────────────
// HELPERS — Deteksi command & ekstraksi argumen
// ─────────────────────────────────────────────────────────────────

/**
 * Ekstrak teks mentah dari pesan.
 * @param {Object} msg
 * @returns {string}
 */
export function getRawText(msg) {
  return (
    msg.message?.conversation ||
    msg.message?.extendedTextMessage?.text ||
    ''
  ).trim();
}

/**
 * Cek apakah pesan ini adalah command yang dikirim oleh owner.
 * Owner dideteksi dari: fromMe === true ATAU nomor pengirim === OWNER_NUMBER.
 *
 * @param {Object} msg
 * @returns {boolean}
 */
export function isOwnerCommand(msg) {
  if (msg.key.fromMe) return true;

  const ownerNumber = config.bot.ownerNumber;
  if (!ownerNumber) return false;

  const senderJid = msg.key.remoteJid || '';
  const senderNum = senderJid.replace('@s.whatsapp.net', '');
  return senderNum === ownerNumber;
}

/**
 * Cek apakah teks pesan adalah command bot (diawali dengan /).
 * @param {Object} msg
 * @returns {boolean}
 */
export function isBotCommand(msg) {
  const text = getRawText(msg);
  return text.startsWith('/');
}

/**
 * Parse command dari pesan.
 * @param {Object} msg
 * @returns {{ command: string, args: string[] }}
 */
export function parseCommand(msg) {
  const text  = getRawText(msg);
  const parts = text.split(/\s+/).filter(Boolean);
  const command = (parts[0] || '').toLowerCase().replace('/', '');
  const args    = parts.slice(1);
  return { command, args };
}

/**
 * Ekstrak nomor target dari command.
 * Priority:
 *   1. Argumen eksplisit: /approve 628xxx
 *   2. Quoted/reply message: kirim /approve sambil reply pesan mereka
 *
 * @param {Object} msg
 * @param {string[]} args
 * @returns {string|null} phoneNumber format 628xxx, atau null jika tidak ditemukan
 */
export function extractTargetNumber(msg, args) {
  // 1. Cek argumen eksplisit
  if (args.length > 0 && /^628\d{7,13}$/.test(args[0])) {
    return args[0];
  }

  // 2. Cek quoted/replied message (contextInfo)
  const ctx = msg.message?.extendedTextMessage?.contextInfo;

  // Dari participant (pesan grup yang di-quote)
  if (ctx?.participant) {
    const num = ctx.participant.replace('@s.whatsapp.net', '');
    if (/^628\d{7,13}$/.test(num)) return num;
  }

  // Dari remoteJid (pesan private yang di-quote)
  if (ctx?.remoteJid && !ctx.remoteJid.endsWith('@g.us')) {
    const num = ctx.remoteJid.replace('@s.whatsapp.net', '');
    if (/^628\d{7,13}$/.test(num)) return num;
  }

  // Dari remoteJid chat saat ini jika private chat (bukan grup)
  const remoteJid = msg.key.remoteJid || '';
  if (!remoteJid.endsWith('@g.us')) {
    const num = remoteJid.replace('@s.whatsapp.net', '');
    // Jangan approve diri sendiri
    if (/^628\d{7,13}$/.test(num) && num !== config.bot.ownerNumber) {
      return num;
    }
  }

  return null;
}

// ─────────────────────────────────────────────────────────────────
// COMMAND HANDLERS — satu fungsi per command
// ─────────────────────────────────────────────────────────────────

/**
 * Handler command /approve
 * Menonaktifkan auto-reply ke nomor target selama 24 jam (rolling).
 *
 * @param {Object} sock  - Baileys socket instance
 * @param {Object} msg   - Message object
 * @param {string[]} args
 */
async function handleApprove(sock, msg, args) {
  const replyTo = msg.key.remoteJid;
  const target  = extractTargetNumber(msg, args);

  if (!target) {
    await sock.sendMessage(replyTo, {
      text: [
        '⚠️ *Nomor target tidak ditemukan!*',
        '',
        'Cara pakai:',
        '• Reply pesan mereka lalu ketik `/approve`',
        '• Atau eksplisit: `/approve 628xxxxxxxxxx`',
      ].join('\n'),
    });
    return;
  }

  // Ambil expiry hours dari setting DB
  const expiryHoursRaw = await getSetting('approve_expiry_hours');
  const expiryHours    = parseInt(expiryHoursRaw || '24', 10);

  const approvedBy = config.bot.ownerNumber || 'owner';
  const { action, expiresAt } = await upsertApprovedSession(target, approvedBy, expiryHours);

  const expStr = expiresAt.toLocaleString('id-ID', {
    timeZone:    'Asia/Jakarta',
    day:         '2-digit',
    month:       'short',
    year:        'numeric',
    hour:        '2-digit',
    minute:      '2-digit',
  });

  const verb = action === 'created' ? 'di-approve' : 'di-refresh';

  await sock.sendMessage(replyTo, {
    text: [
      `✅ *${target}* berhasil ${verb}!`,
      '',
      '🔕 Auto-reply ke nomor ini *dinonaktifkan*',
      `⏰ Akan aktif kembali otomatis jika tidak ada pesan selama *${expiryHours} jam*`,
      `📅 Ekspirasi: *${expStr} WIB*`,
      '',
      `_Kirim \`/revoke ${target}\` untuk batalkan lebih awal_`,
    ].join('\n'),
  });

  logger.info({ target, action, expiresAt }, 'Command /approve dieksekusi');
}

/**
 * Handler command /revoke
 * Batalkan approved session sebelum 24 jam habis.
 *
 * @param {Object} sock
 * @param {Object} msg
 * @param {string[]} args
 */
async function handleRevoke(sock, msg, args) {
  const replyTo = msg.key.remoteJid;
  const target  = extractTargetNumber(msg, args);

  if (!target) {
    await sock.sendMessage(replyTo, {
      text: '⚠️ Format: `/revoke 628xxxxxxxxxx`',
    });
    return;
  }

  const revoked = await revokeApprovedSession(target);

  if (!revoked) {
    await sock.sendMessage(replyTo, {
      text: `ℹ️ Tidak ada approved session aktif untuk *${target}*`,
    });
    return;
  }

  await sock.sendMessage(replyTo, {
    text: [
      `🔄 Session *${target}* berhasil di-revoke!`,
      '✅ Auto-reply ke nomor ini *aktif kembali* sekarang',
    ].join('\n'),
  });

  logger.info({ target }, 'Command /revoke dieksekusi');
}

/**
 * Handler command /status
 * Tampilkan semua approved session yang sedang aktif.
 *
 * @param {Object} sock
 * @param {Object} msg
 */
async function handleStatus(sock, msg) {
  const replyTo  = msg.key.remoteJid;
  const sessions = await getActiveApprovedSessions();

  if (sessions.length === 0) {
    await sock.sendMessage(replyTo, {
      text: '📋 Tidak ada approved session yang aktif saat ini.\nAuto-reply berjalan normal untuk semua nomor.',
    });
    return;
  }

  const lines = sessions.map((s, i) => {
    const expStr = new Date(s.expires_at).toLocaleString('id-ID', {
      timeZone: 'Asia/Jakarta',
      day:      '2-digit',
      month:    'short',
      hour:     '2-digit',
      minute:   '2-digit',
    });
    return `${i + 1}. *${s.phone_number}*\n   ⏰ Expire: ${expStr} WIB`;
  });

  await sock.sendMessage(replyTo, {
    text: [
      `📋 *${sessions.length} Session Aktif:*`,
      '',
      ...lines,
      '',
      '_Auto-reply dinonaktifkan untuk nomor di atas_',
    ].join('\n'),
  });

  logger.info({ count: sessions.length }, 'Command /status dieksekusi');
}

/**
 * Handler command /help
 * Tampilkan daftar command yang tersedia.
 *
 * @param {Object} sock
 * @param {Object} msg
 */
async function handleHelp(sock, msg) {
  const replyTo = msg.key.remoteJid;

  await sock.sendMessage(replyTo, {
    text: [
      '🤖 *Daftar Command Bot:*',
      '',
      '`/approve`',
      '  → Reply pesan seseorang lalu ketik ini',
      '  → Auto-reply ke mereka dinonaktifkan 24 jam',
      '',
      '`/approve 628xxx`',
      '  → Approve dengan nomor eksplisit',
      '',
      '`/revoke 628xxx`',
      '  → Batalkan approve sebelum 24 jam habis',
      '',
      '`/status`',
      '  → Lihat semua session yang sedang aktif',
      '',
      '`/help`',
      '  → Tampilkan pesan ini',
    ].join('\n'),
  });
}

// ─────────────────────────────────────────────────────────────────
// ENTRY POINT — Router command utama
// ─────────────────────────────────────────────────────────────────

/**
 * Router utama untuk semua command bot.
 * Dipanggil dari index.js sebelum handleIncomingMessage.
 *
 * @param {Object} sock
 * @param {Object} msg
 * @returns {Promise<boolean>} true jika pesan adalah command yang berhasil di-route
 */
export async function routeCommand(sock, msg) {
  // Double-check: hanya proses command dari owner
  if (!isOwnerCommand(msg)) return false;

  // Double-check: harus diawali /
  if (!isBotCommand(msg)) return false;

  const { command, args } = parseCommand(msg);

  switch (command) {
    case 'approve':
      await handleApprove(sock, msg, args);
      return true;

    case 'revoke':
      await handleRevoke(sock, msg, args);
      return true;

    case 'status':
      await handleStatus(sock, msg);
      return true;

    case 'help':
      await handleHelp(sock, msg);
      return true;

    default:
      // Command tidak dikenal — informasikan ke owner
      await sock.sendMessage(msg.key.remoteJid, {
        text: `❓ Command \`/${command}\` tidak dikenal. Ketik \`/help\` untuk daftar command.`,
      });
      return true;
  }
}
