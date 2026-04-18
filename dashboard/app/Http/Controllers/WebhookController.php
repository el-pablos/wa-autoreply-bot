<?php

namespace App\Http\Controllers;

use App\Models\ApiKey;
use App\Models\WebhookEndpoint;
use App\Support\AuditTrail;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class WebhookController extends Controller
{
    private const SUPPORTED_EVENTS = [
        'message_received',
        'reply_sent',
        'oof_replied',
        'outside_business_hours_replied',
        'escalated',
    ];

    public function index()
    {
        $endpoints = WebhookEndpoint::query()
            ->orderByDesc('is_active')
            ->orderByDesc('updated_at')
            ->get();

        $apiKeys = ApiKey::query()
            ->orderByRaw('revoked_at IS NULL DESC')
            ->orderByDesc('created_at')
            ->get();

        return view('webhooks.index', [
            'endpoints' => $endpoints,
            'apiKeys' => $apiKeys,
            'supportedEvents' => self::SUPPORTED_EVENTS,
            'newApiKey' => session('new_api_key'),
        ]);
    }

    public function storeEndpoint(Request $request)
    {
        $data = $this->validateEndpoint($request);

        $endpoint = WebhookEndpoint::query()->create($data);

        AuditTrail::record(
            $request,
            'webhooks.endpoint.created',
            $endpoint,
            null,
            $endpoint->only(['name', 'url', 'events', 'is_active'])
        );

        return redirect()->route('webhooks.index')->with('success', 'Webhook endpoint berhasil ditambahkan.');
    }

    public function updateEndpoint(Request $request, WebhookEndpoint $endpoint)
    {
        $old = $endpoint->only(['name', 'url', 'events', 'is_active']);
        $data = $this->validateEndpoint($request);

        $endpoint->update($data);

        AuditTrail::record(
            $request,
            'webhooks.endpoint.updated',
            $endpoint,
            $old,
            $endpoint->fresh()?->only(['name', 'url', 'events', 'is_active'])
        );

        return redirect()->route('webhooks.index')->with('success', 'Webhook endpoint berhasil diperbarui.');
    }

    public function toggleEndpoint(Request $request, WebhookEndpoint $endpoint)
    {
        $old = ['is_active' => $endpoint->is_active];

        $endpoint->update([
            'is_active' => !$endpoint->is_active,
        ]);

        AuditTrail::record(
            $request,
            'webhooks.endpoint.toggled',
            $endpoint,
            $old,
            ['is_active' => $endpoint->is_active]
        );

        return redirect()->route('webhooks.index')->with('success', 'Status webhook endpoint berhasil diubah.');
    }

    public function destroyEndpoint(Request $request, WebhookEndpoint $endpoint)
    {
        $old = $endpoint->only(['name', 'url', 'events', 'is_active']);
        $target = ['type' => $endpoint::class, 'id' => $endpoint->id];

        $endpoint->delete();

        AuditTrail::record(
            $request,
            'webhooks.endpoint.deleted',
            $target,
            $old,
            null
        );

        return redirect()->route('webhooks.index')->with('success', 'Webhook endpoint berhasil dihapus.');
    }

    public function storeApiKey(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'scopes' => ['nullable', 'string', 'max:1000'],
        ]);

        $plainKey = 'wa_' . Str::random(40);
        $keyHash = hash('sha256', $plainKey);

        $scopes = collect(explode(',', (string) ($data['scopes'] ?? '')))
            ->map(fn (string $scope): string => trim($scope))
            ->filter()
            ->values()
            ->all();

        $apiKey = ApiKey::query()->create([
            'name' => $data['name'],
            'key_hash' => $keyHash,
            'scopes' => !empty($scopes) ? $scopes : null,
            'revoked_at' => null,
        ]);

        AuditTrail::record(
            $request,
            'webhooks.api_key.created',
            $apiKey,
            null,
            [
                'name' => $apiKey->name,
                'scopes' => $apiKey->scopes,
                'created_at' => $apiKey->created_at,
            ]
        );

        return redirect()
            ->route('webhooks.index')
            ->with('success', 'API key berhasil dibuat. Simpan secret key-nya sekarang.')
            ->with('new_api_key', $plainKey);
    }

    public function revokeApiKey(Request $request, ApiKey $apiKey)
    {
        if ($apiKey->revoked_at !== null) {
            return redirect()->route('webhooks.index')->with('success', 'API key sudah dalam status revoked.');
        }

        $old = $apiKey->only(['name', 'revoked_at']);

        $apiKey->update([
            'revoked_at' => now(),
        ]);

        AuditTrail::record(
            $request,
            'webhooks.api_key.revoked',
            $apiKey,
            $old,
            ['name' => $apiKey->name, 'revoked_at' => $apiKey->revoked_at]
        );

        return redirect()->route('webhooks.index')->with('success', 'API key berhasil direvoke.');
    }

    private function validateEndpoint(Request $request): array
    {
        $data = $request->validate([
            'name' => ['nullable', 'string', 'max:120'],
            'url' => ['required', 'url', 'max:500'],
            'secret' => ['required', 'string', 'max:1000'],
            'events' => ['nullable', 'array'],
            'events.*' => ['string', Rule::in(self::SUPPORTED_EVENTS)],
            'is_active' => ['nullable', 'in:true,false'],
        ]);

        return [
            'name' => $data['name'] ?? null,
            'url' => $data['url'],
            'secret' => $data['secret'],
            'events' => !empty($data['events']) ? array_values($data['events']) : null,
            'is_active' => $request->boolean('is_active', true),
        ];
    }
}
