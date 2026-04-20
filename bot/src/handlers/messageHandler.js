import { logger } from "../utils/logger.js";
import {
  getSetting,
  isAllowedNumber,
  isInApprovedSession,
  refreshApprovedSession,
  saveMessageLog,
  getBlacklistEntry,
  saveRateLimitViolation,
  getActiveTemplate,
  getMessageTypeTemplates,
  getBusinessHourSchedules,
  getActiveOofSchedules,
} from "../db.js";
import {
  eventBus,
  EVENT_BLACKLIST_HIT,
  EVENT_RATE_LIMIT_HIT,
  publishMessageReceived,
  publishReplySent,
} from "../utils/eventBus.js";
import { isBlacklisted } from "../utils/blacklist.js";
import { RateLimiter } from "../utils/rateLimiter.js";
import {
  resolveTypeTemplate,
  buildTypeTemplatesCache,
} from "../utils/typeTemplates.js";
import { renderTemplate } from "../utils/templateEngine.js";
import { getActiveOof, isWithinBusinessHours } from "../utils/businessHours.js";
import { calculateTypingMs, simulateTyping } from "../utils/humanTyping.js";

const blacklistCache = new Map();
const rateLimiter = new RateLimiter({
  windowMs: 60 * 1000,
  maxPerWindow: 5,
});

let typeTemplatesCache = new Map();
let typeTemplatesCacheLoadedAt = 0;
const TYPE_TEMPLATE_CACHE_TTL_MS = 60 * 1000;

export function normalizePhoneNumber(rawJid = "") {
  const base = String(rawJid).trim().split("@")[0].split(":")[0];
  const digits = base.replace(/\D/g, "");

  if (!digits) return "";
  if (digits.startsWith("62") && /^62\d{8,13}$/.test(digits)) return digits;
  if (digits.startsWith("0") && /^0\d{8,13}$/.test(digits))
    return `62${digits.slice(1)}`;
  if (digits.startsWith("8") && /^8\d{8,12}$/.test(digits))
    return `62${digits}`;
  return "";
}

function getContextParticipant(msg) {
  const payload = msg.message || {};

  for (const value of Object.values(payload)) {
    const participant = value?.contextInfo?.participant;
    if (participant) return participant;
  }

  return payload?.messageContextInfo?.participant || "";
}

function toUnresolvedMarker(raw = "") {
  const base = String(raw).trim();
  if (!base) return "unresolved:unknown";

  const identifier = base.split("@")[0] || base;
  return identifier ? `unresolved:${identifier}` : "unresolved:unknown";
}

export function resolveSenderIdentity(msg) {
  const remoteJid = msg.key?.remoteJid || "";
  const isGroup = remoteJid.endsWith("@g.us");

  const pnCandidates = [
    { source: "key.senderPn", value: msg.key?.senderPn },
    { source: "key.participantPn", value: msg.key?.participantPn },
  ];
  const jidCandidates = isGroup
    ? [
        { source: "key.participant", value: msg.key?.participant },
        {
          source: "message.context.participant",
          value: getContextParticipant(msg),
        },
        { source: "key.remoteJid", value: remoteJid },
      ]
    : [
        { source: "key.remoteJid", value: remoteJid },
        { source: "key.participant", value: msg.key?.participant },
        {
          source: "message.context.participant",
          value: getContextParticipant(msg),
        },
      ];
  const candidates = [...pnCandidates, ...jidCandidates];

  let fallbackRaw = "";
  let fallbackSource = "unknown";

  for (const candidate of candidates) {
    if (!candidate.value) continue;

    const normalized = normalizePhoneNumber(candidate.value);
    if (normalized) {
      return {
        phoneNumber: normalized,
        senderRef: normalized,
        senderSource: candidate.source,
        senderRaw: String(candidate.value),
        isFallback: false,
      };
    }

    if (!fallbackRaw) {
      fallbackRaw = String(candidate.value);
      fallbackSource = candidate.source;
    }
  }

  return {
    phoneNumber: "",
    senderRef: toUnresolvedMarker(fallbackRaw),
    senderSource: fallbackSource,
    senderRaw: fallbackRaw || "",
    isFallback: true,
  };
}

/**
 * Ekstrak teks dari berbagai tipe pesan Baileys.
 * @param {Object} msg - Message object dari Baileys
 * @returns {{ text: string, type: string }}
 */
