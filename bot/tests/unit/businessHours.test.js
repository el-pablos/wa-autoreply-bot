import { describe, expect, test } from '@jest/globals';
import { getActiveOof, isWithinBusinessHours } from '../../src/utils/businessHours.js';

describe('businessHours.isWithinBusinessHours', () => {
  const schedule = {
    1: { start: '09:00', end: '17:00' },
    2: { start: '09:00', end: '17:00' },
    3: { start: '09:00', end: '17:00' },
    4: { start: '09:00', end: '17:00' },
    5: { start: '09:00', end: '17:00' },
    6: null,
    7: null,
  };

  test('return true saat masih di jam kerja', () => {
    const mondayTenWib = new Date('2026-04-20T03:00:00.000Z');
    expect(isWithinBusinessHours('Asia/Jakarta', schedule, mondayTenWib)).toBe(true);
  });

  test('return false saat hari libur', () => {
    const saturdayTenWib = new Date('2026-04-25T03:00:00.000Z');
    expect(isWithinBusinessHours('Asia/Jakarta', schedule, saturdayTenWib)).toBe(false);
  });

  test('return false saat di luar interval', () => {
    const mondayEightWib = new Date('2026-04-20T01:00:00.000Z');
    expect(isWithinBusinessHours('Asia/Jakarta', schedule, mondayEightWib)).toBe(false);
  });

  test('throw jika timezone invalid type', () => {
    expect(() => isWithinBusinessHours(null, schedule, new Date())).toThrow(TypeError);
  });
});

describe('businessHours.getActiveOof', () => {
  test('return oof aktif saat now dalam date range', () => {
    const rows = [
      {
        id: 1,
        start_date: '2026-04-18',
        end_date: '2026-04-21',
        message: 'Tim sedang cuti bersama',
        is_active: 1,
      },
    ];

    const now = new Date('2026-04-20T10:00:00.000Z');
    expect(getActiveOof(rows, now)).toEqual(rows[0]);
  });

  test('return null bila tidak ada jadwal aktif', () => {
    const rows = [
      {
        id: 1,
        start_date: '2026-04-18',
        end_date: '2026-04-21',
        message: 'Tim sedang cuti bersama',
        is_active: 0,
      },
    ];

    const now = new Date('2026-04-20T10:00:00.000Z');
    expect(getActiveOof(rows, now)).toBeNull();
  });

  test('return null jika list bukan array', () => {
    expect(getActiveOof(null, new Date())).toBeNull();
  });
});
