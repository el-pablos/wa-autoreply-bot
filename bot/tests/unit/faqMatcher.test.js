import { describe, expect, jest, test } from '@jest/globals';
import {
  invalidateKnowledgeBaseCache,
  levenshtein,
  loadKnowledgeBase,
  matchFaq,
  similarity,
} from '../../src/utils/faqMatcher.js';

describe('faqMatcher.matchFaq', () => {
  const entries = [
    {
      id: 1,
      question: 'Berapa harga website company profile?',
      keywords: ['harga website', 'biaya website'],
      answer: 'Mulai dari 2 juta.',
      is_active: true,
    },
    {
      id: 2,
      question: 'Jam operasional berapa?',
      keywords: ['jam buka', 'operasional'],
      answer: 'Kami buka Senin-Jumat 09:00-17:00.',
      is_active: true,
    },
  ];

  test('match keyword exact langsung confidence 1', () => {
    const out = matchFaq('boleh info harga website dong', entries);
    expect(out).toEqual({ answer: 'Mulai dari 2 juta.', confidence: 1, matched_id: 1 });
  });

  test('match fuzzy berdasarkan question', () => {
    const out = matchFaq('jam operasional nya kapan', entries, 0.4);
    expect(out).toEqual(
      expect.objectContaining({ matched_id: 2, answer: 'Kami buka Senin-Jumat 09:00-17:00.' }),
    );
  });

  test('return null saat tidak lolos threshold', () => {
    const out = matchFaq('random banget', entries, 0.95);
    expect(out).toBeNull();
  });
});

describe('faqMatcher.loadKnowledgeBase', () => {
  test('menggunakan cache jika force=false', async () => {
    invalidateKnowledgeBaseCache();
    const dbLookup = jest.fn().mockResolvedValue([
      { id: 1, question: 'Q', keywords: '["a"]', answer: 'A', is_active: 1 },
    ]);

    const one = await loadKnowledgeBase(dbLookup);
    const two = await loadKnowledgeBase(dbLookup);

    expect(one).toHaveLength(1);
    expect(two).toHaveLength(1);
    expect(dbLookup).toHaveBeenCalledTimes(1);
  });

  test('force=true bypass cache', async () => {
    invalidateKnowledgeBaseCache();
    const dbLookup = jest.fn().mockResolvedValue([]);

    await loadKnowledgeBase(dbLookup, { force: true });
    await loadKnowledgeBase(dbLookup, { force: true });

    expect(dbLookup).toHaveBeenCalledTimes(2);
  });
});

describe('faqMatcher.distance helpers', () => {
  test('levenshtein dan similarity dasar', () => {
    expect(levenshtein('kitten', 'sitting')).toBe(3);
    expect(similarity('abc', 'abc')).toBe(1);
    expect(similarity('abc', 'xyz')).toBe(0);
  });
});
