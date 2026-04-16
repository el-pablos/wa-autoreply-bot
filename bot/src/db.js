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

// ─────────────────────────────────────────────────────────────────
// FUNGSI BARU — Approved Session
// ─────────────────────────────────────────────────────────────────

/**
 * Buat atau perbarui (refresh) approved session untuk nomor tertentu.
 * Jika sudah ada session aktif → update last_activity & expires_at.
 * Jika belum ada → insert baru.
 *
 * @param {string} phoneNumber  - Nomor target, format: 628xxx
 * @param {string} approvedBy   - Nomor owner yang approve
 * @param {number} expiryHours  - Durasi expire dalam jam (default: 24)
 * @returns {Promise<{action: 'created'|'refreshed', expiresAt: Date}>}
 */
export async function upsertApprovedSession(phoneNumber, approvedBy, expiryHours = 24) {
  const db  = getPool();
  const now = new Date();
  const exp = new Date(now.getTime() + expiryHours * 60 * 60 * 1000);

  const [existing] = await db.execute(
    'SELECT id FROM approved_sessions WHERE phone_number = ? AND is_active = 1 LIMIT 1',
    [phoneNumber]
  );

  if (existing.length > 0) {
    await db.execute(
      `UPDATE approved_sessions
         SET last_activity_at = ?, expires_at = ?, approved_by = ?
       WHERE phone_number = ? AND is_active = 1`,
      [now, exp, approvedBy, phoneNumber]
    );
    return { action: 'refreshed', expiresAt: exp };
  }

  await db.execute(
    `INSERT INTO approved_sessions
       (phone_number, approved_at, last_activity_at, expires_at, approved_by, is_active)
     VALUES (?, ?, ?, ?, ?, 1)`,
    [phoneNumber, now, now, exp, approvedBy]
  );
  return { action: 'created', expiresAt: exp };
}

/**
 * Cek apakah nomor sedang dalam approved session aktif yang belum expired.
 *
 * @param {string} phoneNumber
 * @returns {Promise<boolean>}
 */
export async function isInApprovedSession(phoneNumber) {
  const db  = getPool();
  const now = new Date();

  const [rows] = await db.execute(
    `SELECT id FROM approved_sessions
     WHERE phone_number = ? AND is_active = 1 AND expires_at > ?
     LIMIT 1`,
    [phoneNumber, now]
  );
  return rows.length > 0;
}

/**
 * Update last_activity_at dan reset expires_at (rolling 24 jam).
 * Dipanggil setiap kali ada pesan masuk dari nomor yang sedang approved.
 *
 * @param {string} phoneNumber
 * @param {number} expiryHours
 * @returns {Promise<boolean>} true jika ada record yang diupdate
 */
export async function refreshApprovedSession(phoneNumber, expiryHours = 24) {
  const db  = getPool();
  const now = new Date();
  const exp = new Date(now.getTime() + expiryHours * 60 * 60 * 1000);

  const [result] = await db.execute(
    `UPDATE approved_sessions
       SET last_activity_at = ?, expires_at = ?
     WHERE phone_number = ? AND is_active = 1`,
    [now, exp, phoneNumber]
  );
  return result.affectedRows > 0;
}

/**
 * Revoke (batalkan) approved session secara manual sebelum expired.
 *
 * @param {string} phoneNumber
 * @returns {Promise<boolean>} true jika ada record yang di-revoke
 */
export async function revokeApprovedSession(phoneNumber) {
  const db  = getPool();
  const now = new Date();

  const [result] = await db.execute(
    `UPDATE approved_sessions
       SET is_active = 0, revoked_at = ?
     WHERE phone_number = ? AND is_active = 1`,
    [now, phoneNumber]
  );
  return result.affectedRows > 0;
}

/**
 * Expire semua session yang sudah melewati expires_at.
 * Dipanggil oleh scheduler secara periodik.
 *
 * @returns {Promise<number>} jumlah session yang di-expire
 */
export async function expireStaleSessions() {
  const db  = getPool();
  const now = new Date();

  const [result] = await db.execute(
    `UPDATE approved_sessions
       SET is_active = 0
     WHERE is_active = 1 AND expires_at <= ?`,
    [now]
  );
  return result.affectedRows;
}

/**
 * Ambil semua session aktif beserta detail.
 * Digunakan oleh dashboard.
 *
 * @returns {Promise<Array>}
 */
export async function getActiveApprovedSessions() {
  const db = getPool();
  const [rows] = await db.execute(
    `SELECT id, phone_number, approved_at, last_activity_at, expires_at, approved_by
     FROM approved_sessions
     WHERE is_active = 1 AND expires_at > NOW()
     ORDER BY approved_at DESC`
  );
  return rows;
}
