/**
 * Human-like typing simulation.
 *
 * Hitung durasi "typing" plausible untuk panjang pesan, lalu kirim presence
 * update ke WA (composing → paused) dengan delay sesuai hitungan.
 *
 * Fungsi `calculateTypingMs` sengaja dibikin pure supaya mudah di-unit-test
 * dengan seed acak via parameter `jitterFn`.
 */

const DEFAULT_MS_PER_CHAR = 30;
const DEFAULT_MIN_MS = 400;
const DEFAULT_MAX_MS = 8000;
const DEFAULT_JITTER = 0.3;

/**
 * Hitung durasi typing dalam milidetik.
 *
 * @param {number} messageLength        - jumlah karakter pesan yang akan dikirim
 * @param {Object} [opts]
 * @param {number} [opts.msPerChar]     - default 30ms/char
 * @param {number} [opts.min]           - default 400ms
 * @param {number} [opts.max]           - default 8000ms
 * @param {number} [opts.jitter]        - default 0.3 (±30%)
 * @param {() => number} [opts.jitterFn]- pengganti Math.random (test-friendly)
 * @returns {number}
 */
export function calculateTypingMs(messageLength, opts = {}) {
  const len = Math.max(0, Number(messageLength) || 0);
  const msPerChar = opts.msPerChar ?? DEFAULT_MS_PER_CHAR;
  const min = opts.min ?? DEFAULT_MIN_MS;
  const max = opts.max ?? DEFAULT_MAX_MS;
  const jitter = opts.jitter ?? DEFAULT_JITTER;
  const rng = typeof opts.jitterFn === "function" ? opts.jitterFn : Math.random;

  // Base: proporsional dengan panjang pesan.
  let ms = len * msPerChar;

  // Jitter ±jitter * base: random() ∈ [0,1) → map ke [-jitter, +jitter].
  if (jitter > 0 && ms > 0) {
    const factor = 1 + (rng() * 2 - 1) * jitter;
    ms = ms * factor;
  }

  // Clamp.
  if (ms < min) ms = min;
  if (ms > max) ms = max;

  return Math.round(ms);
}

/**
 * Lakukan presence 'composing' → wait → 'paused'. Aman dipanggil meski sock
 * tidak punya method `sendPresenceUpdate` (dilewati).
 *
 * @param {object} sock                 - Baileys socket
 * @param {string} remoteJid            - target JID
 * @param {number} typingMs             - durasi dari calculateTypingMs()
 * @param {Object} [opts]
 * @param {(ms:number)=>Promise<void>} [opts.sleepFn] - default setTimeout wrapper
 * @returns {Promise<void>}
 */
export async function simulateTyping(sock, remoteJid, typingMs, opts = {}) {
  if (!sock || typeof sock.sendPresenceUpdate !== "function") return;
  if (!remoteJid) return;

  const sleep = opts.sleepFn || defaultSleep;
  const safeMs = Math.max(0, Number(typingMs) || 0);

  try {
    await sock.sendPresenceUpdate("composing", remoteJid);
    await sleep(safeMs);
    await sock.sendPresenceUpdate("paused", remoteJid);
  } catch (_err) {
    // presence update non-fatal; biarkan handler utama lanjut kirim pesan.
  }
}

function defaultSleep(ms) {
  return new Promise((resolve) => setTimeout(resolve, ms));
}

export default { calculateTypingMs, simulateTyping };
