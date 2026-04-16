# Dashboard Refresh "Paper Editorial" + 19 Feature Expansion — Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Refresh seluruh dashboard ke design system "Paper Editorial" mobile-first dan menambah 19 fitur baru di bot + dashboard, end-to-end test, CI/CD release otomatis, README ≥ 2000 kata, dan deploy ke server cihuy.

**Architecture:** Bot Node.js (Baileys + Express) di-extend dengan utility module untuk template/AI/FAQ/webhook/rate-limit, masuk ke pipeline `messageHandler`. Dashboard Laravel 13 + Tailwind v4 + Alpine.js memakai Blade UI components di `resources/views/components/ui/`. Komunikasi internal bot↔dashboard via HTTP shared-secret di Docker network. MySQL menyimpan semua state, dengan migration tambahan 21 buah. Queue jobs pakai database driver (Redis baru di Phase E).

**Tech Stack:** PHP 8.3, Laravel 13, PHPUnit 12, Tailwind CSS 4, Alpine.js 3, Lucide Icons (Blade), Chart.js 4, Flatpickr, SweetAlert2, Node 20, Baileys 6, Express 4, Jest 29, MySQL 8, Docker Compose, GitHub Actions, Groq SDK / OpenAI SDK, Redis 7 (Phase E).

**Spec ref:** `docs/superpowers/specs/2026-04-17-dashboard-refresh-and-feature-expansion-design.md`

---

## File Structure (Master Map)

### Bot baru / dimodifikasi

- Modify: `bot/src/index.js` — register internal HTTP API + event bus
- Modify: `bot/src/handlers/messageHandler.js` — pipeline 18 langkah
- Modify: `bot/src/db.js` — query baru (template, kb, blacklist, rate-limit, history, blacklist)
- Create: `bot/src/utils/{templateEngine,businessHours,typeTemplates,rateLimiter,blacklist,faqMatcher,aiReply,conversationHistory,escalation,webhookDispatcher,eventBus,cache,humanTyping}.js`
- Create: `bot/src/api/{internal,public}.js`
- Create: `bot/tests/unit/{templateEngine,businessHours,typeTemplates,rateLimiter,blacklist,faqMatcher,escalation,humanTyping}.test.js`
- Modify: `bot/package.json` — tambah `@groq/groq-sdk`, `openai`, `crypto-js` (jika perlu)

### Dashboard baru / dimodifikasi

- Create: `dashboard/database/migrations/2026_04_17_*` (21 file)
- Create: `dashboard/database/seeders/{RoleSeeder,BotSettingSeeder,MessageTypeTemplateSeeder}.php`
- Create: `dashboard/app/Models/{ReplyTemplate,KnowledgeBase,Webhook,WebhookLog,ActivityLog,Blacklist,RateLimitViolation,BusinessHourSchedule,OofSchedule,EscalationLog,AlertChannel,AlertHistory,AnalyticsDailySummary,Backup,ApiKey,MessageTypeTemplate,AiConversationHistory}.php`
- Modify: `dashboard/app/Models/{User,AllowedNumber,BotSetting}.php`
- Create: `dashboard/app/Http/Controllers/{TemplateController,KnowledgeBaseController,WebhookController,UserController,TwoFactorController,BlacklistController,BackupController,AlertController,AnalyticsController,ChatStreamController,AuditController,AIController,EscalationController,BusinessHourController,OnboardingController,ExportController,ApiKeyController}.php`
- Modify: `dashboard/app/Http/Controllers/{Auth,Dashboard,AllowList,ApprovedSession,Log,Setting}Controller.php`
- Create: `dashboard/app/Http/Middleware/{CheckRole,RequiresTwoFactor,LogActivity,CheckOnboardingComplete,VerifyApiKey}.php`
- Modify: `dashboard/app/Http/Middleware/SimpleAuthMiddleware.php` (deprecate, gunakan Laravel Auth)
- Create: `dashboard/app/Policies/{AllowListPolicy,SettingPolicy,LogPolicy,UserPolicy,WebhookPolicy,TemplatePolicy,KnowledgeBasePolicy}.php`
- Create: `dashboard/app/Services/{TemplateRenderer,WebhookDispatcher,AlertService,BackupService,AuditTrail,Analytics,BusinessHours,FaqMatcher,AIReply,Escalation,BotApiClient}.php`
- Create: `dashboard/app/Console/Commands/{BotHealthCheck,BotDailyDigest,BackupRun,AnalyticsRollup,PruneAuditLogs,RotateBlacklist}.php`
- Modify: `dashboard/app/Console/Kernel.php` — schedule
- Create: `dashboard/app/Jobs/{DispatchWebhookJob,RunBackupJob,GenerateMonthlyReportJob,SendAlertJob}.php`
- Create: `dashboard/app/Observers/{AllowedNumberObserver,BotSettingObserver,ApprovedSessionObserver,UserObserver}.php`
- Modify: `dashboard/app/Providers/{AppServiceProvider,AuthServiceProvider}.php`
- Modify: `dashboard/bootstrap/app.php` — middleware aliases
- Modify: `dashboard/routes/web.php` — semua route baru
- Create: `dashboard/routes/api.php` — REST API publik
- Create: `dashboard/resources/css/app.css` — Tailwind v4 `@theme`
- Modify: `dashboard/resources/js/app.js` — Alpine + Chart.js + flatpickr + sweetalert
- Create: `dashboard/resources/views/components/ui/{button,icon-button,card,stat-card,session-card,badge,input,textarea,toggle,select,dialog,drawer,dropdown,table,tabs,toast,empty,skeleton,pagination,qr-card,metric-chart,heatmap}.blade.php`
- Create: `dashboard/resources/views/components/shell/{topbar,sidebar,floating-nav,toast-stack}.blade.php`
- Modify: `dashboard/resources/views/layouts/app.blade.php` — komposisi shell
- Modify: `dashboard/resources/views/{auth/login,dashboard/index,allowlist/index,allowlist/form,logs/index,settings/index,approved/index}.blade.php`
- Create: `dashboard/resources/views/{templates,business-hours,users,two-factor,blacklist,backups,alerts,analytics,chat-live,knowledge-base,ai,webhooks,escalation,audit-logs,onboarding}/{index,form,show}.blade.php` (sesuai kebutuhan)
- Create: `dashboard/public/manifest.json`, `dashboard/public/sw.js`, `dashboard/public/icons/{192,512}.png`
- Create: `dashboard/tests/Feature/{TemplateTest,KnowledgeBaseTest,WebhookTest,UserRbacTest,TwoFactorTest,BlacklistTest,BackupTest,AlertTest,AnalyticsTest,ChatStreamTest,AuditTest,AIReplyTest,EscalationTest,BusinessHourTest,ExportTest,OnboardingTest,ApiKeyTest}.php`
- Create: `dashboard/tests/Unit/{TemplateRendererTest,FaqMatcherTest,BusinessHoursTest,AnalyticsTest,WebhookDispatcherTest}.php`
- Modify: `dashboard/tests/Feature/{AllowListTest,LoginTest,SettingTest,DashboardTest,LogTest,ApprovedSessionsTest}.php` (jaga existing pass)
- Modify: `dashboard/composer.json` — `mallardduck/blade-lucide-icons`, `pragmarx/google2fa-laravel`, `barryvdh/laravel-dompdf`, `league/csv`, `predis/predis`
- Modify: `dashboard/package.json` — `alpinejs`, `@alpinejs/focus`, `@alpinejs/collapse`, `chart.js`, `flatpickr`, `sweetalert2`

