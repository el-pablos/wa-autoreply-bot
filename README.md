<div align="center">

# 🤖 WA Auto-Reply Bot

[![CI Status](https://github.com/el-pablos/wa-autoreply-bot/workflows/CI%20—%20Test%20%26%20Lint/badge.svg)](https://github.com/el-pablos/wa-autoreply-bot/actions)
[![Latest Release](https://img.shields.io/github/v/release/el-pablos/wa-autoreply-bot?color=25d366&label=versi+terbaru)](https://github.com/el-pablos/wa-autoreply-bot/releases)
[![License: MIT](https://img.shields.io/badge/lisensi-MIT-blue.svg)](LICENSE)
[![PHP](https://img.shields.io/badge/PHP-8.3-777BB4?logo=php)](https://php.net)
[![Node](https://img.shields.io/badge/Node.js-20-339933?logo=node.js)](https://nodejs.org)
[![Docker](https://img.shields.io/badge/Docker-ready-2496ED?logo=docker)](https://docker.com)
[![MySQL](https://img.shields.io/badge/MySQL-8.0-4479A1?logo=mysql)](https://mysql.com)

**Bot WhatsApp auto-reply berbasis Baileys dengan dashboard monitoring Laravel — dijalankan full via Docker.**

[🚀 Demo](#) · [📖 Dokumentasi](#cara-install) · [🐛 Laporkan Bug](https://github.com/el-pablos/wa-autoreply-bot/issues)

![Dashboard Preview](https://via.placeholder.com/800x400/0d1117/25d366?text=WA+Bot+Dashboard)

</div>

---

## 📖 Deskripsi Proyek

WA Auto-Reply Bot adalah sistem auto-responder WhatsApp yang dibangun di atas library **Baileys** (Node.js), dikombinasikan dengan dashboard monitoring berbasis **Laravel** yang bisa diakses lewat browser. Semua komponen dijalankan di dalam **Docker**, jadi gampang banget buat start, stop, dan restart.

Singkatnya, kalau ada yang WA kamu dan nomornya ada di **allow-list**, bot akan otomatis bales dengan pesan custom yang bisa kamu ubah kapan aja dari dashboard. Cocok buat kamu yang sering offline atau lagi sibuk tapi nggak mau ninggalin orang nunggu tanpa kabar.

**Kenapa pakai sistem ini?**

- **Tidak perlu server WA berbayar** — Baileys jalan langsung di Node.js, gratis
- **Full kontrol** — kamu tentukan siapa yang dapat auto-reply lewat allow-list
- **Dashboard real-time** — lihat semua pesan masuk, statistik, dan ubah pengaturan tanpa restart bot
- **Portable** — Docker compose, tinggal `up -d` dan jalan

---

## 🏗️ Arsitektur Proyek

```
┌─────────────────────────────────────────────────────────────┐
│                        INTERNET                             │
└─────────────────┬───────────────────────────────────────────┘
                  │
        ┌─────────▼──────────┐
        │  WhatsApp Network  │
        │  (Baileys WS)      │
        └─────────┬──────────┘
                  │ WebSocket
        ┌─────────▼──────────────────────────────────────────┐
        │              Docker Network                        │
        │                                                    │
        │  ┌──────────────┐    ┌────────────────────────┐   │
        │  │  wa-bot      │    │  wa-dashboard           │   │
        │  │  (Node.js)   │◄──►│  (Laravel + PHP-FPM)   │   │
        │  │  port 3001   │    │  port 80 → 8002         │   │
        │  └──────┬───────┘    └──────────┬─────────────┘   │
        │         │                       │                  │
        │         └──────────┬────────────┘                  │
        │                    │                               │
        │           ┌────────▼────────┐                      │
        │           │   wa-mysql      │                      │
        │           │  (MySQL 8.0)    │                      │
        │           │  port 3307      │                      │
        │           └─────────────────┘                      │
        └────────────────────────────────────────────────────┘
                  │
        ┌─────────▼──────────┐
        │  Nginx + Certbot   │
        │  monitoring-wa     │
        │  .tams.codes       │
        └────────────────────┘
```

### Alur Kerja Bot

```
Pesan WA Masuk
      │
      ▼
Apakah msg.key.fromMe? ──YES──► Abaikan
      │ NO
      ▼
Apakah dari grup?
      │ YES
      ▼
ignore_groups = true? ──YES──► Log saja, tidak reply
      │ NO
      │
      ▼ (private / grup dengan ignore=false)
Cek allow-list MySQL
      │
      ├──NOT FOUND──► Log (is_allowed=false, replied=false)
      │
      └──FOUND──►
            │
            ▼
      auto_reply_enabled = true?
            │ NO──► Log (is_allowed=true, replied=false)
            │ YES
            ▼
      Tunggu reply_delay_ms
            │
            ▼
      sock.sendMessage(pesan dari DB)
            │
            ▼
      Log (is_allowed=true, replied=true)
```

---

## 📊 ERD Database

```
┌─────────────────────────────┐
│       allowed_numbers       │
├─────────────────────────────┤
│ id            INT PK AI     │
│ phone_number  VARCHAR(20) UQ│
│ label         VARCHAR(100)  │
│ is_active     TINYINT(1)    │
│ created_at    TIMESTAMP     │
│ updated_at    TIMESTAMP     │
└─────────────────────────────┘

┌─────────────────────────────┐
│        message_logs         │
├─────────────────────────────┤
│ id            BIGINT PK AI  │
│ from_number   VARCHAR(20)   │
│ message_text  TEXT          │
│ message_type  VARCHAR(30)   │
│ is_allowed    TINYINT(1)    │
│ replied       TINYINT(1)    │
│ reply_text    TEXT          │
│ group_id      VARCHAR(50)   │
│ received_at   TIMESTAMP     │
└─────────────────────────────┘

┌─────────────────────────────┐
│        bot_settings         │
├─────────────────────────────┤
│ key           VARCHAR(60) PK│
│ value         TEXT          │
│ description   VARCHAR(255)  │
│ updated_at    TIMESTAMP     │
└─────────────────────────────┘
```

---

## 🛠️ Tech Stack

| Komponen | Teknologi | Keterangan |
|---|---|---|
| **WA Bot** | Node.js 20 + Baileys | Koneksi WhatsApp via WebSocket |
| **Dashboard** | Laravel 11 + PHP 8.3 | Web monitoring & manajemen |
| **Database** | MySQL 8.0 | Shared DB antara bot & dashboard |
| **Container** | Docker + Compose | Orkestrasi semua service |
| **Web Server** | Nginx | Reverse proxy + SSL termination |
| **SSL** | Let's Encrypt (Certbot) | HTTPS otomatis |
| **DNS** | Cloudflare | DNS management |
| **CI/CD** | GitHub Actions | Auto test, build, release |

---

## ✨ Fitur Lengkap

### 🤖 Bot (Node.js + Baileys)
- ✅ Auto-reply ke nomor yang ada di allow-list
- ✅ QR code endpoint di `/qr` untuk scan pertama kali
- ✅ Health check endpoint di `/health`
- ✅ Auto-reconnect jika koneksi terputus
- ✅ Support berbagai tipe pesan (teks, gambar, video, audio, sticker, lokasi, kontak)
- ✅ Configurable delay sebelum reply (biar keliatan natural)
- ✅ Toggle ignore pesan dari grup
- ✅ Update status bot di database (online/offline/connecting)
- ✅ Graceful shutdown

### 📊 Dashboard (Laravel)
- ✅ Login dengan password sederhana (single-user)
- ✅ Dashboard dengan statistik real-time
- ✅ Chart pesan 7 hari terakhir
- ✅ Allow-list CRUD (tambah, edit, hapus, toggle aktif)
- ✅ Filter & search allow-list
- ✅ Log viewer dengan filter lengkap (nomor, status reply, tanggal)
- ✅ Halaman pengaturan (ubah pesan reply, delay, toggle auto-reply)
- ✅ Mobile-First responsive design
- ✅ Dark mode by default

### 🔧 DevOps
- ✅ Docker Compose untuk development dan production
- ✅ Health check untuk semua service
- ✅ GitHub Actions CI/CD
- ✅ Auto-release dengan semantic versioning
- ✅ SSL otomatis dengan Certbot
- ✅ Cloudflare DNS management

---

## 🚀 Cara Install

### Prerequisite

- Docker & Docker Compose
- Git
- Domain dengan Cloudflare (untuk production)

### Development (Lokal)

```bash
# 1. Clone repo
git clone https://github.com/el-pablos/wa-autoreply-bot.git
cd wa-autoreply-bot

# 2. Setup env
cp bot/.env.example bot/.env
cp dashboard/.env.example dashboard/.env

# 3. Edit env (isi credential yang dibutuhkan)
nano bot/.env
nano dashboard/.env

# 4. Jalankan semua service
docker-compose up -d --build

# 5. Jalankan migrasi
docker-compose exec dashboard php artisan migrate

# 6. Lihat QR code untuk scan WA
docker-compose logs bot
# atau buka http://localhost:3001/qr

# 7. Buka dashboard
# http://localhost:8002
```

### Production (Server)

Ikuti instruksi lengkap di [LANGKAH 10](#langkah-10--setup-server-production) di mega prompt ini.

---

## 📱 Screenshot Dashboard

| Halaman | Deskripsi |
|---|---|
| Login | Halaman login dengan password |
| Dashboard | Statistik & chart pesan |
| Allow-List | Manajemen nomor WA |
| Log Pesan | Riwayat semua pesan masuk |
| Pengaturan | Konfigurasi bot |

---

## 🔧 Konfigurasi Bot Settings

| Key | Default | Deskripsi |
|---|---|---|
| `auto_reply_enabled` | `true` | Toggle auto-reply on/off |
| `reply_message` | *(lihat DB)* | Pesan yang dikirim bot |
| `reply_delay_ms` | `1500` | Delay sebelum balas (ms) |
| `bot_status` | `offline` | Status koneksi bot |
| `ignore_groups` | `true` | Abaikan pesan dari grup |

---

## 👤 Kontributor

<table>
  <tr>
    <td align="center">
      <a href="https://github.com/el-pablos">
        <img src="https://avatars.githubusercontent.com/el-pablos" width="80" style="border-radius:50%"><br>
        <b>Tama (el-pablos)</b>
      </a>
      <br>Creator & Maintainer
    </td>
  </tr>
</table>

---

## 📄 Lisensi

Proyek ini menggunakan lisensi **MIT**. Bebas dipakai, dimodifikasi, dan didistribusikan.

---

<div align="center">
  <sub>Dibuat dengan ☕ oleh <a href="https://github.com/el-pablos">el-pablos</a></sub>
</div>