export function extractMessageContent(msg) {
  const m = msg.message;
  if (!m) return { text: "", type: "unknown" };

  if (m.conversation) return { text: m.conversation, type: "text" };
  if (m.extendedTextMessage?.text)
    return { text: m.extendedTextMessage.text, type: "text" };
  if (m.imageMessage?.caption)
    return { text: m.imageMessage.caption, type: "image" };
  if (m.videoMessage?.caption)
    return { text: m.videoMessage.caption, type: "video" };
  if (m.documentMessage?.caption)
    return { text: m.documentMessage.caption, type: "document" };
  if (m.audioMessage) return { text: "[Pesan Suara]", type: "audio" };
  if (m.stickerMessage) return { text: "[Sticker]", type: "sticker" };
  if (m.locationMessage) return { text: "[Lokasi]", type: "location" };
  if (m.contactMessage) return { text: "[Kontak]", type: "contact" };
  if (m.reactionMessage)
    return {
      text: `[Reaksi: ${m.reactionMessage.text || ""}]`,
      type: "reaction",
    };

  return { text: "[Pesan Tidak Dikenal]", type: "other" };
}

/**
 * Handler utama untuk pesan masuk dari Baileys.
 * @param {Object} sock - Instance socket Baileys
 * @param {Object} msg  - Message object dari Baileys
 */
