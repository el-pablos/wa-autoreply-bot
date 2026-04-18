/**
 * Resolver template per message type.
 *
 * messageType valid: 'text' | 'image' | 'video' | 'audio' | 'document'
 *                  | 'sticker' | 'location' | 'contact' | 'reaction' | 'other'
 *
 * Cache: `Map<messageType, body>` di-inject dari luar (loader db membaca
 * tabel `message_type_templates`).
 */

const KNOWN_TYPES = new Set([
  "text",
  "image",
  "video",
  "audio",
  "document",
  "sticker",
  "location",
  "contact",
  "reaction",
  "other",
]);

/**
 * Ambil template body untuk messageType. Fallback ke 'text' jika tipe tidak
 * dikenal, lalu 'other' jika 'text' tidak ada. Return null kalau benar-benar
 * tidak ditemukan.
 *
 * @param {string} messageType
 * @param {Map<string, string>|Object} templatesCache
 * @returns {string|null}
 */
export function resolveTypeTemplate(messageType, templatesCache) {
  if (!templatesCache) return null;

  const type = (messageType || "").toLowerCase().trim();
  const get = (key) => {
    if (templatesCache instanceof Map) return templatesCache.get(key);
    if (typeof templatesCache === "object") return templatesCache[key];
    return undefined;
  };

  const direct = get(type);
  if (typeof direct === "string" && direct.length > 0) return direct;

  if (!KNOWN_TYPES.has(type)) {
    const textFallback = get("text");
    if (typeof textFallback === "string" && textFallback.length > 0)
      return textFallback;
  }

  const other = get("other");
  if (typeof other === "string" && other.length > 0) return other;

  return null;
}

/**
 * Utility: convert object/array jadi Map<type, body>.
 *
 * Input dapat berupa:
 *   - Array<{ message_type, body, is_active? }>
 *   - Object<{ [type]: body }>
 *   - Map<type, body>
 *
 * @param {any} raw
 * @returns {Map<string, string>}
 */
export function buildTypeTemplatesCache(raw) {
  const cache = new Map();
  if (!raw) return cache;

  if (raw instanceof Map) {
    for (const [k, v] of raw) {
      if (typeof v === "string" && v.length > 0)
        cache.set(String(k).toLowerCase(), v);
    }
    return cache;
  }

  if (Array.isArray(raw)) {
    for (const row of raw) {
      if (!row) continue;
      const isActive =
        row.is_active === undefined ? true : Boolean(Number(row.is_active));
      if (!isActive) continue;
      const k = String(row.message_type || "")
        .toLowerCase()
        .trim();
      const v = row.body;
      if (k && typeof v === "string" && v.length > 0) cache.set(k, v);
    }
    return cache;
  }

  if (typeof raw === "object") {
    for (const [k, v] of Object.entries(raw)) {
      if (typeof v === "string" && v.length > 0)
        cache.set(String(k).toLowerCase(), v);
    }
  }
  return cache;
}

export default { resolveTypeTemplate, buildTypeTemplatesCache };
