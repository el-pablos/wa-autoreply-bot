import { extractMessageContent } from '../src/handlers/messageHandler.js';

describe('extractMessageContent', () => {
  test('ekstrak teks dari conversation biasa', () => {
    const msg = { message: { conversation: 'Halo!' } };
    expect(extractMessageContent(msg)).toEqual({ text: 'Halo!', type: 'text' });
  });

  test('ekstrak teks dari extendedTextMessage', () => {
    const msg = { message: { extendedTextMessage: { text: 'Extended text' } } };
    expect(extractMessageContent(msg)).toEqual({ text: 'Extended text', type: 'text' });
  });

  test('ekstrak caption dari imageMessage', () => {
    const msg = { message: { imageMessage: { caption: 'Caption gambar' } } };
    expect(extractMessageContent(msg)).toEqual({ text: 'Caption gambar', type: 'image' });
  });

  test('ekstrak caption dari videoMessage', () => {
    const msg = { message: { videoMessage: { caption: 'Caption video' } } };
    expect(extractMessageContent(msg)).toEqual({ text: 'Caption video', type: 'video' });
  });

  test('return [Pesan Suara] untuk audioMessage', () => {
    const msg = { message: { audioMessage: {} } };
    expect(extractMessageContent(msg)).toEqual({ text: '[Pesan Suara]', type: 'audio' });
  });

  test('return [Sticker] untuk stickerMessage', () => {
    const msg = { message: { stickerMessage: {} } };
    expect(extractMessageContent(msg)).toEqual({ text: '[Sticker]', type: 'sticker' });
  });

  test('return [Lokasi] untuk locationMessage', () => {
    const msg = { message: { locationMessage: {} } };
    expect(extractMessageContent(msg)).toEqual({ text: '[Lokasi]', type: 'location' });
  });

  test('return [Kontak] untuk contactMessage', () => {
    const msg = { message: { contactMessage: {} } };
    expect(extractMessageContent(msg)).toEqual({ text: '[Kontak]', type: 'contact' });
  });

  test('return unknown untuk message null', () => {
    const msg = { message: null };
    expect(extractMessageContent(msg)).toEqual({ text: '', type: 'unknown' });
  });

  test('return unknown untuk message kosong', () => {
    const msg = { message: {} };
    expect(extractMessageContent(msg)).toEqual({ text: '[Pesan Tidak Dikenal]', type: 'other' });
  });

  test('ekstrak reaksi dari reactionMessage', () => {
    const msg = { message: { reactionMessage: { text: '❤️' } } };
    const result = extractMessageContent(msg);
    expect(result.type).toBe('reaction');
    expect(result.text).toContain('❤️');
  });
});