### Infrastruktur / CI / Repo

- Modify: `docker-compose.yml`, `docker-compose.prod.yml` — service redis (Phase E), queue worker, scheduler cron
- Modify: `bot/Dockerfile`, `dashboard/Dockerfile` — install Redis ext, dompdf reqs
- Modify: `.github/workflows/release.yml` — semantic version + auto release + GHCR docker push + changelog
- Modify: `.github/workflows/ci.yml` — cache, lint, coverage upload
- Create: `.github/workflows/backup.yml` — scheduled backup via SSH (opsional, Phase E)
- Modify: `README.md` — full rewrite ≥ 2000 kata
- Create: `CHANGELOG.md` — generated tail
- Modify: `.gitignore` (sudah)

---

## Strategi Eksekusi

Plan ini **disusun per phase**. Tiap phase = checkpoint commit (kelipatan banyak commit di dalamnya). Phase wajib selesai 100% test green sebelum lanjut. Tiap phase mengandung sub-task yang dieksekusi via subagent-driven-development. Karena scope sangat besar (~7000-9000 LOC + UI 23 halaman), **eksekusi dilakukan paralel saat memungkinkan** (mis. Phase B sub-tasks B1–B5 bisa di-spawn paralel di subagents berbeda, lalu rebase/merge oleh orchestrator).

Berikut **15 PHASE**:

- **Phase 0** — Bootstrap: dependencies, base config, lint baseline
- **Phase 1** — Design System: tokens, app.css, layout shell
- **Phase 2** — Component Library: 22 Blade UI components + tests
- **Phase 3** — Refactor 7 halaman existing
- **Phase 4** — Migration & Model layer (21 migrasi + model + seeder)
- **Phase 5 (Grup A)** — Quick Wins: A1 Template Dinamis, A2 Business Hours, A3 Type Templates
- **Phase 6 (Grup B)** — Security: B1 Audit Trail, B2 Multi-User RBAC, B3 2FA, B4 Anti-Spam/Rate Limit, B5 Backup
- **Phase 7 (Grup C)** — Observability: C1 Bot Alerting, C2 Analytics Mendalam, C3 Real-Time Chat Viewer (SSE)
- **Phase 8 (Grup D)** — Intelligence: D1 Knowledge Base, D2 AI Smart Reply, D3 Webhook+API, D4 Smart Escalation
- **Phase 9 (Grup E)** — UX Polish: E1 Export Data, E2 PWA, E3 Onboarding, E4 Redis Cache + Queue
- **Phase 10** — Cross-cutting tests (route map verification, policy registration, full PHPUnit + Jest)
- **Phase 11** — CI/CD upgrade (release otomatis + tag + GHCR docker push)
- **Phase 12** — README ≥ 2000 kata + ERD + flowchart + contributors + badges
- **Phase 13** — GitHub repo metadata (description + topics)
- **Phase 14** — Deploy: push origin + SSH cihuy git pull + migrate + restart compose

