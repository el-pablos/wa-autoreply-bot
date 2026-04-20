# 📋 Laporan Implementasi: Fitur "Open Mode"

## Auto-Reply ke Semua Nomor (Kecuali Grup)

**Tanggal:** 19 April 2026
**Status:** Analisa & Rancangan Implementasi
**Author:** Copilot CLI Analysis

---

## 1. Executive Summary

Fitur **"Open Mode"** memungkinkan bot membalas **semua pesan masuk dari chat personal** tanpa harus menambahkan nomor ke allowlist terlebih dahulu — sambil tetap **mengabaikan pesan grup**.

### Kondisi Saat Ini (Allowlist Mode)

```
Pesan masuk → Cek allowlist → Nomor ada & aktif? → Reply
                              Nomor tidak ada?   → Abaikan (log only)
```

### Target (Open Mode)

```
Pesan masuk → Bukan grup? → Reply ke semua
              Grup?       → Abaikan
```

### Perbandingan Mode

| Aspek | Allowlist Mode (Sekarang) | Open Mode (Baru) |
|-------|---------------------------|-------------------|
| Siapa yang dibalas | Hanya nomor di `allowed_numbers` | Semua nomor personal |
| Grup | Tergantung `ignore_groups` | **Selalu diabaikan** |
| Blacklist | Tetap berlaku | Tetap berlaku |
| Rate limit | Tetap berlaku | Tetap berlaku |
| Approved session | Tetap berlaku | Tetap berlaku |
| Setup effort | Harus tambah nomor satu-satu | Zero config |

---

## 2. Arsitektur Saat Ini (Deep Analysis)

### 2.1 Flow Message Handler Lengkap

**File:** `bot/src/handlers/messageHandler.js` (line 183-665)

```
INCOMING MESSAGE (dari Baileys socket)
    │
    ├─[1] Self-message? ──────────────────── return (skip)
    ├─[2] Tidak ada konten? ──────────────── return (skip)
    ├─[3] Status/broadcast? ──────────────── return (skip)
    │
    ├─[4] Resolve sender identity
    │     normalizePhoneNumber() → 628xxx format
    │
    ├─[5] Determine chat type
    │     isGroup = remoteJid.endsWith('@g.us')
    │
    ├─[6] Extract message content & type
    │
    ├─[7] Fetch settings (parallel)
    │     ├── auto_reply_enabled
    │     ├── ignore_groups
    │     ├── reply_message
    │     └── reply_delay_ms
    │
    ├─[GATE 1] Group filter ──────────────── if (isGroup && ignoreGroups) → return
    ├─[GATE 2] Blacklist check ───────────── if (blacklisted) → return
    ├─[GATE 3] Rate limit check ──────────── if (rate limited) → return
    ├─[GATE 4] Approved session ──────────── if (approved) → skip reply, refresh TTL
    │
    ├─[GATE 5] ⭐ ALLOWLIST CHECK ──────── const allowed = await isAllowedNumber(phone)
    │
    └─[DECISION]
          if (approvedActive) → skip
          else if (allowed && autoReplyEnabled) → REPLY ✅
          else → skip (log only)
```

### 2.2 Kode Kritis: Titik Keputusan Allowlist

**`bot/src/handlers/messageHandler.js` line 336-346:**

```javascript
// LINE 336 — Ini yang harus dimodifikasi
const allowed = phoneNumber ? await isAllowedNumber(phoneNumber) : false;

// ... variable declarations ...

// LINE 344-346 — Decision point utama
if (approvedActive) {
  logger.debug({ phoneNumber }, 'Auto-reply skip karena sesi approve aktif');
} else if (allowed && autoReplyEnabled === 'true') {
  // REPLY — hanya jika ada di allowlist DAN auto-reply enabled
  // ... reply generation pipeline ...
} else {
  logger.debug({ phoneNumber, allowed, autoReplyEnabled },
    'Tidak memenuhi syarat untuk reply');
}
```

### 2.3 Database Query Allowlist

**`bot/src/db.js` line 39-46:**

```javascript
export async function isAllowedNumber(phoneNumber) {
  const db = getPool();
  const [rows] = await db.execute(
    'SELECT id FROM allowed_numbers WHERE phone_number = ? AND is_active = 1 LIMIT 1',
    [phoneNumber]
  );
  return rows.length > 0;
}
```

### 2.4 Setting yang Sudah Ada di `bot_settings`

