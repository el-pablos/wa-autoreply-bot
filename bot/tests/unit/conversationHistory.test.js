import { describe, expect, jest, test } from '@jest/globals';
import {
  loadConversationHistory,
  normalizeRole,
  pruneConversationHistory,
  saveConversationTurn,
} from '../../src/utils/conversationHistory.js';

describe('conversationHistory.normalizeRole', () => {
  test('mapping bot -> assistant', () => {
    expect(normalizeRole('bot')).toBe('assistant');
    expect(normalizeRole('user')).toBe('user');
  });

  test('throw untuk role invalid', () => {
    expect(() => normalizeRole('unknown')).toThrow(TypeError);
  });
});

describe('conversationHistory.saveConversationTurn', () => {
  test('simpan row ke dbInsert dengan role normalisasi', async () => {
    const dbInsert = jest.fn().mockResolvedValue({ insertId: 9 });

    await saveConversationTurn(dbInsert, {
      phoneNumber: '6281',
      role: 'bot',
      content: 'halo balik',
      tokens: 10,
    });

    expect(dbInsert).toHaveBeenCalledWith(
      expect.objectContaining({
        phone_number: '6281',
        role: 'assistant',
        content: 'halo balik',
        tokens: 10,
      }),
    );
  });

  test('throw jika content kosong', async () => {
    const dbInsert = jest.fn();
    await expect(
      saveConversationTurn(dbInsert, {
        phoneNumber: '6281',
        role: 'user',
        content: '   ',
      }),
    ).rejects.toThrow(TypeError);
  });
});

describe('conversationHistory.loadConversationHistory', () => {
  test('return history terurut asc dan dipotong maxTurns', async () => {
    const dbLookup = jest.fn().mockResolvedValue([
      { role: 'assistant', content: '3', created_at: '2026-04-20T10:03:00.000Z' },
      { role: 'user', content: '1', created_at: '2026-04-20T10:01:00.000Z' },
      { role: 'assistant', content: '2', created_at: '2026-04-20T10:02:00.000Z' },
    ]);

    const out = await loadConversationHistory(dbLookup, '6281', {
      maxTurns: 2,
      now: new Date('2026-04-20T10:10:00.000Z'),
    });

    expect(out).toEqual([
      { role: 'assistant', content: '2' },
      { role: 'assistant', content: '3' },
    ]);
  });

  test('return [] jika phone kosong', async () => {
    const dbLookup = jest.fn();
    await expect(loadConversationHistory(dbLookup, '')).resolves.toEqual([]);
    expect(dbLookup).not.toHaveBeenCalled();
  });
});

describe('conversationHistory.pruneConversationHistory', () => {
  test('call dbPrune dengan olderThan', async () => {
    const dbPrune = jest.fn().mockResolvedValue(5);
    const now = new Date('2026-04-20T12:00:00.000Z');

    const pruned = await pruneConversationHistory(dbPrune, 24, now);

    expect(pruned).toBe(5);
    expect(dbPrune).toHaveBeenCalledWith(new Date('2026-04-19T12:00:00.000Z'));
  });
});
