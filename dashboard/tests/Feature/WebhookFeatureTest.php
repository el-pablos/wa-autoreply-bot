<?php

namespace Tests\Feature;

use App\Models\ApiKey;
use App\Models\User;
use App\Models\WebhookEndpoint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WebhookFeatureTest extends TestCase
{
    use RefreshDatabase;

    private function actingAsRole(string $role = 'owner')
    {
        $user = User::factory()->create([
            'role' => $role,
        ]);

        return $this->actingAs($user);
    }

    public function test_webhooks_index_accessible(): void
    {
        $response = $this->actingAsRole()->get('/webhooks');

        $response->assertOk();
        $response->assertSee('Webhook Endpoints');
    }

    public function test_owner_can_create_webhook_endpoint(): void
    {
        $response = $this->actingAsRole('owner')->post('/webhooks/endpoints', [
            'name' => 'CRM Hook',
            'url' => 'https://example.test/webhooks/wa',
            'secret' => 'super-secret',
            'events' => ['message_received', 'reply_sent'],
            'is_active' => 'true',
        ]);

        $response->assertRedirect('/webhooks');

        $this->assertDatabaseHas('webhook_endpoints', [
            'name' => 'CRM Hook',
            'url' => 'https://example.test/webhooks/wa',
            'is_active' => true,
        ]);

        $this->assertDatabaseHas('activity_logs', [
            'action' => 'webhooks.endpoint.created',
        ]);
    }

    public function test_owner_can_generate_api_key_and_hash_is_stored(): void
    {
        $response = $this->actingAsRole('owner')->post('/webhooks/api-keys', [
            'name' => 'Public API Client',
            'scopes' => 'send,logs',
        ]);

        $response->assertRedirect('/webhooks');
        $response->assertSessionHas('new_api_key');

        $plain = (string) session('new_api_key');
        $this->assertNotSame('', $plain);

        $row = ApiKey::query()->where('name', 'Public API Client')->first();
        $this->assertNotNull($row);
        $this->assertNotSame($plain, (string) $row->key_hash);
        $this->assertSame(hash('sha256', $plain), (string) $row->key_hash);

        $this->assertDatabaseHas('activity_logs', [
            'action' => 'webhooks.api_key.created',
        ]);
    }

    public function test_owner_can_revoke_api_key(): void
    {
        $apiKey = ApiKey::query()->create([
            'name' => 'Client A',
            'key_hash' => str_repeat('a', 64),
            'scopes' => ['send'],
            'revoked_at' => null,
        ]);

        $response = $this->actingAsRole('owner')->patch("/webhooks/api-keys/{$apiKey->id}/revoke");

        $response->assertRedirect('/webhooks');
        $this->assertNotNull($apiKey->fresh()?->revoked_at);

        $this->assertDatabaseHas('activity_logs', [
            'action' => 'webhooks.api_key.revoked',
        ]);
    }

    public function test_viewer_cannot_mutate_webhooks_or_api_keys(): void
    {
        $endpointResponse = $this->actingAsRole('viewer')->post('/webhooks/endpoints', [
            'name' => 'Blocked',
            'url' => 'https://example.test/blocked',
            'secret' => 'blocked-secret',
        ]);
        $endpointResponse->assertForbidden();

        $apiResponse = $this->actingAsRole('viewer')->post('/webhooks/api-keys', [
            'name' => 'Blocked API',
        ]);
        $apiResponse->assertForbidden();

        $this->assertDatabaseMissing('webhook_endpoints', [
            'name' => 'Blocked',
        ]);

        $this->assertDatabaseMissing('api_keys', [
            'name' => 'Blocked API',
        ]);
    }

    public function test_owner_can_toggle_endpoint_status(): void
    {
        $endpoint = WebhookEndpoint::query()->create([
            'name' => 'Toggle Me',
            'url' => 'https://example.test/toggle',
            'secret' => 'abc',
            'events' => ['message_received'],
            'is_active' => true,
        ]);

        $response = $this->actingAsRole('owner')->patch("/webhooks/endpoints/{$endpoint->id}/toggle");

        $response->assertRedirect('/webhooks');
        $this->assertFalse((bool) $endpoint->fresh()?->is_active);

        $this->assertDatabaseHas('activity_logs', [
            'action' => 'webhooks.endpoint.toggled',
        ]);
    }
}
