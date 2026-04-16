# Spec: Dashboard Refresh "Paper Editorial" + 19 Feature Expansion

- **Tanggal**: 2026-04-17
- **Owner**: el-pablos (saiwae@jawir.id)
- **Repo**: github.com/el-pablos/wa-autoreply-bot
- **Branch dasar**: main
- **Sasaran deploy**: server `cihuy` (vmi3227497) — `/var/www/wa-autoreply-bot`
- **Status**: Approved oleh user via brainstorming visual companion (semua lanjut)

---

## 0. Tujuan

1. Mengganti seluruh tampilan dashboard Laravel ke design system **Paper Editorial** (light, klasik koran, hard shadow, mobile-first).
2. Mengadopsi struktur komponen Blade + Alpine.js sesuai `design-suggestions.md` namun di-extend dengan tokens, mobile patterns, dan pola navigasi top-bar + floating-pill yang dipilih user.
3. Menambah **19 fitur baru** (hasil konsolidasi dua sub-agent riset) yang dikelompokkan ke 5 grup eksekusi: Quick Wins (3), Security/Reliability (5), Observability (3), Intelligence/Integration (4), UX Polish (4).
4. Memodernisasi CI/CD agar setiap push ke `main` menghasilkan release + tag versi otomatis.
5. Menulis ulang `README.md` (≥ 2000 kata, Bahasa Indonesia kasual, ERD + flowchart Mermaid, contributors, badges).
6. Memperbaiki metadata repo GitHub (description + topics).
7. Memastikan unit test PHPUnit + Jest 100% passed sebelum push.
8. Push ke origin lalu `git pull` di server `cihuy` via SSH.

## 1. Design System "Paper Editorial"

### 1.1 Token (Tailwind v4 `@theme`)

| Token | Nilai | Pemakaian |
|---|---|---|
| `--color-paper` | `#f6f3ec` | background page |
| `--color-card` | `#ffffff` | surface card |
| `--color-card-muted` | `#faf7ef` | alt card |
| `--color-ink` | `#1a1a1a` | text & strong border |
| `--color-ink-muted` | `#5a4a2a` | secondary text |
| `--color-brass` | `#a89b6a` | tertiary / pending |
| `--color-brass-soft` | `#d9cfa8` | brass background tint |
| `--color-rule` | `#e7e1d3` | hairline border |
| `--color-verified` | `#2f6b3d` | success |
| `--color-pending` | `#a89b6a` | warning |
| `--color-danger` | `#c92a2a` | error / revoke |
| `--color-info` | `#1f3b6b` | informational |
| `--font-display` | `Playfair Display, Georgia, serif` | judul / hero |
| `--font-body` | `Inter, system-ui, sans-serif` | body / form |
| `--font-mono` | `JetBrains Mono, Courier New, monospace` | nomor, ID |
| `--shadow-stamp` | `3px 3px 0 var(--color-ink)` | hero card / primary CTA |
| `--shadow-stamp-sm` | `2px 2px 0 var(--color-ink)` | card hover |
| `--radius-base` | `8px` | card / button |
| `--radius-pill` | `18px` | floating nav |

### 1.2 Type Scale (mobile-first)

| Token | Mobile | ≥md | Family | Weight |
|---|---|---|---|---|
| display-xl | 30 | 48 | display | 900 |
| display-lg | 24 | 36 | display | 800 |
| display-md | 20 | 28 | display | 700 |
| display-italic | 14 | 18 | display italic | 400 |
| body-lg | 16 | 17 | body | 400 |
| body | 14 | 14 | body | 400 |
| body-sm | 12 | 12 | body | 400 |
| mono-sm | 12 | 12 | mono | 500 |
| eyebrow | 9 | 10 | body uppercase 0.25em | 600 |

### 1.3 Spacing & Density

- Section padding-x: 16 (mobile) / 24 (md) / 32 (lg)
- Card padding internal: 14 / 18
- Vertical rhythm scale: 12 / 16 / 24 / 32 / 48
- Min tap target: 44 × 44 px

### 1.4 Editorial Signature