| Key | Value | Deskripsi |
|-----|-------|-----------|
| `auto_reply_enabled` | `'true'/'false'` | Master toggle auto-reply |
| `ignore_groups` | `'true'/'false'` | Abaikan pesan grup |
| `reply_message` | string | Template balasan default |
| `reply_delay_ms` | number | Delay sebelum balas |
| `ai_reply_enabled` | `'true'/'false'` | Gunakan AI reply |
| `business_hours_enabled` | `'true'/'false'` | Filter jam kerja |
| `rate_limit_enabled` | `'true'/'false'` | Anti-spam per nomor |
| `escalation_enabled` | `'true'/'false'` | Deteksi eskalasi |

**Belum ada:** setting untuk `reply_mode` atau `open_mode`.

### 2.5 Dashboard Settings Controller

**`dashboard/app/Http/Controllers/SettingController.php`:**

```php
public function update(Request $request)
{
    $request->validate([
        'reply_message'        => 'required|string|max:1000',
        'reply_delay_ms'       => 'required|integer|min:0|max:10000',
        'auto_reply_enabled'   => 'in:true,false',
        'ignore_groups'        => 'in:true,false',
    ]);

    $keys = ['reply_message', 'reply_delay_ms', 'auto_reply_enabled', 'ignore_groups'];
    // ... save logic
}
```

### 2.6 Dashboard UI (Settings Page)

**`dashboard/resources/views/settings/index.blade.php`:**

- Tab **"General"** → Menampilkan status badge: `REPLY MODE: AUTO/MANUAL` dan `GROUP POLICY: IGNORE/PROCESS`
- Tab **"Anti-Spam"** → Toggle `auto_reply_enabled` dan `ignore_groups`

---

## 3. Rancangan Implementasi

### 3.1 Strategi: Setting `reply_mode`

Tambahkan setting baru `reply_mode` di `bot_settings` dengan 2 nilai:

| Value | Behavior |
|-------|----------|
| `allowlist` | **(Default)** Hanya balas nomor di `allowed_numbers` — behavior saat ini |
| `open` | Balas semua nomor personal, grup **selalu** diabaikan |

**Kenapa bukan boolean `open_mode: true/false`?**
- Menggunakan enum string lebih extensible (bisa tambah mode lain nanti, misalnya `vip_only`, `business_hours_only`)
- Lebih readable di database dan log
- Konsisten dengan pattern setting lain yang pakai string value

### 3.2 Perubahan Per File

---

#### 📄 File 1: `bot/src/handlers/messageHandler.js`

**Lokasi:** Line 226-231 (fetch settings) dan Line 336-346 (decision logic)

**Perubahan di fetch settings (line ~226):**

```javascript
// SEBELUM:
const [autoReplyEnabled, ignoreGroups, replyMessage, replyDelayMs] = await Promise.all([
  getSetting('auto_reply_enabled'),
  getSetting('ignore_groups'),
  getSetting('reply_message'),
  getSetting('reply_delay_ms'),
]);

// SESUDAH: Tambah fetch reply_mode
const [autoReplyEnabled, ignoreGroups, replyMessage, replyDelayMs, replyMode] =
  await Promise.all([
    getSetting('auto_reply_enabled'),
    getSetting('ignore_groups'),
    getSetting('reply_message'),
    getSetting('reply_delay_ms'),
    getSetting('reply_mode'),       // ← BARU
  ]);

const isOpenMode = replyMode === 'open';
```

**Perubahan di group filter (line ~234):**

```javascript
// SEBELUM:
if (isGroup && ignoreGroups === 'true') {
  // ... skip group
}

// SESUDAH: Open mode SELALU abaikan grup
if (isGroup && (ignoreGroups === 'true' || isOpenMode)) {
  logger.debug({ groupId, isOpenMode },
    'Pesan dari grup diabaikan');
  await saveMessageLog({
    fromNumber: senderRef, messageText, messageType,
    isAllowed: false, replied: false, replyText: null, groupId,
  });
  return;
}
```

**Perubahan di allowlist check & decision (line ~336-346):**

