/**
 * Test db.js dengan mock mysql2
 */

import { beforeEach, describe, expect, jest, test } from '@jest/globals';

// Mock mysql2/promise sebelum import modul
const mockExecute = jest.fn();
const mockPool    = { execute: mockExecute };

jest.unstable_mockModule('mysql2/promise', () => ({
  default: { createPool: jest.fn(() => mockPool) },
  createPool: jest.fn(() => mockPool),
}));

const { getSetting, isAllowedNumber, saveMessageLog } = await import('../src/db.js');

describe('db — getSetting', () => {
  beforeEach(() => mockExecute.mockReset());

  test('return value jika key ditemukan', async () => {
    mockExecute.mockResolvedValue([[{ value: 'true' }]]);
    const result = await getSetting('auto_reply_enabled');
    expect(result).toBe('true');
    expect(mockExecute).toHaveBeenCalledWith(
      expect.stringContaining('bot_settings'),
      ['auto_reply_enabled']
    );
  });

  test('return null jika key tidak ditemukan', async () => {
    mockExecute.mockResolvedValue([[]]);
    const result = await getSetting('key_tidak_ada');
    expect(result).toBeNull();
  });
});

describe('db — isAllowedNumber', () => {
  beforeEach(() => mockExecute.mockReset());

  test('return true jika nomor ada di allow-list', async () => {
    mockExecute.mockResolvedValue([[{ id: 1 }]]);
    const result = await isAllowedNumber('628123456789');
    expect(result).toBe(true);
  });

  test('return false jika nomor tidak ada di allow-list', async () => {
    mockExecute.mockResolvedValue([[]]);
    const result = await isAllowedNumber('628000000000');
    expect(result).toBe(false);
  });
});

describe('db — saveMessageLog', () => {
  beforeEach(() => mockExecute.mockReset());

  test('return insertId setelah insert berhasil', async () => {
    mockExecute.mockResolvedValue([{ insertId: 42 }]);
    const id = await saveMessageLog({
      fromNumber:  '628111111111',
      messageText: 'Halo',
      messageType: 'text',
      isAllowed:   true,
      replied:     true,
      replyText:   'Balasan',
      groupId:     null,
    });
    expect(id).toBe(42);
  });

  test('isAllowed false dikirim sebagai 0', async () => {
    mockExecute.mockResolvedValue([{ insertId: 1 }]);
    await saveMessageLog({
      fromNumber:  '628222222222',
      messageText: 'Test',
      messageType: 'text',
      isAllowed:   false,
      replied:     false,
    });
    const callArgs = mockExecute.mock.calls[0][1];
    expect(callArgs[3]).toBe(0); // isAllowed
    expect(callArgs[4]).toBe(0); // replied
  });
});
