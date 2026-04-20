<?php

namespace Tests\Feature;

use App\Models\AllowedNumber;
use App\Models\ReplyTemplate;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AllowListTest extends TestCase
{
    use RefreshDatabase;

    private function actingAsUser()
    {
        $user = User::factory()->create();

        return $this->actingAs($user);
    }

    public function test_allowlist_index_accessible(): void
    {
        $response = $this->actingAsUser()->get('/allowlist');
        $response->assertStatus(200);
    }

    public function test_can_store_new_number(): void
    {
        $response = $this->actingAsUser()->post('/allowlist', [
            'phone_number' => '628123456789',
            'label'        => 'Test',
            'is_active'    => 1,
        ]);

        $response->assertRedirect('/allowlist');
        $this->assertDatabaseHas('allowed_numbers', ['phone_number' => '628123456789']);
        $this->assertDatabaseHas('activity_logs', ['action' => 'allowlist.created']);
    }

    public function test_can_store_number_with_specific_reply_template(): void
    {
        $template = ReplyTemplate::query()->create([
            'name' => 'Template VIP',
            'body' => 'Halo {{nama}}, ini jalur VIP.',
            'is_default' => false,
            'is_active' => true,
        ]);

        $response = $this->actingAsUser()->post('/allowlist', [
            'phone_number' => '628100000001',
            'label' => 'VIP',
            'template_id' => $template->id,
            'is_active' => 1,
        ]);

        $response->assertRedirect('/allowlist');
        $this->assertDatabaseHas('allowed_numbers', [
            'phone_number' => '628100000001',
            'template_id' => $template->id,
        ]);
    }

    public function test_can_store_number_with_plus_62_and_normalize_to_62(): void
    {
        $response = $this->actingAsUser()->post('/allowlist', [
            'phone_number' => '+628123456789',
            'label'        => 'Plus62',
            'is_active'    => 1,
        ]);

        $response->assertRedirect('/allowlist');
        $this->assertDatabaseHas('allowed_numbers', ['phone_number' => '628123456789']);
    }

    public function test_can_store_number_with_08_and_normalize_to_62(): void
    {
        $response = $this->actingAsUser()->post('/allowlist', [
            'phone_number' => '08123456789',
            'label'        => 'Zero8',
            'is_active'    => 1,
        ]);

        $response->assertRedirect('/allowlist');
        $this->assertDatabaseHas('allowed_numbers', ['phone_number' => '628123456789']);
    }

    public function test_invalid_phone_format_rejected(): void
    {
        $response = $this->actingAsUser()->post('/allowlist', [
            'phone_number' => 'abc123', // format salah
        ]);
        $response->assertSessionHasErrors('phone_number');
    }

    public function test_noisy_phone_format_rejected(): void
    {
        $response = $this->actingAsUser()->post('/allowlist', [
            'phone_number' => '+62 812-3456-789',
        ]);

        $response->assertSessionHasErrors('phone_number');
    }

    public function test_duplicate_number_rejected(): void
    {
        AllowedNumber::create(['phone_number' => '628111111111', 'is_active' => true]);

        $response = $this->actingAsUser()->post('/allowlist', [
            'phone_number' => '628111111111',
        ]);
        $response->assertSessionHasErrors('phone_number');
    }

    public function test_duplicate_number_rejected_after_normalization(): void
    {
        AllowedNumber::create(['phone_number' => '628111111111', 'is_active' => true]);

        $response = $this->actingAsUser()->post('/allowlist', [
            'phone_number' => '08111111111',
        ]);

        $response->assertSessionHasErrors('phone_number');
    }

    public function test_unknown_template_id_rejected(): void
    {
        $response = $this->actingAsUser()->post('/allowlist', [
            'phone_number' => '6281222333444',
            'template_id' => 999999,
            'is_active' => 1,
        ]);

        $response->assertSessionHasErrors('template_id');
    }

    public function test_can_delete_number(): void
    {
        $number = AllowedNumber::create(['phone_number' => '628999999999', 'is_active' => true]);

        $response = $this->actingAsUser()->delete("/allowlist/{$number->id}");
        $response->assertRedirect('/allowlist');
        $this->assertDatabaseMissing('allowed_numbers', ['phone_number' => '628999999999']);
        $this->assertDatabaseHas('activity_logs', ['action' => 'allowlist.deleted']);
    }

    public function test_can_toggle_active_status(): void
    {
        $number = AllowedNumber::create(['phone_number' => '628555555555', 'is_active' => true]);

        $this->actingAsUser()->patch("/allowlist/{$number->id}/toggle");
        $this->assertDatabaseHas('allowed_numbers', ['phone_number' => '628555555555', 'is_active' => false]);
        $this->assertDatabaseHas('activity_logs', ['action' => 'allowlist.toggled']);
    }
}