- Hard shadow `3px 3px 0 #1a1a1a` di hero card & primary CTA.
- Hairline rule `border-top: 1px solid #1a1a1a` antar section.
- Eyebrow label uppercase letter-spacing 0.25em.
- Italic serif untuk subtitle / tagline.
- Status bar kiri kartu (`width: 6px`) berwarna semantic.

### 1.5 Iconography

- Library: `mallardduck/blade-lucide-icons` (Composer).
- Stroke: 1.5–1.75. Tidak emoji di UI inti.

### 1.6 Motion

- Page transition fade 150ms.
- Modal/drawer scale 0.95→1 + fade 200ms.
- Toast slide-up 250ms.
- Skeleton shimmer 1.4s.
- `prefers-reduced-motion` dihormati.

### 1.7 Dark Mode

- v1 = light only. Variant night-edition di-defer.

## 2. Layout, Navigasi & Component Library

### 2.1 App Shell

`resources/views/layouts/app.blade.php` di-refactor jadi struktur:

```
<x-shell>
  <x-shell.topbar :title="..." :status="..." />
  <main class="px-4 md:px-6 lg:px-8 pb-24 md:pb-8">@yield('content')</main>
  <x-shell.toast-stack />
  <x-shell.floating-nav :active="..." class="md:hidden" />
  <x-shell.sidebar class="hidden md:flex" />
</x-shell>
```

### 2.2 Top Bar (sticky, ≥mobile)

- Tinggi 56 px mobile / 64 px desktop.
- Konten: title display-lg + status pill (online/offline) + avatar dropdown user.
- Border bawah `1px solid #1a1a1a`.

### 2.3 Floating Pill Nav (mobile only `<md`)

- Posisi `fixed bottom-4 inset-x-4`, `bg-ink`, `rounded-pill`, padding `10 6`.
- 5 slot: Home, List, Sessions, Chat, More.
- Slot aktif: `bg-paper text-ink`, lainnya `text-brass`.
- Min tap 44×44.
- Slot "More" buka `<x-drawer>` Alpine bottom-sheet untuk Settings/Logs/Audit/Analytics/Admin.

### 2.4 Sidebar Desktop (`≥md`)

- Lebar 240 px, `bg-card`, border kanan hairline.
- Item: Home, Allowlist, Sessions, Chat Live, Logs, Analytics, Audit, Webhooks, Templates, KB, Broadcast, Flow, Backup, Settings, Users.
- Section divider eyebrow.

### 2.5 Component Library — folder `resources/views/components/ui/`

| Komponen | File | Fitur |
|---|---|---|
| Button | `button.blade.php` | 4 variant (primary/secondary/outline/danger) × 3 size + loading state |
| IconButton | `icon-button.blade.php` | icon-only, accessible label |
| Card | `card.blade.php` | slot `header`, `body`, `footer`, prop `editorial` (hard shadow) |
| StatCard | `stat-card.blade.php` | KPI dengan eyebrow + display number + trend |
| SessionCard | `session-card.blade.php` | status bar warna + nomor + countdown |
| Badge | `badge.blade.php` | semantic warna (verified/pending/danger/info/muted) |
| Input | `input.blade.php` | label + error + hint + prefix/suffix |
| Textarea | `textarea.blade.php` | counter + max-len |
| Toggle | `toggle.blade.php` | Alpine `x-data`, label di kiri, track di kanan |
| Select | `select.blade.php` | wrapper `<select>` styled |
| Dialog | `dialog.blade.php` | Alpine `open` state, focus trap, ESC close |
| Drawer | `drawer.blade.php` | bottom-sheet mobile / right-sheet desktop |
| Dropdown | `dropdown.blade.php` | Alpine `@click.away`, transition |
| Table | `table.blade.php` | desktop tabel + mobile auto card stack via slot |
| Tabs | `tabs.blade.php` | Alpine `tab` model |
| Toast | `toast.blade.php` | dispatched via `window.dispatchEvent('toast', {...})` |
| Empty | `empty.blade.php` | empty state + CTA |
| Skeleton | `skeleton.blade.php` | shimmer placeholder |
| Pagination | `pagination.blade.php` | mobile prev/next compact |
| QrCard | `qr-card.blade.php` | tampil QR + countdown refresh |
| MetricChart | `metric-chart.blade.php` | wrapper Chart.js |
| Heatmap | `heatmap.blade.php` | 24×7 CSS grid |