export async function handleIncomingMessage(sock, msg) {
  const startedAt = Date.now();

  // Abaikan pesan dari diri sendiri
  if (msg.key.fromMe) return;

  // Abaikan update status/notifikasi sistem
  if (!msg.message) return;

  const remoteJid = msg.key.remoteJid || "";
  if (remoteJid === "status@broadcast" || remoteJid.endsWith("@broadcast")) {
    logger.debug({ remoteJid }, "Pesan broadcast/status diabaikan");
    return;
  }

  const isGroup = remoteJid.endsWith("@g.us");
  const { phoneNumber, senderRef, senderSource, senderRaw, isFallback } =
    resolveSenderIdentity(msg);
  const groupId = isGroup ? remoteJid.replace("@g.us", "") : null;

  const { text: messageText, type: messageType } = extractMessageContent(msg);

  logger.info(
    { phoneNumber: senderRef, isGroup, messageType, remoteJid },
    "Pesan masuk diterima",
  );
  logger.debug(
    { senderSource, senderRaw, isFallback, hasMsisdn: Boolean(phoneNumber) },
    "Diagnostik resolusi sender",
  );

  publishMessageReceived({
    phoneNumber: senderRef,
    messageText,
    messageType,
    groupId,
    isAllowed: false,
    replied: false,
  });

  // Ambil semua setting sekaligus (parallel query)
  const [autoReplyEnabled, ignoreGroups, replyMessage, replyDelayMs] =
    await Promise.all([
      getSetting("auto_reply_enabled"),
      getSetting("ignore_groups"),
      getSetting("reply_message"),
      getSetting("reply_delay_ms"),
    ]);

  // Jika setting ignore_groups = true dan pesan dari grup, skip reply tapi tetap log
  if (isGroup && ignoreGroups === "true") {
    logger.debug({ groupId }, "Pesan dari grup diabaikan (ignore_groups=true)");
    await saveMessageLog({
      fromNumber: senderRef,
      messageText,
      messageType,
      isAllowed: false,
      replied: false,
      replyText: null,
      groupId,
    });
    return;
  }

  // 3) Blacklist check
  const blacklisted = phoneNumber
    ? await isBlacklisted(phoneNumber, blacklistCache, getBlacklistEntry)
    : false;
  if (blacklisted) {
    eventBus.emit(EVENT_BLACKLIST_HIT, {
      type: EVENT_BLACKLIST_HIT,
      timestamp: new Date().toISOString(),
      phoneNumber: senderRef,
      messageText,
      groupId,
    });

    await saveMessageLog({
      fromNumber: senderRef,
      messageText,
      messageType,
      isAllowed: false,
      replied: false,
      replyText: null,
      groupId,
      responseTimeMs: Date.now() - startedAt,
    });
    return;
  }

  const rateLimitEnabled = await isSettingTrue("rate_limit_enabled", false);
  const rateLimitWindowSeconds = toPositiveInt(
    await getSetting("rate_limit_window_seconds"),
    60,
  );
  const rateLimitMaxMessages = toPositiveInt(
    await getSetting("rate_limit_max_messages"),
    5,
  );
  const rateLimitWindowMs = rateLimitWindowSeconds * 1000;

  // 4) Rate limit check
  if (rateLimitEnabled && phoneNumber) {
    const canReply = rateLimiter.canReply(
      phoneNumber,
      rateLimitMaxMessages,
      rateLimitWindowMs,
    );
    if (!canReply) {
      const currentCount = rateLimiter.countFor(phoneNumber) + 1;
      await saveRateLimitViolation({
        phoneNumber,
        windowStart: new Date(Date.now() - rateLimitWindowMs),
        messageCount: currentCount,
      });

      eventBus.emit(EVENT_RATE_LIMIT_HIT, {
        type: EVENT_RATE_LIMIT_HIT,
        timestamp: new Date().toISOString(),
        phoneNumber,
        messageCount: currentCount,
        windowSeconds: rateLimitWindowSeconds,
      });

      await saveMessageLog({
        fromNumber: senderRef,
        messageText,
        messageType,
        isAllowed: false,
        replied: false,
        replyText: null,
        groupId,
        responseTimeMs: Date.now() - startedAt,
      });
      return;
    }
  }

  if (!phoneNumber) {
    logger.debug(
      { unresolvedMarker: senderRef, senderSource, senderRaw, remoteJid },
      "Sender unresolved: auto-reply dilewati",
    );
  }
  const approvedActive = phoneNumber
    ? await isInApprovedSession(phoneNumber)
    : false;
  if (approvedActive) {
    const expiryHoursSetting = await getSetting("approve_expiry_hours");
    const expiryHours = Math.max(
      1,
      parseInt(expiryHoursSetting || "24", 10) || 24,
    );
    await refreshApprovedSession(phoneNumber, expiryHours);
    logger.info(
      { phoneNumber },
      "Sender dalam sesi approve aktif, auto-reply dilewati",
    );
  }

  const allowed = phoneNumber ? await isAllowedNumber(phoneNumber) : false;

  let replied = false;
  let replyText = null;

  if (approvedActive) {
    logger.debug({ phoneNumber }, "Auto-reply skip karena sesi approve aktif");
  } else if (allowed && autoReplyEnabled === "true") {
    const businessHoursEnabled = await isSettingTrue(
      "business_hours_enabled",
      false,
    );
    const oofEnabled = await isSettingTrue("oof_enabled", false);
    const humanTypingEnabled = await isSettingTrue(
      "human_typing_enabled",
      true,
    );

    // Type template + render template variables
    const activeTemplate = phoneNumber
      ? await getActiveTemplate(phoneNumber).catch(() => null)
      : null;
    const typeTemplate = await resolveTypeTemplateForMessage(messageType);
    const baseTemplate =
      typeTemplate ||
      activeTemplate?.body ||
      replyMessage ||
      "Haiii, lagi offline sebentar! 😴";

    const templateContext = buildTemplateContext({
      phoneNumber,
      senderRef,
      messageText,
      messageType,
    });
    replyText = safeRenderTemplate(baseTemplate, templateContext);

    // Business hours / OoF override
    if (oofEnabled) {
      const oofSchedules = await getActiveOofSchedules(new Date()).catch(
        () => [],
      );
      const activeOof = getActiveOof(oofSchedules, new Date());
      if (activeOof?.message) {
        replyText = String(activeOof.message);
      }
    }

    if (businessHoursEnabled) {
      const schedules = await getBusinessHourSchedules().catch(() => []);
      const { timezone, scheduleMap } = buildScheduleMap(schedules);
      if (Object.keys(scheduleMap).length > 0) {
        const within = isWithinBusinessHours(timezone, scheduleMap, new Date());
        if (!within) {
          const outsideHoursMessage =
            (await getSetting("outside_business_hours_message")) ||
            "Saat ini kami di luar jam operasional. Pesan kamu sudah masuk dan akan kami respon saat jam kerja.";
          replyText = outsideHoursMessage;
        }
      }
    }

    // Delay natural sebelum kirim
    const delay = parseInt(replyDelayMs || "1500", 10);
    if (delay > 0) {
      await new Promise((resolve) => setTimeout(resolve, delay));
    }

    // Human typing simulation
    if (humanTypingEnabled) {
      const typingMs = calculateTypingMs(replyText.length);
      await simulateTyping(sock, remoteJid, typingMs);
    }

    try {
      await sock.sendMessage(remoteJid, { text: replyText });
      replied = true;
      logger.info({ to: phoneNumber }, "Auto-reply terkirim");
      publishReplySent({
        to: remoteJid,
        phoneNumber: senderRef,
        replyText,
      });

      if (rateLimitEnabled && phoneNumber) {
        rateLimiter.recordReply(phoneNumber, rateLimitWindowMs);
      }
    } catch (err) {
      logger.error({ err, to: phoneNumber }, "Gagal kirim auto-reply");
    }
  } else {
    logger.debug(
      { phoneNumber, allowed, autoReplyEnabled },
      "Tidak memenuhi syarat untuk reply",
    );
  }

  // Log ke database apapun hasilnya
  await saveMessageLog({
    fromNumber: senderRef,
    messageText,
    messageType,
    isAllowed: allowed,
    replied,
    replyText,
    groupId,
    responseTimeMs: Date.now() - startedAt,
  });
}