```javascript
// SEBELUM:
const allowed = phoneNumber ? await isAllowedNumber(phoneNumber) : false;
// ...
if (approvedActive) {
  // skip
} else if (allowed && autoReplyEnabled === 'true') {
  // reply
}

// SESUDAH: Open mode bypass allowlist check
const allowed = isOpenMode
  ? !!phoneNumber                                    // Open mode: semua nomor valid = allowed
  : (phoneNumber ? await isAllowedNumber(phoneNumber) : false);  // Allowlist mode: cek DB

// Decision tetap sama — `allowed` sudah di-resolve sesuai mode
if (approvedActive) {
  logger.debug({ phoneNumber }, 'Auto-reply skip karena sesi approve aktif');
} else if (allowed && autoReplyEnabled === 'true') {
  // ← Ini tetap berfungsi karena `allowed` sudah true untuk open mode
  // ... reply generation pipeline ...
} else {
  logger.debug({ phoneNumber, allowed, autoReplyEnabled, replyMode },
    'Tidak memenuhi syarat untuk reply');
}
```

**Dampak perubahan ini:**
- ✅ Semua filter lain (blacklist, rate limit, approved session, business hours) tetap berjalan normal
- ✅ Open mode hanya bypass allowlist check
- ✅ Grup selalu di-skip di open mode
- ✅ Backward compatible — default `reply_mode = null` = allowlist behavior

---

#### 📄 File 2: `bot/src/db.js`

**Tidak perlu perubahan.** Fungsi `isAllowedNumber()` tetap ada dan dipakai saat `reply_mode = 'allowlist'`. Open mode cukup bypass pemanggilan fungsi ini.

---

#### 📄 File 3: Database Migration (Laravel)

**File baru:** `dashboard/database/migrations/xxxx_xx_xx_add_reply_mode_to_bot_settings.php`

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('bot_settings')->upsert(
            [
                [
                    'key'         => 'reply_mode',
                    'value'       => 'allowlist',
                    'description' => 'Mode reply: allowlist (hanya nomor terdaftar) atau open (semua nomor personal)',
                ],
            ],
            ['key'],
            ['value', 'description']
        );
    }

    public function down(): void
    {
        DB::table('bot_settings')
            ->where('key', 'reply_mode')
            ->delete();
    }
};
```

---

#### 📄 File 4: `dashboard/app/Http/Controllers/SettingController.php`

**Perubahan di validasi dan save:**

```php
// SEBELUM:
public function update(Request $request)
{
    $request->validate([
        'reply_message'        => 'required|string|max:1000',
        'reply_delay_ms'       => 'required|integer|min:0|max:10000',
        'auto_reply_enabled'   => 'in:true,false',
        'ignore_groups'        => 'in:true,false',
    ]);

    $keys = ['reply_message', 'reply_delay_ms', 'auto_reply_enabled', 'ignore_groups'];
    // ...
}

// SESUDAH:
public function update(Request $request)
{
    $request->validate([
        'reply_message'        => 'required|string|max:1000',
        'reply_delay_ms'       => 'required|integer|min:0|max:10000',
        'auto_reply_enabled'   => 'in:true,false',
        'ignore_groups'        => 'in:true,false',
        'reply_mode'           => 'in:allowlist,open',           // ← BARU
    ]);

    $keys = ['reply_message', 'reply_delay_ms', 'auto_reply_enabled',
             'ignore_groups', 'reply_mode'];                     // ← BARU
    $oldValues = BotSetting::query()
        ->whereIn('key', $keys)
        ->pluck('value', 'key')
        ->toArray();

    $newValues = [
        'reply_message'      => (string) $request->reply_message,
        'reply_delay_ms'     => (string) $request->reply_delay_ms,
        'auto_reply_enabled' => $request->has('auto_reply_enabled') ? 'true' : 'false',
        'ignore_groups'      => $request->has('ignore_groups') ? 'true' : 'false',
        'reply_mode'         => $request->input('reply_mode', 'allowlist'),  // ← BARU
    ];

    foreach ($newValues as $key => $value) {
        BotSetting::setValue($key, $value);
    }

    AuditTrail::record(
        $request,
        'settings.updated',
        ['type' => 'bot_settings', 'id' => null],
        $oldValues,
        $newValues
    );

    return redirect()->route('settings.index')
        ->with('success', 'Pengaturan berhasil disimpan!');
}
```

---

#### 📄 File 5: `dashboard/resources/views/settings/index.blade.php`

**Perubahan 1 — Tab "General" status badge (line ~108):**

```blade
{{-- SEBELUM --}}
<x-ui.card padding="sm">
  <div class="eyebrow">REPLY MODE</div>
  <div class="mt-1">
    <x-ui.badge variant="{{ $autoReplyValue ? 'verified' : 'muted' }}">
      {{ $autoReplyValue ? 'AUTO' : 'MANUAL' }}
    </x-ui.badge>
  </div>
