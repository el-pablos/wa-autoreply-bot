import { describe, expect, jest, test } from '@jest/globals';
import { RateLimiter } from '../../src/utils/rateLimiter.js';

describe('rateLimiter', () => {
  test('canReply false saat melewati batas window', () => {
    const limiter = new RateLimiter({ windowMs: 1000, maxPerWindow: 2 });
    const nowSpy = jest.spyOn(Date, 'now').mockReturnValue(1000);

    expect(limiter.canReply('6281')).toBe(true);

    limiter.recordReply('6281');
    limiter.recordReply('6281');

    expect(limiter.canReply('6281')).toBe(false);

    nowSpy.mockRestore();
  });

  test('otomatis bisa lagi setelah entry keluar dari window', () => {
    const limiter = new RateLimiter({ windowMs: 1000, maxPerWindow: 1 });
    const nowSpy = jest.spyOn(Date, 'now');

    nowSpy.mockReturnValue(1000);
    limiter.recordReply('6281');
    expect(limiter.canReply('6281')).toBe(false);

    nowSpy.mockReturnValue(2501);
    expect(limiter.canReply('6281')).toBe(true);

    nowSpy.mockRestore();
  });

  test('cleanup membersihkan entry expired', () => {
    const limiter = new RateLimiter({ windowMs: 1000, maxPerWindow: 5 });
    const nowSpy = jest.spyOn(Date, 'now');

    nowSpy.mockReturnValue(1000);
    limiter.recordReply('6281');
    limiter.recordReply('6282');

    nowSpy.mockReturnValue(2500);
    const removed = limiter.cleanup();

    expect(removed).toBe(2);
    expect(limiter.countFor('6281')).toBe(0);
    expect(limiter.countFor('6282')).toBe(0);

    nowSpy.mockRestore();
  });

  test('empty phone selalu diizinkan dan tidak direkam', () => {
    const limiter = new RateLimiter({ windowMs: 1000, maxPerWindow: 1 });
    limiter.recordReply('');
    expect(limiter.canReply('')).toBe(true);
    expect(limiter.countFor('')).toBe(0);
  });

  test('reset menghapus counter nomor', () => {
    const limiter = new RateLimiter({ windowMs: 1000, maxPerWindow: 2 });
    const nowSpy = jest.spyOn(Date, 'now').mockReturnValue(1000);

    limiter.recordReply('6281');
    expect(limiter.countFor('6281')).toBe(1);

    limiter.reset('6281');
    expect(limiter.countFor('6281')).toBe(0);

    nowSpy.mockRestore();
  });

  test('stop mematikan cleanup timer', () => {
    const limiter = new RateLimiter({
      windowMs: 1000,
      maxPerWindow: 2,
      cleanupEveryMs: 100,
    });

    limiter.stop();
    expect(limiter._cleanupTimer).toBeNull();
  });
});
