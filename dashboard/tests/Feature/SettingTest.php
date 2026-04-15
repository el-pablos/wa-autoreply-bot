<?php

namespace Tests\Feature;

use App\Models\BotSetting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SettingTest extends TestCase
{
    use RefreshDatabase;

    private function actingAsAuth()
    {
        return $this->withSession(['authenticated' => true]);
    }

    public function test_settings_page_accessible(): void
    {
        $response = $this->actingAsAuth()->get('/settings');
        $response->assertStatus(200);
    }

    public function test_can_update_reply_message(): void
    {
        BotSetting::create(['key' => 'reply_message',      'value' => 'lama']);
        BotSetting::create(['key' => 'reply_delay_ms',     'value' => '1500']);
        BotSetting::create(['key' => 'auto_reply_enabled', 'value' => 'false']);
        BotSetting::create(['key' => 'ignore_groups',      'value' => 'false']);

        $response = $this->actingAsAuth()->post('/settings', [
            'reply_message'      => 'Pesan baru dari test',
            'reply_delay_ms'     => 2000,
            'auto_reply_enabled' => 'true',
            'ignore_groups'      => 'true',
        ]);

        $response->assertRedirect('/settings');
        $this->assertDatabaseHas('bot_settings', [
            'key' => 'reply_message', 'value' => 'Pesan baru dari test',
        ]);
    }

    public function test_empty_reply_message_rejected(): void
    {
        $response = $this->actingAsAuth()->post('/settings', [
            'reply_message'  => '',
            'reply_delay_ms' => 1000,
        ]);
        $response->assertSessionHasErrors('reply_message');
    }
}
