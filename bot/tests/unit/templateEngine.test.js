import { describe, expect, test } from '@jest/globals';
import { renderTemplate, validateTemplate } from '../../src/utils/templateEngine.js';

describe('templateEngine.renderTemplate', () => {
  test('happy path: ganti semua variabel umum', () => {
    const tpl =
      'Halo {{nama}}, sekarang jam {{jam}} hari {{hari}}, label {{label}}, jenis {{jenis_pesan}}, tgl {{tanggal}}.';
    const out = renderTemplate(tpl, {
      nama: 'Budi',
      jam: '14:30',
      hari: 'Senin',
      label: 'VIP',
      jenis_pesan: 'text',
      tanggal: '2026-04-19',
    });
    expect(out).toBe(
      'Halo Budi, sekarang jam 14:30 hari Senin, label VIP, jenis text, tgl 2026-04-19.'
    );
  });

  test('variabel tidak ditemukan → diganti string kosong', () => {
    const out = renderTemplate('Hi {{nama}}, {{tidakada}} mantap', { nama: 'Tam' });
    expect(out).toBe('Hi Tam,  mantap');
  });

  test('kondisional true: blok dirender', () => {
    const tpl = 'Halo{{#if vip === true}} (member VIP){{/if}}!';
    expect(renderTemplate(tpl, { vip: true })).toBe('Halo (member VIP)!');
  });

  test('kondisional false: blok dihapus', () => {
    const tpl = 'Halo{{#if vip === true}} (member VIP){{/if}}!';
    expect(renderTemplate(tpl, { vip: false })).toBe('Halo!');
  });

  test('kondisional dengan operator numerik >=', () => {
    const tpl = '{{#if jam >= 17}}Selamat sore{{/if}}{{#if jam < 17}}Masih siang{{/if}}';
    expect(renderTemplate(tpl, { jam: 18 })).toBe('Selamat sore');
    expect(renderTemplate(tpl, { jam: 10 })).toBe('Masih siang');
  });

  test('kondisional dengan operator !== dan string literal', () => {
    const tpl = "{{#if label !== 'biasa'}}Spesial {{label}}{{/if}}";
    expect(renderTemplate(tpl, { label: 'VIP' })).toBe('Spesial VIP');
    expect(renderTemplate(tpl, { label: 'biasa' })).toBe('');
  });

  test('invalid syntax: {{#if}} tanpa penutup', () => {
    expect(() => renderTemplate('Halo {{#if x === 1}}dunia', {})).toThrow(SyntaxError);
  });

  test('invalid syntax: {{/if}} tanpa pembuka', () => {
    expect(() => renderTemplate('Halo dunia{{/if}}', {})).toThrow(SyntaxError);
  });

  test('template bukan string → TypeError', () => {
    expect(() => renderTemplate(123, {})).toThrow(TypeError);
  });
});

describe('templateEngine.validateTemplate', () => {
  test('template valid mengembalikan list variabel unik', () => {
    const result = validateTemplate('Hi {{nama}}, umur {{nama}} dan kota {{kota}}');
    expect(result.ok).toBe(true);
    expect(result.variables.sort()).toEqual(['kota', 'nama']);
  });

  test('template invalid → throw SyntaxError', () => {
    expect(() => validateTemplate('{{#if x > 1}}tanpa tutup')).toThrow(SyntaxError);
  });
});
