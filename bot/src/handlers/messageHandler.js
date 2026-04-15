import { logger } from '../utils/logger.js';
import { getSetting, isAllowedNumber, saveMessageLog } from '../db.js';

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

  const remoteJid     = msg.key.remoteJid;
  const isGroup       = remoteJid.endsWith('@g.us');
  const phoneNumber   = isGroup
    ? (msg.key.participant || '').replace('@s.whatsapp.net', '')
    : remoteJid.replace('@s.whatsapp.net', '');
  const groupId       = isGroup ? remoteJid.replace('@g.us', '') : null;

  const { text: messageText, type: messageType } = extractMessageContent(msg);

  logger.info({ phoneNumber, isGroup, messageType }, 'Pesan masuk diterima');

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
      fromNumber:  phoneNumber,
      messageText,
      messageType,
      isAllowed:   false,
      replied:     false,
      replyText:   null,
      groupId,
    });
    return;
  }

  // Cek allow-list
  const allowed = await isAllowedNumber(phoneNumber);

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
    fromNumber:  phoneNumber,
    messageText,
    messageType,
    isAllowed:   allowed,
    replied,
    replyText,
    groupId,
  });
}
