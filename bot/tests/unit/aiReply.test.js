import { describe, expect, jest, test } from '@jest/globals';
import {
  assembleMessages,
  generateAiReply,
  parseModelSpec,
} from '../../src/utils/aiReply.js';

describe('aiReply.parseModelSpec', () => {
  test('default provider groq jika tanpa prefix', () => {
    expect(parseModelSpec('llama-3')).toEqual({ provider: 'groq', modelName: 'llama-3' });
  });

  test('parse provider:model', () => {
    expect(parseModelSpec('openai:gpt-4o-mini')).toEqual({
      provider: 'openai',
      modelName: 'gpt-4o-mini',
    });
  });

  test('throw jika provider unsupported', () => {
    expect(() => parseModelSpec('foo:model')).toThrow(/tidak didukung/);
  });
});

describe('aiReply.assembleMessages', () => {
  test('map role bot -> assistant dan append user message', () => {
    const out = assembleMessages(
      'system prompt',
      [
        { role: 'user', content: 'halo' },
        { role: 'bot', content: 'hai juga' },
      ],
      'lanjut',
    );

    expect(out).toEqual([
      { role: 'system', content: 'system prompt' },
      { role: 'user', content: 'halo' },
      { role: 'assistant', content: 'hai juga' },
      { role: 'user', content: 'lanjut' },
    ]);
  });
});

describe('aiReply.generateAiReply', () => {
  test('sukses generate dengan clientOverride', async () => {
    const create = jest.fn().mockResolvedValue({
      choices: [{ message: { content: '  halo balik  ' } }],
      usage: { total_tokens: 88 },
    });

    const out = await generateAiReply({
      userMessage: 'halo',
      model: 'groq:llama-3.3-70b-versatile',
      clientOverride: { chat: { completions: { create } } },
    });

    expect(create).toHaveBeenCalled();
    expect(out).toEqual(
      expect.objectContaining({
        content: 'halo balik',
        tokens: 88,
        provider: 'groq',
        model: 'llama-3.3-70b-versatile',
      }),
    );
  });

  test('throw jika response kosong', async () => {
    const create = jest.fn().mockResolvedValue({ choices: [{ message: { content: '' } }] });

    await expect(
      generateAiReply({
        userMessage: 'halo',
        clientOverride: { chat: { completions: { create } } },
      }),
    ).rejects.toThrow(/Response kosong/);
  });

  test('throw jika userMessage tidak valid', async () => {
    await expect(generateAiReply({ userMessage: null, apiKey: 'x' })).rejects.toThrow(TypeError);
  });

  test('throw jika tidak ada apiKey dan clientOverride', async () => {
    await expect(generateAiReply({ userMessage: 'halo' })).rejects.toThrow(/apiKey wajib/);
  });
});
