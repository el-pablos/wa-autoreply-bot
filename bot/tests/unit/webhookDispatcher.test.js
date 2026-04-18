import { describe, expect, jest, test } from '@jest/globals';
import {
  dispatchWebhook,
  dispatchWebhooks,
  signPayload,
} from '../../src/utils/webhookDispatcher.js';

describe('webhookDispatcher.signPayload', () => {
  test('hasilkan signature HMAC-SHA256 konsisten', () => {
    const sig1 = signPayload('secret', '{"a":1}');
    const sig2 = signPayload('secret', '{"a":1}');

    expect(sig1).toBe(sig2);
    expect(sig1).toMatch(/^[a-f0-9]{64}$/);
  });
});

describe('webhookDispatcher.dispatchWebhook', () => {
  test('sukses dispatch di percobaan pertama', async () => {
    const fetchImpl = jest.fn().mockResolvedValue({
      ok: true,
      status: 200,
      text: async () => 'ok',
    });

    const out = await dispatchWebhook(
      { id: 1, url: 'https://example.com/hook', secret: 'abc' },
      'message_received',
      { foo: 'bar' },
      { fetchImpl },
    );

    expect(fetchImpl).toHaveBeenCalledTimes(1);
    expect(out).toEqual(
      expect.objectContaining({
        success: true,
        attempts: 1,
        statusCode: 200,
        responseBody: 'ok',
      }),
    );
  });

  test('retry pada status retryable lalu sukses', async () => {
    const fetchImpl = jest
      .fn()
      .mockResolvedValueOnce({ ok: false, status: 500, text: async () => 'err' })
      .mockResolvedValueOnce({ ok: true, status: 200, text: async () => 'ok' });
    const sleepFn = jest.fn(async () => {});

    const out = await dispatchWebhook(
      { id: 2, url: 'https://example.com/hook', secret: 'abc' },
      'reply_sent',
      { foo: 'bar' },
      { fetchImpl, sleepFn, maxAttempts: 3, baseBackoffMs: 10 },
    );

    expect(fetchImpl).toHaveBeenCalledTimes(2);
    expect(sleepFn).toHaveBeenCalledTimes(1);
    expect(out.success).toBe(true);
    expect(out.attempts).toBe(2);
  });

  test('return failure setelah max attempt habis', async () => {
    const fetchImpl = jest.fn().mockRejectedValue(new Error('network down'));
    const sleepFn = jest.fn(async () => {});

    const out = await dispatchWebhook(
      { id: 3, url: 'https://example.com/hook', secret: 'abc' },
      'message_received',
      {},
      { fetchImpl, sleepFn, maxAttempts: 2, baseBackoffMs: 5 },
    );

    expect(fetchImpl).toHaveBeenCalledTimes(2);
    expect(out.success).toBe(false);
    expect(out.error).toContain('network down');
  });
});

describe('webhookDispatcher.dispatchWebhooks', () => {
  test('dispatch paralel ke beberapa endpoint', async () => {
    const fetchImpl = jest.fn().mockResolvedValue({
      ok: true,
      status: 200,
      text: async () => 'ok',
    });

    const out = await dispatchWebhooks(
      [
        { id: 1, url: 'https://a.example/hook', secret: 'a' },
        { id: 2, url: 'https://b.example/hook', secret: 'b' },
      ],
      'reply_sent',
      { id: 123 },
      { fetchImpl },
    );

    expect(out).toHaveLength(2);
    expect(fetchImpl).toHaveBeenCalledTimes(2);
    expect(out[0].success).toBe(true);
    expect(out[1].success).toBe(true);
  });
});
