<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class MigrationsPhase4Test extends TestCase
{
    use RefreshDatabase;

    public function test_phase4_tables_are_created(): void
    {
        $tables = [
            'reply_templates',
            'business_hour_schedules',
            'oof_schedules',
            'message_type_templates',
            'blacklist',
            'rate_limit_violations',
            'activity_logs',
            'alert_channels',
            'alert_history',
            'analytics_daily_summary',
        ];

        foreach ($tables as $table) {
            $this->assertTrue(
                Schema::hasTable($table),
                "Expected table {$table} to exist after migrations."
            );
        }
    }

    public function test_phase4_key_columns_are_available(): void
    {
        $this->assertTrue(Schema::hasColumn('allowed_numbers', 'template_id'));
        $this->assertTrue(Schema::hasColumn('allowed_numbers', 'reply_count_today'));

        $this->assertTrue(Schema::hasColumn('message_logs', 'response_time_ms'));
    }
}
