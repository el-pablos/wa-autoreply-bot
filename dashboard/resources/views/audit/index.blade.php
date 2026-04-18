@extends('layouts.app')

@php
  $pageTitle = 'Audit Trail';
  $pageEyebrow = 'INSIGHT';
  $navActive = 'audit';
@endphp

@section('content')
<div class="space-y-5">
  <x-ui.card editorial padding="sm">
    <form method="GET" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-3 items-end">
      <x-ui.input name="actor" label="Actor" :value="request('actor')" placeholder="email / actor" />
      <x-ui.input name="action" label="Action" :value="request('action')" placeholder="templates.reply.created" />
      <x-ui.input name="target_type" label="Target Type" :value="request('target_type')" placeholder="App\\Models\\..." />
      <x-ui.input name="date_from" type="date" label="Dari" :value="request('date_from')" />
      <x-ui.input name="date_to" type="date" label="Sampai" :value="request('date_to')" />
      <div class="flex gap-2">
        <x-ui.button type="submit" variant="primary" icon="lucide-filter">Filter</x-ui.button>
        @if (request()->query())
          <x-ui.button :href="route('audit.index')" variant="ghost">Reset</x-ui.button>
        @endif
      </div>
    </form>
  </x-ui.card>

  <x-ui.card editorial>
    <x-slot:header>
      <div>
        <div class="eyebrow">B1 · ACTIVITY LOG</div>
        <h2 class="font-display font-extrabold text-2xl text-[var(--color-ink)]">Audit Events</h2>
        <p class="display-italic text-sm">Jejak perubahan semua aksi penting dashboard untuk forensic dan compliance internal.</p>
      </div>
    </x-slot:header>

    @if ($logs->isEmpty())
      <x-ui.empty
        title="Belum ada event audit"
        description="Event akan muncul otomatis setiap aksi mutasi dijalankan dari dashboard."
        icon="lucide-history"
      />
    @else
      <x-ui.table :columns="[
        ['key' => 'time', 'label' => 'Waktu', 'class' => 'w-44'],
        ['key' => 'actor', 'label' => 'Actor', 'class' => 'w-44'],
        ['key' => 'action', 'label' => 'Action', 'class' => 'w-56'],
        ['key' => 'target', 'label' => 'Target', 'class' => 'w-44'],
        ['key' => 'diff', 'label' => 'Changes'],
        ['key' => 'ip', 'label' => 'IP', 'class' => 'w-36'],
      ]">
        @foreach ($logs as $row)
          @php
            $oldValue = $row->old_value ? json_encode($row->old_value, JSON_UNESCAPED_UNICODE) : '-';
            $newValue = $row->new_value ? json_encode($row->new_value, JSON_UNESCAPED_UNICODE) : '-';
          @endphp
          <tr class="hover:bg-[var(--color-card-muted)]">
            <td class="px-4 py-3 font-mono text-xs text-[var(--color-ink-muted)] whitespace-nowrap">{{ $row->created_at?->format('d/m/Y H:i:s') }}</td>
            <td class="px-4 py-3 text-xs text-[var(--color-ink)]">{{ $row->actor }}</td>
            <td class="px-4 py-3"><x-ui.badge variant="info" size="sm">{{ $row->action }}</x-ui.badge></td>
            <td class="px-4 py-3 font-mono text-[10px] text-[var(--color-ink-muted)] break-all">{{ ($row->target_type ?? '-') . ($row->target_id ? '#' . $row->target_id : '') }}</td>
            <td class="px-4 py-3">
              <div class="text-[10px] font-mono text-[var(--color-ink-muted)] space-y-1">
                <div>old: {{ \Illuminate\Support\Str::limit((string) $oldValue, 160) }}</div>
                <div>new: {{ \Illuminate\Support\Str::limit((string) $newValue, 160) }}</div>
              </div>
            </td>
            <td class="px-4 py-3 font-mono text-xs text-[var(--color-ink-muted)]">{{ $row->ip_address ?? '-' }}</td>
          </tr>
        @endforeach
      </x-ui.table>

      <x-ui.pagination :paginator="$logs" />
    @endif
  </x-ui.card>
</div>
@endsection
