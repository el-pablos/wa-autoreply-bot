import { describe, expect, jest, test } from '@jest/globals';
import { TtlCache } from '../../src/utils/cache.js';

describe('cache.TtlCache', () => {
  test('set/get/has bekerja normal', () => {
    const cache = new TtlCache(1000);
    cache.set('a', 1);

    expect(cache.has('a')).toBe(true);
    expect(cache.get('a')).toBe(1);
  });

  test('expired key auto terhapus saat get', () => {
    const nowSpy = jest.spyOn(Date, 'now');
    nowSpy.mockReturnValue(1000);

    const cache = new TtlCache(1000);
    cache.set('a', 1);

    nowSpy.mockReturnValue(2500);
    expect(cache.get('a')).toBeUndefined();
    expect(cache.has('a')).toBe(false);

    nowSpy.mockRestore();
  });

  test('cleanup menghapus entry kadaluarsa', () => {
    const nowSpy = jest.spyOn(Date, 'now');
    nowSpy.mockReturnValue(1000);

    const cache = new TtlCache(1000);
    cache.set('a', 1);
    cache.set('b', 2);

    nowSpy.mockReturnValue(3000);
    expect(cache.cleanup()).toBe(2);

    nowSpy.mockRestore();
  });

  test('constructor throw untuk ttl invalid', () => {
    expect(() => new TtlCache(0)).toThrow(TypeError);
  });
});
