import express from 'express';
import { subscribeSSE } from '../utils/eventBus.js';

/**
 * Internal API router.
 *
 * Endpoint:
 * - POST /internal/send
 * - POST /internal/send-message (alias)
 * - GET  /internal/settings
 * - GET  /internal/sse-feed
 */
export function createInternalApiRouter(deps = {}) {
  const router = express.Router();
  const getSock = typeof deps.getSock === 'function' ? deps.getSock : () => null;
  const logger = deps.logger || console;
  const sharedSecret = String(
    deps.sharedSecret || process.env.INTERNAL_SECRET || '',
  ).trim();

  const db = deps.db || {};
  const getSetting = db.getSetting;
  const getSettingsByKeys = db.getSettingsByKeys;
  const subscribe =
    typeof deps.subscribeSSE === 'function' ? deps.subscribeSSE : subscribeSSE;

  router.use((req, res, next) => {
    if (!sharedSecret) {
      return res.status(503).json({
        ok: false,
        message: 'INTERNAL_SECRET belum dikonfigurasi',
      });
    }

    const provided = String(req.headers['x-internal-secret'] || '').trim();
    if (!provided || provided !== sharedSecret) {
      return res.status(401).json({ ok: false, message: 'Unauthorized' });
    }
    return next();
  });

  const handleSend = async (req, res) => {
    const to = String(req.body?.to || '').trim();
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
      await sock.sendMessage(to, { text });
      return res.json({ ok: true, to });
    } catch (err) {
      logger.error?.({ err, to }, 'Gagal kirim pesan internal');
      return res.status(500).json({
        ok: false,
        message: 'Gagal kirim pesan',
      });
    }
  };

  router.post('/send', handleSend);
  router.post('/send-message', handleSend);

  router.get('/settings', async (req, res) => {
    const keysRaw = String(req.query.keys || '').trim();
    const keys = keysRaw
      ? keysRaw
          .split(',')
          .map((item) => item.trim())
          .filter(Boolean)
      : [
          'auto_reply_enabled',
          'ignore_groups',
          'reply_message',
          'reply_delay_ms',
          'business_hours_enabled',
          'ai_reply_enabled',
          'webhook_enabled',
          'rate_limit_enabled',
        ];

    try {
      if (typeof getSettingsByKeys === 'function') {
        const rows = await getSettingsByKeys(keys);
        const values = Object.fromEntries(
          rows.map((row) => [row.key, row.value]),
        );
        return res.json({ ok: true, values });
      }

      if (typeof getSetting !== 'function') {
        return res.status(500).json({
          ok: false,
          message: 'DB helper untuk settings tidak tersedia',
        });
      }

      const values = {};
      await Promise.all(
        keys.map(async (key) => {
          values[key] = await getSetting(key);
        }),
      );

      return res.json({ ok: true, values });
    } catch (err) {
      logger.error?.({ err }, 'Gagal ambil internal settings');
      return res.status(500).json({ ok: false, message: 'Internal error' });
    }
  });

  router.get('/sse-feed', (req, res) => {
    res.setHeader('Content-Type', 'text/event-stream');
    res.setHeader('Cache-Control', 'no-cache');
    res.setHeader('Connection', 'keep-alive');
    res.flushHeaders?.();

    const sendEvent = (eventName, payload) => {
      res.write(`event: ${eventName}\n`);
      res.write(`data: ${JSON.stringify(payload)}\n\n`);
    };

    sendEvent('connected', {
      type: 'connected',
      timestamp: new Date().toISOString(),
      source: 'bot-sse-feed',
    });

    const unsubscribe = subscribe((eventName, payload) => {
      sendEvent(eventName, payload);
    });

    // Keep-alive untuk mencegah idle timeout di proxy.
    const heartbeat = setInterval(() => {
      sendEvent('heartbeat', { timestamp: new Date().toISOString() });
    }, 25000);

    req.on('close', () => {
      clearInterval(heartbeat);
      unsubscribe();
      res.end();
    });
  });

  return router;
}

export default createInternalApiRouter;
