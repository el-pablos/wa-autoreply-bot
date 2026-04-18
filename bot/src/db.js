import mysql from 'mysql2/promise';
import { createHash } from 'node:crypto';
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
    [phoneNumber]
  );
  if (linkedRows.length > 0) return linkedRows[0];

  const [defaultRows] = await db.execute(
    `SELECT id, name, body, conditions_json, is_default
     FROM reply_templates
     WHERE is_default = 1 AND is_active = 1
     ORDER BY id ASC
     LIMIT 1`
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
     ORDER BY weekday ASC, id ASC`
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
    [dateStr, dateStr]
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
     WHERE is_active = 1`
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
    [phoneNumber]
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
    ]
  );
  return result.insertId;
}

/**
 * Ambil seluruh knowledge base aktif.
 *
 * @returns {Promise<Array<Object>>}
 */
export async function getKnowledgeBaseEntries() {
  const db = getPool();
  const [rows] = await db.execute(
    `SELECT id, question, keywords, answer, is_active, match_count
     FROM knowledge_base
     WHERE is_active = 1
     ORDER BY id ASC`
  );
  return rows;
}

/**
 * Tambah counter match knowledge base.
 *
 * @param {number} entryId
 * @returns {Promise<boolean>}
 */
export async function incrementKnowledgeMatch(entryId) {
  const db = getPool();
  const [result] = await db.execute(
    `UPDATE knowledge_base
       SET match_count = match_count + 1,
           updated_at = CURRENT_TIMESTAMP
     WHERE id = ?`,
    [entryId]
  );
  return result.affectedRows > 0;
}

/**
 * Simpan satu turn histori AI.
 *
 * @param {Object} input
 * @param {string} input.phoneNumber
 * @param {'system'|'user'|'assistant'} input.role
 * @param {string} input.content
 * @param {number|null} [input.tokens]
 * @returns {Promise<number>} insertId
 */
export async function saveAiConversationTurn(input) {
  const db = getPool();
  const [result] = await db.execute(
    `INSERT INTO ai_conversation_history (phone_number, role, content, tokens, created_at, updated_at)
     VALUES (?, ?, ?, ?, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)`,
    [
      input.phoneNumber,
      input.role,
      input.content,
      input.tokens === undefined ? null : input.tokens,
    ]
  );
  return result.insertId;
}

/**
 * Ambil histori AI terbaru berdasarkan nomor.
 *
 * @param {string} phoneNumber
 * @param {Object} [opts]
 * @param {number} [opts.limit=20]
 * @param {Date|string} [opts.since]
 * @returns {Promise<Array<Object>>}
 */
export async function getRecentConversationHistory(phoneNumber, opts = {}) {
  const db = getPool();
  const limit = Math.max(1, Math.min(100, Number(opts.limit) || 20));

  if (opts.since) {
    const [rows] = await db.execute(
      `SELECT id, phone_number, role, content, tokens, created_at
       FROM ai_conversation_history
       WHERE phone_number = ? AND created_at >= ?
       ORDER BY created_at DESC
       LIMIT ?`,
      [phoneNumber, normalizeDateTime(opts.since), limit]
    );
    return rows;
  }

  const [rows] = await db.execute(
    `SELECT id, phone_number, role, content, tokens, created_at
     FROM ai_conversation_history
     WHERE phone_number = ?
     ORDER BY created_at DESC
     LIMIT ?`,
    [phoneNumber, limit]
  );
  return rows;
}

/**
 * Hapus histori AI yang lebih lama dari timestamp tertentu.
 *
 * @param {Date|string} olderThan
 * @returns {Promise<number>} affectedRows
 */
export async function pruneConversationHistory(olderThan) {
  const db = getPool();
  const [result] = await db.execute(
    `DELETE FROM ai_conversation_history WHERE created_at < ?`,
    [normalizeDateTime(olderThan)]
  );
  return result.affectedRows;
}

/**
 * Ambil endpoint webhook aktif untuk event tertentu.
 *
 * @param {string} eventName
 * @returns {Promise<Array<Object>>}
 */
export async function getActiveWebhookEndpoints(eventName) {
  const db = getPool();
  if (!eventName) {
    const [rows] = await db.execute(
      `SELECT id, name, url, secret, events, is_active
       FROM webhook_endpoints
       WHERE is_active = 1
       ORDER BY id ASC`
    );
    return rows;
  }

  const [rows] = await db.execute(
    `SELECT id, name, url, secret, events, is_active
     FROM webhook_endpoints
     WHERE is_active = 1
       AND (
         events IS NULL
         OR JSON_LENGTH(events) = 0
         OR JSON_CONTAINS(events, JSON_QUOTE(?))
       )
     ORDER BY id ASC`,
    [eventName]
  );
  return rows;
}

