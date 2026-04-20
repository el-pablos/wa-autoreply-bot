import { beforeEach, describe, expect, jest, test } from '@jest/globals';

const mockExecute = jest.fn();
const mockPool = { execute: mockExecute };
const mockCreatePool = jest.fn(() => mockPool);

jest.unstable_mockModule('mysql2/promise', () => ({
  default: { createPool: mockCreatePool },
  createPool: mockCreatePool,
}));

const {
  getSetting,
  isAllowedNumber,
  saveMessageLog,
  updateBotStatus,
  upsertApprovedSession,
  isInApprovedSession,
  refreshApprovedSession,
  revokeApprovedSession,
  expireStaleSessions,
  getActiveApprovedSessions,
  getActiveTemplate,
  getBusinessHourSchedules,
  getActiveOofSchedules,
  getMessageTypeTemplates,
  getBlacklistEntry,
  saveRateLimitViolation,
  getSettingsByKeys,
} = await import('../src/db.js');

describe('db - basic lookups', () => {
  beforeEach(() => {
    mockExecute.mockReset();
  });

  test('getSetting return value when key exists', async () => {
    mockExecute.mockResolvedValueOnce([[{ value: 'true' }]]);

    await expect(getSetting('auto_reply_enabled')).resolves.toBe('true');
    expect(mockExecute).toHaveBeenCalledWith(
      expect.stringContaining('FROM bot_settings'),
      ['auto_reply_enabled'],
    );
  });

  test('getSetting return null when key missing', async () => {
    mockExecute.mockResolvedValueOnce([[]]);

    await expect(getSetting('missing_key')).resolves.toBeNull();
  });

  test('isAllowedNumber return true/false based on query result', async () => {
    mockExecute.mockResolvedValueOnce([[{ id: 1 }]]);
    await expect(isAllowedNumber('628111111111')).resolves.toBe(true);

    mockExecute.mockResolvedValueOnce([[]]);
    await expect(isAllowedNumber('628999999999')).resolves.toBe(false);
  });
});

describe('db - message logs and settings', () => {
  beforeEach(() => {
    mockExecute.mockReset();
  });

  test('saveMessageLog return insert id and map booleans to tinyint', async () => {
    mockExecute.mockResolvedValueOnce([{ insertId: 42 }]);

    const id = await saveMessageLog({
      fromNumber: '628111111111',
      messageText: 'halo',
      messageType: 'text',
      isAllowed: false,
      replied: true,
      replyText: 'balasan',
      groupId: null,
      responseTimeMs: -33,
    });

    expect(id).toBe(42);
    const values = mockExecute.mock.calls[0][1];
    expect(values[3]).toBe(0);
    expect(values[4]).toBe(1);
    expect(values[7]).toBe(0);
  });

  test('updateBotStatus updates bot_status key', async () => {
    mockExecute.mockResolvedValueOnce([{ affectedRows: 1 }]);

    await updateBotStatus('online');

    expect(mockExecute).toHaveBeenCalledWith(
      expect.stringContaining("WHERE `key` = 'bot_status'"),
      ['online'],
    );
  });

  test('getSettingsByKeys return empty array for empty input', async () => {
    await expect(getSettingsByKeys([])).resolves.toEqual([]);
    expect(mockExecute).not.toHaveBeenCalled();
  });

  test('getSettingsByKeys fetch values for provided keys', async () => {
    mockExecute.mockResolvedValueOnce([[{ key: 'auto_reply_enabled', value: 'true' }]]);

    await expect(getSettingsByKeys(['auto_reply_enabled'])).resolves.toEqual([
      { key: 'auto_reply_enabled', value: 'true' },
    ]);

    expect(mockExecute).toHaveBeenCalledWith(
      expect.stringContaining('WHERE `key` IN (?)'),
      ['auto_reply_enabled'],
    );
  });
});

