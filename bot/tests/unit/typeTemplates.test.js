import { describe, expect, test } from '@jest/globals';
import {
  buildTypeTemplatesCache,
  resolveTypeTemplate,
} from '../../src/utils/typeTemplates.js';

describe('typeTemplates.resolveTypeTemplate', () => {
  test('ambil template langsung sesuai type', () => {
    const cache = new Map([
      ['text', 'Template text'],
      ['image', 'Template image'],
    ]);

    expect(resolveTypeTemplate('image', cache)).toBe('Template image');
  });

  test('unknown type fallback ke text', () => {
    const cache = { text: 'Template default', other: 'Template other' };
    expect(resolveTypeTemplate('poll', cache)).toBe('Template default');
  });

  test('known type yang kosong fallback ke other', () => {
    const cache = { other: 'Template other' };
    expect(resolveTypeTemplate('video', cache)).toBe('Template other');
  });

  test('return null jika cache tidak ada', () => {
    expect(resolveTypeTemplate('text', null)).toBeNull();
  });
});

describe('typeTemplates.buildTypeTemplatesCache', () => {
  test('build map dari array row aktif', () => {
    const rows = [
      { message_type: 'text', body: 'Halo text', is_active: 1 },
      { message_type: 'image', body: 'Halo image', is_active: 0 },
    ];

    const cache = buildTypeTemplatesCache(rows);
    expect(cache.get('text')).toBe('Halo text');
    expect(cache.has('image')).toBe(false);
  });

  test('build map dari object literal', () => {
    const cache = buildTypeTemplatesCache({ text: 'A', other: 'B' });
    expect(cache.get('text')).toBe('A');
    expect(cache.get('other')).toBe('B');
  });
});