### 2.6 Alpine Plugins / JS

- `alpinejs` core (CDN-compatible, di-bundle Vite).
- `@alpinejs/focus` untuk dialog focus trap.
- `@alpinejs/collapse` untuk drawer/accordion.
- `chart.js` v4 untuk metric.
- `sweetalert2` untuk konfirmasi destructive.
- `flatpickr` untuk date picker (analytics & broadcast).

## 3. Page-by-Page Refactor (7 Halaman)

### 3.1 Login (`auth/login.blade.php`)

- Single full-screen card editorial dengan hero serif "Operator's Console".
- Form 2 field (password + optional 2FA TOTP setelah Fitur B3).
- Mobile center, desktop split (50% editorial illustration kiri, 50% form kanan).
- CTA `Masuk` dengan hard-shadow.

### 3.2 Dashboard Home (`dashboard/index.blade.php`)

- Hero section dengan eyebrow `OVERVIEW · {{ today }}` + display title.
- Grid 2×2 mobile / 4 kolom desktop StatCard: Pesan Hari Ini, Sesi Aktif, Allowlist Aktif, Bot Status.
- "Recent Activity" timeline (10 last messages).
- Quick action floating CTA mobile: tombol "+ Tambah Allow".
- Charts: line 7 hari (existing) + heatmap peak hours (Fitur C2) lazy loaded.

### 3.3 Allowlist (`allowlist/index.blade.php` + `form.blade.php`)

- Index: search + filter aktif/non-aktif + tombol "Tambah".
- Desktop: Table (no, nomor, label, template, aktif, action).
- Mobile: card stack — tiap row jadi SessionCard variant (nomor mono + label + toggle + button group icon).
- Form: stepper 1 step (input nomor, label, pilih template Fitur A1, toggle aktif).
- Validasi inline + sticky bar save.

### 3.4 Logs (`logs/index.blade.php`)

- Filter bar: date range (Flatpickr), level (info/warn/err), nomor.
- Desktop: virtualized table.
- Mobile: timeline card collapsible (tap row → expand body, raw payload mono).
- Tombol "Export CSV" (Fitur E1).

### 3.5 Settings (`settings/index.blade.php`)

- Tabs: General · Reply · Schedule · Anti-Spam · AI · Webhook · Backup · 2FA.
- Tiap tab = section dengan eyebrow + display-md heading + form vertikal mobile / 2-col desktop.
- Sticky save bar bawah dengan diff indicator (badge "3 perubahan belum disimpan").

### 3.6 Approved Sessions (`approved/index.blade.php`)

- List SessionCard dengan countdown live (Alpine `x-data` interval).
- Mobile: swipe-action card → reveal "Revoke" merah; desktop: tombol kanan.
- Empty state editorial illustrasi.

### 3.7 Halaman Baru (akibat fitur baru)

| Path | Komponen utama | Fitur driver |
|---|---|---|
| `/templates` | Table + form template variabel | A1 |
| `/business-hours` | Grid jam × hari + OoF list | A2 |
| `/users` | Table user + role badge + form | B2 |
| `/two-factor` | Setup wizard QR + backup codes | B3 |
| `/blacklist` | Table blacklist + manual unblock | B4 |
| `/backups` | List file + tombol restore + jadwal | B5 |
| `/alerts` | Konfigurasi alert & history | C1 |
| `/analytics` | KPI grid + heatmap + funnel | C2 |
| `/chat-live` | Stream SSE bubble chat | C3 |
| `/knowledge-base` | CRUD entri FAQ + tester | D1 |
| `/ai` | System prompt + history + cost | D2 |
| `/webhooks` | Endpoint CRUD + delivery log + API key | D3 |
| `/escalation` | Konfigurasi keyword + log | D4 |
| `/audit-logs` | Table activity + filter | B1 |
| `/onboarding` | 5-step wizard | E3 |
| `/broadcast` | Campaign CRUD + recipient list (defer Sprint 2 jika kepepet) | (bonus dari A1 list) |
| `/flow` | Visual flow editor sederhana (defer) | (bonus dari A1 list) |

