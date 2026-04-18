/**
 * FAQ matcher — match pesan user ke knowledge base.
 *
 * Dua tahap:
 *   1. Keyword match: kalau pesan mengandung salah satu keyword dari entry,
 *      langsung kembalikan entry tsb dengan confidence = 1.0.
 *   2. Fuzzy match: pakai normalized Levenshtein distance terhadap `question`
 *      dan juga setiap keyword. Ambil similarity tertinggi. Kalau ≥ threshold,
 *      kembalikan entry.
 *
 * Entry shape (hasil loadKnowledgeBase):
 *   {
 *     id: number,
 *     question: string,
 *     keywords: string[],
 *     answer: string,
 *     is_active: boolean,
 *   }
 */

import { TtlCache } from "./cache.js";

const CACHE_TTL_MS = 5 * 60 * 1000; // 5 menit
const cache = new TtlCache(CACHE_TTL_MS);
const CACHE_KEY = "knowledge_base_all";

/**
 * Load knowledge base dengan cache TTL. dbLookup harus mengembalikan array
 * entry mentah (lihat shape di atas).
 *
 * @param {() => Promise<Array<Object>>} dbLookup
 * @param {Object} [opts]
 * @param {boolean} [opts.force] - bypass cache
 * @returns {Promise<Array<Object>>}
 */
export async function loadKnowledgeBase(dbLookup, opts = {}) {
  if (typeof dbLookup !== "function") {
    throw new TypeError("dbLookup harus function");
  }

  if (!opts.force) {
    const cached = cache.get(CACHE_KEY);
    if (cached) return cached;
  }

  const raw = await dbLookup();
  const normalized = Array.isArray(raw)
    ? raw.map(normalizeEntry).filter(Boolean)
    : [];
  cache.set(CACHE_KEY, normalized);
  return normalized;
}

/**
 * Kosongkan cache knowledge base (dipakai saat entry di-update via dashboard).
 */
export function invalidateKnowledgeBaseCache() {
  cache.delete(CACHE_KEY);
}

/**
 * Cari entry yang paling cocok dengan `text`.
 *
 * @param {string} text
 * @param {Array<Object>} entries
 * @param {number} [threshold=0.75]
 * @returns {{ answer: string, confidence: number, matched_id: number }|null}
 */
export function matchFaq(text, entries, threshold = 0.75) {
  if (!text || !Array.isArray(entries) || entries.length === 0) return null;
  if (!(threshold > 0 && threshold <= 1)) {
    throw new RangeError("threshold harus dalam (0, 1]");
  }

  const normText = normalizeText(text);
  if (!normText) return null;

  // Tahap 1: keyword exact (substring, word-boundary).
  for (const e of entries) {
    if (!e || !e.is_active) continue;
    for (const kw of e.keywords || []) {
      const nKw = normalizeText(kw);
      if (!nKw) continue;
      if (containsWord(normText, nKw)) {
        return { answer: e.answer, confidence: 1.0, matched_id: e.id };
      }
    }
  }

  // Tahap 2: fuzzy levenshtein terhadap question dan keywords.
  let best = null;
  for (const e of entries) {
    if (!e || !e.is_active) continue;

    const candidates = [e.question, ...(e.keywords || [])]
      .map(normalizeText)
      .filter(Boolean);

    for (const cand of candidates) {
      const sim = similarity(normText, cand);
      if (!best || sim > best.confidence) {
        best = { answer: e.answer, confidence: sim, matched_id: e.id };
      }
    }
  }

  if (best && best.confidence >= threshold) {
    // Pastikan angka confidence tidak > 1 karena pembulatan float.
    return { ...best, confidence: Math.min(1, round4(best.confidence)) };
  }
  return null;
}

/**
 * Hitung Levenshtein distance dua string (tabulasi DP 2-row).
 * @param {string} a
 * @param {string} b
 * @returns {number}
 */
export function levenshtein(a, b) {
  if (a === b) return 0;
  if (!a) return b.length;
  if (!b) return a.length;

  const la = a.length;
  const lb = b.length;

  let prev = new Array(lb + 1);
  let curr = new Array(lb + 1);

  for (let j = 0; j <= lb; j++) prev[j] = j;

  for (let i = 1; i <= la; i++) {
    curr[0] = i;
    const ca = a.charCodeAt(i - 1);
    for (let j = 1; j <= lb; j++) {
      const cost = ca === b.charCodeAt(j - 1) ? 0 : 1;
      curr[j] = Math.min(
        curr[j - 1] + 1, // insert
        prev[j] + 1, // delete
        prev[j - 1] + cost, // substitute
      );
    }
    [prev, curr] = [curr, prev];
  }
  return prev[lb];
}

/**
 * Similarity = 1 - distance / max(len1, len2). Range [0, 1].
 */
export function similarity(a, b) {
  if (!a && !b) return 1;
  const maxLen = Math.max(a.length, b.length);
  if (maxLen === 0) return 1;
  return 1 - levenshtein(a, b) / maxLen;
}

function normalizeEntry(row) {
  if (!row) return null;
  let keywords = row.keywords;
  if (typeof keywords === "string") {
    try {
      keywords = JSON.parse(keywords);
    } catch {
      keywords = keywords
        .split(",")
        .map((s) => s.trim())
        .filter(Boolean);
    }
  }
  if (!Array.isArray(keywords)) keywords = [];

  return {
    id: row.id,
    question: String(row.question || ""),
    keywords,
    answer: String(row.answer || ""),
    is_active:
      row.is_active === undefined ? true : Boolean(Number(row.is_active)),
  };
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

function round4(n) {
  return Math.round(n * 10000) / 10000;
}

export default {
  loadKnowledgeBase,
  invalidateKnowledgeBaseCache,
  matchFaq,
  levenshtein,
  similarity,
};
