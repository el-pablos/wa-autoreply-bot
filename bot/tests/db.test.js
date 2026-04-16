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

const {
  getSetting,
  isAllowedNumber,
  saveMessageLog,
  upsertApprovedSession,
  isInApprovedSession,
  refreshApprovedSession,
  revokeApprovedSession,
  expireStaleSessions,
  getActiveApprovedSessions,
} = await import('../src/db.js');

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

describe('db — approved sessions', () => {
  beforeEach(() => mockExecute.mockReset());

  test('upsertApprovedSession insert bila data belum ada', async () => {
    mockExecute
      .mockResolvedValueOnce([[]])
      .mockResolvedValueOnce([{ insertId: 1 }]);

    const result = await upsertApprovedSession('628111111111', '628999999999', 24);
    expect(result.action).toBe('created');
    expect(result.expiresAt).toBeInstanceOf(Date);
    expect(mockExecute).toHaveBeenCalledWith(
      expect.stringContaining('SELECT id FROM approved_sessions'),
      ['628111111111']
    );
    expect(mockExecute).toHaveBeenCalledWith(
      expect.stringContaining('INSERT INTO approved_sessions'),
      [
        '628111111111',
        expect.any(Date),
        expect.any(Date),
        expect.any(Date),
        '628999999999',
      ]
    );
  });

  test('upsertApprovedSession refresh bila data aktif sudah ada', async () => {
    mockExecute
      .mockResolvedValueOnce([[{ id: 5 }]])
      .mockResolvedValueOnce([{ affectedRows: 1 }]);

    const result = await upsertApprovedSession('628111111111', '628999999999', 24);
    expect(result.action).toBe('refreshed');
    expect(result.expiresAt).toBeInstanceOf(Date);
    expect(mockExecute).toHaveBeenCalledWith(
      expect.stringContaining('UPDATE approved_sessions'),
      [expect.any(Date), expect.any(Date), '628999999999', '628111111111']
    );
  });

  test('isInApprovedSession return true jika sesi aktif', async () => {
    mockExecute.mockResolvedValueOnce([[{ id: 99 }]]);
    await expect(isInApprovedSession('628111111111')).resolves.toBe(true);
  });

  test('refreshApprovedSession return false jika tidak ada sesi aktif', async () => {
    mockExecute.mockResolvedValueOnce([{ affectedRows: 0 }]);
    await expect(refreshApprovedSession('628111111111', 24)).resolves.toBe(false);
  });

  test('revokeApprovedSession return true jika berhasil revoke', async () => {
    mockExecute.mockResolvedValueOnce([{ affectedRows: 1 }]);
    await expect(revokeApprovedSession('628111111111')).resolves.toBe(true);
  });

  test('expireStaleSessions return jumlah sesi kadaluarsa', async () => {
    mockExecute.mockResolvedValueOnce([{ affectedRows: 3 }]);
    await expect(expireStaleSessions()).resolves.toBe(3);
  });

  test('getActiveApprovedSessions return list sesi aktif', async () => {
    mockExecute.mockResolvedValueOnce([[{ id: 1, phone_number: '6281' }]]);
    await expect(getActiveApprovedSessions()).resolves.toEqual([{ id: 1, phone_number: '6281' }]);
  });
});
