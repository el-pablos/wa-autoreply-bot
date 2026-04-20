<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class BotSettingSeeder extends Seeder
{
    public function run(): void
    {
        $settings = [
            ['key' => 'reply_message', 'value' => 'Halo! Pesan kamu sudah masuk. Tim kami akan merespons secepatnya.', 'description' => 'Pesan balasan default'],
            ['key' => 'reply_delay_ms', 'value' => '1500', 'description' => 'Delay balas dalam milidetik'],
            ['key' => 'auto_reply_enabled', 'value' => 'true', 'description' => 'Aktif/nonaktif auto reply'],
            ['key' => 'ignore_groups', 'value' => 'true', 'description' => 'Abaikan pesan dari grup'],
            ['key' => 'business_hours_enabled', 'value' => 'false', 'description' => 'Aktifkan pembatasan jam kerja'],
            ['key' => 'oof_enabled', 'value' => 'false', 'description' => 'Aktifkan out of office message'],
            ['key' => 'alert_enabled', 'value' => 'false', 'description' => 'Aktifkan alerting bot'],
            ['key' => 'rate_limit_enabled', 'value' => 'false', 'description' => 'Aktifkan rate limiter'],
            ['key' => 'rate_limit_window_seconds', 'value' => '60', 'description' => 'Window rate limit detik'],
            ['key' => 'rate_limit_max_messages', 'value' => '5', 'description' => 'Batas pesan per window'],
            ['key' => 'human_typing_enabled', 'value' => 'true', 'description' => 'Aktifkan simulasi human typing'],
        ];

        foreach ($settings as $setting) {
            DB::table('bot_settings')->updateOrInsert(
                ['key' => $setting['key']],
                [
                    'value' => $setting['value'],
                    'description' => $setting['description'],
                ]
            );
        }
    }
}