## 4. 19 Fitur — Arsitektur, Pipeline, Sequencing

### 4.1 Pipeline Pesan Masuk (urutan baru)

```
WhatsApp incoming
  → messageHandler.handleIncomingMessage()
    1. fromMe check
    2. ignore_groups check (existing)
    3. Blacklist check               (B4 baru)
    4. Rate limit check              (B4 baru, in-memory Map TTL)
    5. Approved session check        (existing)
    6. Allowlist check               (existing, return label)
    7. Conversation flow active?     (defer ke Sprint+1, hook stub)
    8. Knowledge base match?         (D1)
    9. AI smart reply enabled?       (D2 dengan multi-turn history)
   10. Type-template per messageType (A3)
   11. Render template variabel      (A1) dengan context {nama,jam,hari,label}
   12. Business hours / OoF override (A2)
   13. Human typing simulation       (B4: sendPresenceUpdate composing)
   14. sock.sendMessage              (existing)
   15. Log + capture response_time_ms (C2)
   16. Escalation keyword check      (D4)
   17. Webhook outbound dispatch     (D3, queue job)
   18. SSE pub-sub event             (C3)
```

### 4.2 Komponen baru di Bot (Node.js)

| File | Fungsi |
|---|---|
| `bot/src/utils/templateEngine.js` | render `{{var}}` + ekspresi `{{#if}}` minimal |
| `bot/src/utils/businessHours.js` | `isWithinBusinessHours(tz, schedule)` + OoF check |
| `bot/src/utils/typeTemplates.js` | resolve template per messageType |
| `bot/src/utils/rateLimiter.js` | Map TTL + `canReply` + `recordReply` |
| `bot/src/utils/blacklist.js` | cek tabel blacklist (cached) |
| `bot/src/utils/faqMatcher.js` | keyword + Levenshtein fuzzy |
| `bot/src/utils/aiReply.js` | Groq SDK wrapper (default), OpenAI fallback |
| `bot/src/utils/conversationHistory.js` | save/load multi-turn history |
| `bot/src/utils/escalation.js` | keyword match + forward ke owner |
| `bot/src/utils/webhookDispatcher.js` | HMAC POST + retry |
| `bot/src/utils/eventBus.js` | EventEmitter untuk SSE feed |
| `bot/src/utils/cache.js` | small in-memory TTL store |
| `bot/src/api/internal.js` | endpoint `/internal/send`, `/internal/settings`, `/internal/sse-feed` |
| `bot/src/api/public.js` | endpoint `POST /api/send`, `POST /api/allowlist`, `GET /api/logs` (X-API-Key) |

### 4.3 Komponen baru di Dashboard (Laravel)

| File | Fungsi |
|---|---|
| `app/Models/{User,ReplyTemplate,KnowledgeBase,Webhook,WebhookLog,ActivityLog,Blacklist,RateLimitViolation,BusinessHourSchedule,OofSchedule,EscalationLog,AlertChannel,AnalyticsDailySummary,Backup}.php` | model Eloquent |
| `app/Http/Controllers/{Template,KnowledgeBase,Webhook,User,TwoFactor,Blacklist,Backup,Alert,Analytics,ChatStream,Audit,AI,Escalation,BusinessHour,Onboarding,Export}Controller.php` | controller |
| `app/Http/Middleware/{CheckRole,RequiresTwoFactor,LogActivity,CheckOnboardingComplete,VerifyApiKey}.php` | middleware |
| `app/Policies/{AllowList,Setting,Log,User,Webhook,Template,KnowledgeBase}Policy.php` | RBAC |
| `app/Services/{TemplateRenderer,WebhookDispatcher,AlertService,BackupService,AuditTrail,Analytics,BusinessHours,FaqMatcher,AIReply,Escalation}.php` | service classes |
| `app/Console/Commands/{BotHealthCheck,BotDailyDigest,BackupRun,AnalyticsRollup,PruneAuditLogs}.php` | artisan commands |
| `app/Jobs/{DispatchWebhookJob,SendBroadcastJob,RunBackupJob,GenerateMonthlyReportJob}.php` | queue jobs |
| `routes/api.php` | endpoint API publik untuk integrasi |
| `resources/views/components/ui/*` | komponen Blade design system |
| `resources/js/app.js` | bootstrap Alpine + Chart.js + Flatpickr + Sweetalert |
| `resources/css/app.css` | Tailwind v4 `@theme` + utility |
| `public/manifest.json`, `public/sw.js` | PWA |

