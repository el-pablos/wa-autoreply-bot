@extends('layouts.app')

@php
    $pageTitle = 'Dashboard';
    $pageEyebrow = 'OVERVIEW';
    $navActive = 'dashboard';
    $botStatus = ($stats['bot_status'] ?? 'offline') === 'online' ? 'online' : (($stats['bot_status'] ?? 'offline') === 'connecting' ? 'connecting' : 'offline');
    $botStatusLabel = strtoupper($stats['bot_status'] ?? 'OFFLINE');

    $autoReplyOn = ($stats['auto_reply'] ?? 'false') === 'true';

    // Chart data 7 hari
    $chartLabels = $daily->map(fn ($d) => \Carbon\Carbon::parse($d->date)->format('d/m'))->values()->all();
    $chartValues = $daily->pluck('total')->values()->all();

    $chartData = [
        'labels' => $chartLabels,
        'datasets' => [[
            'label' => 'Pesan masuk',
            'data' => $chartValues,
            'backgroundColor' => 'rgba(168, 155, 106, 0.35)',
            'borderColor' => '#1a1a1a',
            'borderWidth' => 2,
            'borderRadius' => 2,
        ]],
    ];

    $chartOptions = [
        'plugins' => ['legend' => ['display' => false]],
    ];
@endphp

