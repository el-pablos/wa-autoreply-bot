/**
 * Business hours & out-of-office helpers.
 *
 * Schedule shape:
 *   {
 *     1: { start: '09:00', end: '17:00' },   // Senin
 *     2: { start: '09:00', end: '17:00' },   // Selasa
 *     ...
 *     7: null,                               // Minggu libur
 *   }
 *
 * OoF list:
 *   [
 *     { start_date: '2026-04-20', end_date: '2026-04-25', message: '...', is_active: 1 },
 *     ...
 *   ]
 *
 * Implementasi pakai `Intl.DateTimeFormat` untuk dapetin jam lokal sesuai
 * `timezone`, tanpa library third-party.
 */

const HHMM_RE = /^([01]\d|2[0-3]):([0-5]\d)$/;

/**
 * Cek apakah saat ini (atau `now` yang diberikan) berada dalam business hours
 * berdasarkan schedule + timezone.
 *
 * @param {string} timezone - mis. 'Asia/Jakarta'
 * @param {Object} schedule - lihat shape di atas
 * @param {Date} [now]      - default: new Date()
 * @returns {boolean}
 */
export function isWithinBusinessHours(timezone, schedule, now = new Date()) {
  if (!timezone || typeof timezone !== "string") {
    throw new TypeError("timezone harus string (mis. Asia/Jakarta)");
  }
  if (!schedule || typeof schedule !== "object") {
    throw new TypeError("schedule harus object dengan key 1..7");
  }
  if (!(now instanceof Date) || Number.isNaN(now.getTime())) {
    throw new TypeError("now harus Date valid");
  }

  const { weekdayIso, hhmm } = getLocalParts(timezone, now);
  const daySchedule = schedule[weekdayIso];
  if (!daySchedule) return false; // libur / null

  const { start, end } = daySchedule;
  if (!HHMM_RE.test(start) || !HHMM_RE.test(end)) {
    throw new Error(
      `Schedule hari ${weekdayIso} invalid: start='${start}' end='${end}' (format HH:MM)`,
    );
  }

  // Cross-midnight schedule (mis. 22:00 – 06:00) tidak dipakai dalam model
  // business hours kita — diperlakukan sebagai invalid interval.
  if (toMinutes(end) <= toMinutes(start)) {
    return false;
  }

  const cur = toMinutes(hhmm);
  return cur >= toMinutes(start) && cur < toMinutes(end);
}

/**
 * Cari OoF yang aktif di `now`. Return object OoF pertama yang cocok, atau null.
 * @param {Array<Object>} oofList
 * @param {Date} [now]
 * @returns {Object|null}
 */
export function getActiveOof(oofList, now = new Date()) {
  if (!Array.isArray(oofList)) return null;
  if (!(now instanceof Date) || Number.isNaN(now.getTime())) {
    throw new TypeError("now harus Date valid");
  }

  const nowMs = now.getTime();
  for (const oof of oofList) {
    if (!oof) continue;
    if (Number(oof.is_active) !== 1 && oof.is_active !== true) continue;

    const start = parseBoundary(oof.start_date, "start");
    const end = parseBoundary(oof.end_date, "end");
    if (!start || !end) continue;
    if (nowMs >= start.getTime() && nowMs <= end.getTime()) {
      return oof;
    }
  }
  return null;
}

/**
 * Dapatkan hari (1..7, ISO: Senin=1) dan jam HH:MM di timezone lokal.
 * @param {string} timezone
 * @param {Date} now
 * @returns {{ weekdayIso: number, hhmm: string }}
 */
function getLocalParts(timezone, now) {
  const fmt = new Intl.DateTimeFormat("en-GB", {
    timeZone: timezone,
    weekday: "short",
    hour: "2-digit",
    minute: "2-digit",
    hour12: false,
  });

  const parts = fmt.formatToParts(now);
  const wkd = parts.find((p) => p.type === "weekday")?.value;
  const hh = parts.find((p) => p.type === "hour")?.value || "00";
  const mm = parts.find((p) => p.type === "minute")?.value || "00";

  const WEEKDAY_MAP = {
    Mon: 1,
    Tue: 2,
    Wed: 3,
    Thu: 4,
    Fri: 5,
    Sat: 6,
    Sun: 7,
  };
  const weekdayIso = WEEKDAY_MAP[wkd];
  if (!weekdayIso) {
    throw new Error(`Gagal parse weekday '${wkd}' dari timezone '${timezone}'`);
  }

  // Intl kadang kasih '24:00' untuk midnight exactly; normalisasi ke '00:00'.
  const hourNorm = hh === "24" ? "00" : hh.padStart(2, "0");
  return { weekdayIso, hhmm: `${hourNorm}:${mm.padStart(2, "0")}` };
}

/**
 * Parse boundary (string 'YYYY-MM-DD' atau Date atau ISO string). `start`
 * diambil jam 00:00:00 UTC, `end` diambil jam 23:59:59.999 UTC supaya range
 * inklusif menutup seluruh hari.
 *
 * @param {string|Date} raw
 * @param {'start'|'end'} kind
 * @returns {Date|null}
 */
function parseBoundary(raw, kind) {
  if (!raw) return null;
  if (raw instanceof Date) {
    return Number.isNaN(raw.getTime()) ? null : raw;
  }
  const s = String(raw);

  // 'YYYY-MM-DD'
  if (/^\d{4}-\d{2}-\d{2}$/.test(s)) {
    const suffix = kind === "start" ? "T00:00:00.000Z" : "T23:59:59.999Z";
    const d = new Date(s + suffix);
    return Number.isNaN(d.getTime()) ? null : d;
  }

  // ISO datetime
  const d = new Date(s);
  return Number.isNaN(d.getTime()) ? null : d;
}

function toMinutes(hhmm) {
  const [h, m] = hhmm.split(":").map((n) => parseInt(n, 10));
  return h * 60 + m;
}

export default { isWithinBusinessHours, getActiveOof };
