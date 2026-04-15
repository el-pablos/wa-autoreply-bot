import { beforeEach, describe, expect, jest, test } from '@jest/globals';

const mockGetSetting = jest.fn();
const mockIsAllowedNumber = jest.fn();
const mockSaveMessageLog = jest.fn();
const mockLogger = {
  info: jest.fn(),
  debug: jest.fn(),
  error: jest.fn(),
};

jest.unstable_mockModule('../src/db.js', () => ({
  getSetting: mockGetSetting,
  isAllowedNumber: mockIsAllowedNumber,
  saveMessageLog: mockSaveMessageLog,
}));

jest.unstable_mockModule('../src/utils/logger.js', () => ({
  logger: mockLogger,
}));

const { handleIncomingMessage } = await import('../src/handlers/messageHandler.js');

describe('handleIncomingMessage', () => {
  beforeEach(() => {
    jest.clearAllMocks();
  });

  test('abaikan pesan dari diri sendiri', async () => {
    const sock = { sendMessage: jest.fn() };
    const msg = { key: { fromMe: true, remoteJid: '6281@s.whatsapp.net' }, message: { conversation: 'halo' } };

    await handleIncomingMessage(sock, msg);

    expect(sock.sendMessage).not.toHaveBeenCalled();
    expect(mockSaveMessageLog).not.toHaveBeenCalled();
  });

  test('abaikan update tanpa message payload', async () => {
    const sock = { sendMessage: jest.fn() };
    const msg = { key: { fromMe: false, remoteJid: '6281@s.whatsapp.net' }, message: null };

    await handleIncomingMessage(sock, msg);

    expect(sock.sendMessage).not.toHaveBeenCalled();
    expect(mockSaveMessageLog).not.toHaveBeenCalled();
  });

  test('abaikan status broadcast', async () => {
    const sock = { sendMessage: jest.fn() };
    const msg = {
      key: { fromMe: false, remoteJid: 'status@broadcast' },
      message: { conversation: 'status update' },
    };

    await handleIncomingMessage(sock, msg);

    expect(mockIsAllowedNumber).not.toHaveBeenCalled();
    expect(mockSaveMessageLog).not.toHaveBeenCalled();
    expect(sock.sendMessage).not.toHaveBeenCalled();
  });

  test('pesan grup diabaikan saat ignore_groups=true tapi tetap dilog', async () => {
    const sock = { sendMessage: jest.fn() };
    const msg = {
      key: {
        fromMe: false,
        remoteJid: '120363025@g.us',
        participant: '628111111111@s.whatsapp.net',
      },
      message: { conversation: 'hai grup' },
    };

    mockGetSetting
      .mockResolvedValueOnce('true')
      .mockResolvedValueOnce('true')
      .mockResolvedValueOnce('balasan')
      .mockResolvedValueOnce('0');

    await handleIncomingMessage(sock, msg);

    expect(sock.sendMessage).not.toHaveBeenCalled();
    expect(mockSaveMessageLog).toHaveBeenCalledWith(
      expect.objectContaining({
        fromNumber: '628111111111',
        groupId: '120363025',
        isAllowed: false,
        replied: false,
      })
    );
  });

  test('kirim auto-reply ketika nomor allowed dan auto reply aktif', async () => {
    const sendMessage = jest.fn().mockResolvedValue(undefined);
    const sock = { sendMessage };
    const msg = {
      key: { fromMe: false, remoteJid: '628222222222@s.whatsapp.net' },
      message: { conversation: 'halo bot' },
    };

    mockGetSetting
      .mockResolvedValueOnce('true')
      .mockResolvedValueOnce('false')
      .mockResolvedValueOnce('Balasan test')
      .mockResolvedValueOnce('0');
    mockIsAllowedNumber.mockResolvedValueOnce(true);

    await handleIncomingMessage(sock, msg);

    expect(sendMessage).toHaveBeenCalledWith('628222222222@s.whatsapp.net', { text: 'Balasan test' });
    expect(mockSaveMessageLog).toHaveBeenCalledWith(
      expect.objectContaining({
        fromNumber: '628222222222',
        isAllowed: true,
        replied: true,
        replyText: 'Balasan test',
      })
    );
  });

  test('kirim auto-reply untuk JID dengan suffix device', async () => {
    const sendMessage = jest.fn().mockResolvedValue(undefined);
    const sock = { sendMessage };
    const msg = {
      key: { fromMe: false, remoteJid: '628777777777:87@s.whatsapp.net' },
      message: { conversation: 'halo dari multi-device' },
    };

    mockGetSetting
      .mockResolvedValueOnce('true')
      .mockResolvedValueOnce('false')
      .mockResolvedValueOnce('Balasan multi-device')
      .mockResolvedValueOnce('0');
    mockIsAllowedNumber.mockResolvedValueOnce(true);

    await handleIncomingMessage(sock, msg);

    expect(mockIsAllowedNumber).toHaveBeenCalledWith('628777777777');
    expect(sendMessage).toHaveBeenCalledWith('628777777777:87@s.whatsapp.net', { text: 'Balasan multi-device' });
    expect(mockSaveMessageLog).toHaveBeenCalledWith(
      expect.objectContaining({
        fromNumber: '628777777777',
        isAllowed: true,
        replied: true,
      })
    );
  });

  test('kirim auto-reply untuk key.senderPn meski remoteJid non-msisdn', async () => {
    const sendMessage = jest.fn().mockResolvedValue(undefined);
    const sock = { sendMessage };
    const msg = {
      key: {
        fromMe: false,
        remoteJid: '20233641300057@lid',
        senderPn: '+628555555555',
      },
      message: { conversation: 'halo dari lid' },
    };

    mockGetSetting
      .mockResolvedValueOnce('true')
      .mockResolvedValueOnce('false')
      .mockResolvedValueOnce('Balasan senderPn')
      .mockResolvedValueOnce('0');
    mockIsAllowedNumber.mockResolvedValueOnce(true);

    await handleIncomingMessage(sock, msg);

    expect(mockIsAllowedNumber).toHaveBeenCalledWith('628555555555');
    expect(sendMessage).toHaveBeenCalledWith('20233641300057@lid', { text: 'Balasan senderPn' });
    expect(mockSaveMessageLog).toHaveBeenCalledWith(
      expect.objectContaining({
        fromNumber: '628555555555',
        isAllowed: true,
        replied: true,
      })
    );
  });

  test('kirim auto-reply untuk key.participantPn pada pesan grup', async () => {
    const sendMessage = jest.fn().mockResolvedValue(undefined);
    const sock = { sendMessage };
    const msg = {
      key: {
        fromMe: false,
        remoteJid: '120363025@g.us',
        participant: '182773731@lid',
        participantPn: '081333333333',
      },
      message: { conversation: 'halo grup dari lid' },
    };

    mockGetSetting
      .mockResolvedValueOnce('true')
      .mockResolvedValueOnce('false')
      .mockResolvedValueOnce('Balasan participantPn')
      .mockResolvedValueOnce('0');
    mockIsAllowedNumber.mockResolvedValueOnce(true);

    await handleIncomingMessage(sock, msg);

    expect(mockIsAllowedNumber).toHaveBeenCalledWith('6281333333333');
    expect(sendMessage).toHaveBeenCalledWith('120363025@g.us', { text: 'Balasan participantPn' });
    expect(mockSaveMessageLog).toHaveBeenCalledWith(
      expect.objectContaining({
        fromNumber: '6281333333333',
        isAllowed: true,
        replied: true,
      })
    );
  });

  test('identifier non-msisdn tanpa PN ditandai unresolved dan tidak cek allowlist', async () => {
    const sendMessage = jest.fn();
    const sock = { sendMessage };
    const msg = {
      key: { fromMe: false, remoteJid: '20233641300057@lid' },
      message: { conversation: 'halo dari lid' },
    };

    mockGetSetting
      .mockResolvedValueOnce('true')
      .mockResolvedValueOnce('false')
      .mockResolvedValueOnce('reply text')
      .mockResolvedValueOnce('0');

    await handleIncomingMessage(sock, msg);

    expect(mockIsAllowedNumber).not.toHaveBeenCalled();
    expect(sendMessage).not.toHaveBeenCalled();
    expect(mockLogger.debug).toHaveBeenCalledWith(
      expect.objectContaining({
        unresolvedMarker: 'unresolved:20233641300057',
        senderSource: 'key.remoteJid',
      }),
      'Sender unresolved: auto-reply dilewati'
    );
    expect(mockSaveMessageLog).toHaveBeenCalledWith(
      expect.objectContaining({
        fromNumber: 'unresolved:20233641300057',
        isAllowed: false,
        replied: false,
      })
    );
  });

  test('tetap logging ketika kirim auto-reply gagal', async () => {
    const sendMessage = jest.fn().mockRejectedValue(new Error('send failed'));
    const sock = { sendMessage };
    const msg = {
      key: { fromMe: false, remoteJid: '628333333333@s.whatsapp.net' },
      message: { conversation: 'ping' },
    };

    mockGetSetting
      .mockResolvedValueOnce('true')
      .mockResolvedValueOnce('false')
      .mockResolvedValueOnce('fallback text')
      .mockResolvedValueOnce('0');
    mockIsAllowedNumber.mockResolvedValueOnce(true);

    await handleIncomingMessage(sock, msg);

    expect(mockLogger.error).toHaveBeenCalled();
    expect(mockSaveMessageLog).toHaveBeenCalledWith(
      expect.objectContaining({
        fromNumber: '628333333333',
        isAllowed: true,
        replied: false,
        replyText: 'fallback text',
      })
    );
  });

  test('tidak kirim balasan jika nomor tidak allowed', async () => {
    const sendMessage = jest.fn();
    const sock = { sendMessage };
    const msg = {
      key: { fromMe: false, remoteJid: '628444444444@s.whatsapp.net' },
      message: { conversation: 'halo' },
    };

    mockGetSetting
      .mockResolvedValueOnce('true')
      .mockResolvedValueOnce('false')
      .mockResolvedValueOnce('reply text')
      .mockResolvedValueOnce('0');
    mockIsAllowedNumber.mockResolvedValueOnce(false);

    await handleIncomingMessage(sock, msg);

    expect(sendMessage).not.toHaveBeenCalled();
    expect(mockSaveMessageLog).toHaveBeenCalledWith(
      expect.objectContaining({
        fromNumber: '628444444444',
        isAllowed: false,
        replied: false,
        replyText: null,
        groupId: null,
      })
    );
  });
});