@section('content')
<div class="space-y-6">

    {{-- KPI Grid --}}
    <section>
        <div class="mb-3">
            <div class="eyebrow">TODAY'S NEWSROOM</div>
            <h2 class="font-display font-extrabold text-2xl md:text-3xl text-[var(--color-ink)]">Ringkasan Operasional</h2>
            <p class="display-italic text-sm">Data agregat pesan masuk, allow-list & auto-reply.</p>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3 md:gap-4">
            <x-ui.stat-card
                eyebrow="TOTAL PESAN"
                :value="number_format($stats['total_messages'])"
                label="Seluruh pesan masuk"
                icon="lucide-mail"
            />
            <x-ui.stat-card
                eyebrow="PESAN HARI INI"
                :value="number_format($stats['today_messages'])"
                label="Masuk 24 jam terakhir"
                icon="lucide-clock"
                :href="route('logs.index')"
            />
            <x-ui.stat-card
                eyebrow="SUDAH DIBALAS"
                :value="number_format($stats['total_replied'])"
                label="Auto-reply terkirim"
                icon="lucide-reply"
                trend="up"
                :trendValue="$stats['total_messages'] > 0 ? round(($stats['total_replied'] / max($stats['total_messages'], 1)) * 100) . '%' : '0%'"
            />
            <x-ui.stat-card
                eyebrow="NOMOR AKTIF"
                :value="$stats['active_numbers']"
                :label="'dari ' . $stats['total_numbers'] . ' nomor'"
                icon="lucide-list-checks"
                :href="route('allowlist.index')"
            />
        </div>
    </section>

    {{-- Chart + Status --}}
    <section class="grid grid-cols-1 lg:grid-cols-3 gap-4">
        <x-ui.card editorial class="lg:col-span-2">
            <x-slot:header>
                <div>
                    <div class="eyebrow">WEEKLY VOLUME</div>
                    <h3 class="font-display font-extrabold text-xl text-[var(--color-ink)]">Pesan 7 Hari Terakhir</h3>
                    <p class="display-italic text-xs">Sumber: log penerimaan · sliding window 7 hari.</p>
                </div>
            </x-slot:header>
            <x-slot:headerActions>
                <x-ui.badge variant="ink" size="sm">{{ array_sum($chartValues) }} pesan</x-ui.badge>
            </x-slot:headerActions>

            @if (empty($chartValues) || array_sum($chartValues) === 0)
                <x-ui.empty
                    title="Belum ada volume pesan"
                    description="Grafik muncul otomatis setelah bot menerima pesan pertama minggu ini."
                    icon="lucide-bar-chart-3"
                />
            @else
                <x-ui.metric-chart
                    id="dashboard-daily"
                    type="bar"
                    :data="$chartData"
                    :options="$chartOptions"
                    height="260"
                />
            @endif
        </x-ui.card>

        <x-ui.card editorial>
            <x-slot:header>
                <div>
                    <div class="eyebrow">BOT STATUS</div>
                    <h3 class="font-display font-extrabold text-xl text-[var(--color-ink)]">Kondisi Runtime</h3>
                </div>
            </x-slot:header>

            <dl class="divide-y divide-[var(--color-rule)]">
                <div class="py-3 flex items-center justify-between gap-3">
                    <dt class="eyebrow">CONNECTION</dt>
                    <dd>
                        @if ($botStatus === 'online')
                            <x-ui.badge variant="verified" :dot="true">Online</x-ui.badge>
                        @elseif ($botStatus === 'connecting')
                            <x-ui.badge variant="pending" :dot="true">Connecting</x-ui.badge>
                        @else
                            <x-ui.badge variant="danger" :dot="true">Offline</x-ui.badge>
                        @endif
                    </dd>
                </div>
                <div class="py-3 flex items-center justify-between gap-3">
                    <dt class="eyebrow">AUTO-REPLY</dt>
                    <dd>
                        @if ($autoReplyOn)
                            <x-ui.badge variant="verified">Aktif</x-ui.badge>
                        @else
                            <x-ui.badge variant="muted">Nonaktif</x-ui.badge>
                        @endif
                    </dd>
                </div>
                <div class="py-3 flex items-center justify-between gap-3">
                    <dt class="eyebrow">NOMOR ALLOWLIST</dt>
                    <dd class="font-mono font-semibold text-[var(--color-ink)]">{{ $stats['active_numbers'] }}/{{ $stats['total_numbers'] }}</dd>
                </div>
                <div class="py-3 flex items-center justify-between gap-3">
                    <dt class="eyebrow">PESAN ALLOWED</dt>
                    <dd class="font-mono font-semibold text-[var(--color-ink)]">{{ number_format($stats['total_allowed']) }}</dd>
                </div>
            </dl>

            <div class="mt-4 grid grid-cols-2 gap-2">
                <x-ui.button :href="route('settings.index')" variant="secondary" size="sm" icon="lucide-settings">Pengaturan</x-ui.button>
                <x-ui.button :href="route('logs.index')" variant="outline" size="sm" icon="lucide-scroll-text">Lihat Log</x-ui.button>
            </div>
        </x-ui.card>
    </section>

    {{-- Top numbers + Recent activity --}}
    <section class="grid grid-cols-1 lg:grid-cols-3 gap-4">
        {{-- Top 5 Numbers --}}
        <x-ui.card editorial>
            <x-slot:header>
                <div>
                    <div class="eyebrow">MOST ACTIVE</div>
                    <h3 class="font-display font-extrabold text-xl text-[var(--color-ink)]">Top 5 Nomor</h3>
                    <p class="display-italic text-xs">Pengirim terbanyak sepanjang waktu.</p>
                </div>
            </x-slot:header>

            @if ($topNumbers->isEmpty())
                <x-ui.empty
                    title="Belum ada kontribusi"
                    description="Daftar ini muncul saat ada pesan pertama."
                    icon="lucide-users"
                />
            @else
                <ol class="divide-y divide-[var(--color-rule)]">
                    @foreach ($topNumbers as $i => $row)
                        <li class="py-3 flex items-center gap-3">
                            <span class="w-8 h-8 rounded-full bg-[var(--color-paper)] border border-[var(--color-ink)] inline-flex items-center justify-center font-display font-extrabold text-sm text-[var(--color-ink)] shrink-0">
                                {{ $i + 1 }}
                            </span>
                            <div class="flex-1 min-w-0">
                                <div class="font-mono text-sm text-[var(--color-ink)] truncate">{{ $row->from_number }}</div>
                                <div class="eyebrow">{{ $row->total }} pesan</div>
                            </div>
                        </li>
                    @endforeach
                </ol>
            @endif
        </x-ui.card>

        {{-- Recent activity --}}
        <x-ui.card editorial class="lg:col-span-2">
            <x-slot:header>
                <div>
                    <div class="eyebrow">LATEST EDITION</div>
                    <h3 class="font-display font-extrabold text-xl text-[var(--color-ink)]">Pesan Terbaru</h3>
                    <p class="display-italic text-xs">10 pesan terakhir yang masuk ke bot.</p>
                </div>
            </x-slot:header>
            <x-slot:headerActions>
                <x-ui.button :href="route('logs.index')" variant="ghost" size="sm" iconRight="lucide-arrow-right">Semua log</x-ui.button>
            </x-slot:headerActions>

            @if ($recentLogs->isEmpty())
                <x-ui.empty
                    title="Belum ada pesan masuk"
                    description="Pastikan bot online & sudah terhubung dengan WhatsApp."
                    icon="lucide-inbox"
                />
            @else
                {{-- Mobile: card stack --}}
                <ul class="md:hidden divide-y divide-[var(--color-rule)]">
                    @foreach ($recentLogs as $log)
                        <li class="py-3">
                            <div class="flex items-start justify-between gap-2 mb-1">
                                <span class="font-mono text-xs text-[var(--color-ink)] truncate">{{ $log->from_number }}</span>
                                <span class="font-mono text-[10px] text-[var(--color-ink-muted)] whitespace-nowrap">{{ $log->received_at?->diffForHumans() }}</span>
                            </div>
                            <p class="text-sm text-[var(--color-ink)] line-clamp-2">{{ $log->message_text ?? '—' }}</p>
                            <div class="mt-2 flex flex-wrap gap-1.5">
                                @if ($log->replied)
                                    <x-ui.badge variant="verified" size="sm">Dibalas</x-ui.badge>
                                @else
                                    <x-ui.badge variant="muted" size="sm">Belum dibalas</x-ui.badge>
                                @endif
                                @if ($log->is_allowed)
                                    <x-ui.badge variant="info" size="sm">Allowlist</x-ui.badge>
                                @endif
                            </div>
                        </li>
                    @endforeach
                </ul>

                {{-- Desktop: table --}}
                <div class="hidden md:block">
                    <x-ui.table :columns="[
                        ['key' => 'from', 'label' => 'Nomor'],
                        ['key' => 'text', 'label' => 'Pesan'],
                        ['key' => 'replied', 'label' => 'Status', 'class' => 'w-28'],
                        ['key' => 'time', 'label' => 'Waktu', 'class' => 'w-40'],
                    ]">
                        @foreach ($recentLogs as $log)
                            <tr class="hover:bg-[var(--color-card-muted)]">
                                <td class="px-4 py-3 font-mono text-xs text-[var(--color-ink)]">{{ $log->from_number }}</td>
                                <td class="px-4 py-3 text-sm text-[var(--color-ink)]">
                                    <span class="block truncate max-w-md">{{ $log->message_text ?? '—' }}</span>
                                </td>
                                <td class="px-4 py-3">
                                    @if ($log->replied)
                                        <x-ui.badge variant="verified" size="sm">Dibalas</x-ui.badge>
                                    @else
                                        <x-ui.badge variant="muted" size="sm">—</x-ui.badge>
                                    @endif
                                </td>
                                <td class="px-4 py-3 font-mono text-xs text-[var(--color-ink-muted)] whitespace-nowrap">{{ $log->received_at?->diffForHumans() }}</td>
                            </tr>
                        @endforeach
                    </x-ui.table>
                </div>
            @endif
        </x-ui.card>
    </section>

</div>
@endsection
