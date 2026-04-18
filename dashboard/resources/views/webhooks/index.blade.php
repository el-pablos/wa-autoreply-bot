@extends('layouts.app')

@php
  $pageTitle = 'Webhooks & API';
  $pageEyebrow = 'INTEGRATION';
  $navActive = 'webhooks';
@endphp

@section('content')
<div class="space-y-5">
  @if (session('success'))
    <x-ui.card editorial padding="sm">
      <div class="flex items-start gap-3">
        <x-ui.badge variant="verified" size="sm" :dot="true">Saved</x-ui.badge>
        <p class="text-sm text-[var(--color-ink)]">{{ session('success') }}</p>
      </div>
    </x-ui.card>
  @endif

  @if ($errors->any())
    <x-ui.card editorial padding="sm">
      <div class="flex items-start gap-3">
        <x-ui.badge variant="danger" size="sm" :dot="true">Error</x-ui.badge>
        <p class="text-sm text-[var(--color-danger)]">{{ $errors->first() }}</p>
      </div>
    </x-ui.card>
  @endif

  @if ($newApiKey)
    <x-ui.card editorial>
      <x-slot:header>
        <div>
          <div class="eyebrow">API KEY GENERATED</div>
          <h2 class="font-display font-extrabold text-2xl text-[var(--color-ink)]">Simpan Secret Ini Sekarang</h2>
          <p class="display-italic text-sm">Key ini hanya ditampilkan sekali.</p>
        </div>
      </x-slot:header>
      <div class="font-mono text-sm border border-[var(--color-rule)] bg-[var(--color-card-muted)] rounded-md px-3 py-2 break-all">{{ $newApiKey }}</div>
    </x-ui.card>
  @endif

  <x-ui.card editorial>
    <x-slot:header>
      <div>
        <div class="eyebrow">D3 · WEBHOOK OUT</div>
        <h2 class="font-display font-extrabold text-2xl text-[var(--color-ink)]">Webhook Endpoints</h2>
        <p class="display-italic text-sm">Endpoint aktif akan menerima event bot dengan signature HMAC SHA-256.</p>
      </div>
    </x-slot:header>

    <form action="{{ route('webhooks.endpoints.store') }}" method="POST" class="space-y-4 border border-[var(--color-rule)] rounded-md p-4 bg-[var(--color-card-muted)]">
      @csrf

      <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <x-ui.input
          name="name"
          label="Nama Endpoint"
          placeholder="Contoh: CRM Callback"
          :value="old('name')"
          :error="$errors->first('name')"
        />

        <x-ui.input
          name="url"
          label="URL Endpoint"
          placeholder="https://example.com/webhook/wa"
          :value="old('url')"
          :error="$errors->first('url')"
          required
        />
      </div>

      <x-ui.input
        name="secret"
        label="Secret"
        placeholder="webhook-secret"
        :value="old('secret')"
        :error="$errors->first('secret')"
        required
      />

      <div class="space-y-2">
        <div class="eyebrow">EVENT SUBSCRIPTIONS</div>
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-2">
          @foreach ($supportedEvents as $event)
            <label class="inline-flex items-center gap-2 text-sm text-[var(--color-ink-muted)] border border-[var(--color-rule)] rounded-md px-2 py-2">
              <input type="checkbox" name="events[]" value="{{ $event }}" class="rounded border-[var(--color-rule)]" @checked(collect(old('events', []))->contains($event))>
              <span class="font-mono text-xs">{{ $event }}</span>
            </label>
          @endforeach
        </div>
      </div>

      <label class="inline-flex items-center gap-2 text-sm text-[var(--color-ink-muted)]">
        <input type="checkbox" name="is_active" value="true" class="rounded border-[var(--color-rule)]" @checked(old('is_active', 'true') === 'true')>
        <span>Aktif</span>
      </label>

      <x-ui.button type="submit" variant="primary" icon="lucide-plus">Tambah Endpoint</x-ui.button>
    </form>

    <div class="mt-4 space-y-3">
      @if ($endpoints->isEmpty())
        <x-ui.empty
          title="Belum ada endpoint webhook"
          description="Tambahkan endpoint pertama untuk mulai menerima event dari bot."
          icon="lucide-webhook"
        />
      @else
        @foreach ($endpoints as $endpoint)
          @php
            $eventList = is_array($endpoint->events) ? $endpoint->events : [];
          @endphp
          <x-ui.card padding="sm" class="bg-[var(--color-card-muted)]">
            <form action="{{ route('webhooks.endpoints.update', $endpoint) }}" method="POST" class="space-y-3">
              @csrf
              @method('PUT')

              <div class="flex flex-wrap items-center gap-2">
                <x-ui.badge :variant="$endpoint->is_active ? 'verified' : 'pending'" size="sm" :dot="$endpoint->is_active">{{ $endpoint->is_active ? 'ACTIVE' : 'INACTIVE' }}</x-ui.badge>
                <x-ui.badge variant="muted" size="sm">#{{ $endpoint->id }}</x-ui.badge>
                @if ($endpoint->last_triggered_at)
                  <x-ui.badge variant="info" size="sm">last: {{ $endpoint->last_triggered_at->format('d/m H:i') }}</x-ui.badge>
                @endif
              </div>

              <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <x-ui.input name="name" label="Nama" :value="$endpoint->name" />
                <x-ui.input name="url" label="URL" :value="$endpoint->url" required />
              </div>

              <x-ui.input name="secret" label="Secret" :value="$endpoint->secret" required />

              <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-2">
                @foreach ($supportedEvents as $event)
                  <label class="inline-flex items-center gap-2 text-sm text-[var(--color-ink-muted)] border border-[var(--color-rule)] rounded-md px-2 py-2">
                    <input type="checkbox" name="events[]" value="{{ $event }}" class="rounded border-[var(--color-rule)]" @checked(in_array($event, $eventList, true))>
                    <span class="font-mono text-xs">{{ $event }}</span>
                  </label>
                @endforeach
              </div>

              <label class="inline-flex items-center gap-2 text-sm text-[var(--color-ink-muted)]">
                <input type="checkbox" name="is_active" value="true" class="rounded border-[var(--color-rule)]" @checked($endpoint->is_active)>
                <span>Aktif</span>
              </label>

              <div class="flex flex-wrap items-center gap-2">
                <x-ui.button type="submit" variant="primary" size="sm" icon="lucide-save">Simpan</x-ui.button>
              </div>
            </form>

            <div class="mt-2 flex flex-wrap items-center gap-2">
              <form action="{{ route('webhooks.endpoints.toggle', $endpoint) }}" method="POST">
                @csrf
                @method('PATCH')
                <x-ui.button type="submit" variant="secondary" size="sm">Toggle Status</x-ui.button>
              </form>

              <form action="{{ route('webhooks.endpoints.destroy', $endpoint) }}" method="POST" onsubmit="return confirm('Hapus endpoint ini?');">
                @csrf
                @method('DELETE')
                <x-ui.button type="submit" variant="danger" size="sm" icon="lucide-trash-2">Hapus</x-ui.button>
              </form>
            </div>
          </x-ui.card>
        @endforeach
      @endif
    </div>
  </x-ui.card>

  <x-ui.card editorial>
    <x-slot:header>
      <div>
        <div class="eyebrow">D3 · REST API IN</div>
        <h2 class="font-display font-extrabold text-2xl text-[var(--color-ink)]">Public API Keys</h2>
        <p class="display-italic text-sm">Key dipakai untuk endpoint publik bot (/api/send, /api/allowlist, /api/logs).</p>
      </div>
    </x-slot:header>

    <form action="{{ route('webhooks.api-keys.store') }}" method="POST" class="space-y-4 border border-[var(--color-rule)] rounded-md p-4 bg-[var(--color-card-muted)]">
      @csrf

      <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <x-ui.input
          name="name"
          label="Nama Key"
          placeholder="Contoh: Integrasi CRM"
          required
        />

        <x-ui.input
          name="scopes"
          label="Scopes (opsional)"
          placeholder="send,allowlist,logs"
          hint="Pisahkan dengan koma."
        />
      </div>

      <x-ui.button type="submit" variant="primary" icon="lucide-key-round">Generate API Key</x-ui.button>
    </form>

    <div class="mt-4 space-y-2">
      @if ($apiKeys->isEmpty())
        <x-ui.empty
          title="Belum ada API key"
          description="Generate key untuk integrasi eksternal ke API publik bot."
          icon="lucide-key-round"
        />
      @else
        @foreach ($apiKeys as $apiKey)
          <x-ui.card padding="sm" class="bg-[var(--color-card-muted)]">
            <div class="flex flex-wrap items-center justify-between gap-2">
              <div class="min-w-0">
                <div class="font-display font-bold text-sm text-[var(--color-ink)]">{{ $apiKey->name }}</div>
                <div class="text-xs text-[var(--color-ink-muted)] font-mono">id: {{ $apiKey->id }} · hash: {{ substr($apiKey->key_hash, 0, 16) }}...</div>
                <div class="text-xs text-[var(--color-ink-muted)]">scopes: {{ is_array($apiKey->scopes) && !empty($apiKey->scopes) ? implode(', ', $apiKey->scopes) : 'all' }}</div>
              </div>

              <div class="flex flex-wrap items-center gap-2">
                <x-ui.badge :variant="$apiKey->revoked_at ? 'danger' : 'verified'" size="sm" :dot="!$apiKey->revoked_at">
                  {{ $apiKey->revoked_at ? 'REVOKED' : 'ACTIVE' }}
                </x-ui.badge>
                @if (!$apiKey->revoked_at)
                  <form action="{{ route('webhooks.api-keys.revoke', $apiKey) }}" method="POST" onsubmit="return confirm('Revoke API key {{ $apiKey->name }}?');">
                    @csrf
                    @method('PATCH')
                    <x-ui.button type="submit" variant="danger" size="sm">Revoke</x-ui.button>
                  </form>
                @endif
              </div>
            </div>
          </x-ui.card>
        @endforeach
      @endif
    </div>
  </x-ui.card>
</div>
@endsection
