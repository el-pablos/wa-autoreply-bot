import { beforeEach, describe, expect, jest, test } from '@jest/globals';

const mockGetSetting = jest.fn();
const mockIsAllowedNumber = jest.fn();
const mockIsInApprovedSession = jest.fn();
const mockRefreshApprovedSession = jest.fn();
const mockSaveMessageLog = jest.fn();
const mockGetBlacklistEntry = jest.fn();
const mockSaveRateLimitViolation = jest.fn();
const mockGetActiveTemplate = jest.fn();
const mockGetMessageTypeTemplates = jest.fn();
const mockGetBusinessHourSchedules = jest.fn();
const mockGetActiveOofSchedules = jest.fn();

const mockLogger = {
  info: jest.fn(),
  debug: jest.fn(),
  error: jest.fn(),
};

jest.unstable_mockModule('../src/db.js', () => ({
  getSetting: mockGetSetting,
  isAllowedNumber: mockIsAllowedNumber,
  isInApprovedSession: mockIsInApprovedSession,
  refreshApprovedSession: mockRefreshApprovedSession,
  saveMessageLog: mockSaveMessageLog,
  getBlacklistEntry: mockGetBlacklistEntry,
  saveRateLimitViolation: mockSaveRateLimitViolation,
  getActiveTemplate: mockGetActiveTemplate,
  getMessageTypeTemplates: mockGetMessageTypeTemplates,
  getBusinessHourSchedules: mockGetBusinessHourSchedules,
  getActiveOofSchedules: mockGetActiveOofSchedules,
}));

jest.unstable_mockModule('../src/utils/logger.js', () => ({
  logger: mockLogger,
}));

const {
  handleIncomingMessage,
  __resetMessagePipelineStateForTest,
} = await import('../src/handlers/messageHandler.js');

