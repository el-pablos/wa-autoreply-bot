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
