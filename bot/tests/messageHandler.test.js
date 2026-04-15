import { extractMessageContent, normalizePhoneNumber, resolveSenderIdentity } from '../src/handlers/messageHandler.js';

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

describe('normalizePhoneNumber', () => {
  test('normalisasi JID standar', () => {
    expect(normalizePhoneNumber('628123456789@s.whatsapp.net')).toBe('628123456789');
  });

  test('normalisasi JID dengan suffix device', () => {
    expect(normalizePhoneNumber('628123456789:72@s.whatsapp.net')).toBe('628123456789');
  });

  test('normalisasi nomor lokal diawali 0', () => {
    expect(normalizePhoneNumber('08123456789@s.whatsapp.net')).toBe('628123456789');
  });

  test('normalisasi nomor +62', () => {
    expect(normalizePhoneNumber('+628123456789')).toBe('628123456789');
  });

  test('identifier non-msisdn menghasilkan string kosong', () => {
    expect(normalizePhoneNumber('20233641300057@lid')).toBe('');
  });
});

describe('resolveSenderIdentity', () => {
  test('prioritaskan key.senderPn dibanding remoteJid non-msisdn', () => {
    const msg = {
      key: {
        remoteJid: '20233641300057@lid',
        senderPn: '+628111111111',
      },
      message: { conversation: 'halo' },
    };

    expect(resolveSenderIdentity(msg)).toEqual(
      expect.objectContaining({
        phoneNumber: '628111111111',
        senderRef: '628111111111',
        senderSource: 'key.senderPn',
        isFallback: false,
      })
    );
  });

  test('prioritaskan key.participantPn untuk pesan grup', () => {
    const msg = {
      key: {
        remoteJid: '120363025@g.us',
        participant: '182773731@lid',
        participantPn: '081222222222',
      },
      message: { conversation: 'halo grup' },
    };

    expect(resolveSenderIdentity(msg)).toEqual(
      expect.objectContaining({
        phoneNumber: '6281222222222',
        senderRef: '6281222222222',
        senderSource: 'key.participantPn',
        isFallback: false,
      })
    );
  });

  test('ambil nomor dari JID standar', () => {
    const msg = {
      key: { remoteJid: '628123456789@s.whatsapp.net' },
      message: { conversation: 'halo' },
    };

    expect(resolveSenderIdentity(msg)).toEqual(
      expect.objectContaining({
        phoneNumber: '628123456789',
        senderRef: '628123456789',
        senderSource: 'key.remoteJid',
        isFallback: false,
      })
    );
  });

  test('ambil nomor dari JID multi-device', () => {
    const msg = {
      key: { remoteJid: '628123456789:17@s.whatsapp.net' },
      message: { conversation: 'halo md' },
    };

    expect(resolveSenderIdentity(msg)).toEqual(
      expect.objectContaining({
        phoneNumber: '628123456789',
        senderRef: '628123456789',
        isFallback: false,
      })
    );
  });

  test('identifier lid/non-62 fallback unresolved dan tidak dianggap msisdn', () => {
    const msg = {
      key: { remoteJid: '20233641300057@lid' },
      message: { conversation: 'halo lid' },
    };

    expect(resolveSenderIdentity(msg)).toEqual(
      expect.objectContaining({
        phoneNumber: '',
        senderRef: 'unresolved:20233641300057',
        senderSource: 'key.remoteJid',
        isFallback: true,
      })
    );
  });
});