describe('handleIncomingMessage', () => {
  beforeEach(() => {
    jest.clearAllMocks();
    __resetMessagePipelineStateForTest();

    mockIsAllowedNumber.mockResolvedValue(false);
    mockIsInApprovedSession.mockResolvedValue(false);
    mockRefreshApprovedSession.mockResolvedValue(true);
    mockSaveMessageLog.mockResolvedValue(1);
    mockGetBlacklistEntry.mockResolvedValue(null);
    mockSaveRateLimitViolation.mockResolvedValue(1);
    mockGetActiveTemplate.mockResolvedValue(null);
    mockGetMessageTypeTemplates.mockResolvedValue([]);
    mockGetBusinessHourSchedules.mockResolvedValue([]);
    mockGetActiveOofSchedules.mockResolvedValue([]);
  });

  function useSettings(overrides = {}) {
    const map = {
      auto_reply_enabled: 'true',
      ignore_groups: 'false',
      reply_message: 'Balasan default',
      reply_delay_ms: '0',
      rate_limit_enabled: 'false',
      rate_limit_window_seconds: '60',
      rate_limit_max_messages: '5',
      business_hours_enabled: 'false',
      outside_business_hours_message: 'di luar jam kerja',
      oof_enabled: 'false',
      human_typing_enabled: 'false',
      approve_expiry_hours: '24',
      ...overrides,
    };

    mockGetSetting.mockImplementation(async (key) => {
      if (Object.prototype.hasOwnProperty.call(map, key)) return map[key];
      return null;
    });
  }

  test('skip when message is from bot itself', async () => {
    const sock = { sendMessage: jest.fn() };
    const msg = {
      key: { fromMe: true, remoteJid: '628100000000@s.whatsapp.net' },
      message: { conversation: 'halo' },
    };

    await handleIncomingMessage(sock, msg);

    expect(sock.sendMessage).not.toHaveBeenCalled();
    expect(mockSaveMessageLog).not.toHaveBeenCalled();
  });

  test('skip when payload has no message body', async () => {
    const sock = { sendMessage: jest.fn() };
    const msg = {
      key: { fromMe: false, remoteJid: '628100000000@s.whatsapp.net' },
      message: null,
    };

    await handleIncomingMessage(sock, msg);

    expect(sock.sendMessage).not.toHaveBeenCalled();
    expect(mockSaveMessageLog).not.toHaveBeenCalled();
  });

  test('skip status broadcast messages', async () => {
    const sock = { sendMessage: jest.fn() };
    const msg = {
      key: { fromMe: false, remoteJid: 'status@broadcast' },
      message: { conversation: 'status' },
    };

    await handleIncomingMessage(sock, msg);

    expect(mockIsAllowedNumber).not.toHaveBeenCalled();
    expect(mockSaveMessageLog).not.toHaveBeenCalled();
  });

  test('group message ignored when ignore_groups=true but still logged', async () => {
    useSettings({ ignore_groups: 'true' });

    const sock = { sendMessage: jest.fn() };
    const msg = {
      key: {
        fromMe: false,
        remoteJid: '120363025@g.us',
        participant: '628111111111@s.whatsapp.net',
      },
      message: { conversation: 'pesan grup' },
    };

    await handleIncomingMessage(sock, msg);

    expect(sock.sendMessage).not.toHaveBeenCalled();
    expect(mockSaveMessageLog).toHaveBeenCalledWith(
      expect.objectContaining({
        fromNumber: '628111111111',
        groupId: '120363025',
        replied: false,
      }),
    );
  });

  test('send auto reply for allowed number', async () => {
    useSettings({ reply_message: 'Balasan test' });
    mockIsAllowedNumber.mockResolvedValueOnce(true);

    const sendMessage = jest.fn().mockResolvedValue(undefined);
    const sock = { sendMessage };
    const msg = {
      key: { fromMe: false, remoteJid: '628222222222@s.whatsapp.net' },
      message: { conversation: 'halo bot' },
    };

    await handleIncomingMessage(sock, msg);

    expect(sendMessage).toHaveBeenCalledWith('628222222222@s.whatsapp.net', { text: 'Balasan test' });
    expect(mockSaveMessageLog).toHaveBeenCalledWith(
      expect.objectContaining({
        fromNumber: '628222222222',
        isAllowed: true,
        replied: true,
        replyText: 'Balasan test',
      }),
    );
  });

  test('senderPn is used when remote JID is non-msisdn', async () => {
    useSettings({ reply_message: 'Balasan senderPn' });
    mockIsAllowedNumber.mockResolvedValueOnce(true);

    const sendMessage = jest.fn().mockResolvedValue(undefined);
    const sock = { sendMessage };
    const msg = {
      key: {
        fromMe: false,
        remoteJid: '20233641300057@lid',
        senderPn: '+628555555555',
      },
      message: { conversation: 'halo' },
    };

    await handleIncomingMessage(sock, msg);

    expect(mockIsAllowedNumber).toHaveBeenCalledWith('628555555555');
    expect(sendMessage).toHaveBeenCalledWith('20233641300057@lid', { text: 'Balasan senderPn' });
  });

  test('unresolved non-msisdn marker does not trigger allowlist check', async () => {
    useSettings({ reply_message: 'unused' });

    const sendMessage = jest.fn();
    const sock = { sendMessage };
    const msg = {
      key: { fromMe: false, remoteJid: '20233641300057@lid' },
      message: { conversation: 'halo lid' },
    };

    await handleIncomingMessage(sock, msg);

    expect(mockIsAllowedNumber).not.toHaveBeenCalled();
    expect(sendMessage).not.toHaveBeenCalled();
    expect(mockSaveMessageLog).toHaveBeenCalledWith(
      expect.objectContaining({
        fromNumber: 'unresolved:20233641300057',
        isAllowed: false,
        replied: false,
      }),
    );
  });

  test('approved session skips auto reply and refreshes expiry', async () => {
    useSettings({ approve_expiry_hours: '12' });
    mockIsAllowedNumber.mockResolvedValueOnce(true);
    mockIsInApprovedSession.mockResolvedValueOnce(true);

    const sendMessage = jest.fn();
    const sock = { sendMessage };
    const msg = {
      key: { fromMe: false, remoteJid: '628999999999@s.whatsapp.net' },
      message: { conversation: 'halo owner' },
    };

    await handleIncomingMessage(sock, msg);

    expect(mockRefreshApprovedSession).toHaveBeenCalledWith('628999999999', 12);
    expect(sendMessage).not.toHaveBeenCalled();
    expect(mockSaveMessageLog).toHaveBeenCalledWith(
      expect.objectContaining({
        fromNumber: '628999999999',
        replied: false,
      }),
    );
  });

  test('blacklisted sender is logged without reply', async () => {
    useSettings();
    mockGetBlacklistEntry.mockResolvedValueOnce({
      id: 1,
      phone_number: '628555111111',
      is_active: 1,
      unblock_at: null,
    });

    const sendMessage = jest.fn();
    const sock = { sendMessage };
    const msg = {
      key: { fromMe: false, remoteJid: '628555111111@s.whatsapp.net' },
      message: { conversation: 'halo' },
    };

    await handleIncomingMessage(sock, msg);

    expect(sendMessage).not.toHaveBeenCalled();
    expect(mockSaveMessageLog).toHaveBeenCalledWith(
      expect.objectContaining({
        fromNumber: '628555111111',
        replied: false,
      }),
    );
  });

  test('rate limit blocks second message in same window', async () => {
    useSettings({
      rate_limit_enabled: 'true',
      rate_limit_window_seconds: '60',
      rate_limit_max_messages: '1',
      reply_message: 'reply text',
    });
    mockIsAllowedNumber.mockResolvedValue(true);

    const sendMessage = jest.fn().mockResolvedValue(undefined);
    const sock = { sendMessage };
    const msg = {
      key: { fromMe: false, remoteJid: '628666111111@s.whatsapp.net' },
      message: { conversation: 'halo lagi' },
    };

    await handleIncomingMessage(sock, msg);
    await handleIncomingMessage(sock, msg);

    expect(sendMessage).toHaveBeenCalledTimes(1);
    expect(mockSaveRateLimitViolation).toHaveBeenCalledTimes(1);
  });

  test('oof schedule message overrides reply template', async () => {
    useSettings({ oof_enabled: 'true', reply_message: 'fallback' });
    mockIsAllowedNumber.mockResolvedValueOnce(true);
    mockGetActiveOofSchedules.mockResolvedValueOnce([
      {
        id: 1,
        start_date: '2025-01-01',
        end_date: '2099-12-31',
        message: 'Tim sedang libur',
        is_active: 1,
      },
    ]);

    const sendMessage = jest.fn().mockResolvedValue(undefined);
    const sock = { sendMessage };
    const msg = {
      key: { fromMe: false, remoteJid: '628700000003@s.whatsapp.net' },
      message: { conversation: 'halo jam kerja?' },
    };

    await handleIncomingMessage(sock, msg);

    expect(sendMessage).toHaveBeenCalledWith('628700000003@s.whatsapp.net', {
      text: 'Tim sedang libur',
    });
  });

  test('outside business hours message overrides reply', async () => {
    useSettings({
      business_hours_enabled: 'true',
      outside_business_hours_message: 'di luar jam kerja',
      reply_message: 'fallback default',
    });
    mockIsAllowedNumber.mockResolvedValueOnce(true);
    mockGetBusinessHourSchedules.mockResolvedValueOnce([
      {
        weekday: 1,
        start_time: '23:00:00',
        end_time: '01:00:00',
        timezone: 'Asia/Jakarta',
        is_active: 1,
      },
    ]);

    const sendMessage = jest.fn().mockResolvedValue(undefined);
    const sock = { sendMessage };
    const msg = {
      key: { fromMe: false, remoteJid: '628700000004@s.whatsapp.net' },
      message: { conversation: 'halo jam kerja?' },
    };

    await handleIncomingMessage(sock, msg);

    expect(sendMessage).toHaveBeenCalledWith('628700000004@s.whatsapp.net', {
      text: 'di luar jam kerja',
    });
  });

  test('message type template has highest priority over active/default template', async () => {
    useSettings({ reply_message: 'reply default' });
    mockIsAllowedNumber.mockResolvedValueOnce(true);
    mockGetActiveTemplate.mockResolvedValueOnce({ body: 'reply from active template' });
    mockGetMessageTypeTemplates.mockResolvedValueOnce([
      { message_type: 'audio', body: 'Balasan khusus audio', is_active: 1 },
    ]);

    const sendMessage = jest.fn().mockResolvedValue(undefined);
    const sock = { sendMessage };
    const msg = {
      key: { fromMe: false, remoteJid: '628700000005@s.whatsapp.net' },
      message: { audioMessage: {} },
    };

    await handleIncomingMessage(sock, msg);

    expect(sendMessage).toHaveBeenCalledWith('628700000005@s.whatsapp.net', {
      text: 'Balasan khusus audio',
    });
  });

  test('failed send still writes message log with replied=false', async () => {
    useSettings({ reply_message: 'fallback text' });
    mockIsAllowedNumber.mockResolvedValueOnce(true);

    const sendMessage = jest.fn().mockRejectedValue(new Error('send failed'));
    const sock = { sendMessage };
    const msg = {
      key: { fromMe: false, remoteJid: '628333333333@s.whatsapp.net' },
      message: { conversation: 'ping' },
    };

    await handleIncomingMessage(sock, msg);

    expect(mockLogger.error).toHaveBeenCalled();
    expect(mockSaveMessageLog).toHaveBeenCalledWith(
      expect.objectContaining({
        fromNumber: '628333333333',
        replied: false,
        replyText: 'fallback text',
      }),
    );
  });
});