describe('db - approved session lifecycle', () => {
  beforeEach(() => {
    mockExecute.mockReset();
  });

  test('upsertApprovedSession creates new session when absent', async () => {
    mockExecute
      .mockResolvedValueOnce([[]])
      .mockResolvedValueOnce([{ insertId: 1 }]);

    const result = await upsertApprovedSession('628111111111', '628900000000', 24);

    expect(result.action).toBe('created');
    expect(result.expiresAt).toBeInstanceOf(Date);
    expect(mockExecute).toHaveBeenCalledWith(
      expect.stringContaining('INSERT INTO approved_sessions'),
      [
        '628111111111',
        expect.any(Date),
        expect.any(Date),
        expect.any(Date),
        '628900000000',
      ],
    );
  });

  test('upsertApprovedSession refreshes active session when exists', async () => {
    mockExecute
      .mockResolvedValueOnce([[{ id: 99 }]])
      .mockResolvedValueOnce([{ affectedRows: 1 }]);

    const result = await upsertApprovedSession('628111111111', '628900000000', 12);

    expect(result.action).toBe('refreshed');
    expect(result.expiresAt).toBeInstanceOf(Date);
    expect(mockExecute).toHaveBeenCalledWith(
      expect.stringContaining('UPDATE approved_sessions'),
      [expect.any(Date), expect.any(Date), '628900000000', '628111111111'],
    );
  });

  test('isInApprovedSession returns true when active row exists', async () => {
    mockExecute.mockResolvedValueOnce([[{ id: 9 }]]);

    await expect(isInApprovedSession('628111111111')).resolves.toBe(true);
  });

  test('refreshApprovedSession and revokeApprovedSession return affected status', async () => {
    mockExecute.mockResolvedValueOnce([{ affectedRows: 1 }]);
    await expect(refreshApprovedSession('628111111111', 24)).resolves.toBe(true);

    mockExecute.mockResolvedValueOnce([{ affectedRows: 0 }]);
    await expect(revokeApprovedSession('628111111111')).resolves.toBe(false);
  });

  test('expireStaleSessions returns number of expired rows', async () => {
    mockExecute.mockResolvedValueOnce([{ affectedRows: 3 }]);

    await expect(expireStaleSessions()).resolves.toBe(3);
  });

  test('getActiveApprovedSessions returns query rows', async () => {
    mockExecute.mockResolvedValueOnce([[{ id: 1, phone_number: '6281' }]]);

    await expect(getActiveApprovedSessions()).resolves.toEqual([{ id: 1, phone_number: '6281' }]);
  });
});

describe('db - template, schedule, and guard helpers', () => {
  beforeEach(() => {
    mockExecute.mockReset();
  });

  test('getActiveTemplate returns linked template first', async () => {
    mockExecute.mockResolvedValueOnce([[{ id: 1, body: 'linked template' }]]);

    await expect(getActiveTemplate('6281')).resolves.toEqual({ id: 1, body: 'linked template' });
    expect(mockExecute).toHaveBeenCalledTimes(1);
  });

  test('getActiveTemplate falls back to default template when no linked template', async () => {
    mockExecute
      .mockResolvedValueOnce([[]])
      .mockResolvedValueOnce([[{ id: 2, body: 'default template' }]]);

    await expect(getActiveTemplate('6281')).resolves.toEqual({ id: 2, body: 'default template' });
    expect(mockExecute).toHaveBeenCalledTimes(2);
  });

  test('getActiveTemplate returns null when no linked and no default template', async () => {
    mockExecute
      .mockResolvedValueOnce([[]])
      .mockResolvedValueOnce([[]]);

    await expect(getActiveTemplate('6281')).resolves.toBeNull();
  });

  test('getBusinessHourSchedules/getActiveOofSchedules/getMessageTypeTemplates return rows', async () => {
    mockExecute
      .mockResolvedValueOnce([[{ weekday: 1 }]])
      .mockResolvedValueOnce([[{ id: 10 }]])
      .mockResolvedValueOnce([[{ message_type: 'text', body: 'x' }]]);

    await expect(getBusinessHourSchedules()).resolves.toEqual([{ weekday: 1 }]);
    await expect(getActiveOofSchedules('2026-04-20')).resolves.toEqual([{ id: 10 }]);
    await expect(getMessageTypeTemplates()).resolves.toEqual([{ message_type: 'text', body: 'x' }]);
  });

  test('getActiveOofSchedules throws for invalid date input', async () => {
    await expect(getActiveOofSchedules('not-a-date')).rejects.toThrow('Tanggal tidak valid');
  });

  test('getBlacklistEntry returns row or null', async () => {
    mockExecute.mockResolvedValueOnce([[{ id: 5, phone_number: '6281' }]]);
    await expect(getBlacklistEntry('6281')).resolves.toEqual({ id: 5, phone_number: '6281' });

    mockExecute.mockResolvedValueOnce([[]]);
    await expect(getBlacklistEntry('6282')).resolves.toBeNull();
  });

  test('saveRateLimitViolation returns insert id', async () => {
    mockExecute.mockResolvedValueOnce([{ insertId: 71 }]);

    await expect(
      saveRateLimitViolation({
        phoneNumber: '6281',
        windowStart: new Date('2026-04-20T00:00:00.000Z'),
        messageCount: 9,
      }),
    ).resolves.toBe(71);

    expect(mockExecute).toHaveBeenCalledWith(
      expect.stringContaining('INSERT INTO rate_limit_violations'),
      ['6281', expect.any(Date), 9],
    );
  });
});
