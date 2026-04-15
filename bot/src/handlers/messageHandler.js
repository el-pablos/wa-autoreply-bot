import { logger } from '../utils/logger.js';
import { getSetting, isAllowedNumber, saveMessageLog } from '../db.js';

export function normalizePhoneNumber(rawJid = '') {
  const base   = String(rawJid).trim().split('@')[0].split(':')[0];
  const digits = base.replace(/\D/g, '');

  if (!digits) return '';
  if (digits.startsWith('62') && /^62\d{8,13}$/.test(digits)) return digits;
  if (digits.startsWith('0') && /^0\d{8,13}$/.test(digits)) return `62${digits.slice(1)}`;
  if (digits.startsWith('8') && /^8\d{8,12}$/.test(digits)) return `62${digits}`;
  return '';
}

function getContextParticipant(msg) {
  const payload = msg.message || {};

  for (const value of Object.values(payload)) {
    const participant = value?.contextInfo?.participant;
    if (participant) return participant;
  }

  return payload?.messageContextInfo?.participant || '';
}

function toUnresolvedMarker(raw = '') {
  const base = String(raw).trim();
  if (!base) return 'unresolved:unknown';

  const identifier = base.split('@')[0] || base;
  return identifier ? `unresolved:${identifier}` : 'unresolved:unknown';
}

export function resolveSenderIdentity(msg) {
  const remoteJid = msg.key?.remoteJid || '';
  const isGroup   = remoteJid.endsWith('@g.us');

  const pnCandidates = [
    { source: 'key.senderPn', value: msg.key?.senderPn },
    { source: 'key.participantPn', value: msg.key?.participantPn },
  ];
  const jidCandidates = isGroup
    ? [
      { source: 'key.participant', value: msg.key?.participant },
      { source: 'message.context.participant', value: getContextParticipant(msg) },
      { source: 'key.remoteJid', value: remoteJid },
    ]
    : [
      { source: 'key.remoteJid', value: remoteJid },
      { source: 'key.participant', value: msg.key?.participant },
      { source: 'message.context.participant', value: getContextParticipant(msg) },
    ];
  const candidates = [...pnCandidates, ...jidCandidates];

  let fallbackRaw    = '';
  let fallbackSource = 'unknown';

  for (const candidate of candidates) {
    if (!candidate.value) continue;

    const normalized = normalizePhoneNumber(candidate.value);
    if (normalized) {
      return {
        phoneNumber:  normalized,
        senderRef:    normalized,
        senderSource: candidate.source,
        senderRaw:    String(candidate.value),
        isFallback:   false,
      };
    }

    if (!fallbackRaw) {
      fallbackRaw    = String(candidate.value);
      fallbackSource = candidate.source;
    }
  }

  return {
    phoneNumber:  '',
    senderRef:    toUnresolvedMarker(fallbackRaw),
    senderSource: fallbackSource,
    senderRaw:    fallbackRaw || '',
    isFallback:   true,
  };
}

/**
 * Ekstrak teks dari berbagai tipe pesan Baileys.
 * @param {Object} msg - Message object dari Baileys
 * @returns {{ text: string, type: string }}
 */
export function extractMessageContent(msg) {
  const m = msg.message;
  if (!m) return { text: '', type: 'unknown' };

  if (m.conversation)                       return { text: m.conversation,                            type: 'text' };
  if (m.extendedTextMessage?.text)          return { text: m.extendedTextMessage.text,                type: 'text' };
  if (m.imageMessage?.caption)              return { text: m.imageMessage.caption,                    type: 'image' };
  if (m.videoMessage?.caption)              return { text: m.videoMessage.caption,                    type: 'video' };
  if (m.documentMessage?.caption)           return { text: m.documentMessage.caption,                 type: 'document' };
  if (m.audioMessage)                       return { text: '[Pesan Suara]',                           type: 'audio' };
  if (m.stickerMessage)                     return { text: '[Sticker]',                               type: 'sticker' };
  if (m.locationMessage)                    return { text: '[Lokasi]',                                type: 'location' };
  if (m.contactMessage)                     return { text: '[Kontak]',                                type: 'contact' };
  if (m.reactionMessage)                    return { text: `[Reaksi: ${m.reactionMessage.text || ''}]`, type: 'reaction' };

  return { text: '[Pesan Tidak Dikenal]', type: 'other' };
}