</x-ui.card>

{{-- SESUDAH — Tampilkan mode yang lebih informatif --}}
<x-ui.card padding="sm">
  <div class="eyebrow">REPLY MODE</div>
  <div class="mt-1">
    @if(!$autoReplyValue)
      <x-ui.badge variant="muted">MANUAL</x-ui.badge>
    @elseif($replyMode === 'open')
      <x-ui.badge variant="verified">OPEN (ALL)</x-ui.badge>
    @else
      <x-ui.badge variant="info">ALLOWLIST</x-ui.badge>
    @endif
  </div>
</x-ui.card>
```

**Perubahan 2 — Tab "Anti-Spam" tambah reply mode selector (sebelum toggle `auto_reply_enabled`):**

```blade
{{-- Tambahan baru: Reply Mode Selector --}}
<div class="space-y-2 pb-4 border-b border-[var(--color-rule)]">
  <div class="font-display font-bold text-sm text-[var(--color-ink)]">Reply Mode</div>
  <div class="text-xs text-[var(--color-ink-muted)] mb-2">
    Tentukan siapa yang akan dibalas otomatis oleh bot.
  </div>

  <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
    {{-- Allowlist Card --}}
    <label class="relative flex flex-col p-3 rounded-lg border-2 cursor-pointer transition-all"
      :class="form.reply_mode === 'allowlist'
        ? 'border-[var(--color-verified)] bg-[var(--color-verified)]/5'
        : 'border-[var(--color-rule)] hover:border-[var(--color-ink-muted)]'">
      <input type="radio" name="reply_mode" value="allowlist"
        x-model="form.reply_mode" class="sr-only">
      <div class="flex items-center gap-2 mb-1">
        <x-lucide-shield-check class="w-4 h-4 text-[var(--color-verified)]"/>
        <span class="font-display font-bold text-sm">Allowlist</span>
      </div>
      <span class="text-xs text-[var(--color-ink-muted)]">
        Hanya nomor yang terdaftar di allowlist yang dibalas.
        Cocok untuk layanan eksklusif / VIP.
      </span>
    </label>

    {{-- Open Mode Card --}}
    <label class="relative flex flex-col p-3 rounded-lg border-2 cursor-pointer transition-all"
      :class="form.reply_mode === 'open'
        ? 'border-[var(--color-verified)] bg-[var(--color-verified)]/5'
        : 'border-[var(--color-rule)] hover:border-[var(--color-ink-muted)]'">
      <input type="radio" name="reply_mode" value="open"
        x-model="form.reply_mode" class="sr-only">
      <div class="flex items-center gap-2 mb-1">
        <x-lucide-globe class="w-4 h-4 text-[var(--color-info)]"/>
        <span class="font-display font-bold text-sm">Open</span>
      </div>
      <span class="text-xs text-[var(--color-ink-muted)]">
        Balas semua chat personal. Grup otomatis diabaikan.
        Cocok untuk customer service / info umum.
      </span>
    </label>
  </div>
</div>
```

**Perubahan 3 — Conditional visibility toggle `ignore_groups`:**

```blade
{{-- Toggle ignore_groups: sembunyikan saat Open Mode karena auto-enforced --}}
<label class="flex items-start justify-between gap-4 min-tap py-2 cursor-pointer border-t border-[var(--color-rule)]"
  x-show="form.reply_mode !== 'open'"
  x-transition>
  <div class="flex-1 min-w-0">
    <div class="font-display font-bold text-sm text-[var(--color-ink)]">Abaikan Pesan Grup</div>
    <div class="text-xs text-[var(--color-ink-muted)] mt-0.5">
      Saat aktif, hanya DM personal yang akan diproses bot.
    </div>
  </div>
  {{-- ... toggle input ... --}}
</label>

{{-- Info banner saat Open Mode --}}
<div x-show="form.reply_mode === 'open'" x-transition
  class="flex items-start gap-3 p-3 rounded-lg bg-[var(--color-info)]/10 border border-[var(--color-info)]/20">
  <x-lucide-info class="w-4 h-4 mt-0.5 text-[var(--color-info)] shrink-0"/>
  <div class="text-xs text-[var(--color-ink-muted)]">
    <strong>Open Mode aktif</strong> — Pesan grup otomatis diabaikan.
    Blacklist dan rate limit tetap berlaku untuk proteksi spam.
  </div>
