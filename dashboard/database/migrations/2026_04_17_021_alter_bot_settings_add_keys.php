<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        $settings = [
            'business_hours_enabled' => 'false',
            'oof_enabled' => 'false',
            'ai_reply_enabled' => 'false',
            'ai_model' => 'groq/llama-3.1-8b-instant',
            'ai_system_prompt' => 'Kamu adalah asisten WA bisnis yang ramah, singkat, dan to the point.',
            'webhook_enabled' => 'false',
            'alert_enabled' => 'false',
            'escalation_enabled' => 'false',
            'rate_limit_enabled' => 'false',
            'rate_limit_window_seconds' => '60',
            'rate_limit_max_messages' => '5',
            'human_typing_enabled' => 'true',
        ];

        foreach ($settings as $key => $value) {
            DB::table('bot_settings')->updateOrInsert(
                ['key' => $key],
                ['value' => $value]
            );
        }
    }

    public function down(): void
    {
        DB::table('bot_settings')->whereIn('key', [
            'business_hours_enabled',
            'oof_enabled',
            'ai_reply_enabled',
            'ai_model',
            'ai_system_prompt',
            'webhook_enabled',
            'alert_enabled',
            'escalation_enabled',
            'rate_limit_enabled',
            'rate_limit_window_seconds',
            'rate_limit_max_messages',
            'human_typing_enabled',
        ])->delete();
    }
};
