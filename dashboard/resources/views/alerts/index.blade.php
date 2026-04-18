@extends('layouts.app')

@php
  $pageTitle = 'Alerts';
  $pageEyebrow = 'INSIGHT';
  $navActive = 'alerts';
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

  <x-ui.card editorial>
    <x-slot:header>
      <div>
        <div class="eyebrow">C1 · ALERTING</div>
        <h2 class="font-display font-extrabold text-2xl text-[var(--color-ink)]">Alert Channels</h2>
        <p class="display-italic text-sm">Konfigurasi channel notifikasi untuk event bot penting dan kirim test alert cepat.</p>
      </div>
    </x-slot:header>

    <form action="{{ route('alerts.channels.store') }}" method="POST" class="space-y-4 border border-[var(--color-rule)] rounded-md p-4 bg-[var(--color-card-muted)]">
      @csrf

      <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
        <x-ui.select
          name="type"
          label="Type"
          :options="[
            'wa' => 'WhatsApp',
            'email' => 'Email',
          ]"
          :value="old('type', 'wa')"
          :error="$errors->first('type')"
        />

        <x-ui.input
          name="target"
          label="Target"
          :value="old('target')"
          :error="$errors->first('target')"
          placeholder="6281... atau email@domain.com"
          required
        />
      </div>

      <label class="inline-flex items-center gap-2 text-sm text-[var(--color-ink-muted)]">
        <input type="checkbox" name="is_active" value="true" class="rounded border-[var(--color-rule)]" @checked(old('is_active', 'true') === 'true')>
        <span>Aktif</span>
      </label>

      <x-ui.button type="submit" variant="primary" icon="lucide-plus">Tambah Channel</x-ui.button>
    </form>

    <div class="mt-4 space-y-3">
      @if ($channels->isEmpty())
        <x-ui.empty
          title="Belum ada channel"
          description="Tambahkan channel WA/email supaya alert operasional bisa dikirim otomatis."
          icon="lucide-bell"
        />
      @else
        @foreach ($channels as $channel)
          <x-ui.card padding="sm" class="bg-[var(--color-card-muted)]">
            <form action="{{ route('alerts.channels.update', $channel) }}" method="POST" class="space-y-3">
              @csrf
              @method('PUT')

              <div class="flex flex-wrap items-center gap-2">
                <x-ui.badge :variant="$channel->is_active ? 'verified' : 'pending'" size="sm" :dot="$channel->is_active">{{ $channel->is_active ? 'ACTIVE' : 'INACTIVE' }}</x-ui.badge>
                <x-ui.badge variant="muted" size="sm">{{ strtoupper($channel->type) }}</x-ui.badge>
                <span class="font-mono text-xs text-[var(--color-ink-muted)]">last: {{ $channel->last_alert_at?->format('d/m/Y H:i:s') ?? '-' }}</span>
              </div>

              <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                <x-ui.select
                  name="type"
                  label="Type"
                  :options="[
                    'wa' => 'WhatsApp',
                    'email' => 'Email',
                  ]"
                  :value="$channel->type"
                />

                <x-ui.input
                  name="target"
                  label="Target"
                  :value="$channel->target"
                  required
                />
              </div>

              <label class="inline-flex items-center gap-2 text-sm text-[var(--color-ink-muted)]">
                <input type="checkbox" name="is_active" value="true" class="rounded border-[var(--color-rule)]" @checked($channel->is_active)>
                <span>Aktif</span>
              </label>

              <div class="flex flex-wrap gap-2">
                <x-ui.button type="submit" variant="primary" size="sm" icon="lucide-save">Simpan</x-ui.button>
              </div>
            </form>

            <div class="mt-2 flex flex-wrap items-center gap-2">
              <form action="{{ route('alerts.channels.toggle', $channel) }}" method="POST">
                @csrf
                @method('PATCH')
                <x-ui.button type="submit" variant="secondary" size="sm">Toggle Status</x-ui.button>
              </form>

              <form action="{{ route('alerts.channels.test', $channel) }}" method="POST">
                @csrf
                <x-ui.button type="submit" variant="outline" size="sm" icon="lucide-send">Send Test</x-ui.button>
              </form>

              <form action="{{ route('alerts.channels.destroy', $channel) }}" method="POST" onsubmit="return confirm('Hapus alert channel ini?');">
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
        <div class="eyebrow">DELIVERY HISTORY</div>
        <h3 class="font-display font-extrabold text-xl text-[var(--color-ink)]">Alert History</h3>
      </div>
    </x-slot:header>

    @if ($history->isEmpty())
      <x-ui.empty
        title="Belum ada histori alert"
        description="Riwayat akan muncul setiap alert dikirim dari scheduler atau test channel."
        icon="lucide-history"
      />
    @else
      <x-ui.table :columns="[
        ['key' => 'time', 'label' => 'Waktu', 'class' => 'w-44'],
        ['key' => 'channel', 'label' => 'Channel', 'class' => 'w-28'],
        ['key' => 'target', 'label' => 'Target', 'class' => 'w-56'],
        ['key' => 'severity', 'label' => 'Severity', 'class' => 'w-28'],
        ['key' => 'message', 'label' => 'Pesan'],
        ['key' => 'status', 'label' => 'Status', 'class' => 'w-24'],
      ]">
        @foreach ($history as $row)
          <tr class="hover:bg-[var(--color-card-muted)]">
            <td class="px-4 py-3 font-mono text-xs text-[var(--color-ink-muted)] whitespace-nowrap">{{ $row->created_at?->format('d/m/Y H:i:s') }}</td>
            <td class="px-4 py-3"><x-ui.badge variant="muted" size="sm">{{ strtoupper((string) optional($row->channel)->type) }}</x-ui.badge></td>
            <td class="px-4 py-3 font-mono text-xs text-[var(--color-ink)]">{{ optional($row->channel)->target ?? '-' }}</td>
            <td class="px-4 py-3"><x-ui.badge variant="{{ $row->severity === 'err' ? 'danger' : ($row->severity === 'warn' ? 'pending' : 'info') }}" size="sm">{{ strtoupper((string) $row->severity) }}</x-ui.badge></td>
            <td class="px-4 py-3 text-sm text-[var(--color-ink)]">{{ $row->message }}</td>
            <td class="px-4 py-3">
              <x-ui.badge :variant="$row->success ? 'verified' : 'danger'" size="sm">{{ $row->success ? 'OK' : 'FAIL' }}</x-ui.badge>
            </td>
          </tr>
        @endforeach
      </x-ui.table>
    @endif
  </x-ui.card>
</div>
@endsection
