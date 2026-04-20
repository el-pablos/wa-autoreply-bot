<?php

namespace Tests\Feature;

use App\Models\AlertChannel;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AlertFeatureTest extends TestCase
{
    use RefreshDatabase;

    private function actingAsUser()
    {
        $user = User::factory()->create();

        return $this->actingAs($user);
    }

    public function test_alerts_index_accessible(): void
    {
        $response = $this->actingAsUser()->get('/alerts');

        $response->assertOk();
        $response->assertSee('Alert Channels');
    }

    public function test_can_create_email_alert_channel(): void
    {
        $response = $this->actingAsUser()->post('/alerts/channels', [
            'target' => 'owner@gmail.com',
            'is_active' => 'true',
        ]);

        $response->assertRedirect('/alerts');

        $this->assertDatabaseHas('alert_channels', [
            'type' => 'email',
            'target' => 'owner@gmail.com',
            'is_active' => true,
        ]);

        $this->assertDatabaseHas('activity_logs', [
            'action' => 'alerts.channel.created',
        ]);
    }

    public function test_owner_can_send_test_alert(): void
    {
        $channel = AlertChannel::query()->create([
            'type' => 'email',
            'target' => 'ops@example.test',
            'is_active' => true,
        ]);

        $response = $this->actingAsUser()->post("/alerts/channels/{$channel->id}/test");

        $response->assertRedirect('/alerts');

        $this->assertDatabaseHas('alert_history', [
            'channel_id' => $channel->id,
            'severity' => 'info',
            'success' => true,
        ]);

        $this->assertDatabaseHas('activity_logs', [
            'action' => 'alerts.channel.tested',
        ]);
    }

    public function test_can_toggle_alert_channel(): void
    {
        $channel = AlertChannel::query()->create([
            'type' => 'email',
            'target' => 'owner@gmail.com',
            'is_active' => true,
        ]);

        $response = $this->actingAsUser()->patch("/alerts/channels/{$channel->id}/toggle");

        $response->assertRedirect('/alerts');

        $this->assertFalse((bool) $channel->fresh()?->is_active);
    }

    public function test_invalid_email_target_rejected(): void
    {
        $response = $this->actingAsUser()->post('/alerts/channels', [
            'target' => 'not-an-email',
        ]);

        $response->assertSessionHasErrors('target');
    }
}
