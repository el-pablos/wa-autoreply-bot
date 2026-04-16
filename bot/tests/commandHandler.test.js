import { beforeEach, describe, expect, jest, test } from '@jest/globals';

const mockGetSetting = jest.fn();
const mockUpsertApprovedSession = jest.fn();
const mockRevokeApprovedSession = jest.fn();
const mockGetActiveApprovedSessions = jest.fn();

jest.unstable_mockModule('../src/config.js', () => ({
  config: {
    env: 'test',
    bot: {
      ownerNumber: '628123456789',
      logLevel: 'silent',
    },
  },
}));

jest.unstable_mockModule('../src/db.js', () => ({
  getSetting: mockGetSetting,
  upsertApprovedSession: mockUpsertApprovedSession,
  revokeApprovedSession: mockRevokeApprovedSession,
  getActiveApprovedSessions: mockGetActiveApprovedSessions,
}));

jest.unstable_mockModule('../src/utils/logger.js', () => ({
  logger: {
    info: jest.fn(),
  },
}));

const {
  getRawText,
  isOwnerCommand,
  isBotCommand,
  parseCommand,
  extractTargetNumber,
  routeCommand,
} = await import('../src/handlers/commandHandler.js');

describe('commandHandler helpers', () => {
  test('getRawText ambil text conversation / extended', () => {
    expect(getRawText({ message: { conversation: '/help' } })).toBe('/help');
    expect(getRawText({ message: { extendedTextMessage: { text: '/status' } } })).toBe('/status');
  });

  test('isOwnerCommand true untuk fromMe atau owner number', () => {
    expect(isOwnerCommand({ key: { fromMe: true, remoteJid: '120363@g.us' } })).toBe(true);
    expect(isOwnerCommand({ key: { fromMe: false, remoteJid: '628123456789@s.whatsapp.net' } })).toBe(true);
    expect(isOwnerCommand({ key: { fromMe: false, remoteJid: '628000000000@s.whatsapp.net' } })).toBe(false);
  });

  test('isBotCommand mendeteksi prefix slash', () => {
    expect(isBotCommand({ message: { conversation: '/approve 6281' } })).toBe(true);
    expect(isBotCommand({ message: { conversation: 'halo' } })).toBe(false);
  });

  test('parseCommand memisahkan command dan args', () => {
    expect(parseCommand({ message: { conversation: '/approve 62811 test' } })).toEqual({
      command: 'approve',
      args: ['62811', 'test'],
    });
  });

  test('extractTargetNumber prioritas argumen eksplisit', () => {
    const msg = { key: { remoteJid: '120363@g.us' }, message: { conversation: '/approve 628111111111' } };
    expect(extractTargetNumber(msg, ['628111111111'])).toBe('628111111111');
  });

  test('extractTargetNumber dari quoted participant / private remoteJid', () => {
    expect(
      extractTargetNumber(
        {
          key: { remoteJid: '120363@g.us' },
          message: { extendedTextMessage: { contextInfo: { participant: '628777777777@s.whatsapp.net' } } },
        },
        []
      )
    ).toBe('628777777777');

    expect(
      extractTargetNumber(
        { key: { remoteJid: '628888888888@s.whatsapp.net' }, message: { conversation: '/approve' } },
        []
      )
    ).toBe('628888888888');
  });
});

