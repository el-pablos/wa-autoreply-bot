<?php

namespace Tests\Feature;

use App\Models\MessageLog;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ChatLiveFeatureTest extends TestCase
{
    use RefreshDatabase;

    private function actingAsRole(string $role = 'owner')
    {
        $user = User::factory()->create([
            'role' => $role,
        ]);

        return $this->actingAs($user);
    }

    public function test_chat_live_index_accessible(): void
    {
        $response = $this->actingAsRole()->get('/chat-live');

        $response->assertOk();
        $response->assertSee('Live Incoming Stream');
    }

    public function test_chat_live_feed_returns_new_messages_after_id(): void
    {
        $old = MessageLog::query()->create([
            'from_number' => '628111',
            'message_text' => 'pesan lama',
            'message_type' => 'text',
            'is_allowed' => true,
            'replied' => true,
            'reply_text' => 'ok',
            'received_at' => now()->subMinutes(10),
        ]);

        $new = MessageLog::query()->create([
            'from_number' => '628222',
            'message_text' => 'pesan baru',
            'message_type' => 'text',
            'is_allowed' => true,
            'replied' => false,
            'reply_text' => null,
            'received_at' => now(),
        ]);

        $response = $this->actingAsRole()->getJson('/chat-live/feed?after_id=' . $old->id);

        $response->assertOk();
        $response->assertJsonPath('count', 1);
        $response->assertJsonPath('messages.0.id', $new->id);
        $response->assertJsonPath('messages.0.from_number', '628222');
    }

    public function test_chat_live_index_can_filter_by_number(): void
    {
        MessageLog::query()->create([
            'from_number' => '628111111111',
            'message_text' => 'alpha',
            'message_type' => 'text',
            'is_allowed' => true,
            'replied' => false,
            'reply_text' => null,
            'received_at' => now(),
        ]);

        MessageLog::query()->create([
            'from_number' => '628222222222',
            'message_text' => 'beta',
            'message_type' => 'text',
            'is_allowed' => true,
            'replied' => false,
            'reply_text' => null,
            'received_at' => now(),
        ]);

        $response = $this->actingAsRole()->get('/chat-live?number=628111');

        $response->assertOk();
        $response->assertSee('628111111111');
        $response->assertDontSee('628222222222');
    }
}
