@props([
    'active' => null,
])

@php
    // Bottom-pill nav: 4 slot utama + 1 slot "Lainnya" buka drawer.
    $primary = [
        ['key' => 'dashboard', 'label' => 'Home',     'route' => 'dashboard',      'pattern' => 'dashboard*'],
        ['key' => 'allowlist', 'label' => 'List',     'route' => 'allowlist.index', 'pattern' => 'allowlist*'],
        ['key' => 'chat-live', 'label' => 'Chat',     'route' => 'chat-live.index', 'pattern' => 'chat-live*'],
        ['key' => 'settings',  'label' => 'Settings', 'route' => 'settings.index',  'pattern' => 'settings*'],
    ];

    $more = [
        ['key' => 'logs',           'label' => 'Logs',           'route' => 'logs.index'],
        ['key' => 'approved',       'label' => 'Approved',       'route' => 'approved.index'],
        ['key' => 'analytics',      'label' => 'Analytics',      'route' => 'analytics.index'],
        ['key' => 'audit',          'label' => 'Audit Trail',    'route' => 'audit.index'],
        ['key' => 'templates',      'label' => 'Templates',      'route' => 'templates.index'],
        ['key' => 'business-hours', 'label' => 'Business Hours', 'route' => 'business-hours.index'],
        ['key' => 'blacklist',      'label' => 'Blacklist',      'route' => 'blacklist.index'],
        ['key' => 'alerts',         'label' => 'Alerts',         'route' => 'alerts.index'],
    ];

    $hasRoute = fn (?string $name): bool => $name !== null && \Illuminate\Support\Facades\Route::has($name);
@endphp

<div {{ $attributes->merge(['class' => 'md:hidden fixed bottom-4 inset-x-4 z-40 pointer-events-none']) }}
     x-data="{ moreOpen: false }"
>
    <nav class="pointer-events-auto bg-[var(--color-ink)] rounded-[var(--radius-pill)] px-2 py-2 flex justify-around shadow-elevate" aria-label="Navigasi utama mobile">
        @foreach ($primary as $item)
            @php
                $isActive = $active === $item['key'] || (isset($item['pattern']) && request()->routeIs($item['pattern']));
                $href = $hasRoute($item['route']) ? route($item['route']) : '#';
                $disabled = !$hasRoute($item['route']);
            @endphp
            <a
                href="{{ $href }}"
                @class([
                    'min-tap flex flex-col items-center justify-center gap-1 rounded-[14px] px-3 py-1 transition-colors',
                    'bg-[var(--color-paper)] text-[var(--color-ink)] font-semibold' => $isActive,
                    'text-[var(--color-brass)] hover:text-[var(--color-paper)]' => !$isActive && !$disabled,
                    'text-[var(--color-brass-700)] opacity-60 pointer-events-none' => $disabled,
                ])
                @if ($disabled) aria-disabled="true" @endif
            >
                <span class="block w-4 h-4 rounded-sm border-2 {{ $isActive ? 'border-[var(--color-ink)]' : 'border-current' }}" aria-hidden="true"></span>
                <span class="text-[9px] font-display tracking-[0.12em] uppercase">{{ $item['label'] }}</span>
            </a>
        @endforeach

        <button
            type="button"
            @click="moreOpen = true"
            class="min-tap flex flex-col items-center justify-center gap-1 rounded-[14px] px-3 py-1 text-[var(--color-brass)] hover:text-[var(--color-paper)] transition-colors"
            aria-label="Buka menu lainnya"
        >
            <span class="block w-4 h-4 rounded-sm border-2 border-current" aria-hidden="true"></span>
            <span class="text-[9px] font-display tracking-[0.12em] uppercase">More</span>
        </button>
    </nav>

    {{-- Bottom sheet drawer --}}
    <template x-teleport="body">
        <div
            x-show="moreOpen"
            x-transition.opacity
            class="fixed inset-0 z-50 md:hidden"
            style="display: none;"
            x-trap.noscroll="moreOpen"
        >
            <div class="absolute inset-0 bg-black/50" @click="moreOpen = false"></div>
            <div
                x-show="moreOpen"
                x-transition:enter="transition ease-out duration-200"
                x-transition:enter-start="translate-y-full"
                x-transition:enter-end="translate-y-0"
                x-transition:leave="transition ease-in duration-150"
                x-transition:leave-start="translate-y-0"
                x-transition:leave-end="translate-y-full"
                class="absolute inset-x-0 bottom-0 bg-[var(--color-paper)] border-t-2 border-[var(--color-ink)] rounded-t-2xl pt-4 pb-6 max-h-[80vh] overflow-y-auto"
            >
                <div class="mx-auto w-12 h-1.5 rounded-full bg-[var(--color-brass)] mb-4"></div>
                <div class="px-5 mb-3">
                    <div class="eyebrow">MENU LAINNYA</div>
                    <div class="font-display font-extrabold text-xl text-[var(--color-ink)]">Operator's Index</div>
                </div>
                <ul class="px-5 grid grid-cols-2 gap-3">
                    @foreach ($more as $item)
                        @php
                            $href = $hasRoute($item['route']) ? route($item['route']) : null;
                        @endphp
                        @if ($href)
                            <li>
                                <a
                                    href="{{ $href }}"
                                    class="block min-tap p-3 bg-[var(--color-card)] border border-[var(--color-rule)] rounded-md hover:border-[var(--color-ink)] hover:shadow-stamp-sm transition-all"
                                >
                                    <div class="font-display font-bold text-sm text-[var(--color-ink)]">{{ $item['label'] }}</div>
                                </a>
                            </li>
                        @endif
                    @endforeach
                </ul>
                <button
                    type="button"
                    @click="moreOpen = false"
                    class="block mx-5 mt-5 w-[calc(100%-2.5rem)] min-tap bg-[var(--color-ink)] text-[var(--color-paper)] font-semibold rounded-md py-3"
                >
                    Tutup
                </button>
            </div>
        </div>
    </template>
</div>
