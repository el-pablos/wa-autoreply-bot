# Changelog

Semua perubahan penting di proyek ini didokumentasikan di file ini. Format mengikuti
[Keep a Changelog](https://keepachangelog.com/id-ID/1.1.0/) dan proyek ini mengadopsi
[Semantic Versioning](https://semver.org/lang/id/).

Changelog otomatis per rilis dihasilkan oleh workflow `release.yml` dan diterbitkan ke
halaman **Releases** di GitHub — file ini menyimpan highlight rilis utama saja.

## [Unreleased]

### Ditambahkan

- Integrasi alert report Gmail via EmailJS di halaman alerts dashboard.
- Validasi channel alert dipersempit ke email saja (target wajib format email valid).
- Test suite dashboard disederhanakan ke mode single-operator (tanpa role/RBAC).
- Test suite bot ditulis ulang mengikuti pipeline aktual tanpa modul AI/FAQ/webhook/escalation.

### Diubah

- Public API bot (`/api`) dinonaktifkan dari runtime.
- Seeder dashboard dibersihkan dari key/kolom yang sudah tidak dipakai
  (`role`, `two_factor_enabled`, `ai_*`, `webhook_enabled`, `escalation_enabled`).
- Dependensi 2FA Laravel dan SDK AI (OpenAI/Groq) dihapus dari dependency aktif.

### Diperbaiki

- Konsistensi test terhadap skema users tanpa kolom `role`.
- Drift antara kode aktif dan test legacy untuk fitur yang sudah dihapus.
- Workflow backup terjadwal dihapus agar sesuai scope produk saat ini.

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