Setiap phase memuat:

- File yang disentuh (paths absolut/relatif).
- TDD step (test → fail → impl → pass → commit).
- Commit message pakai format `<tipe>: <pesan ID kasual>` 1 baris.

---

## Phase 0 — Bootstrap

**Goal:** Tambah dependencies, sediakan baseline `app.css` dengan `@theme` Tailwind v4, register Alpine.js, lint baseline hijau.

**Files:**

- Modify: `dashboard/composer.json`
- Modify: `dashboard/package.json`
- Modify: `bot/package.json`
- Create: `dashboard/resources/css/app.css`
- Modify: `dashboard/resources/js/app.js`

- [ ] **Step 0.1** — Tambah Composer deps

```bash
cd dashboard && composer require \
  mallardduck/blade-lucide-icons \
  pragmarx/google2fa-laravel \
  pragmarx/google2fa-qrcode \
  barryvdh/laravel-dompdf \
  league/csv \
  predis/predis
```

- [ ] **Step 0.2** — Tambah NPM deps

```bash
cd dashboard && npm install --save \
  alpinejs @alpinejs/focus @alpinejs/collapse \
  chart.js flatpickr sweetalert2
```

- [ ] **Step 0.3** — Tambah Bot deps

```bash
cd bot && npm install --save \
  groq-sdk openai
```

- [ ] **Step 0.4** — Buat `dashboard/resources/css/app.css` dengan `@theme` block lengkap (lihat spec §1.1).

- [ ] **Step 0.5** — Update `dashboard/resources/js/app.js`:

```js
import Alpine from "alpinejs";
import focus from "@alpinejs/focus";
import collapse from "@alpinejs/collapse";
import flatpickr from "flatpickr";
import "flatpickr/dist/flatpickr.min.css";
import Swal from "sweetalert2";
import Chart from "chart.js/auto";

window.Alpine = Alpine;
window.flatpickr = flatpickr;
window.Swal = Swal;
window.Chart = Chart;

Alpine.plugin(focus);
Alpine.plugin(collapse);
Alpine.start();
```

- [ ] **Step 0.6** — Verifikasi `npm run build` & `composer install` sukses.

- [ ] **Step 0.7** — Commit

```bash
git add dashboard/composer.json dashboard/composer.lock dashboard/package.json dashboard/package-lock.json bot/package.json bot/package-lock.json dashboard/resources/css/app.css dashboard/resources/js/app.js
git commit -m "chore: tambah dependency design system blade lucide alpine chart flatpickr sweetalert dompdf csv predis groq openai"
```

---

## Phase 1 — Design System Foundation

**Goal:** App shell, top bar, floating nav, sidebar component skeleton dipakai oleh layout `app.blade.php`. Halaman lama belum diubah, hanya layoutnya.

**Files:**

- Create: `dashboard/resources/views/components/shell/{topbar,sidebar,floating-nav,toast-stack}.blade.php`
- Modify: `dashboard/resources/views/layouts/app.blade.php`

- [ ] **Step 1.1** — Tulis Feature test smoke `tests/Feature/LayoutShellTest.php` yang verifikasi layout render top bar, floating nav (mobile), sidebar (desktop).
- [ ] **Step 1.2** — Run test → expected fail (komponen belum ada).
- [ ] **Step 1.3** — Implement `topbar.blade.php` (status pill, avatar, title prop).
- [ ] **Step 1.4** — Implement `floating-nav.blade.php` (5 slot Alpine state + active prop).
- [ ] **Step 1.5** — Implement `sidebar.blade.php` (12 menu item editorial).
- [ ] **Step 1.6** — Implement `toast-stack.blade.php` (Alpine listener `@toast.window`).
- [ ] **Step 1.7** — Update `layouts/app.blade.php` jadi shell composition.
- [ ] **Step 1.8** — Run test → pass.
- [ ] **Step 1.9** — Commit `add: implement app shell layout dengan top bar floating pill nav sidebar dan toast stack`.

---

## Phase 2 — Component Library (22 komponen)

**Goal:** 22 Blade UI komponen siap pakai dengan Alpine.js state, accessible, mobile-first.

**Strategi:** Setiap komponen punya storybook page (preview) + Feature test render-snapshot smoke.

**Sub-tasks (eksekusi paralel via subagent):**

