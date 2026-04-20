<?php

namespace Tests\Feature;

use App\Models\BotSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SettingTest extends TestCase
{
    use RefreshDatabase;

    private function actingAsUser()
    {
        $user = User::factory()->create();

        return $this->actingAs($user);
    }

    public function test_settings_page_accessible(): void
    {
        $response = $this->actingAsUser()->get('/settings');
        $response->assertStatus(200);
    }

    public function test_can_update_reply_message(): void
    {
        BotSetting::create(['key' => 'reply_message',      'value' => 'lama']);
        BotSetting::create(['key' => 'reply_delay_ms',     'value' => '1500']);
        BotSetting::create(['key' => 'auto_reply_enabled', 'value' => 'false']);
        BotSetting::create(['key' => 'ignore_groups',      'value' => 'false']);

        $response = $this->actingAsUser()->post('/settings', [
            'reply_message'      => 'Pesan baru dari test',
            'reply_delay_ms'     => 2000,
            'auto_reply_enabled' => 'true',
            'ignore_groups'      => 'true',
        ]);

        $response->assertRedirect('/settings');
        $this->assertDatabaseHas('bot_settings', [
            'key' => 'reply_message', 'value' => 'Pesan baru dari test',
        ]);
        $this->assertDatabaseHas('activity_logs', ['action' => 'settings.updated']);
    }

    public function test_empty_reply_message_rejected(): void
    {
        $response = $this->actingAsUser()->post('/settings', [
            'reply_message'  => '',
            'reply_delay_ms' => 1000,
        ]);
        $response->assertSessionHasErrors('reply_message');
    }
}