/**
 * Simpan log delivery webhook.
 *
 * @param {Object} input
 * @returns {Promise<number>} insertId
 */
export async function createWebhookDeliveryLog(input) {
  const db = getPool();
  const [result] = await db.execute(
    `INSERT INTO webhook_delivery_logs
      (endpoint_id, event, payload, status, response_code, attempts, response_body, created_at, updated_at)
     VALUES (?, ?, ?, ?, ?, ?, ?, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)`,
    [
      input.endpointId,
      input.event,
      JSON.stringify(input.payload ?? null),
      input.status || 'pending',
      input.responseCode ?? null,
      Number(input.attempts) || 0,
      input.responseBody ?? null,
    ]
  );
  return result.insertId;
}

/**
 * Update hasil delivery webhook.
 *
 * @param {number} deliveryLogId
 * @param {Object} patch
 * @returns {Promise<boolean>}
 */
export async function updateWebhookDeliveryLog(deliveryLogId, patch) {
  const db = getPool();
  const [result] = await db.execute(
    `UPDATE webhook_delivery_logs
       SET status = ?,
           response_code = ?,
           attempts = ?,
           response_body = ?,
           updated_at = CURRENT_TIMESTAMP
     WHERE id = ?`,
    [
      patch.status || 'failed',
      patch.responseCode ?? null,
      Number(patch.attempts) || 0,
      patch.responseBody ?? null,
      deliveryLogId,
    ]
  );
  return result.affectedRows > 0;
}

/**
 * Update timestamp trigger endpoint webhook.
 *
 * @param {number} endpointId
 * @returns {Promise<boolean>}
 */
export async function touchWebhookEndpoint(endpointId) {
  const db = getPool();
  const [result] = await db.execute(
    `UPDATE webhook_endpoints
       SET last_triggered_at = CURRENT_TIMESTAMP,
           updated_at = CURRENT_TIMESTAMP
     WHERE id = ?`,
    [endpointId]
  );
  return result.affectedRows > 0;
}

/**
 * Simpan escalation log.
 *
 * @param {Object} input
 * @returns {Promise<number>} insertId
 */
export async function saveEscalationLog(input) {
  const db = getPool();
  const [result] = await db.execute(
    `INSERT INTO escalation_logs (from_number, trigger_reason, escalated_to, message_snippet, escalated_at)
     VALUES (?, ?, ?, ?, ?)`,
    [
      input.fromNumber,
      input.triggerReason,
      input.escalatedTo,
      input.messageSnippet,
      normalizeDateTime(input.escalatedAt || new Date()),
    ]
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
  const key = String(apiKeyRaw || '').trim();
  if (!key) return null;

  const db = getPool();
  const keyHash = createHash('sha256').update(key, 'utf8').digest('hex');
  const [rows] = await db.execute(
    `SELECT id, name, scopes, revoked_at
     FROM api_keys
     WHERE key_hash = ?
       AND revoked_at IS NULL
     LIMIT 1`,
    [keyHash]
  );
  const row = rows.length > 0 ? rows[0] : null;
  if (!row) return null;

  await db.execute(
    `UPDATE api_keys SET last_used_at = CURRENT_TIMESTAMP WHERE id = ?`,
    [row.id]
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
    [
      input.phoneNumber,
      input.label ?? null,
      input.isActive === false ? 0 : 1,
    ]
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
      [opts.fromNumber, limit, offset]
    );
    return rows;
  }

  const [rows] = await db.execute(
    `SELECT id, from_number, message_text, message_type, is_allowed, replied, reply_text, group_id, received_at, response_time_ms
     FROM message_logs
     ORDER BY id DESC
     LIMIT ? OFFSET ?`,
    [limit, offset]
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
  const placeholders = list.map(() => '?').join(', ');
  const [rows] = await db.execute(
    `SELECT \`key\`, \`value\`
     FROM bot_settings
     WHERE \`key\` IN (${placeholders})`,
    list
  );
  return rows;
}

function normalizeDateOnly(input) {
  const d = new Date(input);
  if (Number.isNaN(d.getTime())) {
    throw new TypeError('Tanggal tidak valid');
  }
  const year = d.getUTCFullYear();
  const month = String(d.getUTCMonth() + 1).padStart(2, '0');
  const day = String(d.getUTCDate()).padStart(2, '0');
  return `${year}-${month}-${day}`;
}

function normalizeDateTime(input) {
  const d = new Date(input);
  if (Number.isNaN(d.getTime())) {
    throw new TypeError('Datetime tidak valid');
  }
  return d;
}
