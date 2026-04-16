import { logger } from './logger.js';
import { expireStaleSessions, getSetting } from '../db.js';

let schedulerInterval = null;

/**
 * Jalankan satu kali job expire sessions.
 * Mengambil interval dari DB setting, fallback 5 menit.
 *
 * @returns {Promise<number>} jumlah session yang di-expire
 */
export async function runExpireJob() {
  try {
    const expired = await expireStaleSessions();
    if (expired > 0) {
      logger.info(
        { expired },
        `Scheduler: ${expired} approved session expired — auto-reply aktif kembali`
      );
    } else {
      logger.debug('Scheduler: tidak ada session yang expired');
    }
    return expired;
  } catch (err) {
    logger.error({ err }, 'Scheduler: error saat menjalankan expire job');
    return 0;
  }
}

/**
 * Mulai scheduler — jalankan expire job secara periodik.
 * Interval diambil dari bot_settings.approve_expire_check_interval_minutes (default: 5).
 *
 * @returns {Promise<void>}
 */
export async function startScheduler() {
  if (schedulerInterval) {
    logger.warn('Scheduler sudah berjalan, skip start ulang');
    return;
  }

  // Ambil interval dari DB
  const intervalMinRaw = await getSetting('approve_expire_check_interval_minutes');
  const intervalMin    = parseInt(intervalMinRaw || '5', 10);
  const intervalMs     = intervalMin * 60 * 1000;

  logger.info(
    { intervalMin },
    `Scheduler dimulai — expire check setiap ${intervalMin} menit`
  );

  // Jalankan langsung sekali saat startup
  await runExpireJob();

  // Lalu jadwalkan secara periodik
  schedulerInterval = setInterval(async () => {
    await runExpireJob();
  }, intervalMs);
}

/**
 * Hentikan scheduler (dipakai untuk graceful shutdown).
 */
export function stopScheduler() {
  if (schedulerInterval) {
    clearInterval(schedulerInterval);
    schedulerInterval = null;
    logger.info('Scheduler dihentikan');
  }
}