</div>
```

---

#### 📄 File 6: `dashboard/app/Http/Controllers/DashboardController.php`

**Tambahkan passing `$replyMode` ke view:**

```php
// Tambah di method yang render settings.index
$replyMode = BotSetting::getValue('reply_mode', 'allowlist');

return view('settings.index', compact(
    'settings',
    'botStatus',
    'autoReplyValue',
    'ignoreGroupsValue',
    'replyMode',          // ← BARU
));
```

---

#### 📄 File 7: Alpine.js Form State (di settings view)

**Tambah `reply_mode` ke initial form state:**

```javascript
// Di Alpine x-data initialization
form: {
  reply_message: '{{ $settings["reply_message"]->value ?? "" }}',
  reply_delay_ms: '{{ $settings["reply_delay_ms"]->value ?? "0" }}',
  auto_reply_enabled: {{ ($settings['auto_reply_enabled']->value ?? 'false') === 'true' ? 'true' : 'false' }},
  ignore_groups: {{ ($settings['ignore_groups']->value ?? 'false') === 'true' ? 'true' : 'false' }},
  reply_mode: '{{ $settings["reply_mode"]->value ?? "allowlist" }}',  // ← BARU
},
```

---

## 4. Diagram Flow Setelah Implementasi

```
INCOMING MESSAGE
    │
    ├─[1-3] Basic filters (self, empty, broadcast) → skip
    │
    ├─[4] Resolve sender → 628xxx
    ├─[5] isGroup? & isOpenMode?
    │
    ├─[GATE 1] Group filter
    │   ├── Open Mode?  → SELALU skip grup
    │   └── Allowlist Mode? → skip hanya jika ignore_groups=true
    │
    ├─[GATE 2] Blacklist → skip jika blacklisted ← TETAP BERLAKU
    ├─[GATE 3] Rate limit → skip jika exceeded  ← TETAP BERLAKU
    ├─[GATE 4] Approved session → skip reply     ← TETAP BERLAKU
    │
    ├─[GATE 5] Allowlist / Open Mode Check
    │   ├── Open Mode?  → allowed = true (jika phoneNumber valid)
    │   └── Allowlist?  → allowed = await isAllowedNumber(phone)
    │
    └─[DECISION]
          allowed && autoReplyEnabled → REPLY ✅
          else → log only
