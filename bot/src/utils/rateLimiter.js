/**
 * Rate limiter sliding-window per nomor telepon.
 *
 * Map<phoneNumber, number[]> — array berisi timestamp (ms) dari tiap reply
 * yang tercatat. Saat `canReply` / `recordReply` dipanggil, entry yang sudah
 * keluar jendela otomatis di-prune.
 *
 * Dipakai oleh pipeline `messageHandler` untuk cegah spam: kalau nomor tsb
 * sudah dapat auto-reply N kali dalam window X detik, skip balas.
 */

const DEFAULT_WINDOW_MS = 60 * 1000;
const DEFAULT_MAX_PER_WINDOW = 5;

export class RateLimiter {
  /**
   * @param {Object} [opts]
   * @param {number} [opts.windowMs]      - default window (ms).
   * @param {number} [opts.maxPerWindow]  - default max reply per window.
   * @param {number} [opts.cleanupEveryMs]- interval cleanup otomatis (0 = off).
   */
  constructor(opts = {}) {
    this.windowMs = opts.windowMs || DEFAULT_WINDOW_MS;
    this.maxPerWindow = opts.maxPerWindow || DEFAULT_MAX_PER_WINDOW;
    this.store = new Map();
    this._cleanupTimer = null;

    if (opts.cleanupEveryMs && opts.cleanupEveryMs > 0) {
      this._cleanupTimer = setInterval(
        () => this.cleanup(),
        opts.cleanupEveryMs,
      );
      // Jangan menahan proses exit saat di Node.
      if (typeof this._cleanupTimer.unref === "function")
        this._cleanupTimer.unref();
    }
  }

  /**
   * Cek apakah nomor boleh di-reply sekarang. Tidak melakukan mutasi store
   * selain prune entry expired.
   *
   * @param {string} phoneNumber
   * @param {number} [maxPerWindow] - override default
   * @param {number} [windowMs]     - override default
   * @returns {boolean}
   */
  canReply(
    phoneNumber,
    maxPerWindow = this.maxPerWindow,
    windowMs = this.windowMs,
  ) {
    if (!phoneNumber) return true; // no key, always allow
    const list = this._pruneFor(phoneNumber, windowMs);
    return list.length < maxPerWindow;
  }

  /**
   * Catat bahwa kita baru saja membalas nomor ini. Simpan timestamp sekarang.
   * @param {string} phoneNumber
   * @param {number} [windowMs]
   */
  recordReply(phoneNumber, windowMs = this.windowMs) {
    if (!phoneNumber) return;
    const list = this._pruneFor(phoneNumber, windowMs);
    list.push(Date.now());
    this.store.set(phoneNumber, list);
  }

  /**
   * Hitung berapa entry aktif untuk nomor (post-prune).
   * @param {string} phoneNumber
   * @returns {number}
   */
  countFor(phoneNumber) {
    return this._pruneFor(phoneNumber, this.windowMs).length;
  }

  /**
   * Reset entry untuk nomor tertentu (dipakai admin unblock).
   * @param {string} phoneNumber
   */
  reset(phoneNumber) {
    this.store.delete(phoneNumber);
  }

  /**
   * Bersihkan seluruh entry yang sudah di luar window.
   * @returns {number} jumlah entry yang dihapus total
   */
  cleanup() {
    const now = Date.now();
    let removed = 0;
    for (const [key, list] of this.store) {
      const fresh = list.filter((ts) => now - ts < this.windowMs);
      if (fresh.length === 0) {
        this.store.delete(key);
        removed += list.length;
      } else if (fresh.length !== list.length) {
        removed += list.length - fresh.length;
        this.store.set(key, fresh);
      }
    }
    return removed;
  }

  /**
   * Stop interval cleanup kalau di-setup di constructor.
   */
  stop() {
    if (this._cleanupTimer) {
      clearInterval(this._cleanupTimer);
      this._cleanupTimer = null;
    }
  }

  /**
   * Prune + return array timestamp aktif untuk key.
   * @param {string} phoneNumber
   * @param {number} windowMs
   * @returns {number[]}
   */
  _pruneFor(phoneNumber, windowMs) {
    const now = Date.now();
    const list = this.store.get(phoneNumber) || [];
    const fresh = list.filter((ts) => now - ts < windowMs);
    if (fresh.length !== list.length) this.store.set(phoneNumber, fresh);
    return fresh;
  }
}

export default RateLimiter;