async function isSettingTrue(key, defaultValue = false) {
  const raw = await getSetting(key);
  if (raw === null || raw === undefined || raw === "") return defaultValue;
  return String(raw).toLowerCase() === "true" || String(raw) === "1";
}

async function resolveTypeTemplateForMessage(messageType) {
  const now = Date.now();
  if (now - typeTemplatesCacheLoadedAt > TYPE_TEMPLATE_CACHE_TTL_MS) {
    const rows = await getMessageTypeTemplates().catch(() => []);
    typeTemplatesCache = buildTypeTemplatesCache(rows);
    typeTemplatesCacheLoadedAt = now;
  }

  return resolveTypeTemplate(messageType, typeTemplatesCache);
}

function safeRenderTemplate(template, context) {
  try {
    return renderTemplate(template, context);
  } catch (err) {
    logger.error({ err }, "Template render gagal, fallback ke template mentah");
    return String(template || "");
  }
}

function buildTemplateContext({
  phoneNumber,
  senderRef,
  messageText,
  messageType,
}) {
  const now = new Date();
  const localeDate = new Intl.DateTimeFormat("id-ID", {
    timeZone: "Asia/Jakarta",
    day: "2-digit",
    month: "2-digit",
    year: "numeric",
  }).format(now);
  const localeTime = new Intl.DateTimeFormat("id-ID", {
    timeZone: "Asia/Jakarta",
    hour: "2-digit",
    minute: "2-digit",
    hour12: false,
  }).format(now);
  const localeDay = new Intl.DateTimeFormat("id-ID", {
    timeZone: "Asia/Jakarta",
    weekday: "long",
  }).format(now);

  return {
    nama: phoneNumber || senderRef,
    jam: localeTime,
    hari: localeDay,
    label: phoneNumber ? `Kontak ${phoneNumber}` : "Unknown",
    jenis_pesan: messageType,
    tanggal: localeDate,
    pesan: messageText,
  };
}

function buildScheduleMap(rows) {
  const scheduleMap = {};
  let timezone = "Asia/Jakarta";

  for (const row of Array.isArray(rows) ? rows : []) {
    if (!row || Number(row.is_active) !== 1) continue;
    const weekday = Number(row.weekday);
    if (!(weekday >= 1 && weekday <= 7)) continue;

    scheduleMap[weekday] = {
      start: String(row.start_time || "").slice(0, 5),
      end: String(row.end_time || "").slice(0, 5),
    };

    if (row.timezone) {
      timezone = String(row.timezone);
    }
  }

  return { timezone, scheduleMap };
}

function toPositiveInt(raw, fallback) {
  const n = Number(raw);
  if (!Number.isFinite(n) || n <= 0) return fallback;
  return Math.floor(n);
}

// Test helper untuk reset state in-memory antar test case.
export function __resetMessagePipelineStateForTest() {
  blacklistCache.clear();
  rateLimiter.store.clear();
  typeTemplatesCache = new Map();
  typeTemplatesCacheLoadedAt = 0;
}
