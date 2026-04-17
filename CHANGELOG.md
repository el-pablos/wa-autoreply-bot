# Changelog

Semua perubahan penting di proyek ini didokumentasikan di file ini. Format mengikuti
[Keep a Changelog](https://keepachangelog.com/id-ID/1.1.0/) dan proyek ini mengadopsi
[Semantic Versioning](https://semver.org/lang/id/).

Changelog otomatis per rilis dihasilkan oleh workflow `release.yml` dan diterbitkan ke
halaman **Releases** di GitHub — file ini menyimpan highlight rilis utama saja.

## [Unreleased]

### Ditambahkan

- Design system **Paper Editorial** (light editorial, hard shadow, serif-first) —
  mobile-first dengan top bar + floating pill nav.
- Library komponen Blade UI di `resources/views/components/ui/` (22 komponen:
  atoms / molecules / overlays / data).
- Pipeline pesan bot 18-langkah (blacklist → rate-limit → flow → knowledge base →
  AI reply → typing simulation → webhook dispatch → SSE feed → escalation check).
- 19 fitur baru tersebar di 5 grup: Quick Wins (template dinamis, business hours,
  multi-template per jenis pesan), Security (audit trail, multi-user RBAC, 2FA,
  rate limit + anti-spam, backup otomatis), Observability (alerting, analytics
  mendalam, chat viewer SSE), Intelligence (knowledge base FAQ matcher, AI smart
  reply Groq/OpenAI, webhook outbound + REST API, smart escalation), UX Polish
  (export CSV/PDF, PWA, onboarding wizard, Redis cache + queue worker).
- Workflow CI/CD baru: auto release dengan semantic versioning, changelog
  otomatis per kategori, push Docker image ke GHCR tiap push ke `main`.
- Dokumentasi:
  - Spec design: `docs/superpowers/specs/2026-04-17-dashboard-refresh-and-feature-expansion-design.md`
  - Implementation plan: `docs/superpowers/plans/2026-04-17-dashboard-refresh-and-feature-expansion.md`

### Diubah

- `layouts/app.blade.php` sekarang menggunakan Blade shell components alih-alih
  inline CSS — semua halaman ikut konsisten.
- 7 halaman existing (login, dashboard, allowlist, logs, settings, approved
  sessions) di-refactor ke Paper Editorial mobile-first.
- Auth diganti dari single-password `SimpleAuthMiddleware` ke Laravel Auth +
  Role-Based Access Control (owner / admin / viewer).

### Diperbaiki

- `.gitignore` sekarang mengizinkan dokumentasi di folder `docs/**/*.md` dan
  file utama seperti `CHANGELOG.md`, `CONTRIBUTING.md`, `LICENSE.md`,
  `CODE_OF_CONDUCT.md`.
- `storage/framework/views` tidak lagi bikin bootstrap error pertama kali
  clone karena auto-created oleh setup script + CI pipeline.

## [1.x] — Sebelumnya

Untuk riwayat sebelum refresh Paper Editorial, lihat tag git di bawah ini dan
halaman [Releases](https://github.com/el-pablos/wa-autoreply-bot/releases).

---

Convention commit yang dipakai di proyek ini (semua bahasa Indonesia kasual, 1 baris):

- `add:` — fitur baru
- `update:` — peningkatan fitur yang sudah ada
- `fix:` — perbaikan bug
- `refactor:` — refactor tanpa ubah behavior
- `remove:` — hapus fitur / kode
- `docs:` — dokumentasi
- `test:` — test saja
- `chore:` — dependency, config, housekeeping
