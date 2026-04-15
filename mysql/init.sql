-- ============================================================
-- WA Auto-Reply Bot — Database Initialization Script
-- ============================================================

CREATE DATABASE IF NOT EXISTS wabot CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE wabot;

-- ============================================================
-- TABLE: allowed_numbers
-- Nomor WA yang boleh mendapat auto-reply
-- ============================================================
CREATE TABLE IF NOT EXISTS allowed_numbers (
    id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    phone_number  VARCHAR(20)  NOT NULL UNIQUE COMMENT 'Format: 628xxx tanpa tanda + atau spasi',
    label         VARCHAR(100) NULL     COMMENT 'Nama/catatan opsional untuk nomor ini',
    is_active     TINYINT(1)   NOT NULL DEFAULT 1 COMMENT '1=aktif mendapat reply, 0=nonaktif',
    created_at    TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at    TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_phone_active (phone_number, is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- TABLE: message_logs
-- Log seluruh pesan masuk beserta status reply-nya
-- ============================================================
CREATE TABLE IF NOT EXISTS message_logs (
    id            BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    from_number   VARCHAR(20)  NOT NULL COMMENT 'Nomor pengirim',
    message_text  TEXT         NULL     COMMENT 'Isi pesan yang masuk',
    message_type  VARCHAR(30)  NOT NULL DEFAULT 'text' COMMENT 'text/image/audio/video/document/sticker/location/other',
    is_allowed    TINYINT(1)   NOT NULL DEFAULT 0 COMMENT '1=ada di allow-list',
    replied       TINYINT(1)   NOT NULL DEFAULT 0 COMMENT '1=sudah dibalas oleh bot',
    reply_text    TEXT         NULL     COMMENT 'Teks yang dikirim bot sebagai balasan',
    group_id      VARCHAR(50)  NULL     COMMENT 'Diisi jika pesan dari grup, NULL jika private',
    received_at   TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_from_number    (from_number),
    INDEX idx_received_at    (received_at),
    INDEX idx_is_allowed     (is_allowed),
    INDEX idx_replied        (replied)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- TABLE: bot_settings
-- Key-value config yang bisa diubah dari dashboard
-- ============================================================
CREATE TABLE IF NOT EXISTS bot_settings (
    `key`        VARCHAR(60)  NOT NULL PRIMARY KEY,
    `value`      TEXT         NOT NULL,
    description  VARCHAR(255) NULL,
    updated_at   TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- SEED: default settings
-- ============================================================
INSERT INTO bot_settings (`key`, `value`, description) VALUES
  ('auto_reply_enabled', 'true',  'Toggle auto-reply on/off. Value: true | false'),
  ('reply_message',      'Haiii, Tama nya lagi offline nih 😴 tunggu beberapa menit lagi ya atau kalau urgent bisa langsung call aku orait~', 'Pesan balasan otomatis yang dikirim bot'),
  ('reply_delay_ms',     '1500',  'Delay sebelum bot balas (milliseconds), biar keliatan natural'),
  ('bot_status',         'offline', 'Status koneksi bot: online | offline | connecting'),
  ('ignore_groups',      'true',  'Abaikan pesan dari grup. Value: true | false')
ON DUPLICATE KEY UPDATE `value` = VALUES(`value`);
