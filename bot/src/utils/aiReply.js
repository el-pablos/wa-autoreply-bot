/**
 * AI reply wrapper untuk Groq (default) dan OpenAI fallback.
 *
 * Contract:
 *   generateAiReply({
 *     systemPrompt, history, userMessage,
 *     model: 'groq:llama-3.3-70b-versatile' | 'openai:gpt-4o-mini' | ...,
 *     maxTokens, apiKey,
 *   }) => { content, tokens, latencyMs, provider, model }
 *
 * History shape: Array<{ role: 'user'|'assistant'|'system', content: string }>.
 * System prompt selalu dimasukkan sebagai message pertama kalau ada.
 *
 * Kalau model tidak prefix dengan provider, default-nya `groq:<name>`.
 * SDK di-lazy-load supaya file ini tetap safe untuk di-import dari code lain
 * tanpa memaksa instantiasi client (yang akan fail kalau apiKey belum ada).
 */

const DEFAULT_MODEL = "groq:llama-3.3-70b-versatile";
const DEFAULT_MAX_TOKENS = 512;
const DEFAULT_TEMP = 0.4;

/**
 * Generate reply.
 *
 * @param {Object} opts
 * @param {string} [opts.systemPrompt]
 * @param {Array<{role,content}>} [opts.history]
 * @param {string} opts.userMessage
 * @param {string} [opts.model]
 * @param {number} [opts.maxTokens]
 * @param {number} [opts.temperature]
 * @param {string} opts.apiKey
 * @param {Object} [opts.clientOverride] - untuk test: { chat: { completions: { create } } }
 * @returns {Promise<{content:string, tokens:number, latencyMs:number, provider:string, model:string}>}
 */
export async function generateAiReply(opts = {}) {
  const {
    systemPrompt,
    history = [],
    userMessage,
    model = DEFAULT_MODEL,
    maxTokens = DEFAULT_MAX_TOKENS,
    temperature = DEFAULT_TEMP,
    apiKey,
    clientOverride,
  } = opts;

  if (!userMessage || typeof userMessage !== "string") {
    throw new TypeError("userMessage wajib string non-empty");
  }
  if (!apiKey && !clientOverride) {
    throw new Error(
      "apiKey wajib diisi (atau pakai clientOverride untuk test)",
    );
  }

  const { provider, modelName } = parseModelSpec(model);
  const messages = assembleMessages(systemPrompt, history, userMessage);

  const started = Date.now();
  const client = clientOverride || (await getClient(provider, apiKey));

  let response;
  try {
    response = await client.chat.completions.create({
      model: modelName,
      messages,
      max_tokens: maxTokens,
      temperature,
    });
  } catch (err) {
    const wrapped = new Error(
      `AI provider '${provider}' gagal memproses request: ${err.message || err}`,
    );
    wrapped.cause = err;
    wrapped.provider = provider;
    wrapped.model = modelName;
    throw wrapped;
  }

  const latencyMs = Date.now() - started;
  const choice = response?.choices?.[0];
  const content = choice?.message?.content || "";
  if (!content) {
    throw new Error(
      `Response kosong dari provider '${provider}' model '${modelName}'`,
    );
  }
  const tokens =
    response?.usage?.total_tokens ??
    (response?.usage?.prompt_tokens || 0) +
      (response?.usage?.completion_tokens || 0);

  return {
    content: String(content).trim(),
    tokens: Number(tokens) || 0,
    latencyMs,
    provider,
    model: modelName,
  };
}

/**
 * Parse spec `provider:model` (case-insensitive). Default provider = groq.
 * @param {string} spec
 */
export function parseModelSpec(spec) {
  const s = String(spec || DEFAULT_MODEL).trim();
  const idx = s.indexOf(":");
  if (idx === -1) {
    return { provider: "groq", modelName: s };
  }
  const provider = s.slice(0, idx).toLowerCase();
  const modelName = s.slice(idx + 1).trim();
  if (!modelName) {
    throw new Error(
      `Model spec tidak valid: '${spec}' (expected 'provider:name')`,
    );
  }
  if (provider !== "groq" && provider !== "openai") {
    throw new Error(`Provider '${provider}' tidak didukung (groq|openai)`);
  }
  return { provider, modelName };
}

/**
 * Bangun array messages dengan system prompt + history + user message.
 */
export function assembleMessages(systemPrompt, history, userMessage) {
  const msgs = [];
  if (systemPrompt && String(systemPrompt).trim()) {
    msgs.push({ role: "system", content: String(systemPrompt).trim() });
  }

  if (Array.isArray(history)) {
    for (const h of history) {
      if (!h || !h.role || !h.content) continue;
      const role = h.role === "bot" ? "assistant" : h.role;
      if (!["user", "assistant", "system"].includes(role)) continue;
      msgs.push({ role, content: String(h.content) });
    }
  }

  msgs.push({ role: "user", content: String(userMessage) });
  return msgs;
}

/**
 * Lazy-load SDK per provider. Bikin client baru per panggilan supaya API key
 * yang di-rotate dari dashboard selalu dihormati tanpa perlu restart bot.
 * Kalau ini perlu dioptimasi ke pool, bisa ditambah TTL cache key-by-apiKey.
 */
async function getClient(provider, apiKey) {
  if (provider === "groq") {
    const mod = await import("groq-sdk");
    const Groq = mod.default || mod.Groq || mod;
    return new Groq({ apiKey });
  }
  if (provider === "openai") {
    const mod = await import("openai");
    const OpenAI = mod.default || mod.OpenAI || mod;
    return new OpenAI({ apiKey });
  }
  throw new Error(`Provider '${provider}' tidak didukung`);
}

export default { generateAiReply, parseModelSpec, assembleMessages };
