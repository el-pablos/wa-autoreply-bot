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
  getActiveTemplate,
  getBusinessHourSchedules,
  getActiveOofSchedules,
  getMessageTypeTemplates,
  getBlacklistEntry,
  saveRateLimitViolation,
  getKnowledgeBaseEntries,
  incrementKnowledgeMatch,
  saveAiConversationTurn,
  getRecentConversationHistory,
  pruneConversationHistory,
  getActiveWebhookEndpoints,
  createWebhookDeliveryLog,
  updateWebhookDeliveryLog,
  touchWebhookEndpoint,
  saveEscalationLog,
  verifyApiKey,
  upsertAllowListEntry,
  getMessageLogs,
  getSettingsByKeys,
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

describe('db — template/schedule helpers', () => {
  beforeEach(() => mockExecute.mockReset());

  test('getActiveTemplate return linked template jika ada', async () => {
    mockExecute
      .mockResolvedValueOnce([[{ id: 1, body: 'template linked' }]])
      .mockResolvedValueOnce([[{ id: 9, body: 'template default' }]]);

    await expect(getActiveTemplate('6281')).resolves.toEqual({ id: 1, body: 'template linked' });
    expect(mockExecute).toHaveBeenCalledTimes(1);
  });

  test('getActiveTemplate fallback ke default jika linked tidak ada', async () => {
    mockExecute
      .mockResolvedValueOnce([[]])
      .mockResolvedValueOnce([[{ id: 9, body: 'template default' }]]);

    await expect(getActiveTemplate('6281')).resolves.toEqual({ id: 9, body: 'template default' });
    expect(mockExecute).toHaveBeenCalledTimes(2);
  });

  test('getBusinessHourSchedules/getActiveOofSchedules/getMessageTypeTemplates', async () => {
    mockExecute
      .mockResolvedValueOnce([[{ weekday: 1 }]])
      .mockResolvedValueOnce([[{ id: 2 }]])
      .mockResolvedValueOnce([[{ message_type: 'text' }]]);

    await expect(getBusinessHourSchedules()).resolves.toEqual([{ weekday: 1 }]);
    await expect(getActiveOofSchedules('2026-04-20')).resolves.toEqual([{ id: 2 }]);
    await expect(getMessageTypeTemplates()).resolves.toEqual([{ message_type: 'text' }]);
  });
});

describe('db — anti-spam/kb/history helpers', () => {
  beforeEach(() => mockExecute.mockReset());

  test('getBlacklistEntry return null jika tidak ada', async () => {
    mockExecute.mockResolvedValueOnce([[]]);
    await expect(getBlacklistEntry('6281')).resolves.toBeNull();
  });

  test('saveRateLimitViolation return insertId', async () => {
    mockExecute.mockResolvedValueOnce([{ insertId: 77 }]);
    await expect(
      saveRateLimitViolation({ phoneNumber: '6281', windowStart: new Date(), messageCount: 9 })
    ).resolves.toBe(77);
  });

  test('knowledge base read + increment', async () => {
    mockExecute
      .mockResolvedValueOnce([[{ id: 1, answer: 'ok' }]])
      .mockResolvedValueOnce([{ affectedRows: 1 }]);

    await expect(getKnowledgeBaseEntries()).resolves.toEqual([{ id: 1, answer: 'ok' }]);
    await expect(incrementKnowledgeMatch(1)).resolves.toBe(true);
  });

  test('AI history helpers', async () => {
    mockExecute
      .mockResolvedValueOnce([{ insertId: 11 }])
      .mockResolvedValueOnce([[{ id: 1, content: 'halo' }]])
      .mockResolvedValueOnce([{ affectedRows: 4 }]);

    await expect(
      saveAiConversationTurn({ phoneNumber: '6281', role: 'user', content: 'halo', tokens: 12 })
    ).resolves.toBe(11);
    await expect(getRecentConversationHistory('6281', { limit: 5 })).resolves.toEqual([
      { id: 1, content: 'halo' },
    ]);
    await expect(pruneConversationHistory(new Date())).resolves.toBe(4);
  });
});

describe('db — webhook/api helpers', () => {
  beforeEach(() => mockExecute.mockReset());

  test('getActiveWebhookEndpoints by event', async () => {
    mockExecute.mockResolvedValueOnce([[{ id: 1, url: 'https://hook' }]]);
    await expect(getActiveWebhookEndpoints('reply_sent')).resolves.toEqual([
      { id: 1, url: 'https://hook' },
    ]);
    expect(mockExecute).toHaveBeenCalledWith(
      expect.stringContaining('JSON_CONTAINS'),
      ['reply_sent']
    );
  });

  test('webhook log create/update/touch endpoint', async () => {
    mockExecute
      .mockResolvedValueOnce([{ insertId: 91 }])
      .mockResolvedValueOnce([{ affectedRows: 1 }])
      .mockResolvedValueOnce([{ affectedRows: 1 }]);

    await expect(
      createWebhookDeliveryLog({ endpointId: 1, event: 'x', payload: { a: 1 } })
    ).resolves.toBe(91);
    await expect(
      updateWebhookDeliveryLog(91, { status: 'success', responseCode: 200, attempts: 1 })
    ).resolves.toBe(true);
    await expect(touchWebhookEndpoint(1)).resolves.toBe(true);
  });

  test('saveEscalationLog return insertId', async () => {
    mockExecute.mockResolvedValueOnce([{ insertId: 12 }]);
    await expect(
      saveEscalationLog({
        fromNumber: '6281',
        triggerReason: 'komplain',
        escalatedTo: '6289',
        messageSnippet: 'tolong',
      })
    ).resolves.toBe(12);
  });

  test('verifyApiKey return row + update last_used_at', async () => {
    mockExecute
      .mockResolvedValueOnce([[{ id: 1, name: 'integration', scopes: null }]])
      .mockResolvedValueOnce([{ affectedRows: 1 }]);

    const row = await verifyApiKey('plain-key');
    expect(row).toEqual({ id: 1, name: 'integration', scopes: null });
    expect(mockExecute).toHaveBeenCalledTimes(2);

    const hashArg = mockExecute.mock.calls[0][1][0];
    expect(hashArg).toMatch(/^[a-f0-9]{64}$/);
  });

  test('upsertAllowListEntry, getMessageLogs, getSettingsByKeys', async () => {
    mockExecute
      .mockResolvedValueOnce([{ affectedRows: 1 }])
      .mockResolvedValueOnce([[{ id: 1 }]])
      .mockResolvedValueOnce([[{ key: 'auto_reply_enabled', value: 'true' }]]);

    await expect(
      upsertAllowListEntry({ phoneNumber: '6281', label: 'VIP', isActive: true })
    ).resolves.toBe(true);
    await expect(getMessageLogs({ fromNumber: '6281', limit: 10, offset: 0 })).resolves.toEqual([
      { id: 1 },
    ]);
    await expect(getSettingsByKeys(['auto_reply_enabled'])).resolves.toEqual([
      { key: 'auto_reply_enabled', value: 'true' },
    ]);
  });
});
