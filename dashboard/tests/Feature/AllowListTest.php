<?php

namespace Tests\Feature;

use App\Models\AllowedNumber;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AllowListTest extends TestCase
{
    use RefreshDatabase;

    private function actingAsAuth()
    {
        return $this->withSession(['authenticated' => true]);
    }

    public function test_allowlist_index_accessible(): void
    {
        $response = $this->actingAsAuth()->get('/allowlist');
        $response->assertStatus(200);
    }

    public function test_can_store_new_number(): void
    {
        $response = $this->actingAsAuth()->post('/allowlist', [
            'phone_number' => '628123456789',
            'label'        => 'Test',
            'is_active'    => 1,
        ]);

        $response->assertRedirect('/allowlist');
        $this->assertDatabaseHas('allowed_numbers', ['phone_number' => '628123456789']);
    }

    public function test_can_store_number_with_plus_62_and_normalize_to_62(): void
    {
        $response = $this->actingAsAuth()->post('/allowlist', [
            'phone_number' => '+628123456789',
            'label'        => 'Plus62',
            'is_active'    => 1,
        ]);

        $response->assertRedirect('/allowlist');
        $this->assertDatabaseHas('allowed_numbers', ['phone_number' => '628123456789']);
    }

    public function test_can_store_number_with_08_and_normalize_to_62(): void
    {
        $response = $this->actingAsAuth()->post('/allowlist', [
            'phone_number' => '08123456789',
            'label'        => 'Zero8',
            'is_active'    => 1,
        ]);

        $response->assertRedirect('/allowlist');
        $this->assertDatabaseHas('allowed_numbers', ['phone_number' => '628123456789']);
    }

    public function test_invalid_phone_format_rejected(): void
    {
        $response = $this->actingAsAuth()->post('/allowlist', [
            'phone_number' => 'abc123', // format salah
        ]);
        $response->assertSessionHasErrors('phone_number');
    }

    public function test_noisy_phone_format_rejected(): void
    {
        $response = $this->actingAsAuth()->post('/allowlist', [
            'phone_number' => '+62 812-3456-789',
        ]);

        $response->assertSessionHasErrors('phone_number');
    }

    public function test_duplicate_number_rejected(): void
    {
        AllowedNumber::create(['phone_number' => '628111111111', 'is_active' => true]);

        $response = $this->actingAsAuth()->post('/allowlist', [
            'phone_number' => '628111111111',
        ]);
        $response->assertSessionHasErrors('phone_number');
    }

    public function test_duplicate_number_rejected_after_normalization(): void
    {
        AllowedNumber::create(['phone_number' => '628111111111', 'is_active' => true]);

        $response = $this->actingAsAuth()->post('/allowlist', [
            'phone_number' => '08111111111',
        ]);

        $response->assertSessionHasErrors('phone_number');
    }

    public function test_can_delete_number(): void
    {
        $number = AllowedNumber::create(['phone_number' => '628999999999', 'is_active' => true]);

        $response = $this->actingAsAuth()->delete("/allowlist/{$number->id}");
        $response->assertRedirect('/allowlist');
        $this->assertDatabaseMissing('allowed_numbers', ['phone_number' => '628999999999']);
    }

    public function test_can_toggle_active_status(): void
    {
        $number = AllowedNumber::create(['phone_number' => '628555555555', 'is_active' => true]);

        $this->actingAsAuth()->patch("/allowlist/{$number->id}/toggle");
        $this->assertDatabaseHas('allowed_numbers', ['phone_number' => '628555555555', 'is_active' => false]);
    }
}