- **2A** — Atoms: `button`, `icon-button`, `badge`, `input`, `textarea`, `toggle`, `select`, `skeleton`, `empty`
- **2B** — Molecules: `card`, `stat-card`, `session-card`, `tabs`, `pagination`, `dropdown`, `toast`
- **2C** — Overlays: `dialog`, `drawer`
- **2D** — Data: `table`, `metric-chart`, `heatmap`, `qr-card`

Per komponen, langkah:

- [ ] Step a: tulis test render & assert prop.
- [ ] Step b: implement component blade file.
- [ ] Step c: jalankan test, hijau.
- [ ] Step d: commit `add: komponen ui <nama>`.

Setelah 22 komponen jadi:

- [ ] **Step 2.99** — Tambah halaman `/preview/components` (gated dev-only) untuk visual QA semua komponen sekaligus. Commit `add: halaman preview komponen ui untuk visual qa`.

---

## Phase 3 — Refactor 7 Halaman Existing

Per halaman:

- [ ] Step a: pastikan Feature test existing masih ada (atau buat smoke jika belum).
- [ ] Step b: refactor view ke komponen UI baru (mobile-first, gunakan `<x-card>`, `<x-table>`, dll).
- [ ] Step c: jalankan test, hijau.
- [ ] Step d: commit `update: refactor halaman <nama> ke design paper editorial`.

Halaman:

- [ ] 3.1 `auth/login.blade.php`
- [ ] 3.2 `dashboard/index.blade.php`
- [ ] 3.3 `allowlist/index.blade.php`
- [ ] 3.4 `allowlist/form.blade.php`
- [ ] 3.5 `logs/index.blade.php`
- [ ] 3.6 `settings/index.blade.php`
- [ ] 3.7 `approved/index.blade.php`

Cross-check: tidak ada hex color hardcoded di view (semua via token Tailwind).

---

## Phase 4 — Migration & Model Layer (21 migrasi)

**Goal:** Skema DB siap untuk semua fitur Phase 5–9.

Per migrasi:

- [ ] Step a: `php artisan make:migration <name>` lalu fill schema.
- [ ] Step b: `make:model <Model>` + relationship.
- [ ] Step c: `php artisan migrate` di environment test (sqlite-mem) → sukses.
- [ ] Step d: commit `add: migrasi dan model <nama>`.

Daftar migrasi (mengikuti spec §5, prefix `2026_04_17_*`):

- [ ] 4.01 alter_users_add_role_2fa
- [ ] 4.02 create_reply_templates
- [ ] 4.03 alter_allowed_numbers_add_template_id_counter
- [ ] 4.04 create_business_hour_schedules
- [ ] 4.05 create_oof_schedules
- [ ] 4.06 create_message_type_templates
- [ ] 4.07 create_knowledge_base
- [ ] 4.08 create_ai_conversation_history
- [ ] 4.09 create_webhook_endpoints
- [ ] 4.10 create_webhook_delivery_logs
- [ ] 4.11 create_blacklist
- [ ] 4.12 create_rate_limit_violations
- [ ] 4.13 create_activity_logs
- [ ] 4.14 create_escalation_logs
- [ ] 4.15 create_alert_channels
- [ ] 4.16 create_alert_history
- [ ] 4.17 alter_message_logs_add_response_time
- [ ] 4.18 create_analytics_daily_summary
- [ ] 4.19 create_backups_table
- [ ] 4.20 create_api_keys
- [ ] 4.21 alter_bot_settings_add_keys

Seeder:

- [ ] 4.S1 `RoleSeeder` + commit
- [ ] 4.S2 `BotSettingSeeder` + commit
- [ ] 4.S3 `MessageTypeTemplateSeeder` + commit

---

## Phase 5 — Grup A: Quick Wins

### 5.A1 — Template Dinamis & Variabel

**Files (bot):** `bot/src/utils/templateEngine.js`, `bot/tests/unit/templateEngine.test.js`, `bot/src/handlers/messageHandler.js`, `bot/src/db.js`
**Files (dashboard):** `app/Http/Controllers/TemplateController.php`, `app/Services/TemplateRenderer.php`, `app/Policies/TemplatePolicy.php`, `routes/web.php`, `resources/views/templates/{index,form}.blade.php`, `tests/Feature/TemplateTest.php`, `tests/Unit/TemplateRendererTest.php`

- [ ] 5.A1.1 Test unit `templateEngine`: render `{{nama}}`, `{{jam}}`, `{{label}}`, kondisi `{{#if}}`. Run → fail.
- [ ] 5.A1.2 Implement `templateEngine.js` (no library, regex + small AST). Run → pass.
- [ ] 5.A1.3 Update `db.js` `getActiveTemplate(allowedNumber)` query JOIN.
- [ ] 5.A1.4 Wire ke `messageHandler.js` (langkah render template).
- [ ] 5.A1.5 Test Laravel Feature `TemplateTest`: index/store/update/delete + validasi sintaks template.
- [ ] 5.A1.6 Implement controller + service + policy + view + route.
- [ ] 5.A1.7 Commit `add: fitur template balasan dinamis dengan variabel dan kondisi`.

### 5.A2 — Business Hours & Out-of-Office

