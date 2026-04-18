<?php

namespace Tests\Feature;

use App\Models\ApprovedSession;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ApprovedSessionFeatureTest extends TestCase
{
    use RefreshDatabase;

    private function actingAsRole(string $role = 'owner')
    {
        $user = User::factory()->create([
            'role' => $role,
        ]);

        return $this->actingAs($user);
    }

    public function test_index_redirects_to_login_when_not_authenticated(): void
    {
        $response = $this->get('/approved-sessions');

        $response->assertRedirect('/login');
    }

    public function test_index_shows_active_and_history_sessions(): void
    {
        ApprovedSession::create([
            'phone_number' => '628111100001',
            'approved_at' => now()->subMinutes(10),
            'last_activity_at' => now()->subMinute(),
            'expires_at' => now()->addHour(),
            'is_active' => true,
            'approved_by' => 'admin',
        ]);

        ApprovedSession::create([
            'phone_number' => '628111100002',
            'approved_at' => now()->subHours(2),
            'last_activity_at' => now()->subHours(2),
            'expires_at' => now()->subMinute(),
            'is_active' => true,
            'approved_by' => 'admin',
        ]);

        $response = $this->actingAsRole()->get('/approved-sessions');

        $response->assertOk();
        $response->assertSee('Sesi Aktif');
        $response->assertSee('Riwayat Sesi');
        $response->assertSee('628111100001');
        $response->assertSee('628111100002');
    }

    public function test_can_revoke_active_session(): void
    {
        $session = ApprovedSession::create([
            'phone_number' => '628111100003',
            'approved_at' => now()->subMinutes(15),
            'last_activity_at' => now()->subMinutes(2),
            'expires_at' => now()->addMinutes(20),
            'is_active' => true,
            'approved_by' => 'admin',
        ]);

        $response = $this->actingAsRole()->post("/approved-sessions/{$session->id}/revoke");

        $response->assertRedirect();
        $response->assertSessionHas('success');
        $this->assertDatabaseHas('approved_sessions', [
            'id' => $session->id,
            'is_active' => false,
        ]);
        $this->assertDatabaseHas('activity_logs', ['action' => 'approved_session.revoked']);
        $this->assertNotNull($session->fresh()->revoked_at);
    }

    public function test_revoke_returns_error_for_non_active_or_expired_session(): void
    {
        $session = ApprovedSession::create([
            'phone_number' => '628111100004',
            'approved_at' => now()->subHours(2),
            'last_activity_at' => now()->subHours(2),
            'expires_at' => now()->subMinute(),
            'is_active' => true,
            'approved_by' => 'admin',
        ]);

        $response = $this->actingAsRole()->post("/approved-sessions/{$session->id}/revoke");

        $response->assertRedirect();
        $response->assertSessionHas('error');
        $this->assertNull($session->fresh()->revoked_at);
    }

    public function test_viewer_cannot_revoke_session(): void
    {
        $session = ApprovedSession::create([
            'phone_number' => '628111100005',
            'approved_at' => now()->subMinutes(10),
            'last_activity_at' => now()->subMinute(),
            'expires_at' => now()->addHour(),
            'is_active' => true,
            'approved_by' => 'admin',
        ]);

        $response = $this->actingAsRole('viewer')->post("/approved-sessions/{$session->id}/revoke");

        $response->assertForbidden();
        $this->assertTrue((bool) $session->fresh()?->is_active);
        $this->assertDatabaseMissing('activity_logs', ['action' => 'approved_session.revoked']);
    }
}
