@extends('layouts.app')

@php
  $pageTitle = 'Log Pesan';
  $pageEyebrow = 'OPERATIONS';
  $navActive = 'logs';

  $selectedRange = trim(collect([request('date_from'), request('date_to')])->filter()->implode(' to '));
@endphp

@section('content')
<div class="space-y-5">

  <x-ui.card editorial padding="sm">
    <form method="GET" class="grid grid-cols-1 lg:grid-cols-[1fr_220px_240px_auto] gap-3 items-end">
      <x-ui.input
        name="number"
        label="Nomor"
        placeholder="Cari nomor 628..."
        :value="request('number')"
        mono
      />

      <x-ui.select
        name="level"
        label="Level"
        :options="[
          '' => 'Semua level',
          'info' => 'Info',
          'warn' => 'Warn',
          'err' => 'Err',
        ]"
        :value="request('level')"
      />

      <x-ui.input
        id="logs-date-range"
        name="date_range"
        label="Rentang Tanggal"
        placeholder="YYYY-MM-DD to YYYY-MM-DD"
        :value="request('date_range', $selectedRange)"
        hint="Pilih rentang tanggal dari kalender."
      />

      <input type="hidden" name="date_from" id="logs-date-from" value="{{ request('date_from') }}">
      <input type="hidden" name="date_to" id="logs-date-to" value="{{ request('date_to') }}">

      <div class="flex flex-wrap gap-2">
        <x-ui.button type="submit" variant="primary" icon="lucide-filter">Filter</x-ui.button>

        @if (request()->query())
          <x-ui.button :href="route('logs.index')" variant="ghost">Reset</x-ui.button>
        @endif

        <x-ui.button
          type="button"
          variant="secondary"
          icon="lucide-download"
          onclick="window.toast('Export CSV akan diaktifkan di Phase E1.', 'info')"
        >
          Export CSV
        </x-ui.button>
      </div>
    </form>
  </x-ui.card>

  @if ($logs->isEmpty())
    <x-ui.card editorial>
      <x-ui.empty
        title="Belum ada log pesan"
        description="Data log akan muncul otomatis saat bot menerima pesan masuk."
        icon="lucide-scroll-text"
      />
    </x-ui.card>
  @else
    <x-ui.card editorial class="hidden md:block" padding="none">
      <x-ui.table :columns="[
        ['key' => 'number', 'label' => 'Nomor', 'class' => 'w-44'],
        ['key' => 'level', 'label' => 'Level', 'class' => 'w-28'],
        ['key' => 'type', 'label' => 'Tipe', 'class' => 'w-24'],
        ['key' => 'message', 'label' => 'Pesan'],
        ['key' => 'status', 'label' => 'Status', 'class' => 'w-32'],
        ['key' => 'time', 'label' => 'Waktu', 'class' => 'w-44'],
      ]" class="rounded-none border-0">
        @foreach ($logs as $log)
          @php
            $severity = $log->is_allowed ? ($log->replied ? 'info' : 'err') : 'warn';
            $severityLabel = strtoupper($severity);
            $severityVariant = $severity === 'err' ? 'danger' : ($severity === 'warn' ? 'pending' : 'info');
          @endphp
          <tr class="hover:bg-[var(--color-card-muted)]">
            <td class="px-4 py-3 font-mono text-xs text-[var(--color-ink)]">{{ $log->from_number }}</td>
            <td class="px-4 py-3">
              <x-ui.badge :variant="$severityVariant" size="sm">{{ $severityLabel }}</x-ui.badge>
            </td>
            <td class="px-4 py-3">
              <x-ui.badge variant="muted" size="sm">{{ $log->message_type }}</x-ui.badge>
            </td>
            <td class="px-4 py-3 text-sm text-[var(--color-ink)]">
              <span class="block truncate max-w-xl">{{ $log->message_text ?: '—' }}</span>
            </td>
            <td class="px-4 py-3">
              @if ($log->replied)
                <x-ui.badge variant="verified" size="sm" :dot="true">Dibalas</x-ui.badge>
              @else
                <x-ui.badge :variant="$log->is_allowed ? 'danger' : 'pending'" size="sm">
                  {{ $log->is_allowed ? 'Gagal balas' : 'Diblokir' }}
                </x-ui.badge>
              @endif
            </td>
            <td class="px-4 py-3 font-mono text-xs text-[var(--color-ink-muted)] whitespace-nowrap">
              {{ $log->received_at?->format('d/m/Y H:i:s') }}
            </td>
          </tr>
        @endforeach
      </x-ui.table>
    </x-ui.card>

    <div class="md:hidden space-y-3">
      @foreach ($logs as $log)
        @php
          $severity = $log->is_allowed ? ($log->replied ? 'info' : 'err') : 'warn';
          $severityVariant = $severity === 'err' ? 'danger' : ($severity === 'warn' ? 'pending' : 'info');
        @endphp
        <x-ui.card editorial padding="sm" x-data="{ open: false }">
          <button type="button" class="w-full text-left" @click="open = !open" :aria-expanded="open">
            <div class="flex items-start justify-between gap-3">
              <div class="min-w-0">
                <div class="font-mono text-xs text-[var(--color-ink)] truncate">{{ $log->from_number }}</div>
                <div class="text-sm text-[var(--color-ink)] mt-1 line-clamp-2">{{ $log->message_text ?: '—' }}</div>
                <div class="mt-2 flex flex-wrap gap-1.5">
                  <x-ui.badge :variant="$severityVariant" size="sm">{{ strtoupper($severity) }}</x-ui.badge>
                  <x-ui.badge variant="muted" size="sm">{{ $log->message_type }}</x-ui.badge>
                </div>
              </div>
              <div class="text-right">
                <div class="font-mono text-[10px] text-[var(--color-ink-muted)] whitespace-nowrap">{{ $log->received_at?->format('d/m H:i') }}</div>
                <div class="eyebrow mt-2" x-text="open ? 'TUTUP' : 'DETAIL'"></div>
              </div>
            </div>
          </button>

          <div x-show="open" x-collapse class="pt-3 mt-3 border-t border-[var(--color-rule)] space-y-2" style="display: none;">
            <dl class="grid grid-cols-2 gap-2 text-xs">
              <div>
                <dt class="eyebrow">ALLOWLIST</dt>
                <dd class="text-[var(--color-ink)] font-medium">{{ $log->is_allowed ? 'Ya' : 'Tidak' }}</dd>
              </div>
              <div>
                <dt class="eyebrow">REPLIED</dt>
                <dd class="text-[var(--color-ink)] font-medium">{{ $log->replied ? 'Ya' : 'Tidak' }}</dd>
              </div>
            </dl>

            <pre class="text-[11px] font-mono bg-[var(--color-paper)] border border-[var(--color-rule)] rounded-md p-3 text-[var(--color-ink-muted)] overflow-x-auto whitespace-pre-wrap">{{ trim("from: {$log->from_number}\ntype: {$log->message_type}\nallowed: " . ($log->is_allowed ? 'true' : 'false') . "\nreplied: " . ($log->replied ? 'true' : 'false') . "\nmessage: " . ($log->message_text ?: '-') . "\nreply: " . ($log->reply_text ?: '-') . "\ngroup_id: " . ($log->group_id ?: '-') . "\nreceived_at: " . ($log->received_at?->format('Y-m-d H:i:s') ?: '-')) }}</pre>
          </div>
        </x-ui.card>
      @endforeach
    </div>

    <x-ui.pagination :paginator="$logs" />
  @endif
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
  var rangeEl = document.getElementById('logs-date-range');
  var fromEl = document.getElementById('logs-date-from');
  var toEl = document.getElementById('logs-date-to');

  if (!rangeEl || !fromEl || !toEl || typeof window.flatpickr !== 'function') {
    return;
  }

  window.flatpickr(rangeEl, {
    mode: 'range',
    dateFormat: 'Y-m-d',
    defaultDate: [fromEl.value || null, toEl.value || null].filter(Boolean),
    onClose: function (selectedDates) {
      if (!selectedDates.length) {
        fromEl.value = '';
        toEl.value = '';
        rangeEl.value = '';
        return;
      }

      var toDateString = function (date) {
        var pad = function (num) {
          return String(num).padStart(2, '0');
        };
        return date.getFullYear() + '-' + pad(date.getMonth() + 1) + '-' + pad(date.getDate());
      };

      fromEl.value = toDateString(selectedDates[0]);
      toEl.value = selectedDates[1] ? toDateString(selectedDates[1]) : fromEl.value;
    },
  });
});
</script>
@endpush