**Files (bot):** `bot/src/utils/businessHours.js`, `bot/tests/unit/businessHours.test.js`, `bot/src/handlers/messageHandler.js`, `bot/src/db.js`
**Files (dashboard):** `BusinessHourController`, `BusinessHours` service, view `business-hours/index.blade.php` (grid jam × hari + OoF list), tests.

- [ ] 5.A2.1 Test `isWithinBusinessHours(tz, schedule, now)` — Senin 10:00 in 09-17 = true; Sabtu = false; etc.
- [ ] 5.A2.2 Implement `businessHours.js` pakai `Intl.DateTimeFormat`.
- [ ] 5.A2.3 Test OoF override: tanggal Lebaran → return OoF message.
- [ ] 5.A2.4 Implement OoF lookup + integrate ke pipeline `messageHandler` (sebelum render template default).
- [ ] 5.A2.5 Dashboard CRUD jadwal + OoF.
- [ ] 5.A2.6 Commit `add: fitur business hours dan out of office mode dengan override per tanggal`.

### 5.A3 — Multi-Template per Jenis Pesan

**Files (bot):** `bot/src/utils/typeTemplates.js`, `bot/tests/unit/typeTemplates.test.js`, `bot/src/handlers/messageHandler.js`
**Files (dashboard):** `SettingController` extension atau halaman tersendiri `/message-type-templates` (tabel 9 baris).

- [ ] 5.A3.1 Test `resolveTypeTemplate(messageType)` return template aktif atau null fallback.
- [ ] 5.A3.2 Implement util + integrate ke pipeline (override default sebelum render).
- [ ] 5.A3.3 UI tabel 9 row, edit inline.
- [ ] 5.A3.4 Test Feature update + cache refresh.
- [ ] 5.A3.5 Commit `add: fitur multi template balasan per jenis pesan masuk`.

---

## Phase 6 — Grup B: Security & Reliability

### 6.B1 — Audit Trail

**Files:** `app/Models/ActivityLog.php`, `app/Observers/{AllowedNumber,BotSetting,ApprovedSession,User}Observer.php`, `app/Http/Middleware/LogActivity.php`, `app/Http/Controllers/AuditController.php`, view `audit-logs/index.blade.php`, tests.

- [ ] 6.B1.1 Test `AuditTest::test_create_allowed_number_records_activity` — assert row di `activity_logs`.
- [ ] 6.B1.2 Implement Observer `AllowedNumberObserver::created/updated/deleted` → `ActivityLog::record(...)`.
- [ ] 6.B1.3 Register di `AppServiceProvider::boot()`.
- [ ] 6.B1.4 Sama untuk BotSetting, ApprovedSession, User.
- [ ] 6.B1.5 View `audit-logs` dengan filter action/date/actor.
- [ ] 6.B1.6 Artisan command `PruneAuditLogs` (>90 hari) + schedule.
- [ ] 6.B1.7 Commit `add: fitur audit trail dengan observer dan halaman activity log`.

### 6.B2 — Multi-User RBAC

**Files:** modify `User` model, `routes/web.php`, replace `SimpleAuthMiddleware`, add `CheckRole` middleware, `UserController`, policies, `auth/login.blade.php` ubah ke email+password, seeder `RoleSeeder`.

- [ ] 6.B2.1 Test `UserRbacTest::owner_can_create_user` + `viewer_cannot_modify_settings` + `admin_cannot_delete_owner`.
- [ ] 6.B2.2 Migrate role enum.
- [ ] 6.B2.3 Implement Auth login (Laravel `attempt`).
- [ ] 6.B2.4 Implement `CheckRole` middleware + register alias di `bootstrap/app.php`.
- [ ] 6.B2.5 Implement Policies + register di `AuthServiceProvider`.
- [ ] 6.B2.6 Update semua controller existing dengan `$this->authorize(...)`.
- [ ] 6.B2.7 Seeder bikin owner dari `DASHBOARD_PASSWORD` lama.
- [ ] 6.B2.8 Commit `add: fitur multi user dengan role owner admin viewer dan policy`.

### 6.B3 — 2FA Login

**Files:** `TwoFactorController`, `RequiresTwoFactor` middleware, view `two-factor/{setup,challenge}.blade.php`, tests.

- [ ] 6.B3.1 Test setup → generate secret → verify TOTP → enable.
- [ ] 6.B3.2 Implement using `pragmarx/google2fa-laravel`.
- [ ] 6.B3.3 Implement `RequiresTwoFactor` middleware.
- [ ] 6.B3.4 Backup codes (8 buah hash).
- [ ] 6.B3.5 Master override dokumentasi.
- [ ] 6.B3.6 Commit `add: fitur dua faktor otentikasi totp dengan backup code`.

### 6.B4 — Anti-Spam, Rate Limit, Human Typing

**Files (bot):** `bot/src/utils/{rateLimiter,blacklist,humanTyping}.js`, tests, integrate `messageHandler`.
**Files (dashboard):** `BlacklistController`, view `blacklist/index.blade.php`, settings tab "Anti-Spam".

