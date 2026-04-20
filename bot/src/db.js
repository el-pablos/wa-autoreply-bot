import mysql from "mysql2/promise";
import { createHash } from "node:crypto";
import { config } from "./config.js";
import { logger } from "./utils/logger.js";

let pool = null;

/**
 * Dapatkan pool koneksi MySQL (singleton).
 * @returns {mysql.Pool}
 */
export function getPool() {
  if (!pool) {
    pool = mysql.createPool(config.db);
    logger.info("MySQL pool dibuat");
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
    "SELECT `value` FROM bot_settings WHERE `key` = ? LIMIT 1",
    [key],
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
    "SELECT id FROM allowed_numbers WHERE phone_number = ? AND is_active = 1 LIMIT 1",
    [phoneNumber],
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
 * @param {number|null} logData.responseTimeMs
 * @returns {Promise<number>} insertId
 */
export async function saveMessageLog(logData) {
  const db = getPool();
  const {
    fromNumber,
    messageText,
    messageType = "text",
    isAllowed,
    replied,
    replyText = null,
    groupId = null,
    responseTimeMs = null,
  } = logData;

  const [result] = await db.execute(
    `INSERT INTO message_logs
      (from_number, message_text, message_type, is_allowed, replied, reply_text, group_id, response_time_ms)
     VALUES (?, ?, ?, ?, ?, ?, ?, ?)`,
    [
      fromNumber,
      messageText,
      messageType,
      isAllowed ? 1 : 0,
      replied ? 1 : 0,
      replyText,
      groupId,
      responseTimeMs === null || responseTimeMs === undefined
        ? null
        : Math.max(0, Number(responseTimeMs) || 0),
    ],
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
    [status],
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
export async function upsertApprovedSession(
  phoneNumber,
  approvedBy,
  expiryHours = 24,
) {
  const db = getPool();
  const now = new Date();
  const exp = new Date(now.getTime() + expiryHours * 60 * 60 * 1000);

  const [existing] = await db.execute(
    "SELECT id FROM approved_sessions WHERE phone_number = ? AND is_active = 1 LIMIT 1",
    [phoneNumber],
  );

  if (existing.length > 0) {
    await db.execute(
      `UPDATE approved_sessions
         SET last_activity_at = ?, expires_at = ?, approved_by = ?
       WHERE phone_number = ? AND is_active = 1`,
      [now, exp, approvedBy, phoneNumber],
    );
    return { action: "refreshed", expiresAt: exp };
  }

  await db.execute(
    `INSERT INTO approved_sessions
       (phone_number, approved_at, last_activity_at, expires_at, approved_by, is_active)
     VALUES (?, ?, ?, ?, ?, 1)`,
    [phoneNumber, now, now, exp, approvedBy],
  );
  return { action: "created", expiresAt: exp };
}

/**
 * Cek apakah nomor sedang dalam approved session aktif yang belum expired.
 *
 * @param {string} phoneNumber
 * @returns {Promise<boolean>}
 */
export async function isInApprovedSession(phoneNumber) {
  const db = getPool();
  const now = new Date();

  const [rows] = await db.execute(
    `SELECT id FROM approved_sessions
     WHERE phone_number = ? AND is_active = 1 AND expires_at > ?
     LIMIT 1`,
    [phoneNumber, now],
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
  const db = getPool();
  const now = new Date();
  const exp = new Date(now.getTime() + expiryHours * 60 * 60 * 1000);

  const [result] = await db.execute(
    `UPDATE approved_sessions
       SET last_activity_at = ?, expires_at = ?
     WHERE phone_number = ? AND is_active = 1`,
    [now, exp, phoneNumber],
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
  const db = getPool();
  const now = new Date();

  const [result] = await db.execute(
    `UPDATE approved_sessions
       SET is_active = 0, revoked_at = ?
     WHERE phone_number = ? AND is_active = 1`,
    [now, phoneNumber],
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
  const db = getPool();
  const now = new Date();

  const [result] = await db.execute(
    `UPDATE approved_sessions
       SET is_active = 0
     WHERE is_active = 1 AND expires_at <= ?`,
    [now],
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
     ORDER BY approved_at DESC`,
  );
  return rows;
}

// ─────────────────────────────────────────────────────────────────
// FUNGSI BARU — Feature Expansion Helpers
// ─────────────────────────────────────────────────────────────────

/**
 * Ambil template aktif untuk nomor tertentu.
 * Prioritas: template yang ditautkan di allowlist, lalu fallback template default.
 *
 * @param {string} phoneNumber
 * @returns {Promise<Object|null>}
 */
export async function getActiveTemplate(phoneNumber) {
  const db = getPool();

  const [linkedRows] = await db.execute(
    `SELECT t.id, t.name, t.body, t.conditions_json, t.is_default
     FROM allowed_numbers a
     INNER JOIN reply_templates t ON t.id = a.template_id
     WHERE a.phone_number = ?
       AND a.is_active = 1
       AND t.is_active = 1
     LIMIT 1`,
    [phoneNumber],
  );
  if (linkedRows.length > 0) return linkedRows[0];

  const [defaultRows] = await db.execute(
    `SELECT id, name, body, conditions_json, is_default
     FROM reply_templates
     WHERE is_default = 1 AND is_active = 1
     ORDER BY id ASC
     LIMIT 1`,
  );
  return defaultRows.length > 0 ? defaultRows[0] : null;
}

/**
 * Ambil jadwal business hours aktif.
 *
 * @returns {Promise<Array<Object>>}
 */
export async function getBusinessHourSchedules() {
  const db = getPool();
  const [rows] = await db.execute(
    `SELECT id, weekday, start_time, end_time, timezone, is_active
     FROM business_hour_schedules
     WHERE is_active = 1
     ORDER BY weekday ASC, id ASC`,
  );
  return rows;
}

/**
 * Ambil semua OoF schedule aktif yang mencakup tanggal tertentu.
 *
 * @param {Date|string} [date]
 * @returns {Promise<Array<Object>>}
 */
export async function getActiveOofSchedules(date = new Date()) {
  const db = getPool();
  const dateStr = normalizeDateOnly(date);
  const [rows] = await db.execute(
    `SELECT id, start_date, end_date, message, is_active
     FROM oof_schedules
     WHERE is_active = 1
       AND start_date <= ?
       AND end_date >= ?
     ORDER BY start_date ASC, id ASC`,
    [dateStr, dateStr],
  );
  return rows;
}

/**
 * Ambil template aktif per jenis pesan.
 *
 * @returns {Promise<Array<Object>>}
 */
export async function getMessageTypeTemplates() {
  const db = getPool();
  const [rows] = await db.execute(
    `SELECT message_type, body, is_active
     FROM message_type_templates
     WHERE is_active = 1`,
  );
  return rows;
}

/**
 * Cek blacklist aktif untuk nomor tertentu.
 *
 * @param {string} phoneNumber
 * @returns {Promise<Object|null>}
 */
export async function getBlacklistEntry(phoneNumber) {
  const db = getPool();
  const [rows] = await db.execute(
    `SELECT id, phone_number, reason, blocked_at, unblock_at, blocked_by, is_active
     FROM blacklist
     WHERE phone_number = ?
       AND is_active = 1
     LIMIT 1`,
    [phoneNumber],
  );
  return rows.length > 0 ? rows[0] : null;
}

/**
 * Catat pelanggaran rate limit.
 *
 * @param {Object} input
 * @param {string} input.phoneNumber
 * @param {Date|string} input.windowStart
 * @param {number} input.messageCount
 * @returns {Promise<number>} insertId
 */
export async function saveRateLimitViolation(input) {
  const db = getPool();
  const [result] = await db.execute(
    `INSERT INTO rate_limit_violations (phone_number, window_start, message_count)
     VALUES (?, ?, ?)`,
    [
      input.phoneNumber,
      normalizeDateTime(input.windowStart || new Date()),
      Number(input.messageCount) || 0,
    ],
  );
  return result.insertId;
}

/**
 * Verifikasi API key plaintext ke hash di DB.
 *
 * @param {string} apiKeyRaw
 * @returns {Promise<Object|null>}
 */
export async function verifyApiKey(apiKeyRaw) {
  const key = String(apiKeyRaw || "").trim();
  if (!key) return null;

  const db = getPool();
  const keyHash = createHash("sha256").update(key, "utf8").digest("hex");
  const [rows] = await db.execute(
    `SELECT id, name, scopes, revoked_at
     FROM api_keys
     WHERE key_hash = ?
       AND revoked_at IS NULL
     LIMIT 1`,
    [keyHash],
  );
  const row = rows.length > 0 ? rows[0] : null;
  if (!row) return null;

  await db.execute(
    `UPDATE api_keys SET last_used_at = CURRENT_TIMESTAMP WHERE id = ?`,
    [row.id],
  );
  return row;
}

/**
 * Tambah/update allowlist entry dari API publik.
 *
 * @param {Object} input
 * @param {string} input.phoneNumber
 * @param {string|null} [input.label]
 * @param {boolean} [input.isActive]
 * @returns {Promise<boolean>}
 */
export async function upsertAllowListEntry(input) {
  const db = getPool();
  await db.execute(
    `INSERT INTO allowed_numbers (phone_number, label, is_active, created_at, updated_at)
     VALUES (?, ?, ?, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)
     ON DUPLICATE KEY UPDATE
       label = VALUES(label),
       is_active = VALUES(is_active),
       updated_at = CURRENT_TIMESTAMP`,
    [input.phoneNumber, input.label ?? null, input.isActive === false ? 0 : 1],
  );
  return true;
}

/**
 * Ambil log pesan untuk endpoint publik.
 *
 * @param {Object} [opts]
 * @param {number} [opts.limit=50]
 * @param {number} [opts.offset=0]
 * @param {string} [opts.fromNumber]
 * @returns {Promise<Array<Object>>}
 */
export async function getMessageLogs(opts = {}) {
  const db = getPool();
  const limit = Math.max(1, Math.min(500, Number(opts.limit) || 50));
  const offset = Math.max(0, Number(opts.offset) || 0);

  if (opts.fromNumber) {
    const [rows] = await db.execute(
      `SELECT id, from_number, message_text, message_type, is_allowed, replied, reply_text, group_id, received_at, response_time_ms
       FROM message_logs
       WHERE from_number = ?
       ORDER BY id DESC
       LIMIT ? OFFSET ?`,
      [opts.fromNumber, limit, offset],
    );
    return rows;
  }

  const [rows] = await db.execute(
    `SELECT id, from_number, message_text, message_type, is_allowed, replied, reply_text, group_id, received_at, response_time_ms
     FROM message_logs
     ORDER BY id DESC
     LIMIT ? OFFSET ?`,
    [limit, offset],
  );
  return rows;
}

/**
 * Ambil kumpulan setting berdasarkan daftar key.
 *
 * @param {string[]} keys
 * @returns {Promise<Array<{key:string, value:string}>>}
 */
export async function getSettingsByKeys(keys) {
  const list = Array.isArray(keys) ? keys.filter(Boolean) : [];
  if (list.length === 0) return [];

  const db = getPool();
  const placeholders = list.map(() => "?").join(", ");
  const [rows] = await db.execute(
    `SELECT \`key\`, \`value\`
     FROM bot_settings
     WHERE \`key\` IN (${placeholders})`,
    list,
  );
  return rows;
}

function normalizeDateOnly(input) {
  const d = new Date(input);
  if (Number.isNaN(d.getTime())) {
    throw new TypeError("Tanggal tidak valid");
  }
  const year = d.getUTCFullYear();
  const month = String(d.getUTCMonth() + 1).padStart(2, "0");
  const day = String(d.getUTCDate()).padStart(2, "0");
  return `${year}-${month}-${day}`;
}

function normalizeDateTime(input) {
  const d = new Date(input);
  if (Number.isNaN(d.getTime())) {
    throw new TypeError("Datetime tidak valid");
  }
  return d;
}
