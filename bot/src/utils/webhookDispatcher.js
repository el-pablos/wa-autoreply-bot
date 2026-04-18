import { createHmac } from "node:crypto";

const DEFAULT_MAX_ATTEMPTS = 3;
const DEFAULT_BASE_BACKOFF_MS = 300;
const DEFAULT_TIMEOUT_MS = 7000;

/**
 * Bangun signature HMAC-SHA256 hex.
 *
 * @param {string} secret
 * @param {string} bodyRaw
 * @returns {string}
 */
export function signPayload(secret, bodyRaw) {
  return createHmac("sha256", String(secret || ""))
    .update(String(bodyRaw || ""), "utf8")
    .digest("hex");
}

/**
 * Dispatch single webhook endpoint dengan retry exponential backoff.
 *
 * @param {Object} endpoint
 * @param {number|string} endpoint.id
 * @param {string} endpoint.url
 * @param {string} endpoint.secret
 * @param {Object} endpoint.headers
 * @param {string} event
 * @param {Object} payload
 * @param {Object} [opts]
 * @param {number} [opts.maxAttempts=3]
 * @param {number} [opts.baseBackoffMs=300]
 * @param {number} [opts.timeoutMs=7000]
 * @param {typeof fetch} [opts.fetchImpl=globalThis.fetch]
 * @param {(ms:number)=>Promise<void>} [opts.sleepFn]
 * @param {() => Date} [opts.nowFn]
 * @returns {Promise<{ endpointId:any, success:boolean, attempts:number, statusCode:number|null, responseBody:string|null, error:string|null, event:string, dispatchedAt:string, signature:string }>}
 */
export async function dispatchWebhook(endpoint, event, payload, opts = {}) {
  const fetchImpl = opts.fetchImpl || globalThis.fetch;
  if (typeof fetchImpl !== "function") {
    throw new Error("fetch tidak tersedia untuk webhookDispatcher");
  }

  const url = String(endpoint?.url || "").trim();
  const secret = String(endpoint?.secret || "").trim();
  if (!url) throw new TypeError("endpoint.url wajib diisi");
  if (!secret) throw new TypeError("endpoint.secret wajib diisi");

  const maxAttempts = toPositiveInt(opts.maxAttempts, DEFAULT_MAX_ATTEMPTS);
  const baseBackoffMs = toPositiveInt(
    opts.baseBackoffMs,
    DEFAULT_BASE_BACKOFF_MS,
  );
  const timeoutMs = toPositiveInt(opts.timeoutMs, DEFAULT_TIMEOUT_MS);
  const sleep = typeof opts.sleepFn === "function" ? opts.sleepFn : defaultSleep;
  const nowFn = typeof opts.nowFn === "function" ? opts.nowFn : () => new Date();

  const envelope = {
    event,
    timestamp: nowFn().toISOString(),
    data: payload,
  };
  const bodyRaw = JSON.stringify(envelope);
  const signature = signPayload(secret, bodyRaw);

  let lastStatusCode = null;
  let lastResponseBody = null;
  let lastError = null;

  for (let attempt = 1; attempt <= maxAttempts; attempt++) {
    const controller = new AbortController();
    const timeoutId = setTimeout(() => controller.abort(), timeoutMs);

    try {
      const response = await fetchImpl(url, {
        method: "POST",
        headers: {
          "content-type": "application/json",
          "x-webhook-event": String(event || ""),
          "x-webhook-signature": signature,
          "x-webhook-timestamp": envelope.timestamp,
          ...(endpoint?.headers && typeof endpoint.headers === "object"
            ? endpoint.headers
            : {}),
        },
        body: bodyRaw,
        signal: controller.signal,
      });

      clearTimeout(timeoutId);

      lastStatusCode = response.status;
      lastResponseBody = await safeReadText(response);

      if (response.ok) {
        return {
          endpointId: endpoint?.id ?? null,
          success: true,
          attempts: attempt,
          statusCode: response.status,
          responseBody: lastResponseBody,
          error: null,
          event,
          dispatchedAt: envelope.timestamp,
          signature,
        };
      }

      if (!shouldRetryStatus(response.status) || attempt === maxAttempts) {
        break;
      }

      const waitMs = baseBackoffMs * Math.pow(2, attempt - 1);
      await sleep(waitMs);
    } catch (err) {
      clearTimeout(timeoutId);
      lastError = err;

      if (attempt === maxAttempts) {
        break;
      }

      const waitMs = baseBackoffMs * Math.pow(2, attempt - 1);
      await sleep(waitMs);
    }
  }

  return {
    endpointId: endpoint?.id ?? null,
    success: false,
    attempts: maxAttempts,
    statusCode: lastStatusCode,
    responseBody: lastResponseBody,
    error: lastError ? String(lastError.message || lastError) : null,
    event,
    dispatchedAt: envelope.timestamp,
    signature,
  };
}

/**
 * Dispatch banyak endpoint sekaligus secara paralel.
 *
 * @param {Array<Object>} endpoints
 * @param {string} event
 * @param {Object} payload
 * @param {Object} [opts]
 * @returns {Promise<Array<Object>>}
 */
export async function dispatchWebhooks(endpoints, event, payload, opts = {}) {
  const list = Array.isArray(endpoints) ? endpoints : [];
  const tasks = list.map((endpoint) => dispatchWebhook(endpoint, event, payload, opts));
  return Promise.all(tasks);
}

function shouldRetryStatus(status) {
  return status === 408 || status === 429 || status >= 500;
}

function toPositiveInt(raw, fallback) {
  const n = Number(raw);
  if (!Number.isFinite(n) || n <= 0) return fallback;
  return Math.floor(n);
}

function defaultSleep(ms) {
  return new Promise((resolve) => setTimeout(resolve, ms));
}

async function safeReadText(response) {
  try {
    return await response.text();
  } catch {
    return null;
  }
}

export default {
  signPayload,
  dispatchWebhook,
  dispatchWebhooks,
};