- [ ] 6.B4.1 Test `rateLimiter.canReply(num)` burst > N → false.
- [ ] 6.B4.2 Implement Map TTL.
- [ ] 6.B4.3 Test blacklist insert + check.
- [ ] 6.B4.4 Implement blacklist DB + cache.
- [ ] 6.B4.5 Test `humanTyping.calculate(messageLen)` returns proportional ms + jitter.
- [ ] 6.B4.6 Implement humanTyping + `sock.sendPresenceUpdate('composing')`.
- [ ] 6.B4.7 Integrate ke pipeline (langkah 3 blacklist, 4 rate limit, 13 typing).
- [ ] 6.B4.8 Dashboard CRUD blacklist + tab settings.
- [ ] 6.B4.9 Commit `add: fitur rate limit blacklist dan simulasi human typing untuk anti spam`.

### 6.B5 — Backup & Restore

**Files:** `scripts/backup.sh`, `scripts/restore.sh`, `app/Console/Commands/BackupRun.php`, `app/Jobs/RunBackupJob.php`, `BackupController`, view `backups/index.blade.php`, docker-compose tambah volume `backups:`.

- [ ] 6.B5.1 Test artisan `backup:run --type=db` create file + row di `backups`.
- [ ] 6.B5.2 Implement `BackupRun` command (mysqldump via env, gzip).
- [ ] 6.B5.3 Tambah session backup (tar auth_info).
- [ ] 6.B5.4 Schedule daily 02:00.
- [ ] 6.B5.5 Dashboard list + download + restore (UI confirmation Sweetalert).
- [ ] 6.B5.6 Commit `add: fitur backup dan restore otomatis untuk database dan session whatsapp`.

---

## Phase 7 — Grup C: Observability

### 7.C1 — Bot Alerting (WA + Email)

**Files:** `app/Services/AlertService.php`, `app/Console/Commands/{BotHealthCheck,BotDailyDigest}.php`, `app/Jobs/SendAlertJob.php`, `AlertController`, view `alerts/index.blade.php`, bot endpoint `/internal/send-message`, tests.

- [ ] 7.C1.1 Test `AlertService::send('bot_offline')` dispatch job.
- [ ] 7.C1.2 Implement service multi-channel (wa, email).
- [ ] 7.C1.3 Implement `BotHealthCheck` command schedule 5 menit.
- [ ] 7.C1.4 Implement `BotDailyDigest` schedule 08:00 WIB.
- [ ] 7.C1.5 Bot endpoint `/internal/send-message` (shared secret).
- [ ] 7.C1.6 Dashboard tab alert config + history.
- [ ] 7.C1.7 Commit `add: fitur alert bot via wa dan email plus daily digest harian`.

### 7.C2 — Analytics Mendalam

**Files:** `AnalyticsController`, `Analytics` service, view `analytics/index.blade.php` (heatmap 24×7, P95 response, funnel), command `AnalyticsRollup`, tests.

- [ ] 7.C2.1 Test endpoint `/analytics` returns hourly + p95 + funnel data.
- [ ] 7.C2.2 Update `messageHandler.js` catat `response_time_ms`.
- [ ] 7.C2.3 Implement service & controller.
- [ ] 7.C2.4 View dengan Chart.js + heatmap CSS grid.
- [ ] 7.C2.5 Daily rollup ke `analytics_daily_summary`.
- [ ] 7.C2.6 Commit `add: fitur analytics mendalam dengan heatmap peak hours dan p95 response time`.

### 7.C3 — Real-Time Chat Viewer (SSE)

**Files:** `ChatStreamController`, view `chat-live/index.blade.php`, bot endpoint `/internal/sse-feed` (long-poll), tests.

- [ ] 7.C3.1 Test SSE endpoint stream baris baru sejak `last_id`.
- [ ] 7.C3.2 Implement controller `StreamedResponse`.
- [ ] 7.C3.3 View `EventSource` JS bubble chat + Page Visibility auto-disconnect.
- [ ] 7.C3.4 Auto-scroll + tab badge unread.
- [ ] 7.C3.5 Commit `add: fitur real time chat viewer pakai server sent events`.

---

## Phase 8 — Grup D: Intelligence & Integration

### 8.D1 — Knowledge Base / FAQ Matcher

**Files (bot):** `bot/src/utils/faqMatcher.js` (Levenshtein), tests, integrate `messageHandler`.
**Files (dashboard):** `KnowledgeBaseController`, view `knowledge-base/{index,form,test}.blade.php`, tests.

- [ ] 8.D1.1 Test `match("berapa cost web", kb)` returns hit dengan keyword `harga`.
- [ ] 8.D1.2 Implement Levenshtein + keyword pre-filter + cache.
- [ ] 8.D1.3 Integrate pipeline (langkah 8).
- [ ] 8.D1.4 Dashboard CRUD + tester live preview.
- [ ] 8.D1.5 Commit `add: fitur knowledge base dengan fuzzy matcher untuk balasan otomatis pintar`.

