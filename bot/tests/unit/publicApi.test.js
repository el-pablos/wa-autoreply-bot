import express from 'express';
import { beforeEach, describe, expect, jest, test } from '@jest/globals';
import { createPublicApiRouter } from '../../src/api/public.js';

function makeApp(router) {
  const app = express();
  app.use(express.json());
  app.use('/api', router);
  return app;
}

async function withServer(app, fn) {
  const server = await new Promise((resolve) => {
    const s = app.listen(0, '127.0.0.1', () => resolve(s));
  });

  const address = server.address();
  if (!address || typeof address === 'string') {
    throw new Error(`Gagal mendapatkan port test server: ${String(address)}`);
  }

  const port = Number(address.port);
  const baseUrl = `http://127.0.0.1:${port}`;

  try {
    await fn(baseUrl);
  } finally {
    await new Promise((resolve, reject) => {
      server.close((err) => (err ? reject(err) : resolve()));
    });
  }
}

describe('public api router', () => {
  const mockVerifyApiKey = jest.fn();
  const mockUpsertAllowListEntry = jest.fn();
  const mockGetMessageLogs = jest.fn();
  const mockSendMessage = jest.fn();

  beforeEach(() => {
    jest.clearAllMocks();
  });

  function createRouter() {
    return createPublicApiRouter({
      getSock: () => ({ sendMessage: mockSendMessage }),
      db: {
        verifyApiKey: mockVerifyApiKey,
        upsertAllowListEntry: mockUpsertAllowListEntry,
        getMessageLogs: mockGetMessageLogs,
      },
      logger: { error: jest.fn() },
    });
  }

  test('reject tanpa API key', async () => {
    const app = makeApp(createRouter());

    await withServer(app, async (baseUrl) => {
      const resp = await fetch(`${baseUrl}/api/logs`);
      expect(resp.status).toBe(401);
    });
  });

  test('reject API key invalid', async () => {
    mockVerifyApiKey.mockResolvedValue(null);
    const app = makeApp(createRouter());

    await withServer(app, async (baseUrl) => {
      const resp = await fetch(`${baseUrl}/api/logs`, {
        headers: { 'x-api-key': 'bad-key' },
      });
      expect(resp.status).toBe(401);
    });
  });

  test('return 500 saat verifikasi key gagal', async () => {
    mockVerifyApiKey.mockRejectedValue(new Error('db error'));
    const app = makeApp(createRouter());

    await withServer(app, async (baseUrl) => {
      const resp = await fetch(`${baseUrl}/api/logs`, {
        headers: { 'x-api-key': 'key' },
      });
      expect(resp.status).toBe(500);
    });
  });

  test('send message berhasil jika API key valid', async () => {
    mockVerifyApiKey.mockResolvedValue({ id: 1, name: 'client' });
    mockSendMessage.mockResolvedValue(undefined);

    const app = makeApp(createRouter());

    await withServer(app, async (baseUrl) => {
      const resp = await fetch(`${baseUrl}/api/send`, {
        method: 'POST',
        headers: {
          'content-type': 'application/json',
          'x-api-key': 'secret-key',
        },
        body: JSON.stringify({ to: '08123456789', text: 'halo' }),
      });

      expect(resp.status).toBe(200);
      expect(mockSendMessage).toHaveBeenCalledWith('628123456789@s.whatsapp.net', { text: 'halo' });
    });
  });

  test('send message validasi payload + socket readiness', async () => {
    mockVerifyApiKey.mockResolvedValue({ id: 1, name: 'client' });

    const noSockRouter = createPublicApiRouter({
      getSock: () => null,
      db: {
        verifyApiKey: mockVerifyApiKey,
        upsertAllowListEntry: mockUpsertAllowListEntry,
        getMessageLogs: mockGetMessageLogs,
      },
      logger: { error: jest.fn() },
    });

    const app = makeApp(noSockRouter);

    await withServer(app, async (baseUrl) => {
      const badPayload = await fetch(`${baseUrl}/api/send`, {
        method: 'POST',
        headers: {
          'content-type': 'application/json',
          'x-api-key': 'secret-key',
        },
        body: JSON.stringify({ to: '', text: '' }),
      });
      expect(badPayload.status).toBe(400);

      const noSock = await fetch(`${baseUrl}/api/send`, {
        method: 'POST',
        headers: {
          'content-type': 'application/json',
          'x-api-key': 'secret-key',
        },
        body: JSON.stringify({ to: '628123456789', text: 'halo' }),
      });
      expect(noSock.status).toBe(503);
    });
  });

  test('allowlist endpoint memanggil upsert helper', async () => {
    mockVerifyApiKey.mockResolvedValue({ id: 1, name: 'client' });
    mockUpsertAllowListEntry.mockResolvedValue(true);

    const app = makeApp(createRouter());

    await withServer(app, async (baseUrl) => {
      const resp = await fetch(`${baseUrl}/api/allowlist`, {
        method: 'POST',
        headers: {
          'content-type': 'application/json',
          'x-api-key': 'secret-key',
        },
        body: JSON.stringify({ phone_number: '081111111111', label: 'VIP', is_active: true }),
      });

      expect(resp.status).toBe(200);
      expect(mockUpsertAllowListEntry).toHaveBeenCalledWith({
        phoneNumber: '6281111111111',
        label: 'VIP',
        isActive: true,
      });
    });
  });

  test('allowlist validasi phone dan helper availability', async () => {
    mockVerifyApiKey.mockResolvedValue({ id: 1, name: 'client' });

    const app = makeApp(createRouter());
    await withServer(app, async (baseUrl) => {
      const invalidPhone = await fetch(`${baseUrl}/api/allowlist`, {
        method: 'POST',
        headers: {
          'content-type': 'application/json',
          'x-api-key': 'secret-key',
        },
        body: JSON.stringify({ phone_number: 'abc' }),
      });
      expect(invalidPhone.status).toBe(400);
    });

    const brokenRouter = createPublicApiRouter({
      getSock: () => ({ sendMessage: mockSendMessage }),
      db: {
        verifyApiKey: mockVerifyApiKey,
        upsertAllowListEntry: undefined,
        getMessageLogs: mockGetMessageLogs,
      },
      logger: { error: jest.fn() },
    });
    const brokenApp = makeApp(brokenRouter);

    await withServer(brokenApp, async (baseUrl) => {
      const resp = await fetch(`${baseUrl}/api/allowlist`, {
        method: 'POST',
        headers: {
          'content-type': 'application/json',
          'x-api-key': 'secret-key',
        },
        body: JSON.stringify({ phone_number: '081111111111' }),
      });
      expect(resp.status).toBe(500);
    });
  });

  test('logs endpoint forward filter + pagination', async () => {
    mockVerifyApiKey.mockResolvedValue({ id: 1, name: 'client' });
    mockGetMessageLogs.mockResolvedValue([{ id: 1 }]);

    const app = makeApp(createRouter());

    await withServer(app, async (baseUrl) => {
      const resp = await fetch(
        `${baseUrl}/api/logs?limit=10&offset=5&from_number=081222222222`,
        {
          headers: { 'x-api-key': 'secret-key' },
        },
      );

      expect(resp.status).toBe(200);
      expect(mockGetMessageLogs).toHaveBeenCalledWith({
        limit: 10,
        offset: 5,
        fromNumber: '6281222222222',
      });
    });
  });

  test('logs endpoint return 500 jika helper tidak tersedia', async () => {
    mockVerifyApiKey.mockResolvedValue({ id: 1, name: 'client' });

    const router = createPublicApiRouter({
      getSock: () => ({ sendMessage: mockSendMessage }),
      db: {
        verifyApiKey: mockVerifyApiKey,
        upsertAllowListEntry: mockUpsertAllowListEntry,
        getMessageLogs: undefined,
      },
      logger: { error: jest.fn() },
    });
    const app = makeApp(router);

    await withServer(app, async (baseUrl) => {
      const resp = await fetch(`${baseUrl}/api/logs`, {
        headers: { 'x-api-key': 'secret-key' },
      });
      expect(resp.status).toBe(500);
    });
  });
});
