import { beforeEach, describe, expect, jest, test } from '@jest/globals';
import { invalidateKnowledgeBaseCache } from '../src/utils/faqMatcher.js';

const mockGetSetting = jest.fn();
const mockIsAllowedNumber = jest.fn();
const mockIsInApprovedSession = jest.fn();
const mockRefreshApprovedSession = jest.fn();
const mockSaveMessageLog = jest.fn();
const mockGetBlacklistEntry = jest.fn();
const mockSaveRateLimitViolation = jest.fn();
const mockGetKnowledgeBaseEntries = jest.fn();
const mockIncrementKnowledgeMatch = jest.fn();
const mockGetActiveTemplate = jest.fn();
const mockGetMessageTypeTemplates = jest.fn();
const mockGetBusinessHourSchedules = jest.fn();
const mockGetActiveOofSchedules = jest.fn();
const mockGetRecentConversationHistory = jest.fn();
const mockSaveAiConversationTurn = jest.fn();
const mockPruneConversationHistory = jest.fn();
const mockGetActiveWebhookEndpoints = jest.fn();
const mockCreateWebhookDeliveryLog = jest.fn();
const mockUpdateWebhookDeliveryLog = jest.fn();
const mockTouchWebhookEndpoint = jest.fn();
const mockSaveEscalationLog = jest.fn();
const mockGenerateAiReply = jest.fn();
const mockDispatchWebhook = jest.fn();
const mockLogger = {
  info: jest.fn(),
  debug: jest.fn(),
  error: jest.fn(),
};

jest.unstable_mockModule('../src/config.js', () => ({
  config: {
    bot: {
      ownerNumber: '628123456789',
    },
  },
}));

jest.unstable_mockModule('../src/db.js', () => ({
  getSetting: mockGetSetting,
  isAllowedNumber: mockIsAllowedNumber,
  isInApprovedSession: mockIsInApprovedSession,
  refreshApprovedSession: mockRefreshApprovedSession,
  saveMessageLog: mockSaveMessageLog,
  getBlacklistEntry: mockGetBlacklistEntry,
  saveRateLimitViolation: mockSaveRateLimitViolation,
  getKnowledgeBaseEntries: mockGetKnowledgeBaseEntries,
  incrementKnowledgeMatch: mockIncrementKnowledgeMatch,
  getActiveTemplate: mockGetActiveTemplate,
  getMessageTypeTemplates: mockGetMessageTypeTemplates,
  getBusinessHourSchedules: mockGetBusinessHourSchedules,
  getActiveOofSchedules: mockGetActiveOofSchedules,
  getRecentConversationHistory: mockGetRecentConversationHistory,
  saveAiConversationTurn: mockSaveAiConversationTurn,
  pruneConversationHistory: mockPruneConversationHistory,
  getActiveWebhookEndpoints: mockGetActiveWebhookEndpoints,
  createWebhookDeliveryLog: mockCreateWebhookDeliveryLog,
  updateWebhookDeliveryLog: mockUpdateWebhookDeliveryLog,
  touchWebhookEndpoint: mockTouchWebhookEndpoint,
  saveEscalationLog: mockSaveEscalationLog,
}));

jest.unstable_mockModule('../src/utils/logger.js', () => ({
  logger: mockLogger,
}));

jest.unstable_mockModule('../src/utils/aiReply.js', () => ({
  generateAiReply: mockGenerateAiReply,
}));

jest.unstable_mockModule('../src/utils/webhookDispatcher.js', () => ({
  dispatchWebhook: mockDispatchWebhook,
}));

const {
  handleIncomingMessage,
  __resetMessagePipelineStateForTest,
} = await import('../src/handlers/messageHandler.js');

