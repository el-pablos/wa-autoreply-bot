<?php

namespace Tests\Feature;

use App\Models\MessageLog;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AnalyticsFeatureTest extends TestCase
{
    use RefreshDatabase;

    private function actingAsUser()
    {
        $user = User::factory()->create();

        return $this->actingAs($user);
    }

    public function test_analytics_index_accessible(): void
    {
        $response = $this->actingAsUser()->get('/analytics');

        $response->assertOk();
        $response->assertSeeText('Traffic');
    }

    public function test_analytics_displays_top_numbers_and_metrics(): void
    {
        MessageLog::query()->create([
            'from_number' => '628111111111',
            'message_text' => 'Halo 1',
            'message_type' => 'text',
            'is_allowed' => true,
            'replied' => true,
            'reply_text' => 'Balas 1',
            'received_at' => now()->subDay(),
            'response_time_ms' => 400,
        ]);

        MessageLog::query()->create([
            'from_number' => '628111111111',
            'message_text' => 'Halo 2',
            'message_type' => 'text',
            'is_allowed' => true,
            'replied' => false,
            'reply_text' => null,
            'received_at' => now()->subHours(8),
            'response_time_ms' => 1200,
        ]);

        MessageLog::query()->create([
            'from_number' => '628222222222',
            'message_text' => 'Halo 3',
            'message_type' => 'text',
            'is_allowed' => false,
            'replied' => false,
            'reply_text' => null,
            'received_at' => now()->subHours(3),
            'response_time_ms' => 900,
        ]);

        $response = $this->actingAsUser()->get('/analytics?days=7');

        $response->assertOk();
        $response->assertSee('Most Active Senders');
        $response->assertSee('628111111111');
    }

    public function test_second_operator_login_can_access_analytics_page(): void
    {
        $response = $this->actingAsUser()->get('/analytics');

        $response->assertOk();
    }
}
