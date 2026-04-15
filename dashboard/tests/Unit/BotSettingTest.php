<?php

namespace Tests\Unit;

use App\Models\BotSetting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BotSettingTest extends TestCase
{
    use RefreshDatabase;

    public function test_get_value_returns_correct_value(): void
    {
        BotSetting::create(['key' => 'test_key', 'value' => 'test_value']);
        $this->assertEquals('test_value', BotSetting::getValue('test_key'));
    }

    public function test_get_value_returns_default_if_not_found(): void
    {
        $result = BotSetting::getValue('nonexistent_key', 'default');
        $this->assertEquals('default', $result);
    }

    public function test_get_value_returns_null_if_not_found_no_default(): void
    {
        $result = BotSetting::getValue('nonexistent_key');
        $this->assertNull($result);
    }

    public function test_set_value_creates_new_setting(): void
    {
        BotSetting::setValue('new_key', 'new_value');
        $this->assertDatabaseHas('bot_settings', ['key' => 'new_key', 'value' => 'new_value']);
    }

    public function test_set_value_updates_existing_setting(): void
    {
        BotSetting::create(['key' => 'auto_reply_enabled', 'value' => 'false']);
        BotSetting::setValue('auto_reply_enabled', 'true');
        $this->assertDatabaseHas('bot_settings', ['key' => 'auto_reply_enabled', 'value' => 'true']);
    }
}