### 4.4 Sequencing implementasi (sprint logical, dijalankan berurut)

1. **Foundation UI** — Tokens, layout, top bar, floating nav, sidebar, 22 komponen UI.
2. **Refactor 7 halaman existing** ke design baru.
3. **Grup A** — A1 Template Dinamis, A2 Business Hours, A3 Type Template.
4. **Grup B** — B1 Audit, B2 RBAC, B3 2FA, B4 Anti-Spam, B5 Backup.
5. **Grup C** — C1 Alerting, C2 Analytics, C3 Chat Viewer SSE.
6. **Grup D** — D1 KB, D2 AI Reply, D3 Webhook+API, D4 Escalation.
7. **Grup E** — E1 Export, E2 PWA, E3 Onboarding, E4 Redis Cache+Queue.
8. **Cross-cutting** — Tests, README, CI/CD, repo metadata.
9. **Deploy** — Push origin → SSH cihuy → git pull + migrate + restart docker compose.

## 5. Database Schema Migration Plan

Daftar migration baru (semua di `dashboard/database/migrations/`, prefix `2026_04_17_*`):

| # | Migration | Isi utama |
|---|---|---|
| 01 | `alter_users_add_role_2fa` | `role` enum(owner/admin/viewer), `totp_secret` nullable, `two_factor_enabled` bool, `last_login_at` |
| 02 | `create_reply_templates` | `name`, `body` text, `is_default` bool, `conditions_json` json |
| 03 | `alter_allowed_numbers_add_template_id_counter` | `template_id` FK nullable, `reply_count_today` int, `last_reply_at` ts |
| 04 | `create_business_hour_schedules` | `weekday` 1–7, `start_time`, `end_time`, `timezone` default `Asia/Jakarta` |
| 05 | `create_oof_schedules` | `start_date`, `end_date`, `message`, `is_active` |
| 06 | `create_message_type_templates` | `message_type` PK varchar, `body` text, `is_active` |
| 07 | `create_knowledge_base` | `question`, `keywords` json, `answer` text, `is_active`, `match_count` |
| 08 | `create_ai_conversation_history` | `phone_number`, `role` enum, `content` text, `tokens` int, indeks (`phone_number`,`created_at`) |
| 09 | `create_webhook_endpoints` | `url`, `secret`, `events` json, `is_active`, `last_triggered_at` |
| 10 | `create_webhook_delivery_logs` | `endpoint_id`, `event`, `payload` json, `status`, `response_code`, `attempts` |
| 11 | `create_blacklist` | `phone_number`, `reason`, `blocked_at`, `unblock_at` nullable, `blocked_by` |
| 12 | `create_rate_limit_violations` | `phone_number`, `window_start`, `message_count` |
| 13 | `create_activity_logs` | `actor`, `action`, `target_type`, `target_id`, `old_value` json, `new_value` json, `ip_address` |
| 14 | `create_escalation_logs` | `from_number`, `trigger_reason`, `escalated_to`, `message_snippet`, `escalated_at` |
| 15 | `create_alert_channels` | `type` enum(wa/email), `target`, `is_active`, `last_alert_at` |
| 16 | `create_alert_history` | `channel_id`, `severity`, `message`, `delivered_at`, `success` |
| 17 | `alter_message_logs_add_response_time` | `response_time_ms` unsigned nullable, indeks komposit |
| 18 | `create_analytics_daily_summary` | `date` PK, `messages_in`, `messages_out`, `avg_response_ms`, `top_numbers` json |
| 19 | `create_backups_table` | `path`, `size_bytes`, `type` enum(db/session), `checksum`, `created_at` |
| 20 | `create_api_keys` | `key_hash`, `name`, `scopes` json, `last_used_at`, `revoked_at` nullable |
| 21 | `alter_bot_settings_add_keys` | seed key baru: business_hours_enabled, ai_reply_enabled, ai_model, ai_system_prompt, webhook_enabled, alert_enabled, escalation_enabled, rate_limit_enabled, dst |

