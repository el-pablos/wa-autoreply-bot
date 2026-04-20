/**
 * Singleton EventEmitter untuk pub-sub event bot.
 *
 * Dipakai oleh:
 *   - Pipeline pesan masuk: publish `message_received`, `reply_sent`,
 *     `blacklist_hit`, `rate_limit_hit`.
 *   - SSE feed (`GET /internal/sse-feed`) yang stream event ke dashboard.
 *
 * Event bus sengaja tanpa payload schema ketat supaya consumer di dashboard
 * bisa render apapun. Tetapi helper `publishMessageReceived` menyiapkan
 * bentuk payload standar.
 */
import { EventEmitter } from "node:events";

export const EVENT_MESSAGE_RECEIVED = "message_received";
export const EVENT_REPLY_SENT = "reply_sent";
export const EVENT_BLACKLIST_HIT = "blacklist_hit";
export const EVENT_RATE_LIMIT_HIT = "rate_limit_hit";

// Emitter diekspor agar consumer bisa attach listener mereka sendiri
// (mis. integration tests). Default maxListeners dinaikkan agar SSE multi-tab
// tidak memicu warning "possible memory leak".
export const eventBus = new EventEmitter();
eventBus.setMaxListeners(50);

/**
 * Publish event `message_received` dengan shape seragam.
 * @param {Object} payload
 * @param {string} payload.phoneNumber
 * @param {string} payload.messageText
 * @param {string} payload.messageType
 * @param {boolean} [payload.isAllowed]
 * @param {boolean} [payload.replied]
 * @param {string|null} [payload.replyText]
 * @param {string|null} [payload.groupId]
 */
export function publishMessageReceived(payload) {
  eventBus.emit(EVENT_MESSAGE_RECEIVED, {
    type: EVENT_MESSAGE_RECEIVED,
    timestamp: new Date().toISOString(),
    ...payload,
  });
}

/**
 * Publish event `reply_sent`.
 * @param {Object} payload
 */
export function publishReplySent(payload) {
  eventBus.emit(EVENT_REPLY_SENT, {
    type: EVENT_REPLY_SENT,
    timestamp: new Date().toISOString(),
    ...payload,
  });
}

/**
 * Subscribe ke semua event untuk keperluan SSE feed.
 * Callback dipanggil untuk setiap event dengan signature `(eventName, payload)`.
 * Return function unsubscribe.
 *
 * @param {(eventName: string, payload: object) => void} callback
 * @returns {() => void}
 */
export function subscribeSSE(callback) {
  if (typeof callback !== "function") {
    throw new TypeError("callback harus function");
  }

  const events = [
    EVENT_MESSAGE_RECEIVED,
    EVENT_REPLY_SENT,
    EVENT_BLACKLIST_HIT,
    EVENT_RATE_LIMIT_HIT,
  ];

  const handlers = events.map((evt) => {
    const handler = (payload) => callback(evt, payload);
    eventBus.on(evt, handler);
    return { evt, handler };
  });

  return function unsubscribe() {
    for (const { evt, handler } of handlers) {
      eventBus.off(evt, handler);
    }
  };
}

export default eventBus;
