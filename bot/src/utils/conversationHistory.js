/**
 * Conversation history helper untuk AI multi-turn.
 *
 * Modul ini sengaja stateless dan callback-driven agar mudah diuji.
 * I/O database di-inject via function parameter.
 */

const DEFAULT_MAX_TURNS = 10;
const DEFAULT_RETENTION_HOURS = 24;

/**
 * Normalisasi role ke role yang diterima AI SDK.
 * @param {string} role
 * @returns {'system'|'user'|'assistant'}
 */
export function normalizeRole(role) {
  const raw = String(role || "").toLowerCase().trim();
  if (raw === "bot") return "assistant";
  if (raw === "assistant" || raw === "system" || raw === "user") {
    return raw;
  }
  throw new TypeError(`role tidak valid: '${role}'`);
}

/**
 * Simpan satu turn percakapan ke DB.
 *
 * @param {(row: { phone_number:string, role:string, content:string, tokens:number|null, created_at:Date }) => Promise<any>} dbInsert
 * @param {Object} turn
 * @param {string} turn.phoneNumber
 * @param {string} turn.role
 * @param {string} turn.content
 * @param {number|null} [turn.tokens]
 * @param {Date} [turn.createdAt]
 * @returns {Promise<any>}
 */
export async function saveConversationTurn(dbInsert, turn) {
  if (typeof dbInsert !== "function") {
    throw new TypeError("dbInsert harus function");
  }
  const phoneNumber = String(turn?.phoneNumber || "").trim();
  const content = String(turn?.content || "").trim();

  if (!phoneNumber) throw new TypeError("phoneNumber wajib diisi");
  if (!content) throw new TypeError("content wajib diisi");

  const role = normalizeRole(turn?.role);
  const createdAt =
    turn?.createdAt instanceof Date && !Number.isNaN(turn.createdAt.getTime())
      ? turn.createdAt
      : new Date();
  const tokens =
    turn?.tokens === undefined || turn?.tokens === null
      ? null
      : Math.max(0, Number(turn.tokens) || 0);

  return dbInsert({
    phone_number: phoneNumber,
    role,
    content,
    tokens,
    created_at: createdAt,
  });
}

/**
 * Load histori percakapan terbaru dengan retention window.
 *
 * @param {(phoneNumber: string, opts: { limit:number, since:Date }) => Promise<Array<{ role:string, content:string, created_at?:Date|string }>>} dbLookup
 * @param {string} phoneNumber
 * @param {Object} [opts]
 * @param {number} [opts.maxTurns=10]
 * @param {number} [opts.retentionHours=24]
 * @param {Date} [opts.now]
 * @returns {Promise<Array<{ role:'system'|'user'|'assistant', content:string }>>}
 */
export async function loadConversationHistory(dbLookup, phoneNumber, opts = {}) {
  if (typeof dbLookup !== "function") {
    throw new TypeError("dbLookup harus function");
  }

  const phone = String(phoneNumber || "").trim();
  if (!phone) return [];

  const maxTurns = toPositiveInt(opts.maxTurns, DEFAULT_MAX_TURNS);
  const retentionHours = toPositiveInt(
    opts.retentionHours,
    DEFAULT_RETENTION_HOURS,
  );
  const now =
    opts.now instanceof Date && !Number.isNaN(opts.now.getTime())
      ? opts.now
      : new Date();

  const since = new Date(now.getTime() - retentionHours * 60 * 60 * 1000);
  const rawRows = await dbLookup(phone, {
    // Ambil sedikit lebih banyak agar aman saat normalisasi/filter.
    limit: maxTurns * 2,
    since,
  });

  const rows = Array.isArray(rawRows) ? rawRows : [];
  const normalized = rows
    .map((row) => normalizeHistoryRow(row))
    .filter(Boolean)
    .sort((a, b) => a.createdAt - b.createdAt)
    .slice(-maxTurns)
    .map((row) => ({ role: row.role, content: row.content }));

  return normalized;
}

/**
 * Prune histori yang lebih tua dari retentionHours.
 *
 * @param {(olderThan: Date) => Promise<number>} dbPrune
 * @param {number} [retentionHours=24]
 * @param {Date} [now]
 * @returns {Promise<number>}
 */
export async function pruneConversationHistory(
  dbPrune,
  retentionHours = DEFAULT_RETENTION_HOURS,
  now = new Date(),
) {
  if (typeof dbPrune !== "function") {
    throw new TypeError("dbPrune harus function");
  }
  const hours = toPositiveInt(retentionHours, DEFAULT_RETENTION_HOURS);
  const baseNow =
    now instanceof Date && !Number.isNaN(now.getTime()) ? now : new Date();
  const olderThan = new Date(baseNow.getTime() - hours * 60 * 60 * 1000);
  const pruned = await dbPrune(olderThan);
  return Math.max(0, Number(pruned) || 0);
}

function normalizeHistoryRow(row) {
  if (!row) return null;

  const content = String(row.content || "").trim();
  if (!content) return null;

  let role;
  try {
    role = normalizeRole(row.role);
  } catch {
    return null;
  }

  const createdAt = normalizeDate(row.created_at);
  if (!createdAt) return null;

  return {
    role,
    content,
    createdAt,
  };
}

function normalizeDate(raw) {
  if (!raw) return new Date(0);
  if (raw instanceof Date) {
    return Number.isNaN(raw.getTime()) ? null : raw;
  }
  const d = new Date(raw);
  return Number.isNaN(d.getTime()) ? null : d;
}

function toPositiveInt(raw, fallback) {
  const n = Number(raw);
  if (!Number.isFinite(n) || n <= 0) return fallback;
  return Math.floor(n);
}

export default {
  normalizeRole,
  saveConversationTurn,
  loadConversationHistory,
  pruneConversationHistory,
};