Seeder baru:
- `RoleSeeder` — promote owner pertama dari `DASHBOARD_PASSWORD` lama.
- `BotSettingSeeder` — pastikan default key baru ada.
- `MessageTypeTemplateSeeder` — seed default 9 tipe pesan.

## 6. CI/CD, README, Repo Polish, Testing & Deploy

### 6.1 CI/CD

`.github/workflows/release.yml` (revisi):

- Trigger: `push` ke `main` dan `workflow_dispatch`.
- Job `version`: jalankan `paulhatch/semantic-version@v5` untuk hitung versi (semver) dari conventional commits.
- Job `tag`: buat tag `vX.Y.Z`, push tag.
- Job `release`: gunakan `softprops/action-gh-release@v2` untuk buat GitHub Release dengan changelog otomatis (`mikepenz/release-changelog-builder-action@v4`).
- Job `docker`: build image bot + dashboard (multi-platform amd64/arm64), push ke GHCR `ghcr.io/el-pablos/wa-autoreply-bot/{bot,dashboard}` dengan tag `latest` + `vX.Y.Z`.
- Job `notify`: post comment ke commit dengan link release & docker image.

`.github/workflows/ci.yml` (revisi minor):

- Tambah cache composer + npm.
- Tambah job `lint` (`pint --test`, `eslint`).
- Tambah job `coverage-report` upload artifact.

### 6.2 README.md

Struktur (≥ 2000 kata, Bahasa Indonesia kasual):

1. Banner + 8 badges (build, release, license, docker pulls, php, node, laravel, contributors).
2. Deskripsi proyek (cerita real use case).
3. Fitur utama (bullet 19 fitur dengan ikon Lucide-style/emoji prefix).
4. Tech stack (table).
5. Arsitektur — diagram Mermaid `flowchart` & `C4-style` container diagram.
6. ERD — Mermaid `erDiagram` (semua 20+ tabel).
7. Pipeline pesan masuk — Mermaid `sequenceDiagram`.
8. Quick start — `git clone`, isi `.env`, `docker compose up -d`, scan QR.
9. Konfigurasi mendalam (per fitur).
10. API integrasi (sample curl webhook + send).
11. Deployment — Docker production, cihuy SSH workflow.
12. Mobile & PWA install guide.
13. Backup & restore guide.
14. Troubleshooting.
15. Roadmap (defer items: broadcast UI, flow editor visual).
16. Contributors — table foto + role + link.
17. Statistik repo (badges shields.io).
18. License + credits.

### 6.3 GitHub Repo Metadata (via `gh` CLI dengan token user)

- Description: `WhatsApp auto-reply operator's console — bot Baileys + dashboard Laravel mobile-first dengan AI smart reply, business hours, multi-user RBAC, webhook, analytics, dan PWA.`
- Topics: `whatsapp-bot`, `baileys`, `laravel`, `tailwindcss`, `alpinejs`, `auto-reply`, `chatbot`, `wa-bot`, `dashboard`, `mobile-first`, `pwa`, `nodejs`, `php`, `mysql`, `docker`, `ai-chatbot`, `webhook`, `groq`, `openai`, `paper-editorial`.
- Homepage: jika ada, isi (kosong dulu).
- **Token TIDAK pernah ditulis ke file repo. Hanya digunakan via env var sesi shell.**

### 6.4 Testing

- **PHPUnit** target: semua fitur baru wajib ada Feature test minimal happy-path + 1 negative (mis. unauthorized, invalid input). Repo sudah punya `phpunit.xml`.
- **Jest** target: bot wajib coverage ≥ threshold existing (lines 80, functions 80, branches 70, statements 80) — semua util baru ditest unit.
- Cross-check: setiap controller baru diverifikasi route map (`php artisan route:list`), middleware terdaftar di `bootstrap/app.php`, kebijakan terdaftar di `AuthServiceProvider`.
- CI gate: pipeline gagal kalau coverage turun atau test merah.
- Skip-list: tidak ada. Zero tolerance simplifikasi.