### 8.D2 — AI Smart Reply (Groq default)

**Files (bot):** `bot/src/utils/{aiReply,conversationHistory}.js`, tests.
**Files (dashboard):** `AIController`, view `ai/index.blade.php` (system prompt textarea, model dropdown, history viewer), tests.

- [ ] 8.D2.1 Test mock Groq → returns reply.
- [ ] 8.D2.2 Implement `aiReply.generate(systemPrompt, history, userMsg)`.
- [ ] 8.D2.3 ConversationHistory: load N last turns per phone (TTL 24h auto purge).
- [ ] 8.D2.4 Integrate pipeline (langkah 9).
- [ ] 8.D2.5 Settings UI + per-number toggle + token cost meter.
- [ ] 8.D2.6 Commit `add: fitur ai smart reply pakai groq dengan conversation history multi turn`.

### 8.D3 — Webhook Out + REST API In

**Files:** `WebhookController`, `WebhookDispatcher` service, `DispatchWebhookJob`, bot internal endpoint forward, public `routes/api.php` (`POST /api/send`, `POST /api/allowlist`, `GET /api/logs`), `VerifyApiKey` middleware, `ApiKeyController`, tests.

- [ ] 8.D3.1 Test webhook dispatch HMAC signature header.
- [ ] 8.D3.2 Implement service + queue job + retry.
- [ ] 8.D3.3 Test public API key auth + send message.
- [ ] 8.D3.4 Implement endpoint + middleware + ApiKey CRUD.
- [ ] 8.D3.5 Dashboard webhook endpoint CRUD + delivery log + test fire.
- [ ] 8.D3.6 Commit `add: fitur webhook outbound dan rest api inbound dengan api key dan signature hmac`.

### 8.D4 — Smart Escalation

**Files (bot):** `bot/src/utils/escalation.js`, tests, integrate.
**Files (dashboard):** `EscalationController`, view, tests.

- [ ] 8.D4.1 Test detect keyword `komplain` → trigger escalate.
- [ ] 8.D4.2 Implement util + cooldown per nomor.
- [ ] 8.D4.3 Integrate pipeline (langkah 16).
- [ ] 8.D4.4 Dashboard config + log.
- [ ] 8.D4.5 Commit `add: fitur smart escalation forward ke owner saat bot stuck`.

---

## Phase 9 — Grup E: UX Polish

### 9.E1 — Export Data

**Files:** `ExportController`, `GenerateMonthlyReportJob`, template Blade `exports/monthly-report.blade.php`, tests.

- [ ] 9.E1.1 Test export CSV logs return file dengan kolom benar.
- [ ] 9.E1.2 Implement CSV via `league/csv`.
- [ ] 9.E1.3 PDF monthly report via `dompdf`.
- [ ] 9.E1.4 Dashboard tombol export di Logs + Dashboard.
- [ ] 9.E1.5 Commit `add: fitur export data csv dan laporan bulanan pdf`.

### 9.E2 — PWA

**Files:** `public/manifest.json`, `public/sw.js`, `public/icons/{192,512}.png`, layout meta tags, tests smoke.

- [ ] 9.E2.1 Implement manifest + theme color.
- [ ] 9.E2.2 Service worker cache-first asset + network-first dynamic.
- [ ] 9.E2.3 Layout meta tags Apple + theme.
- [ ] 9.E2.4 Alpine prompt install setelah 3 visit.
- [ ] 9.E2.5 Commit `add: fitur progressive web app supaya bisa install ke homescreen`.

### 9.E3 — Onboarding Wizard

**Files:** `OnboardingController`, `CheckOnboardingComplete` middleware, view `onboarding/index.blade.php` (5 step Alpine), tests.

- [ ] 9.E3.1 Test middleware redirect ke `/onboarding` saat setup belum selesai.
- [ ] 9.E3.2 Implement multi-step Alpine wizard.
- [ ] 9.E3.3 Integration dengan QR endpoint bot.
- [ ] 9.E3.4 Commit `add: fitur onboarding wizard lima langkah untuk setup pertama`.

### 9.E4 — Redis Cache + Queue

**Files:** docker-compose service `redis`, env update, queue worker service, cache config, tests.

- [ ] 9.E4.1 Tambah service Redis.
- [ ] 9.E4.2 Update env `CACHE_DRIVER=redis`, `SESSION_DRIVER=redis`, `QUEUE_CONNECTION=redis`.
- [ ] 9.E4.3 Tambah queue worker container.
- [ ] 9.E4.4 Wrap analytics & dashboard stats dengan `Cache::remember`.
- [ ] 9.E4.5 Verifikasi webhook & alert job migrate ke Redis driver.
- [ ] 9.E4.6 Commit `add: fitur redis caching dan queue worker untuk performa produksi`.

---

## Phase 10 — Cross-cutting Tests & Verification

