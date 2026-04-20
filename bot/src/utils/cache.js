/**
 * Generic in-memory TTL cache.
 *
 * Setiap entry disimpan bersama `expiresAt`. Saat `get()`/`has()` dipanggil,
 * entry yang sudah expired otomatis dihapus secara lazy (no background timer).
 *
 * Dipakai oleh: blacklist cache, type template cache, dan lookup ringan lain
 * yang butuh TTL cepat.
 */
export class TtlCache {
  /**
   * @param {number} ttlMs - default TTL dalam milidetik (boleh di-override per set()).
   */
  constructor(ttlMs = 5 * 60 * 1000) {
    if (!Number.isFinite(ttlMs) || ttlMs <= 0) {
      throw new TypeError("ttlMs harus bilangan positif berhingga");
    }
    this.defaultTtl = ttlMs;
    this.store = new Map();
  }

  /**
   * Simpan value dengan TTL opsional.
   * @param {string} key
   * @param {*} value
   * @param {number} [ttlMs]
   */
  set(key, value, ttlMs) {
    const ttl = Number.isFinite(ttlMs) && ttlMs > 0 ? ttlMs : this.defaultTtl;
    this.store.set(key, { value, expiresAt: Date.now() + ttl });
    return value;
  }

  /**
   * Ambil value. Return undefined kalau tidak ada atau sudah expired.
   * @param {string} key
   * @returns {*}
   */
  get(key) {
    const entry = this.store.get(key);
    if (!entry) return undefined;
    if (entry.expiresAt <= Date.now()) {
      this.store.delete(key);
      return undefined;
    }
    return entry.value;
  }

  /**
   * Cek keberadaan key (dan otomatis hapus kalau expired).
   * @param {string} key
   * @returns {boolean}
   */
  has(key) {
    const entry = this.store.get(key);
    if (!entry) return false;
    if (entry.expiresAt <= Date.now()) {
      this.store.delete(key);
      return false;
    }
    return true;
  }

  /**
   * Hapus satu key.
   * @param {string} key
   */
  delete(key) {
    return this.store.delete(key);
  }

  /** Hapus semua entry. */
  clear() {
    this.store.clear();
  }

  /**
   * Bersihkan entry yang sudah kadaluarsa. Dipanggil manual atau via setInterval.
   * @returns {number} jumlah entry yang dihapus.
   */
  cleanup() {
    const now = Date.now();
    let removed = 0;
    for (const [key, entry] of this.store) {
      if (entry.expiresAt <= now) {
        this.store.delete(key);
        removed++;
      }
    }
    return removed;
  }

  /**
   * Jumlah entry yang masih hidup (tanpa cleanup implicit).
   */
  size() {
    return this.store.size;
  }
}

export default TtlCache;
