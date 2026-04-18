<?php

namespace Tests\Feature;

use App\Models\AlertChannel;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AlertFeatureTest extends TestCase
{
    use RefreshDatabase;

    private function actingAsRole(string $role = 'owner')
    {
        $user = User::factory()->create([
            'role' => $role,
        ]);

        return $this->actingAs($user);
    }

    public function test_alerts_index_accessible(): void
    {
        $response = $this->actingAsRole()->get('/alerts');

        $response->assertOk();
        $response->assertSee('Alert Channels');
    }

    public function test_owner_can_create_alert_channel(): void
    {
        $response = $this->actingAsRole('owner')->post('/alerts/channels', [
            'type' => 'wa',
            'target' => '628111111111',
            'is_active' => 'true',
        ]);

        $response->assertRedirect('/alerts');

        $this->assertDatabaseHas('alert_channels', [
            'type' => 'wa',
            'target' => '628111111111',
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

        $response = $this->actingAsRole('owner')->post("/alerts/channels/{$channel->id}/test");

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

    public function test_owner_can_toggle_alert_channel(): void
    {
        $channel = AlertChannel::query()->create([
            'type' => 'wa',
            'target' => '628111111111',
            'is_active' => true,
        ]);

        $response = $this->actingAsRole('owner')->patch("/alerts/channels/{$channel->id}/toggle");

        $response->assertRedirect('/alerts');

        $this->assertFalse((bool) $channel->fresh()?->is_active);
    }

    public function test_viewer_cannot_mutate_alert_channels(): void
    {
        $response = $this->actingAsRole('viewer')->post('/alerts/channels', [
            'type' => 'wa',
            'target' => '628999999999',
        ]);

        $response->assertForbidden();

        $this->assertDatabaseMissing('alert_channels', [
            'target' => '628999999999',
        ]);
    }
}
