import express from 'express';

/**
 * Public API router (X-API-Key auth).
 *
 * Endpoint:
 * - POST /api/send
 * - POST /api/allowlist
 * - GET  /api/logs
 */
export function createPublicApiRouter(deps = {}) {
  const router = express.Router();
  const getSock = typeof deps.getSock === 'function' ? deps.getSock : () => null;
  const logger = deps.logger || console;

  const db = deps.db || {};
  const verifyApiKey = db.verifyApiKey;
  const upsertAllowListEntry = db.upsertAllowListEntry;
  const getMessageLogs = db.getMessageLogs;

  router.use(async (req, res, next) => {
    if (typeof verifyApiKey !== 'function') {
      return res.status(500).json({
        ok: false,
        message: 'verifyApiKey helper belum tersedia',
      });
    }

    const apiKey = String(req.headers['x-api-key'] || '').trim();
    if (!apiKey) {
      return res.status(401).json({ ok: false, message: 'Missing API key' });
    }

    try {
      const keyRow = await verifyApiKey(apiKey);
      if (!keyRow) {
        return res.status(401).json({ ok: false, message: 'Invalid API key' });
      }

      req.apiClient = keyRow;
      return next();
    } catch (err) {
      logger.error?.({ err }, 'Gagal verifikasi API key');
      return res.status(500).json({ ok: false, message: 'Internal error' });
    }
  });

  router.post('/send', async (req, res) => {
    const to = normalizePhoneTarget(req.body?.to);
    const text = String(req.body?.text || '').trim();

    if (!to || !text) {
      return res.status(400).json({
        ok: false,
        message: 'Payload wajib: to, text',
      });
    }

    const sock = getSock();
    if (!sock || typeof sock.sendMessage !== 'function') {
      return res.status(503).json({
        ok: false,
        message: 'WhatsApp socket belum siap',
      });
    }

    try {
      const toJid = to.includes('@') ? to : `${to}@s.whatsapp.net`;
      await sock.sendMessage(toJid, { text });
      return res.json({ ok: true, to: toJid });
    } catch (err) {
      logger.error?.({ err, to }, 'Gagal kirim pesan via API publik');
      return res.status(500).json({ ok: false, message: 'Gagal kirim pesan' });
    }
  });

  router.post('/allowlist', async (req, res) => {
    if (typeof upsertAllowListEntry !== 'function') {
      return res.status(500).json({
        ok: false,
        message: 'upsertAllowListEntry helper belum tersedia',
      });
    }

    const phoneNumber = normalizePhoneTarget(req.body?.phone_number || req.body?.phoneNumber);
    const label = req.body?.label === undefined ? null : String(req.body.label || '');
    const isActive = req.body?.is_active === undefined ? true : Boolean(req.body.is_active);

    if (!phoneNumber) {
      return res.status(400).json({
        ok: false,
        message: 'phone_number tidak valid',
      });
    }

    try {
      await upsertAllowListEntry({ phoneNumber, label, isActive });
      return res.json({ ok: true, phoneNumber, isActive });
    } catch (err) {
      logger.error?.({ err, phoneNumber }, 'Gagal update allowlist via API');
      return res.status(500).json({ ok: false, message: 'Internal error' });
    }
  });

  router.get('/logs', async (req, res) => {
    if (typeof getMessageLogs !== 'function') {
      return res.status(500).json({
        ok: false,
        message: 'getMessageLogs helper belum tersedia',
      });
    }

    const limit = clampInt(req.query.limit, 50, 1, 500);
    const offset = clampInt(req.query.offset, 0, 0, 50000);
    const fromNumber = req.query.from_number
      ? normalizePhoneTarget(req.query.from_number)
      : null;

    try {
      const rows = await getMessageLogs({ limit, offset, fromNumber });
      return res.json({
        ok: true,
        limit,
        offset,
        count: rows.length,
        data: rows,
      });
    } catch (err) {
      logger.error?.({ err }, 'Gagal ambil logs via API');
      return res.status(500).json({ ok: false, message: 'Internal error' });
    }
  });

  return router;
}

function normalizePhoneTarget(raw) {
  const base = String(raw || '').trim().replace(/^\+/, '');
  if (!base) return '';

  if (base.includes('@')) {
    const local = base.split('@')[0].split(':')[0];
    return /^\d{8,15}$/.test(local) ? base : '';
  }

  const digits = base.replace(/\D/g, '');
  if (digits.startsWith('62') && /^62\d{8,13}$/.test(digits)) return digits;
  if (digits.startsWith('0') && /^0\d{8,13}$/.test(digits)) return `62${digits.slice(1)}`;
  if (digits.startsWith('8') && /^8\d{8,12}$/.test(digits)) return `62${digits}`;
  return '';
}

function clampInt(value, fallback, min, max) {
  const n = Number(value);
  if (!Number.isFinite(n)) return fallback;
  return Math.max(min, Math.min(max, Math.floor(n)));
}

export default createPublicApiRouter;
