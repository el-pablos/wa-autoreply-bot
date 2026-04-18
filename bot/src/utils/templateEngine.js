/**
 * Template Engine sederhana tanpa eval.
 *
 * Fitur yang didukung:
 *  - Variabel: {{nama}}, {{jam}}, {{hari}}, {{label}}, {{jenis_pesan}}, {{tanggal}}
 *    (dan semua key yang dikirim lewat context).
 *  - Kondisional block: {{#if <ekspresi>}} ... {{/if}}
 *  - Operator perbandingan di dalam #if: ===, !==, >=, <=, >, <
 *  - Literal boolean (true/false), number, string single/double quote.
 *
 * Tidak ada eval, tidak ada Function. Semua parse via regex + AST manual.
 */

const VAR_RE = /\{\{\s*([a-zA-Z_][a-zA-Z0-9_]*)\s*\}\}/g;
const IF_OPEN_RE = /\{\{\s*#if\s+(.+?)\s*\}\}/g;
const IF_CLOSE_RE = /\{\{\s*\/if\s*\}\}/g;

// Operator diurutkan biar yang 3-char diuji dulu (===, !==) baru 2-char, terakhir 1-char.
const OPERATORS = ["===", "!==", ">=", "<=", ">", "<"];

/**
 * Tokenize template jadi array token: TEXT, VAR, IF_START, IF_END.
 * @param {string} template
 */
function tokenize(template) {
  if (typeof template !== "string") {
    throw new TypeError("template harus string");
  }

  const matches = [];

  const pushRe = (re, mapper) => {
    re.lastIndex = 0;
    let m;
    while ((m = re.exec(template)) !== null) {
      matches.push({
        index: m.index,
        end: m.index + m[0].length,
        ...mapper(m),
      });
    }
  };

  pushRe(IF_OPEN_RE, (m) => ({ kind: "IF_START", expr: m[1] }));
  pushRe(IF_CLOSE_RE, () => ({ kind: "IF_END" }));
  pushRe(VAR_RE, (m) => ({ kind: "VAR", name: m[1] }));

  matches.sort((a, b) => a.index - b.index);

  const tokens = [];
  let cursor = 0;
  for (const m of matches) {
    if (m.index < cursor) continue;
    if (m.index > cursor) {
      tokens.push({ type: "TEXT", value: template.slice(cursor, m.index) });
    }
    if (m.kind === "IF_START") {
      tokens.push({ type: "IF_START", expr: m.expr });
    } else if (m.kind === "IF_END") {
      tokens.push({ type: "IF_END" });
    } else if (m.kind === "VAR") {
      tokens.push({ type: "VAR", name: m.name });
    }
    cursor = m.end;
  }
  if (cursor < template.length) {
    tokens.push({ type: "TEXT", value: template.slice(cursor) });
  }
  return tokens;
}

function parse(tokens) {
  const root = { kind: "root", children: [] };
  const stack = [root];
  for (const tok of tokens) {
    const top = stack[stack.length - 1];
    if (tok.type === "TEXT") {
      top.children.push({ kind: "text", value: tok.value });
    } else if (tok.type === "VAR") {
      top.children.push({ kind: "var", name: tok.name });
    } else if (tok.type === "IF_START") {
      const node = { kind: "if", expr: tok.expr, children: [] };
      top.children.push(node);
      stack.push(node);
    } else if (tok.type === "IF_END") {
      if (stack.length === 1) {
        throw new SyntaxError("Template error: {{/if}} tanpa {{#if}} pasangan");
      }
      stack.pop();
    }
  }
  if (stack.length !== 1) {
    throw new SyntaxError(
      "Template error: {{#if}} tidak ditutup dengan {{/if}}",
    );
  }
  return root;
}

function evalOperand(raw, context) {
  const trimmed = raw.trim();
  if (trimmed === "") {
    throw new SyntaxError("Template error: operand kosong di ekspresi #if");
  }

  if (
    (trimmed.startsWith('"') && trimmed.endsWith('"')) ||
    (trimmed.startsWith("'") && trimmed.endsWith("'"))
  ) {
    return trimmed.slice(1, -1);
  }
  if (trimmed === "true") return true;
  if (trimmed === "false") return false;
  if (trimmed === "null") return null;

  if (/^-?\d+(\.\d+)?$/.test(trimmed)) {
    return Number(trimmed);
  }

  if (/^[a-zA-Z_][a-zA-Z0-9_]*$/.test(trimmed)) {
    return context[trimmed];
  }

  throw new SyntaxError(`Template error: operand tidak valid "${trimmed}"`);
}

function evalExpression(expr, context) {
  const trimmed = (expr || "").trim();
  if (trimmed === "") {
    throw new SyntaxError("Template error: ekspresi #if kosong");
  }

  for (const op of OPERATORS) {
    const idx = trimmed.indexOf(op);
    if (idx <= 0) continue;
    const left = trimmed.slice(0, idx);
    const right = trimmed.slice(idx + op.length);
    const leftVal = evalOperand(left, context);
    const rightVal = evalOperand(right, context);
    switch (op) {
      case "===":
        return leftVal === rightVal;
      case "!==":
        return leftVal !== rightVal;
      case ">=":
        return leftVal >= rightVal;
      case "<=":
        return leftVal <= rightVal;
      case ">":
        return leftVal > rightVal;
      case "<":
        return leftVal < rightVal;
      default:
        break;
    }
  }

  const val = evalOperand(trimmed, context);
  return Boolean(val);
}

function renderNode(node, context) {
  if (node.kind === "root") {
    return node.children.map((c) => renderNode(c, context)).join("");
  }
  if (node.kind === "text") {
    return node.value;
  }
  if (node.kind === "var") {
    const val = context[node.name];
    if (val === undefined || val === null) return "";
    return String(val);
  }
  if (node.kind === "if") {
    const ok = evalExpression(node.expr, context);
    if (!ok) return "";
    return node.children.map((c) => renderNode(c, context)).join("");
  }
  return "";
}

/**
 * Render template string dengan context.
 * @param {string} template
 * @param {Record<string, any>} [context]
 * @returns {string}
 */
export function renderTemplate(template, context = {}) {
  const tokens = tokenize(template);
  const ast = parse(tokens);
  return renderNode(ast, context);
}

/**
 * Validasi template tanpa render. Throw SyntaxError kalau invalid.
 * @param {string} template
 * @returns {{ ok: true, variables: string[] }}
 */
export function validateTemplate(template) {
  const tokens = tokenize(template);
  parse(tokens);

  const variables = new Set();
  for (const t of tokens) {
    if (t.type === "VAR") variables.add(t.name);
  }
  return { ok: true, variables: Array.from(variables) };
}