### 6.5 Deploy ke `cihuy`

```bash
# Lokal
git push origin main

# SSH
ssh cihuy "cd /var/www/wa-autoreply-bot && \
  git stash push -m 'pre-pull-2026-04-17' --include-untracked && \
  git pull --rebase origin main && \
  docker compose -f docker-compose.prod.yml pull && \
  docker compose -f docker-compose.prod.yml up -d --remove-orphans && \
  docker compose -f docker-compose.prod.yml exec -T dashboard php artisan migrate --force && \
  docker compose -f docker-compose.prod.yml exec -T dashboard php artisan optimize && \
  docker compose -f docker-compose.prod.yml ps"
```

Langkah pre-deploy:
1. Verifikasi server tidak ada `M` (atau stash dulu).
2. Backup DB lewat `docker compose exec mysql mysqldump`.
3. Snapshot session WA (volume `bot_auth`).
4. Tag release di GitHub muncul otomatis (Sprint CI/CD).

Rollback strategy: `git reset --hard <previous-tag>` + `docker compose pull` + `up -d` + restore mysqldump bila migration fatal.

## 7. Risk Register

| Risiko | Mitigasi |
|---|---|
| Refactor UI 7 halaman + 16 halaman baru = scope masif → potensi memburu deadline & quality menurun | Sprint sequencing + commit per fitur (1 line ID kasual) + unit test per fitur sebelum lanjut |
| AI Reply boros token | Default OFF, perlu owner aktifkan; Groq sebagai default cost-rendah; hard cap `max_tokens` |
| Broadcasting belum ada UI penuh | Defer halaman `/broadcast` & `/flow` ke Sprint+1; placeholder route dengan banner "coming soon" tetap dibuat |
| 2FA setup salah lock owner | Backup codes 8 buah + master override `DASHBOARD_PASSWORD` env |
| Token GitHub bocor | Hardcode `.gitignore` (`.env*`, `*.token`, `secrets/`), token dipakai via env var sesi shell, tidak commit |
| Pull di server konflik dengan modifikasi local | Pre-pull stash, post-pull pop atau diskip sesuai instruksi |
| Migration berat saat live | Tambah index batched, gunakan `--force` dan timing window |
| Redis baru bikin docker compose down jika config salah | Rollout Redis paling akhir di Grup E, dengan fallback driver `database` queue |

## 8. Out of Scope (eksplisit)

- Visual flow editor drag-drop (D feature stub saja, halaman `/flow` placeholder).
- Halaman broadcast UI penuh (hanya tabel CRUD basic, scheduler defer).
- Dark mode.
- i18n EN (defer ke Sprint+1, infrastruktur tetap pakai `__()` agar siap migrasi).
- Mobile app native.

## 9. Definition of Done

1. ✅ 22 Blade UI komponen jadi & dipakai konsisten 7 halaman lama + 16 halaman baru.
2. ✅ Token Tailwind v4 ada di `resources/css/app.css` `@theme` + dipakai (no hex hardcoded di template).
3. ✅ 19 fitur baru terimplementasi end-to-end (model + migration + service + controller + view + test).
4. ✅ PHPUnit + Jest 100% green (lihat output `php artisan test` & `npm test`).
5. ✅ CI/CD push main → otomatis tag + release + docker images.
6. ✅ README.md ≥ 2000 kata, ID kasual, ERD/flowchart Mermaid, contributors table, badges.
7. ✅ GitHub repo: description + 20 topics di-set.
8. ✅ Commit history: tiap perubahan punya commit terpisah, format `<tipe>: <pesan ID kasual>` 1 baris.
9. ✅ `.gitignore` mencakup `.env*`, secrets, token files, `.superpowers/`.
10. ✅ Deploy ke `cihuy` sukses, `docker compose ps` semua healthy, dashboard reachable.

---

**Status spec**: Approved by user (semua lanjut) → siap masuk fase writing-plans + implementation.
