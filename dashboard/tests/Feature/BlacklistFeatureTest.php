<?php

namespace Tests\Feature;

use App\Models\Blacklist;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BlacklistFeatureTest extends TestCase
{
    use RefreshDatabase;

    private function actingAsUser()
    {
        $user = User::factory()->create();

        return $this->actingAs($user);
    }

    public function test_blacklist_index_accessible(): void
    {
        $response = $this->actingAsUser()->get('/blacklist');

        $response->assertOk();
        $response->assertSee('Blacklist Numbers');
    }

    public function test_can_store_blacklist_number_and_normalize_phone(): void
    {
        $response = $this->actingAsUser()->post('/blacklist', [
            'phone_number' => '0812-3456-7890',
            'reason' => 'Spam berulang',
            'is_active' => 'true',
        ]);

        $response->assertRedirect('/blacklist');

        $this->assertDatabaseHas('blacklist', [
            'phone_number' => '6281234567890',
            'reason' => 'Spam berulang',
            'is_active' => true,
        ]);

        $this->assertDatabaseHas('activity_logs', [
            'action' => 'blacklist.created',
        ]);
    }

    public function test_can_toggle_blacklist_status(): void
    {
        $entry = Blacklist::query()->create([
            'phone_number' => '628111111111',
            'reason' => 'test',
            'blocked_at' => now(),
            'blocked_by' => 'owner@example.test',
            'is_active' => true,
        ]);

        $response = $this->actingAsUser()->patch("/blacklist/{$entry->id}/toggle");

        $response->assertRedirect('/blacklist');
        $this->assertFalse((bool) $entry->fresh()?->is_active);
    }

    public function test_invalid_phone_rejected_for_blacklist(): void
    {
        $response = $this->actingAsUser()->post('/blacklist', [
            'phone_number' => 'abc',
            'reason' => 'blocked',
        ]);

        $response->assertSessionHasErrors('phone_number');
    }
}
