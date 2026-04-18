@extends('layouts.app')

@php
  $pageTitle = 'Analytics';
  $pageEyebrow = 'INSIGHT';
  $navActive = 'analytics';

  $chartLabels = collect($daily)->map(fn ($row) => \Carbon\Carbon::parse($row['date'])->format('d/m'))->values()->all();
  $chartIncoming = collect($daily)->pluck('messages_in')->map(fn ($v) => (int) $v)->values()->all();
  $chartOutgoing = collect($daily)->pluck('messages_out')->map(fn ($v) => (int) $v)->values()->all();

  $chartData = [
    'labels' => $chartLabels,
    'datasets' => [
      [
        'label' => 'Incoming',
        'data' => $chartIncoming,
        'borderColor' => '#1a1a1a',
        'backgroundColor' => 'rgba(168, 155, 106, 0.25)',
        'tension' => 0.25,
      ],
      [
        'label' => 'Outgoing',
        'data' => $chartOutgoing,
        'borderColor' => '#2f6b3d',
        'backgroundColor' => 'rgba(47, 107, 61, 0.2)',
        'tension' => 0.25,
      ],
    ],
  ];

  $maxHourly = max(1, (int) collect($hourly)->max());
@endphp

@section('content')
<div class="space-y-5">
  <x-ui.card editorial padding="sm">
    <form method="GET" class="grid grid-cols-1 md:grid-cols-[220px_auto] gap-3 items-end">
      <x-ui.select
        name="days"
        label="Rentang"
        :options="[
          '7' => '7 hari',
          '14' => '14 hari',
          '30' => '30 hari',
        ]"
        :value="(string) $days"
      />

      <div class="flex gap-2">
        <x-ui.button type="submit" variant="primary" icon="lucide-filter">Terapkan</x-ui.button>
        @if (request()->query())
          <x-ui.button :href="route('analytics.index')" variant="ghost">Reset</x-ui.button>
        @endif
      </div>
    </form>
  </x-ui.card>

  <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3">
    <x-ui.stat-card eyebrow="INCOMING" :value="number_format($messagesIn)" label="Pesan masuk" icon="lucide-inbox" />
    <x-ui.stat-card eyebrow="OUTGOING" :value="number_format($messagesOut)" label="Pesan dibalas" icon="lucide-send" />
    <x-ui.stat-card eyebrow="REPLY RATE" :value="$replyRate . '%'" label="Rasio balas" icon="lucide-percent" />
    <x-ui.stat-card eyebrow="BLOCKED" :value="number_format($blockedCount)" label="Terblokir" icon="lucide-ban" />
    <x-ui.stat-card eyebrow="AVG RESPONSE" :value="number_format($avgResponseMs) . ' ms'" label="Rata-rata" icon="lucide-timer" />
    <x-ui.stat-card eyebrow="P95 RESPONSE" :value="number_format($p95ResponseMs) . ' ms'" label="Latency p95" icon="lucide-gauge" />
  </div>

  <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
    <x-ui.card editorial class="lg:col-span-2">
      <x-slot:header>
        <div>
          <div class="eyebrow">C2 · TREND</div>
          <h2 class="font-display font-extrabold text-2xl text-[var(--color-ink)]">Traffic & Reply Trend</h2>
          <p class="display-italic text-sm">Perbandingan incoming vs outgoing dalam {{ $days }} hari terakhir.</p>
        </div>
      </x-slot:header>

      <x-ui.metric-chart id="analytics-trend" type="line" :data="$chartData" height="280" />
    </x-ui.card>

    <x-ui.card editorial>
      <x-slot:header>
        <div>
          <div class="eyebrow">TOP NUMBERS</div>
          <h3 class="font-display font-extrabold text-xl text-[var(--color-ink)]">Most Active Senders</h3>
        </div>
      </x-slot:header>

      @if ($topNumbers->isEmpty())
        <x-ui.empty
          title="Belum ada data"
          description="Top sender akan muncul otomatis saat traffic masuk tersedia."
          icon="lucide-users"
        />
      @else
        <ol class="divide-y divide-[var(--color-rule)]">
          @foreach ($topNumbers as $index => $row)
            <li class="py-2.5 flex items-center justify-between gap-2">
              <div class="min-w-0">
                <div class="font-mono text-xs text-[var(--color-ink)] truncate">{{ $row->from_number }}</div>
                <div class="eyebrow">rank #{{ $index + 1 }}</div>
              </div>
              <x-ui.badge variant="info" size="sm">{{ (int) $row->total }} msg</x-ui.badge>
            </li>
          @endforeach
        </ol>
      @endif
    </x-ui.card>
  </div>

  <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
    <x-ui.card editorial>
      <x-slot:header>
        <div>
          <div class="eyebrow">HOURLY SPLIT</div>
          <h3 class="font-display font-extrabold text-xl text-[var(--color-ink)]">Distribusi Jam</h3>
        </div>
      </x-slot:header>

      <div class="space-y-2">
        @foreach ($hourly as $hour => $count)
          @php
            $widthPercent = $maxHourly > 0 ? ($count / $maxHourly) * 100 : 0;
            $widthClass = match (true) {
              $widthPercent >= 95 => 'w-full',
              $widthPercent >= 85 => 'w-11/12',
              $widthPercent >= 75 => 'w-9/12',
              $widthPercent >= 65 => 'w-8/12',
              $widthPercent >= 55 => 'w-7/12',
              $widthPercent >= 45 => 'w-6/12',
              $widthPercent >= 35 => 'w-5/12',
              $widthPercent >= 25 => 'w-4/12',
              $widthPercent >= 15 => 'w-3/12',
              $widthPercent >= 5 => 'w-2/12',
              default => 'w-0',
            };
          @endphp
          <div class="grid grid-cols-[42px_1fr_48px] items-center gap-2">
            <span class="font-mono text-xs text-[var(--color-ink-muted)]">{{ str_pad((string) $hour, 2, '0', STR_PAD_LEFT) }}:00</span>
            <div class="h-3 rounded-full border border-[var(--color-rule)] bg-[var(--color-paper)] overflow-hidden">
              <div class="h-full bg-[var(--color-brass)] {{ $widthClass }}"></div>
            </div>
            <span class="font-mono text-xs text-[var(--color-ink)] text-right">{{ $count }}</span>
          </div>
        @endforeach
      </div>
    </x-ui.card>

    <x-ui.card editorial>
      <x-slot:header>
        <div>
          <div class="eyebrow">HEATMAP</div>
          <h3 class="font-display font-extrabold text-xl text-[var(--color-ink)]">Peak Hours Matrix</h3>
        </div>
      </x-slot:header>

      <x-ui.heatmap :data="$heatmap" />
    </x-ui.card>
  </div>

  <x-ui.card editorial>
    <x-slot:header>
      <div>
        <div class="eyebrow">DAILY ROLLUP</div>
        <h3 class="font-display font-extrabold text-xl text-[var(--color-ink)]">analytics_daily_summary Snapshot</h3>
      </div>
    </x-slot:header>

    @if ($dailySummary->isEmpty())
      <x-ui.empty
        title="Rollup belum tersedia"
        description="Jadwal rollup harian belum mengisi tabel summary."
        icon="lucide-calendar-range"
      />
    @else
      <x-ui.table :columns="[
        ['key' => 'date', 'label' => 'Tanggal', 'class' => 'w-36'],
        ['key' => 'in', 'label' => 'In', 'class' => 'w-24'],
        ['key' => 'out', 'label' => 'Out', 'class' => 'w-24'],
        ['key' => 'avg', 'label' => 'Avg (ms)', 'class' => 'w-32'],
      ]">
        @foreach ($dailySummary as $row)
          <tr class="hover:bg-[var(--color-card-muted)]">
            <td class="px-4 py-3 font-mono text-xs text-[var(--color-ink)]">{{ $row->date?->format('Y-m-d') }}</td>
            <td class="px-4 py-3 font-mono text-xs text-[var(--color-ink)]">{{ $row->messages_in }}</td>
            <td class="px-4 py-3 font-mono text-xs text-[var(--color-ink)]">{{ $row->messages_out }}</td>
            <td class="px-4 py-3 font-mono text-xs text-[var(--color-ink-muted)]">{{ $row->avg_response_ms }}</td>
          </tr>
        @endforeach
      </x-ui.table>
    @endif
  </x-ui.card>
</div>
@endsection
