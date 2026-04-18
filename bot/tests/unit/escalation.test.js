import { describe, expect, test } from '@jest/globals';
import {
  clearAllEscalationCooldown,
  evaluateEscalation,
  matchEscalationKeyword,
} from '../../src/utils/escalation.js';

describe('escalation.matchEscalationKeyword', () => {
  test('return keyword yang match', () => {
    const keyword = matchEscalationKeyword('saya mau komplain layanan', [
      'komplain',
      'refund',
    ]);
    expect(keyword).toBe('komplain');
  });

  test('return null jika tidak ada match', () => {
    const keyword = matchEscalationKeyword('halo biasa', ['komplain']);
    expect(keyword).toBeNull();
  });
});

describe('escalation.evaluateEscalation', () => {
  test('trigger pada match pertama', () => {
    clearAllEscalationCooldown();

    const out = evaluateEscalation({
      phoneNumber: '6281',
      messageText: 'tolong refund dong',
      keywords: ['refund'],
      nowMs: 1000,
      cooldownMs: 5000,
    });

    expect(out).toEqual(
      expect.objectContaining({
        triggered: true,
        keyword: 'refund',
        reason: 'triggered',
      }),
    );
  });

  test('kena cooldown untuk nomor yang sama', () => {
    clearAllEscalationCooldown();

    evaluateEscalation({
      phoneNumber: '6281',
      messageText: 'komplain',
      keywords: ['komplain'],
      nowMs: 1000,
      cooldownMs: 5000,
    });

    const out = evaluateEscalation({
      phoneNumber: '6281',
      messageText: 'komplain lagi',
      keywords: ['komplain'],
      nowMs: 2000,
      cooldownMs: 5000,
    });

    expect(out).toEqual(
      expect.objectContaining({
        triggered: false,
        reason: 'cooldown',
        keyword: 'komplain',
      }),
    );
  });

  test('tidak trigger saat tidak ada keyword', () => {
    clearAllEscalationCooldown();

    const out = evaluateEscalation({
      phoneNumber: '6281',
      messageText: 'halo biasa',
      keywords: ['komplain'],
      nowMs: 1000,
    });

    expect(out).toEqual(
      expect.objectContaining({
        triggered: false,
        reason: 'no_keyword',
      }),
    );
  });
});
