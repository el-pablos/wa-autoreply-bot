import { describe, expect, jest, test } from '@jest/globals';
import { addToBlacklist, isBlacklisted } from '../../src/utils/blacklist.js';

describe('blacklist.isBlacklisted', () => {
  test('return false jika phone kosong', async () => {
    const cache = new Map();
    const dbLookup = jest.fn();
    await expect(isBlacklisted('', cache, dbLookup)).resolves.toBe(false);
    expect(dbLookup).not.toHaveBeenCalled();
  });

  test('return true untuk row blacklist permanen', async () => {
    const cache = new Map();
    const dbLookup = jest.fn().mockResolvedValue({
      phone_number: '6281',
      unblock_at: null,
    });

    await expect(isBlacklisted('6281', cache, dbLookup)).resolves.toBe(true);
    expect(dbLookup).toHaveBeenCalledTimes(1);
  });

  test('gunakan cache tanpa hit DB ulang', async () => {
    const cache = new Map();
    const dbLookup = jest.fn().mockResolvedValue({
      phone_number: '6281',
      unblock_at: null,
    });

    await expect(isBlacklisted('6281', cache, dbLookup)).resolves.toBe(true);
    await expect(isBlacklisted('6281', cache, dbLookup)).resolves.toBe(true);

    expect(dbLookup).toHaveBeenCalledTimes(1);
  });

  test('return false bila unblock_at sudah lewat', async () => {
    const cache = new Map();
    const dbLookup = jest.fn().mockResolvedValue({
      phone_number: '6281',
      unblock_at: '2026-04-10T00:00:00.000Z',
    });

    await expect(
      isBlacklisted('6281', cache, dbLookup, {
        now: new Date('2026-04-11T00:00:00.000Z'),
      }),
    ).resolves.toBe(false);
  });
});

describe('blacklist.addToBlacklist', () => {
  test('memanggil dbInsert + update cache', async () => {
    const cache = new Map();
    const dbInsert = jest.fn().mockResolvedValue({ insertId: 1 });
    const unblockAt = new Date('2026-04-20T00:00:00.000Z');

    await addToBlacklist('6281', 'spam', unblockAt, dbInsert, cache);

    expect(dbInsert).toHaveBeenCalledWith(
      expect.objectContaining({
        phone_number: '6281',
        reason: 'spam',
        unblock_at: unblockAt,
      }),
    );
    expect(cache.has('6281')).toBe(true);
  });
});
