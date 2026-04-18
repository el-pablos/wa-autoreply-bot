@extends('layouts.app')

@php
  $pageTitle = 'Blacklist';
  $pageEyebrow = 'INSIGHT';
  $navActive = 'blacklist';
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

  <x-ui.card editorial padding="sm">
    <form method="GET" class="grid grid-cols-1 md:grid-cols-[1fr_200px_auto] gap-3 items-end">
      <x-ui.input
        name="search"
        label="Cari"
        placeholder="nomor, reason, blocked_by"
        :value="request('search')"
      />

      <x-ui.select
        name="status"
        label="Status"
        :options="[
          '' => 'Semua',
          'active' => 'Active',
          'inactive' => 'Inactive',
        ]"
        :value="request('status')"
      />

      <div class="flex gap-2">
        <x-ui.button type="submit" variant="primary" icon="lucide-filter">Filter</x-ui.button>
        @if (request()->query())
          <x-ui.button :href="route('blacklist.index')" variant="ghost">Reset</x-ui.button>
        @endif
      </div>
    </form>
  </x-ui.card>

  <x-ui.card editorial>
    <x-slot:header>
      <div>
        <div class="eyebrow">SECURITY</div>
        <h2 class="font-display font-extrabold text-2xl text-[var(--color-ink)]">Blacklist Numbers</h2>
      </div>
    </x-slot:header>

    <form action="{{ route('blacklist.store') }}" method="POST" class="space-y-3 border border-[var(--color-rule)] rounded-md p-4 bg-[var(--color-card-muted)]">
      @csrf

      <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
        <x-ui.input
          name="phone_number"
          label="Nomor"
          :value="old('phone_number')"
          :error="$errors->first('phone_number')"
          placeholder="08..., 628..., +628..."
          required
        />

        <x-ui.input
          name="unblock_at"
          type="datetime-local"
          label="Unblock At (opsional)"
          :value="old('unblock_at')"
          :error="$errors->first('unblock_at')"
        />
      </div>

      <x-ui.textarea
        name="reason"
        label="Reason"
        :value="old('reason')"
        :error="$errors->first('reason')"
        rows="3"
      />

      <label class="inline-flex items-center gap-2 text-sm text-[var(--color-ink-muted)]">
        <input type="checkbox" name="is_active" value="true" class="rounded border-[var(--color-rule)]" @checked(old('is_active', 'true') === 'true')>
        <span>Aktif</span>
      </label>

      <x-ui.button type="submit" variant="primary" icon="lucide-ban">Tambah ke Blacklist</x-ui.button>
    </form>

    <div class="mt-4 space-y-3">
      @if ($rows->isEmpty())
        <x-ui.empty
          title="Blacklist masih kosong"
          description="Tambahkan nomor yang perlu diblokir agar pipeline auto-reply mengabaikan pesan dari nomor tersebut."
          icon="lucide-ban"
        />
      @else
        @foreach ($rows as $entry)
          <x-ui.card padding="sm" class="bg-[var(--color-card-muted)]">
            <form action="{{ route('blacklist.update', $entry) }}" method="POST" class="space-y-3">
              @csrf
              @method('PUT')

              <div class="flex flex-wrap items-center gap-2">
                <x-ui.badge :variant="$entry->is_active ? 'danger' : 'muted'" size="sm" :dot="$entry->is_active">{{ $entry->is_active ? 'BLOCKED' : 'INACTIVE' }}</x-ui.badge>
                <span class="font-mono text-xs text-[var(--color-ink-muted)]">blocked_at: {{ $entry->blocked_at?->format('d/m/Y H:i:s') ?? '-' }}</span>
                <span class="font-mono text-xs text-[var(--color-ink-muted)]">by: {{ $entry->blocked_by ?? '-' }}</span>
              </div>

              <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                <x-ui.input name="phone_number" label="Nomor" :value="$entry->phone_number" required />
                <x-ui.input
                  name="unblock_at"
                  type="datetime-local"
                  label="Unblock At"
                  :value="$entry->unblock_at ? $entry->unblock_at->format('Y-m-d\TH:i') : ''"
                />
              </div>

              <x-ui.textarea name="reason" label="Reason" :value="$entry->reason" rows="3" />

              <label class="inline-flex items-center gap-2 text-sm text-[var(--color-ink-muted)]">
                <input type="checkbox" name="is_active" value="true" class="rounded border-[var(--color-rule)]" @checked($entry->is_active)>
                <span>Aktif</span>
              </label>

              <x-ui.button type="submit" variant="primary" size="sm" icon="lucide-save">Simpan</x-ui.button>
            </form>

            <div class="mt-2 flex flex-wrap items-center gap-2">
              <form action="{{ route('blacklist.toggle', $entry) }}" method="POST">
                @csrf
                @method('PATCH')
                <x-ui.button type="submit" variant="secondary" size="sm">Toggle Status</x-ui.button>
              </form>

              <form action="{{ route('blacklist.destroy', $entry) }}" method="POST" onsubmit="return confirm('Hapus entry blacklist ini?');">
                @csrf
                @method('DELETE')
                <x-ui.button type="submit" variant="danger" size="sm" icon="lucide-trash-2">Hapus</x-ui.button>
              </form>
            </div>
          </x-ui.card>
        @endforeach

        <x-ui.pagination :paginator="$rows" />
      @endif
    </div>
  </x-ui.card>
</div>
@endsection
