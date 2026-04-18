<?php

namespace Tests\Feature;

use App\Models\AiConversationHistory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AiFeatureTest extends TestCase
{
    use RefreshDatabase;

    private function actingAsRole(string $role = 'owner')
    {
        $user = User::factory()->create([
            'role' => $role,
        ]);

        return $this->actingAs($user);
    }

    public function test_ai_index_accessible(): void
    {
        $response = $this->actingAsRole()->get('/ai');

        $response->assertOk();
        $response->assertSee('AI Reply Configuration');
    }

    public function test_owner_can_update_ai_settings(): void
    {
        $response = $this->actingAsRole('owner')->post('/ai', [
            'ai_model' => 'openai/gpt-4.1-mini',
            'ai_system_prompt' => 'Jawab singkat dan ramah.',
            'ai_max_context_messages' => 10,
            'ai_temperature' => 0.6,
            'ai_fallback_provider' => 'openai',
            'ai_reply_enabled' => 'true',
        ]);

        $response->assertRedirect('/ai');

        $this->assertDatabaseHas('bot_settings', [
            'key' => 'ai_model',
            'value' => 'openai/gpt-4.1-mini',
        ]);
        $this->assertDatabaseHas('bot_settings', [
            'key' => 'ai_reply_enabled',
            'value' => 'true',
        ]);

        $this->assertDatabaseHas('activity_logs', [
            'action' => 'ai.settings.updated',
        ]);
    }

    public function test_ai_history_filter_works(): void
    {
        AiConversationHistory::query()->create([
            'phone_number' => '628111111111',
            'role' => 'assistant',
            'content' => 'Halo',
            'tokens' => 20,
        ]);
        AiConversationHistory::query()->create([
            'phone_number' => '628222222222',
            'role' => 'assistant',
            'content' => 'Hai',
            'tokens' => 30,
        ]);

        $response = $this->actingAsRole()->get('/ai?phone_number=628111');

        $response->assertOk();
        $response->assertSee('628111111111');
        $response->assertDontSee('628222222222');
    }

    public function test_viewer_cannot_update_ai_settings(): void
    {
        $response = $this->actingAsRole('viewer')->post('/ai', [
            'ai_model' => 'groq/llama-3.1-8b-instant',
            'ai_system_prompt' => 'No',
            'ai_max_context_messages' => 12,
            'ai_temperature' => 0.4,
            'ai_fallback_provider' => 'none',
        ]);

        $response->assertForbidden();

        $this->assertDatabaseMissing('activity_logs', [
            'action' => 'ai.settings.updated',
        ]);
    }
}
