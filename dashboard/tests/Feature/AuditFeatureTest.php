<?php

namespace Tests\Feature;

use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuditFeatureTest extends TestCase
{
    use RefreshDatabase;

    private function actingAsUser()
    {
        $user = User::factory()->create();

        return $this->actingAs($user);
    }

    public function test_audit_index_accessible(): void
    {
        ActivityLog::query()->create([
            'actor' => 'owner@example.test',
            'action' => 'test.event',
            'target_type' => 'App\\Models\\User',
            'target_id' => 1,
            'old_value' => null,
            'new_value' => ['ok' => true],
            'ip_address' => '127.0.0.1',
            'user_agent' => 'phpunit',
        ]);

        $response = $this->actingAsUser()->get('/audit');

        $response->assertOk();
        $response->assertSee('Audit Events');
        $response->assertSee('test.event');
    }

    public function test_audit_filter_by_actor(): void
    {
        ActivityLog::query()->create([
            'actor' => 'alpha@example.test',
            'action' => 'event.alpha',
            'target_type' => null,
            'target_id' => null,
            'old_value' => null,
            'new_value' => null,
            'ip_address' => null,
            'user_agent' => null,
        ]);

        ActivityLog::query()->create([
            'actor' => 'beta@example.test',
            'action' => 'event.beta',
            'target_type' => null,
            'target_id' => null,
            'old_value' => null,
            'new_value' => null,
            'ip_address' => null,
            'user_agent' => null,
        ]);

        $response = $this->actingAsUser()->get('/audit?actor=alpha');

        $response->assertOk();
        $response->assertSee('event.alpha');
        $response->assertDontSee('event.beta');
    }
}
