/**
 * Smart escalation helper.
 *
 * Tanggung jawab modul:
 * - Deteksi keyword eskalasi pada pesan user.
 * - Terapkan cooldown per nomor agar tidak spam eskalasi.
 */

const DEFAULT_COOLDOWN_MS = 15 * 60 * 1000;
const cooldownStore = new Map();

/**
 * Cari keyword eskalasi yang cocok dari text.
 * Return keyword yang match pertama kali, atau null.
 *
 * @param {string} text
 * @param {string[]|string} keywords
 * @returns {string|null}
 */
export function matchEscalationKeyword(text, keywords) {
  const input = normalizeText(text);
  if (!input) return null;

  const list = normalizeKeywords(keywords);
  for (const keyword of list) {
    if (containsWord(input, keyword)) {
      return keyword;
    }
  }
  return null;
}

/**
 * Evaluasi apakah pesan perlu di-escalate.
 *
 * @param {Object} params
 * @param {string} params.phoneNumber
 * @param {string} params.messageText
 * @param {string[]|string} params.keywords
 * @param {number} [params.cooldownMs=15menit]
 * @param {number} [params.nowMs=Date.now()]
 * @param {Map<string, number>} [params.store]
 * @returns {{ triggered: boolean, keyword: string|null, reason: 'triggered'|'cooldown'|'no_keyword', nextAllowedAt: number|null }}
 */
export function evaluateEscalation(params = {}) {
  const phoneNumber = String(params.phoneNumber || "").trim();
  const nowMs = Number.isFinite(params.nowMs) ? params.nowMs : Date.now();
  const cooldownMs =
    Number.isFinite(params.cooldownMs) && params.cooldownMs > 0
      ? params.cooldownMs
      : DEFAULT_COOLDOWN_MS;
  const store = params.store instanceof Map ? params.store : cooldownStore;

  const keyword = matchEscalationKeyword(params.messageText, params.keywords);
  if (!keyword) {
    return {
      triggered: false,
      keyword: null,
      reason: "no_keyword",
      nextAllowedAt: null,
    };
  }

  const key = phoneNumber || "__unknown__";
  const lastAt = store.get(key);
  if (Number.isFinite(lastAt) && nowMs - lastAt < cooldownMs) {
    return {
      triggered: false,
      keyword,
      reason: "cooldown",
      nextAllowedAt: lastAt + cooldownMs,
    };
  }

  store.set(key, nowMs);
  return {
    triggered: true,
    keyword,
    reason: "triggered",
    nextAllowedAt: nowMs + cooldownMs,
  };
}

/**
 * Hapus data cooldown nomor tertentu.
 * @param {string} phoneNumber
 * @param {Map<string, number>} [store]
 */
export function clearEscalationCooldown(phoneNumber, store = cooldownStore) {
  if (!(store instanceof Map)) return false;
  const key = String(phoneNumber || "").trim() || "__unknown__";
  return store.delete(key);
}

/**
 * Reset semua state cooldown (dipakai untuk test).
 * @param {Map<string, number>} [store]
 */
export function clearAllEscalationCooldown(store = cooldownStore) {
  if (!(store instanceof Map)) return;
  store.clear();
}

function normalizeKeywords(raw) {
  if (Array.isArray(raw)) {
    return raw.map(normalizeText).filter(Boolean);
  }
  if (typeof raw === "string") {
    return raw
      .split(",")
      .map((item) => normalizeText(item))
      .filter(Boolean);
  }
  return [];
}

function normalizeText(s) {
  return String(s || "")
    .toLowerCase()
    .replace(/[\s\u00A0]+/g, " ")
    .replace(/[^\p{L}\p{N} ]+/gu, "")
    .trim();
}

function containsWord(haystack, needle) {
  if (!needle) return false;
  const pattern = new RegExp(
    `(^|\\s)${needle.replace(/[.*+?^${}()|[\]\\]/g, "\\$&")}(\\s|$)`,
  );
  return pattern.test(haystack);
}

export default {
  matchEscalationKeyword,
  evaluateEscalation,
  clearEscalationCooldown,
  clearAllEscalationCooldown,
};