```

---

## 5. Test Cases

### 5.1 Unit Tests — `messageHandler.js`

| # | Test Case | Mode | Input | Expected |
|---|-----------|------|-------|----------|
| 1 | Open mode: personal chat dibalas | `open` | DM dari 628xxx | ✅ Reply |
| 2 | Open mode: grup diabaikan | `open` | Pesan dari `xxx@g.us` | ❌ Skip, log |
| 3 | Open mode: blacklisted tetap diblock | `open` | DM dari nomor blacklist | ❌ Skip |
| 4 | Open mode: rate limited tetap diblock | `open` | DM ke-6 dalam 60 detik | ❌ Skip |
| 5 | Open mode: approved session tetap skip | `open` | DM dari nomor /approve | ❌ Skip reply |
| 6 | Open mode: auto_reply disabled | `open` | DM + auto_reply=false | ❌ Skip |
| 7 | Open mode: unresolved phone skip | `open` | phoneNumber = '' | ❌ Skip |
| 8 | Allowlist mode: nomor terdaftar dibalas | `allowlist` | DM dari nomor allowed | ✅ Reply |
| 9 | Allowlist mode: nomor tidak terdaftar | `allowlist` | DM dari nomor random | ❌ Skip |
| 10 | Allowlist mode: grup + ignore on | `allowlist` | Grup + ignore=true | ❌ Skip |
| 11 | Allowlist mode: grup + ignore off | `allowlist` | Grup + ignore=false | Cek allowlist |
| 12 | Default (null): fallback ke allowlist | `null` | DM dari nomor random | ❌ Skip |
| 13 | Open mode + business hours off | `open` | DM di luar jam kerja | Reply OoF msg |

### 5.2 Dashboard Tests

| # | Test Case | Expected |
|---|-----------|----------|
| 1 | Save reply_mode = 'open' | Setting tersimpan di DB |
| 2 | Save reply_mode = 'allowlist' | Setting tersimpan di DB |
| 3 | Save reply_mode = 'invalid' | Validation error |
| 4 | General tab: show OPEN badge | Badge berubah saat mode open |
| 5 | Anti-spam tab: hide ignore_groups saat open | Toggle tersembunyi |
| 6 | Anti-spam tab: show info banner saat open | Banner muncul |
| 7 | Audit trail: catat perubahan mode | Log old→new value |

---

## 6. Risiko & Mitigasi

| Risiko | Dampak | Mitigasi |
|--------|--------|----------|
| Spam flood di open mode | Bot membalas ribuan pesan | ✅ Rate limiter tetap aktif per nomor |
| Cost AI tinggi di open mode | Banyak AI reply ke nomor random | ✅ Rate limit + bisa disable AI reply terpisah |
| Privacy: reply ke nomor tidak dikenal | Bot expose ke strangers | ⚠️ Tambah disclaimer di reply? / Admin aware |
| Backward compatibility | Bot existing terganggu | ✅ Default = `allowlist`, zero impact tanpa action |
| DB query tambahan | 1 extra getSetting per message | ✅ Minimal: 1 query ringan, bisa di-batch dengan yang ada |
| Open mode + ignore_groups=false | Konflik logic | ✅ Open mode override: grup selalu di-skip |

---

## 7. Effort Estimate

| Komponen | File | Kompleksitas | Deskripsi |
|----------|------|-------------|-----------|
| Bot logic | `messageHandler.js` | 🟢 Low | ~15 baris perubahan di 3 spot |
| DB migration | migration baru | 🟢 Low | Insert 1 row ke bot_settings |
| Dashboard controller | `SettingController.php` | 🟢 Low | Tambah 1 field validasi + save |
| Dashboard controller | `DashboardController.php` | 🟢 Low | Pass 1 variable ke view |
| Dashboard view | `settings/index.blade.php` | 🟡 Medium | Radio selector UI + conditional logic |
| Unit tests bot | `tests/` | 🟡 Medium | ~13 test cases baru |
| Dashboard tests | `tests/` | 🟢 Low | ~7 test cases |

**Total estimate: ~2-3 jam implementasi + testing**

---

## 8. Checklist Implementasi

```
[ ] 1. Buat migration: tambah reply_mode='allowlist' ke bot_settings
[ ] 2. Run migration: php artisan migrate
[ ] 3. Update SettingController.php: validasi + save reply_mode
[ ] 4. Update DashboardController.php: pass $replyMode ke view
[ ] 5. Update settings/index.blade.php:
      [ ] a. Tambah form.reply_mode ke Alpine state
      [ ] b. Tambah Reply Mode selector (radio cards)
      [ ] c. Update General tab badge
      [ ] d. Conditional hide ignore_groups saat open mode
      [ ] e. Info banner saat open mode
[ ] 6. Update messageHandler.js:
      [ ] a. Fetch reply_mode setting
      [ ] b. Override group filter saat open mode
      [ ] c. Bypass allowlist check saat open mode
[ ] 7. Tulis unit tests bot (13 cases)
[ ] 8. Tulis dashboard tests (7 cases)
[ ] 9. Test end-to-end:
      [ ] a. Allowlist mode tetap berfungsi normal
      [ ] b. Open mode reply ke nomor random
      [ ] c. Open mode skip grup
      [ ] d. Switch mode di dashboard → bot langsung apply
[ ] 10. Update CHANGELOG.md
```

---

## 9. Catatan Arsitektur

### Kenapa Modifikasi Minimal?

Perubahan ini dirancang **surgical** — hanya menyentuh 1 variabel logic (`allowed`) dan 1 kondisi group filter. Semua layer proteksi lain (blacklist, rate limit, business hours, escalation) **tidak tersentuh** karena mereka berada di gate yang berbeda dalam pipeline.

### Extensibility

Dengan pattern `reply_mode` sebagai enum string, mode baru bisa ditambahkan tanpa breaking change:

```javascript
// Contoh mode future
const isOpenMode     = replyMode === 'open';
const isVipMode      = replyMode === 'vip';         // future
const isScheduleMode = replyMode === 'scheduled';   // future

const allowed = isOpenMode
  ? !!phoneNumber
  : (phoneNumber ? await isAllowedNumber(phoneNumber) : false);
```

### Shared Database Architecture

Bot dan Dashboard berbagi MySQL database yang sama. Setting `reply_mode` yang disimpan via dashboard **langsung terbaca** oleh bot pada message berikutnya tanpa perlu cache invalidation atau restart.

```
Dashboard ──save──→ MySQL ←──read── Bot
                  (real-time)
```