describe('routeCommand', () => {
  beforeEach(() => {
    jest.clearAllMocks();
  });

  test('return false jika bukan owner', async () => {
    const sock = { sendMessage: jest.fn() };
    const msg = {
      key: { fromMe: false, remoteJid: '628000000000@s.whatsapp.net' },
      message: { conversation: '/status' },
    };
    await expect(routeCommand(sock, msg)).resolves.toBe(false);
    expect(sock.sendMessage).not.toHaveBeenCalled();
  });

  test('return false jika owner tapi bukan command', async () => {
    const sock = { sendMessage: jest.fn() };
    const msg = {
      key: { fromMe: true, remoteJid: '628123456789@s.whatsapp.net' },
      message: { conversation: 'halo' },
    };
    await expect(routeCommand(sock, msg)).resolves.toBe(false);
  });

  test('/approve sukses', async () => {
    const sock = { sendMessage: jest.fn().mockResolvedValue(undefined) };
    const msg = {
      key: { fromMe: true, remoteJid: '628123456789@s.whatsapp.net' },
      message: { conversation: '/approve 628111111111' },
    };

    mockGetSetting.mockResolvedValueOnce('24');
    mockUpsertApprovedSession.mockResolvedValueOnce({
      action: 'created',
      expiresAt: new Date('2026-01-01T10:00:00.000Z'),
    });

    await expect(routeCommand(sock, msg)).resolves.toBe(true);
    expect(mockUpsertApprovedSession).toHaveBeenCalledWith('628111111111', '628123456789', 24);
    expect(sock.sendMessage).toHaveBeenCalled();
  });

  test('/approve gagal karena target tidak ada', async () => {
    const sock = { sendMessage: jest.fn().mockResolvedValue(undefined) };
    const msg = {
      key: { fromMe: true, remoteJid: '120363025@g.us' },
      message: { conversation: '/approve' },
    };

    await expect(routeCommand(sock, msg)).resolves.toBe(true);
    expect(mockUpsertApprovedSession).not.toHaveBeenCalled();
    expect(sock.sendMessage).toHaveBeenCalledWith(
      '120363025@g.us',
      expect.objectContaining({ text: expect.stringContaining('Nomor target tidak ditemukan') })
    );
  });

  test('/revoke validasi format', async () => {
    const sock = { sendMessage: jest.fn().mockResolvedValue(undefined) };
    const msg = {
      key: { fromMe: true, remoteJid: '120363025@g.us' },
      message: { conversation: '/revoke' },
    };

    await expect(routeCommand(sock, msg)).resolves.toBe(true);
    expect(sock.sendMessage).toHaveBeenCalledWith(
      '120363025@g.us',
      expect.objectContaining({ text: expect.stringContaining('/revoke 628') })
    );
  });

  test('/revoke ketika tidak ada sesi aktif', async () => {
    const sock = { sendMessage: jest.fn().mockResolvedValue(undefined) };
    const msg = {
      key: { fromMe: true, remoteJid: '628123456789@s.whatsapp.net' },
      message: { conversation: '/revoke 628111111111' },
    };

    mockRevokeApprovedSession.mockResolvedValueOnce(false);
    await expect(routeCommand(sock, msg)).resolves.toBe(true);
    expect(sock.sendMessage).toHaveBeenCalledWith(
      '628123456789@s.whatsapp.net',
      expect.objectContaining({ text: expect.stringContaining('Tidak ada approved session aktif') })
    );
  });

  test('/status menampilkan data dan empty state', async () => {
    const sock = { sendMessage: jest.fn().mockResolvedValue(undefined) };
    const msg = {
      key: { fromMe: true, remoteJid: '628123456789@s.whatsapp.net' },
      message: { conversation: '/status' },
    };

    mockGetActiveApprovedSessions.mockResolvedValueOnce([
      { phone_number: '628111111111', expires_at: '2026-01-01T10:00:00.000Z' },
    ]);
    await expect(routeCommand(sock, msg)).resolves.toBe(true);
    expect(sock.sendMessage).toHaveBeenCalledWith(
      '628123456789@s.whatsapp.net',
      expect.objectContaining({ text: expect.stringContaining('628111111111') })
    );

    mockGetActiveApprovedSessions.mockResolvedValueOnce([]);
    await expect(routeCommand(sock, msg)).resolves.toBe(true);
    expect(sock.sendMessage).toHaveBeenLastCalledWith(
      '628123456789@s.whatsapp.net',
      expect.objectContaining({ text: expect.stringContaining('Tidak ada approved session') })
    );
  });

  test('/help dan unknown command', async () => {
    const sock = { sendMessage: jest.fn().mockResolvedValue(undefined) };
    const helpMsg = {
      key: { fromMe: true, remoteJid: '628123456789@s.whatsapp.net' },
      message: { conversation: '/help' },
    };
    const unknownMsg = {
      key: { fromMe: true, remoteJid: '628123456789@s.whatsapp.net' },
      message: { conversation: '/abc' },
    };

    await expect(routeCommand(sock, helpMsg)).resolves.toBe(true);
    await expect(routeCommand(sock, unknownMsg)).resolves.toBe(true);
    expect(sock.sendMessage).toHaveBeenCalledWith(
      '628123456789@s.whatsapp.net',
      expect.objectContaining({ text: expect.stringContaining('Daftar Command') })
    );
    expect(sock.sendMessage).toHaveBeenCalledWith(
      '628123456789@s.whatsapp.net',
      expect.objectContaining({ text: expect.stringContaining('/abc') })
    );
  });
});