/**
 * Handler utama untuk pesan masuk dari Baileys.
 * @param {Object} sock - Instance socket Baileys
 * @param {Object} msg  - Message object dari Baileys
 */
export async function handleIncomingMessage(sock, msg) {
  // Abaikan pesan dari diri sendiri
  if (msg.key.fromMe) return;

  // Abaikan update status/notifikasi sistem
  if (!msg.message) return;

  const remoteJid     = msg.key.remoteJid || '';
  if (remoteJid === 'status@broadcast' || remoteJid.endsWith('@broadcast')) {
    logger.debug({ remoteJid }, 'Pesan broadcast/status diabaikan');
    return;
  }

  const isGroup = remoteJid.endsWith('@g.us');
  const {
    phoneNumber,
    senderRef,
    senderSource,
    senderRaw,
    isFallback,
  } = resolveSenderIdentity(msg);
  const groupId = isGroup ? remoteJid.replace('@g.us', '') : null;

  const { text: messageText, type: messageType } = extractMessageContent(msg);

  logger.info({ phoneNumber: senderRef, isGroup, messageType, remoteJid }, 'Pesan masuk diterima');
  logger.debug(
    { senderSource, senderRaw, isFallback, hasMsisdn: Boolean(phoneNumber) },
    'Diagnostik resolusi sender'
  );

  // Ambil semua setting sekaligus (parallel query)
  const [autoReplyEnabled, ignoreGroups, replyMessage, replyDelayMs] = await Promise.all([
    getSetting('auto_reply_enabled'),
    getSetting('ignore_groups'),
    getSetting('reply_message'),
    getSetting('reply_delay_ms'),
  ]);

  // Jika setting ignore_groups = true dan pesan dari grup, skip reply tapi tetap log
  if (isGroup && ignoreGroups === 'true') {
    logger.debug({ groupId }, 'Pesan dari grup diabaikan (ignore_groups=true)');
    await saveMessageLog({
      fromNumber:  senderRef,
      messageText,
      messageType,
      isAllowed:   false,
      replied:     false,
      replyText:   null,
      groupId,
    });
    return;
  }

  if (!phoneNumber) {
    logger.debug(
      { unresolvedMarker: senderRef, senderSource, senderRaw, remoteJid },
      'Sender unresolved: auto-reply dilewati'
    );
  }
  const allowed = phoneNumber ? await isAllowedNumber(phoneNumber) : false;

  let replied    = false;
  let replyText  = null;

  if (allowed && autoReplyEnabled === 'true') {
    replyText = replyMessage || 'Haiii, lagi offline sebentar! 😴';

    // Delay natural sebelum kirim
    const delay = parseInt(replyDelayMs || '1500', 10);
    await new Promise(resolve => setTimeout(resolve, delay));

    try {
      await sock.sendMessage(remoteJid, { text: replyText });
      replied = true;
      logger.info({ to: phoneNumber }, 'Auto-reply terkirim');
    } catch (err) {
      logger.error({ err, to: phoneNumber }, 'Gagal kirim auto-reply');
    }
  } else {
    logger.debug({ phoneNumber, allowed, autoReplyEnabled }, 'Tidak memenuhi syarat untuk reply');
  }

  // Log ke database apapun hasilnya
  await saveMessageLog({
    fromNumber:  senderRef,
    messageText,
    messageType,
    isAllowed:   allowed,
    replied,
    replyText,
    groupId,
  });
}
