/**
 * Blacklist helper dengan cache TTL.
 *
 * Cache: `Map<phone, unblockAt|null|false>`
 *   - `null`   → blacklisted permanen (unblock_at NULL di DB)
 *   - Date     → blacklisted sampai timestamp tertentu (blok kadaluarsa)
 *   - `false`  → tidak di-blacklist (negatif cache, agar lookup ulang hemat)
 *
 * Fungsi helper di-design untuk dipakai dari pipeline messageHandler:
 *
 *   const blocked = await isBlacklisted(phone, cache, () => db.checkBlacklist(phone));
 */

const DEFAULT_TTL_MS = 5 * 60 * 1000; // 5 menit

/**
 * Cek apakah nomor sedang di-blacklist.
 *
 * @param {string} phoneNumber
 * @param {Map<string, any>} cache
 * @param {() => Promise<{ phone_number: string, unblock_at: Date|null, reason?: string }|null>} dbLookup
 * @param {Object} [opts]
 * @param {number} [opts.ttlMs]   - TTL cache (default 5 menit)
 * @param {Date}   [opts.now]     - dipakai di test
 * @returns {Promise<boolean>}
 */
export async function isBlacklisted(phoneNumber, cache, dbLookup, opts = {}) {
  if (!phoneNumber) return false;
  if (!(cache instanceof Map)) {
    throw new TypeError("cache harus instance Map");
  }
  if (typeof dbLookup !== "function") {
    throw new TypeError("dbLookup harus function");
  }

  const now = opts.now instanceof Date ? opts.now : new Date();
  const ttlMs = opts.ttlMs || DEFAULT_TTL_MS;

  const cached = cache.get(phoneNumber);
  if (cached !== undefined) {
    const verdict = evaluateCacheEntry(cached, now);
    if (verdict !== undefined) return verdict;
    // cache stale → drop & re-fetch
    cache.delete(phoneNumber);
  }

  const row = await dbLookup(phoneNumber);
  if (!row) {
    cache.set(phoneNumber, { value: false, expiresAt: now.getTime() + ttlMs });
    return false;
  }

  const unblockAt = normalizeUnblockAt(row.unblock_at);
  if (unblockAt && unblockAt.getTime() <= now.getTime()) {
    // blok sudah kadaluarsa → anggap tidak blacklisted
    cache.set(phoneNumber, { value: false, expiresAt: now.getTime() + ttlMs });
    return false;
  }

  cache.set(phoneNumber, {
    value: unblockAt ? unblockAt : null,
    expiresAt: now.getTime() + ttlMs,
  });
  return true;
}

/**
 * Tambah nomor ke blacklist. Update cache + panggil dbInsert yang user
 * kirimkan.
 *
 * @param {string} phoneNumber
 * @param {string} reason
 * @param {Date|null} unblockAt
 * @param {(input: {phone_number, reason, unblock_at}) => Promise<any>} dbInsert
 * @param {Map<string, any>} [cache]
 */
export async function addToBlacklist(
  phoneNumber,
  reason,
  unblockAt,
  dbInsert,
  cache,
) {
  if (!phoneNumber) throw new TypeError("phoneNumber wajib");
  if (typeof dbInsert !== "function")
    throw new TypeError("dbInsert harus function");

  const ub =
    unblockAt instanceof Date
      ? unblockAt
      : unblockAt
        ? new Date(unblockAt)
        : null;
  await dbInsert({
    phone_number: phoneNumber,
    reason: reason || null,
    unblock_at: ub,
  });

  if (cache instanceof Map) {
    cache.set(phoneNumber, {
      value: ub,
      expiresAt: Date.now() + DEFAULT_TTL_MS,
    });
  }
}

/**
 * Evaluate cache entry. Return boolean verdict atau undefined kalau stale.
 * @param {{value:any, expiresAt:number}} entry
 * @param {Date} now
 * @returns {boolean|undefined}
 */
function evaluateCacheEntry(entry, now) {
  if (!entry || typeof entry !== "object") return undefined;
  if (entry.expiresAt && entry.expiresAt <= now.getTime()) return undefined;

  const v = entry.value;
  if (v === false) return false; // negatif cache
  if (v === null) return true; // blacklist permanen
  if (v instanceof Date) return v.getTime() > now.getTime();
  return undefined;
}

function normalizeUnblockAt(raw) {
  if (!raw) return null;
  if (raw instanceof Date) return Number.isNaN(raw.getTime()) ? null : raw;
  const d = new Date(raw);
  return Number.isNaN(d.getTime()) ? null : d;
}

export default { isBlacklisted, addToBlacklist };