describe('handleIncomingMessage', () => {
  beforeEach(() => {
    jest.clearAllMocks();
    __resetMessagePipelineStateForTest();
    invalidateKnowledgeBaseCache();

    mockIsInApprovedSession.mockResolvedValue(false);
    mockRefreshApprovedSession.mockResolvedValue(true);
    mockGetBlacklistEntry.mockResolvedValue(null);
    mockSaveRateLimitViolation.mockResolvedValue(1);
    mockGetKnowledgeBaseEntries.mockResolvedValue([]);
    mockIncrementKnowledgeMatch.mockResolvedValue(true);
    mockGetActiveTemplate.mockResolvedValue(null);
    mockGetMessageTypeTemplates.mockResolvedValue([]);
    mockGetBusinessHourSchedules.mockResolvedValue([]);
    mockGetActiveOofSchedules.mockResolvedValue([]);
    mockGetRecentConversationHistory.mockResolvedValue([]);
    mockSaveAiConversationTurn.mockResolvedValue(1);
    mockPruneConversationHistory.mockResolvedValue(0);
    mockGetActiveWebhookEndpoints.mockResolvedValue([]);
    mockCreateWebhookDeliveryLog.mockResolvedValue(1);
    mockUpdateWebhookDeliveryLog.mockResolvedValue(true);
    mockTouchWebhookEndpoint.mockResolvedValue(true);
    mockSaveEscalationLog.mockResolvedValue(1);
    mockGenerateAiReply.mockResolvedValue({
      content: 'Balasan AI',
      tokens: 42,
      latencyMs: 10,
      provider: 'groq',
      model: 'llama',
    });
    mockDispatchWebhook.mockResolvedValue({
      success: true,
      statusCode: 200,
      attempts: 1,
      responseBody: 'ok',
      error: null,
    });
  });

  function useSettings(map) {
    mockGetSetting.mockImplementation(async (key) => {
      if (Object.prototype.hasOwnProperty.call(map, key)) return map[key];
      return null;
    });
  }

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
    mockIsInApprovedSession.mockResolvedValueOnce(false);
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
    mockIsInApprovedSession.mockResolvedValueOnce(false);
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
    mockIsInApprovedSession.mockResolvedValueOnce(false);
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
    mockIsInApprovedSession.mockResolvedValueOnce(false);
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
    mockIsInApprovedSession.mockResolvedValueOnce(false);
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
    mockIsInApprovedSession.mockResolvedValueOnce(false);
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

  test('skip auto-reply dan refresh expiry ketika sender dalam approved session', async () => {
    const sendMessage = jest.fn();
    const sock = { sendMessage };
    const msg = {
      key: { fromMe: false, remoteJid: '628999999999@s.whatsapp.net' },
      message: { conversation: 'halo owner' },
    };

    mockGetSetting
      .mockResolvedValueOnce('true')
      .mockResolvedValueOnce('false')
      .mockResolvedValueOnce('reply text')
      .mockResolvedValueOnce('0')
      .mockResolvedValueOnce('24');
    mockIsInApprovedSession.mockResolvedValueOnce(true);
    mockIsAllowedNumber.mockResolvedValueOnce(true);

    await handleIncomingMessage(sock, msg);

    expect(mockRefreshApprovedSession).toHaveBeenCalledWith('628999999999', 24);
    expect(sendMessage).not.toHaveBeenCalled();
    expect(mockSaveMessageLog).toHaveBeenCalledWith(
      expect.objectContaining({
        fromNumber: '628999999999',
        replied: false,
      })
    );
  });

  test('skip auto-reply jika nomor masuk blacklist', async () => {
    const sendMessage = jest.fn();
    const sock = { sendMessage };
    const msg = {
      key: { fromMe: false, remoteJid: '628555111111@s.whatsapp.net' },
      message: { conversation: 'halo' },
    };

    mockGetSetting
      .mockResolvedValueOnce('true')
      .mockResolvedValueOnce('false')
      .mockResolvedValueOnce('reply text')
      .mockResolvedValueOnce('0');
    mockGetBlacklistEntry.mockResolvedValueOnce({
      id: 1,
      phone_number: '628555111111',
      unblock_at: null,
      is_active: 1,
    });

    await handleIncomingMessage(sock, msg);

    expect(sendMessage).not.toHaveBeenCalled();
    expect(mockSaveMessageLog).toHaveBeenCalledWith(
      expect.objectContaining({
        fromNumber: '628555111111',
        replied: false,
      })
    );
  });

  test('skip auto-reply jika rate limit terlampaui', async () => {
    const sendMessage = jest.fn();
    const sock = { sendMessage };
    const msg = {
      key: { fromMe: false, remoteJid: '628666111111@s.whatsapp.net' },
      message: { conversation: 'halo lagi' },
    };

    // Pesan pertama: masih lolos.
    mockGetSetting
      .mockResolvedValueOnce('true')
      .mockResolvedValueOnce('false')
      .mockResolvedValueOnce('reply text')
      .mockResolvedValueOnce('0')
      .mockResolvedValueOnce('true')
      .mockResolvedValueOnce('60')
      .mockResolvedValueOnce('1')
      .mockResolvedValueOnce('false')
      .mockResolvedValueOnce('false')
      .mockResolvedValueOnce('true')
      .mockResolvedValueOnce('false')
      .mockResolvedValueOnce('false')
      .mockResolvedValueOnce('false');
    mockIsAllowedNumber.mockResolvedValue(true);

    await handleIncomingMessage(sock, msg);

    // Pesan kedua: kena limit karena max=1 dalam window aktif.
    mockGetSetting
      .mockResolvedValueOnce('true')
      .mockResolvedValueOnce('false')
      .mockResolvedValueOnce('reply text')
      .mockResolvedValueOnce('0')
      .mockResolvedValueOnce('true')
      .mockResolvedValueOnce('60')
      .mockResolvedValueOnce('1');

    await handleIncomingMessage(sock, msg);

    expect(mockSaveRateLimitViolation).toHaveBeenCalled();
    expect(sendMessage).toHaveBeenCalledTimes(1);
  });

  test('FAQ match diprioritaskan dan incrementKnowledgeMatch dipanggil', async () => {
    const sendMessage = jest.fn().mockResolvedValue(undefined);
    const sock = { sendMessage };
    const msg = {
      key: { fromMe: false, remoteJid: '628700000001@s.whatsapp.net' },
      message: { conversation: 'boleh info harga website?' },
    };

    useSettings({
      auto_reply_enabled: 'true',
      ignore_groups: 'false',
      reply_message: 'fallback',
      reply_delay_ms: '0',
      rate_limit_enabled: 'false',
      ai_reply_enabled: 'false',
      business_hours_enabled: 'false',
      oof_enabled: 'false',
      human_typing_enabled: 'false',
      escalation_enabled: 'false',
      webhook_enabled: 'false',
    });

    mockIsAllowedNumber.mockResolvedValue(true);
    mockGetKnowledgeBaseEntries.mockResolvedValue([
      {
        id: 77,
        question: 'Berapa harga website?',
        keywords: ['harga website'],
        answer: 'Harga mulai 2 juta.',
        is_active: 1,
      },
    ]);

    await handleIncomingMessage(sock, msg);

    expect(sendMessage).toHaveBeenCalledWith('628700000001@s.whatsapp.net', {
      text: 'Harga mulai 2 juta.',
    });
    expect(mockIncrementKnowledgeMatch).toHaveBeenCalledWith(77);
  });

  test('AI reply dipakai saat FAQ miss dan ai_reply_enabled=true', async () => {
    const sendMessage = jest.fn().mockResolvedValue(undefined);
    const sock = { sendMessage };
    const msg = {
      key: { fromMe: false, remoteJid: '628700000002@s.whatsapp.net' },
      message: { conversation: 'tolong bantu jawab detail project' },
    };

    useSettings({
      auto_reply_enabled: 'true',
      ignore_groups: 'false',
      reply_message: 'fallback',
      reply_delay_ms: '0',
      rate_limit_enabled: 'false',
      ai_reply_enabled: 'true',
      ai_model: 'groq:llama-3.3-70b-versatile',
      ai_system_prompt: 'kamu asisten',
      ai_api_key: 'dummy-key',
      business_hours_enabled: 'false',
      oof_enabled: 'false',
      human_typing_enabled: 'false',
      escalation_enabled: 'false',
      webhook_enabled: 'false',
    });

    mockIsAllowedNumber.mockResolvedValue(true);
    mockGetKnowledgeBaseEntries.mockResolvedValue([]);
    mockGetRecentConversationHistory.mockResolvedValue([
      { role: 'user', content: 'halo', created_at: '2026-04-19T01:00:00.000Z' },
    ]);

    await handleIncomingMessage(sock, msg);

    expect(mockGenerateAiReply).toHaveBeenCalled();
    expect(mockSaveAiConversationTurn).toHaveBeenCalledTimes(2);
    expect(sendMessage).toHaveBeenCalledWith('628700000002@s.whatsapp.net', {
      text: 'Balasan AI',
    });
  });

  test('outside business hours override mengganti reply', async () => {
    const sendMessage = jest.fn().mockResolvedValue(undefined);
    const sock = { sendMessage };
    const msg = {
      key: { fromMe: false, remoteJid: '628700000003@s.whatsapp.net' },
      message: { conversation: 'halo jam kerja?' },
    };

    useSettings({
      auto_reply_enabled: 'true',
      ignore_groups: 'false',
      reply_message: 'fallback default',
      reply_delay_ms: '0',
      rate_limit_enabled: 'false',
      ai_reply_enabled: 'false',
      business_hours_enabled: 'true',
      outside_business_hours_message: 'di luar jam kerja',
      oof_enabled: 'false',
      human_typing_enabled: 'false',
      escalation_enabled: 'false',
      webhook_enabled: 'false',
    });

    mockIsAllowedNumber.mockResolvedValue(true);
    mockGetBusinessHourSchedules.mockResolvedValue([
      {
        weekday: 1,
        start_time: '23:00:00',
        end_time: '01:00:00',
        timezone: 'Asia/Jakarta',
        is_active: 1,
      },
    ]);

    await handleIncomingMessage(sock, msg);

    expect(sendMessage).toHaveBeenCalledWith('628700000003@s.whatsapp.net', {
      text: 'di luar jam kerja',
    });
  });

  test('escalation + webhook dijalankan setelah reply sukses', async () => {
    const sendMessage = jest.fn().mockResolvedValue(undefined);
    const sock = { sendMessage };
    const msg = {
      key: { fromMe: false, remoteJid: '628700000004@s.whatsapp.net' },
      message: { conversation: 'saya komplain layanan ini' },
    };

    useSettings({
      auto_reply_enabled: 'true',
      ignore_groups: 'false',
      reply_message: 'fallback default',
      reply_delay_ms: '0',
      rate_limit_enabled: 'false',
      ai_reply_enabled: 'false',
      business_hours_enabled: 'false',
      oof_enabled: 'false',
      human_typing_enabled: 'false',
      escalation_enabled: 'true',
      escalation_keywords: 'komplain,refund',
      escalation_cooldown_minutes: '15',
      webhook_enabled: 'true',
    });

    mockIsAllowedNumber.mockResolvedValue(true);
    mockGetActiveWebhookEndpoints.mockResolvedValue([
      { id: 11, url: 'https://example.com/hook', secret: 'abc' },
    ]);

    await handleIncomingMessage(sock, msg);

    expect(sendMessage).toHaveBeenCalledWith('628700000004@s.whatsapp.net', {
      text: 'fallback default',
    });
    expect(sendMessage).toHaveBeenCalledWith('628123456789@s.whatsapp.net', {
      text: expect.stringContaining('Escalation trigger terdeteksi'),
    });
    expect(mockSaveEscalationLog).toHaveBeenCalled();
    expect(mockCreateWebhookDeliveryLog).toHaveBeenCalled();
    expect(mockDispatchWebhook).toHaveBeenCalled();
    expect(mockUpdateWebhookDeliveryLog).toHaveBeenCalled();
    expect(mockTouchWebhookEndpoint).toHaveBeenCalledWith(11);
  });
});