- [ ] 10.1 `php artisan route:list` snapshot — bandingkan dengan ekspektasi (semua route ada, no orphan).
- [ ] 10.2 `php artisan test --parallel` → 100% pass.
- [ ] 10.3 `npm test` di `bot/` → 100% pass + coverage threshold OK.
- [ ] 10.4 Manual smoke pakai Playwright headless (opsional): login, navigate semua halaman, no JS error console.
- [ ] 10.5 Commit `test: jalankan dan lampirkan laporan unit test full pass untuk dashboard dan bot`.

---

## Phase 11 — CI/CD Upgrade

**Files:** `.github/workflows/release.yml`, `.github/workflows/ci.yml`.

- [ ] 11.1 Update `release.yml`:
  - Trigger `push: branches: [main]` + `workflow_dispatch`.
  - Job `version` pakai `paulhatch/semantic-version@v5`.
  - Job `tag` push tag `vX.Y.Z`.
  - Job `release` pakai `softprops/action-gh-release@v2` + `mikepenz/release-changelog-builder-action@v4`.
  - Job `docker` build + push GHCR `ghcr.io/el-pablos/wa-autoreply-bot/{bot,dashboard}` tag `latest` + `vX.Y.Z`.
- [ ] 11.2 Update `ci.yml`: cache composer/npm, lint job (`pint --test`, `eslint`), coverage upload artifact.
- [ ] 11.3 Test workflow: `gh workflow run release.yml` (manual) → release dibuat.
- [ ] 11.4 Commit `update: workflow release otomatis dengan semantic version dan publish image ghcr`.

---

## Phase 12 — README Lengkap (≥2000 kata)

**Files:** `README.md`, `CHANGELOG.md`.

- [ ] 12.1 Tulis ulang README sesuai struktur spec §6.2 (18 bagian, ≥2000 kata, ID kasual).
- [ ] 12.2 Sertakan Mermaid `flowchart`, `erDiagram`, `sequenceDiagram`.
- [ ] 12.3 Tabel kontributor dengan `<img>` GitHub avatar.
- [ ] 12.4 8 badge shields.io.
- [ ] 12.5 Hitung kata: `wc -w README.md` ≥ 2000.
- [ ] 12.6 `CHANGELOG.md` initial entry.
- [ ] 12.7 Commit `docs: tulis ulang readme lengkap dengan arsitektur diagram erd flowchart kontributor dan badge`.

---

## Phase 13 — GitHub Repo Metadata

**Files:** none (gunakan `gh` CLI).

- [ ] 13.1 Set token sesi: `export GH_TOKEN=<token-dari-server-remote>` (TIDAK di-commit).
- [ ] 13.2 `gh repo edit el-pablos/wa-autoreply-bot --description "..."` (lihat spec §6.3).
- [ ] 13.3 `gh repo edit el-pablos/wa-autoreply-bot --add-topic <topic>` (loop 20 topic).
- [ ] 13.4 Verifikasi `gh repo view el-pablos/wa-autoreply-bot --json description,topics`.
- [ ] 13.5 Commit `chore: set deskripsi dan topik repo github` (commit kosong/dokumentasi only — opsional skip).

---

## Phase 14 — Deploy ke `cihuy`

- [ ] 14.1 `git push origin main`.
- [ ] 14.2 Tunggu CI selesai hijau (workflow `ci.yml` + `release.yml`).
- [ ] 14.3 SSH ke cihuy, stash modifikasi local jika ada.
- [ ] 14.4 `git pull --rebase origin main`.
- [ ] 14.5 `docker compose -f docker-compose.prod.yml pull && up -d --remove-orphans`.
- [ ] 14.6 `docker compose exec -T dashboard php artisan migrate --force`.
- [ ] 14.7 `docker compose exec -T dashboard php artisan db:seed --class=BotSettingSeeder --force`.
- [ ] 14.8 `docker compose exec -T dashboard php artisan optimize`.
- [ ] 14.9 `docker compose ps` semua healthy.
- [ ] 14.10 Verifikasi via curl health endpoint dashboard + bot.
- [ ] 14.11 Commit local `chore: catat deployment 2026-04-17 ke cihuy` (opsional / skip).

---

## Self-Review Checklist (Pre-Execution)

**Coverage:** Semua section spec §1–§9 → punya phase yang men-cover. ✅
**Placeholder scan:** No TBD/TODO. Setiap step punya file path + perintah konkret. ✅
**Type consistency:** `templateRenderer` (PHP service) vs `templateEngine` (JS bot util) dibedakan jelas; `WebhookDispatcher` (PHP) vs `webhookDispatcher` (JS) — konsisten naming style per bahasa. ✅
**Test discipline:** Setiap fitur punya test sebelum implementasi (TDD). ✅
**Commit discipline:** Setiap fitur punya commit terpisah, format `<tipe>: <pesan ID kasual>` 1 line. ✅
**Out-of-scope honor:** Broadcasting UI dan Flow editor visual tetap defer (placeholder route only kalau dibutuhkan). ✅

---

**Plan ready. Eksekusi dimulai dari Phase 0.**
