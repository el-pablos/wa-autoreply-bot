import express from 'express';
import { beforeEach, describe, expect, jest, test } from '@jest/globals';
import { createInternalApiRouter } from '../../src/api/internal.js';

function makeApp(router) {
  const app = express();
  app.use(express.json());
  app.use('/internal', router);
  return app;
}

async function withServer(app, fn) {
  const server = await new Promise((resolve) => {
    const s = app.listen(0, () => resolve(s));
  });

  const { port } = server.address();
  const baseUrl = `http://127.0.0.1:${port}`;

  try {
    await fn(baseUrl);
  } finally {
    await new Promise((resolve, reject) => {
      server.close((err) => (err ? reject(err) : resolve()));
    });
  }
}

describe('internal api router', () => {
  const mockSendMessage = jest.fn();
  const mockGetSettingsByKeys = jest.fn();

  beforeEach(() => {
    jest.clearAllMocks();
  });

  test('reject unauthorized request', async () => {
    const router = createInternalApiRouter({
      sharedSecret: 'shh',
      getSock: () => ({ sendMessage: mockSendMessage }),
      db: { getSettingsByKeys: mockGetSettingsByKeys },
    });

    const app = makeApp(router);
    await withServer(app, async (baseUrl) => {
      const resp = await fetch(`${baseUrl}/internal/settings`);
      expect(resp.status).toBe(401);
    });
  });

  test('return 503 jika shared secret belum diset', async () => {
    const router = createInternalApiRouter({
      sharedSecret: '',
      getSock: () => ({ sendMessage: mockSendMessage }),
      db: { getSettingsByKeys: mockGetSettingsByKeys },
    });

    const app = makeApp(router);
    await withServer(app, async (baseUrl) => {
      const resp = await fetch(`${baseUrl}/internal/settings`, {
        headers: { 'x-internal-secret': 'shh' },
      });
      expect(resp.status).toBe(503);
    });
  });

  test('send message berhasil', async () => {
    mockSendMessage.mockResolvedValue(undefined);

    const router = createInternalApiRouter({
      sharedSecret: 'shh',
      getSock: () => ({ sendMessage: mockSendMessage }),
      db: { getSettingsByKeys: mockGetSettingsByKeys },
      logger: { error: jest.fn() },
    });

    const app = makeApp(router);
    await withServer(app, async (baseUrl) => {
      const resp = await fetch(`${baseUrl}/internal/send`, {
        method: 'POST',
        headers: {
          'content-type': 'application/json',
          'x-internal-secret': 'shh',
        },
        body: JSON.stringify({ to: '6281@s.whatsapp.net', text: 'halo' }),
      });

      expect(resp.status).toBe(200);
      expect(mockSendMessage).toHaveBeenCalledWith('6281@s.whatsapp.net', { text: 'halo' });
    });
  });

  test('send endpoint validasi payload + socket readiness', async () => {
    const noSockRouter = createInternalApiRouter({
      sharedSecret: 'shh',
      getSock: () => null,
      db: { getSettingsByKeys: mockGetSettingsByKeys },
      logger: { error: jest.fn() },
    });
    const app = makeApp(noSockRouter);

    await withServer(app, async (baseUrl) => {
      const badPayload = await fetch(`${baseUrl}/internal/send`, {
        method: 'POST',
        headers: {
          'content-type': 'application/json',
          'x-internal-secret': 'shh',
        },
        body: JSON.stringify({ to: '', text: '' }),
      });
      expect(badPayload.status).toBe(400);

      const noSock = await fetch(`${baseUrl}/internal/send`, {
        method: 'POST',
        headers: {
          'content-type': 'application/json',
          'x-internal-secret': 'shh',
        },
        body: JSON.stringify({ to: '6281@s.whatsapp.net', text: 'halo' }),
      });
      expect(noSock.status).toBe(503);
    });
  });

  test('settings return map value', async () => {
    mockGetSettingsByKeys.mockResolvedValue([
      { key: 'auto_reply_enabled', value: 'true' },
      { key: 'reply_delay_ms', value: '1500' },
    ]);

    const router = createInternalApiRouter({
      sharedSecret: 'shh',
      getSock: () => ({ sendMessage: mockSendMessage }),
      db: { getSettingsByKeys: mockGetSettingsByKeys },
    });

    const app = makeApp(router);
    await withServer(app, async (baseUrl) => {
      const resp = await fetch(
        `${baseUrl}/internal/settings?keys=auto_reply_enabled,reply_delay_ms`,
        {
          headers: { 'x-internal-secret': 'shh' },
        },
      );

      expect(resp.status).toBe(200);
      const body = await resp.json();

      expect(body).toEqual({
        ok: true,
        values: {
          auto_reply_enabled: 'true',
          reply_delay_ms: '1500',
        },
      });
    });
  });

  test('settings fallback ke getSetting per key', async () => {
    const mockGetSetting = jest.fn(async (key) => `value:${key}`);
    const router = createInternalApiRouter({
      sharedSecret: 'shh',
      getSock: () => ({ sendMessage: mockSendMessage }),
      db: { getSetting: mockGetSetting },
      logger: { error: jest.fn() },
    });

    const app = makeApp(router);
    await withServer(app, async (baseUrl) => {
      const resp = await fetch(`${baseUrl}/internal/settings?keys=a,b`, {
        headers: { 'x-internal-secret': 'shh' },
      });
      expect(resp.status).toBe(200);
      const body = await resp.json();
      expect(body.values).toEqual({ a: 'value:a', b: 'value:b' });
    });
  });

  test('settings return 500 jika helper settings tidak tersedia', async () => {
    const router = createInternalApiRouter({
      sharedSecret: 'shh',
      getSock: () => ({ sendMessage: mockSendMessage }),
      db: {},
      logger: { error: jest.fn() },
    });

    const app = makeApp(router);
    await withServer(app, async (baseUrl) => {
      const resp = await fetch(`${baseUrl}/internal/settings`, {
        headers: { 'x-internal-secret': 'shh' },
      });
      expect(resp.status).toBe(500);
    });
  });

  test('sse feed kirim event connected', async () => {
    const router = createInternalApiRouter({
      sharedSecret: 'shh',
      getSock: () => ({ sendMessage: mockSendMessage }),
      db: { getSettingsByKeys: mockGetSettingsByKeys },
      subscribeSSE: () => () => {},
    });
    const app = makeApp(router);

    await withServer(app, async (baseUrl) => {
      const resp = await fetch(`${baseUrl}/internal/sse-feed`, {
        headers: { 'x-internal-secret': 'shh' },
      });

      expect(resp.status).toBe(200);

      const reader = resp.body.getReader();
      const chunk = await reader.read();
      const text = new TextDecoder().decode(chunk.value || new Uint8Array());

      expect(text).toContain('event: connected');
      await reader.cancel();
    });
  });
});
