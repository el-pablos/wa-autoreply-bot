@props([
    'title' => 'Dashboard',
    'eyebrow' => null,
    'status' => null, // 'online' | 'offline' | 'connecting'
    'statusLabel' => null,
])

@php
    $statusMap = [
        'online' => ['dot' => 'bg-[var(--color-verified)]', 'text' => 'ONLINE', 'color' => 'text-[var(--color-verified)]'],
        'offline' => ['dot' => 'bg-[var(--color-danger)]', 'text' => 'OFFLINE', 'color' => 'text-[var(--color-danger)]'],
        'connecting' => ['dot' => 'bg-[var(--color-pending)]', 'text' => 'CONNECTING', 'color' => 'text-[var(--color-pending)]'],
    ];
    $resolved = $status && isset($statusMap[$status]) ? $statusMap[$status] : null;
    $userInitial = strtoupper(substr(auth()->user()?->name ?? session('user_name', 'O'), 0, 1));
@endphp

<header class="sticky top-0 z-30 bg-[var(--color-card)] border-b border-[var(--color-ink)]">
    <div class="flex items-center gap-3 px-4 md:px-6 lg:px-8 h-14 md:h-16">
        @isset($leading)
            {{ $leading }}
        @endisset

        <div class="flex-1 min-w-0">
            @if ($eyebrow)
                <div class="eyebrow truncate">{{ $eyebrow }}</div>
            @endif
            <h1 class="font-display font-extrabold text-xl md:text-2xl leading-none truncate text-[var(--color-ink)]">
                {{ $title }}
            </h1>
        </div>

        @if ($resolved)
            <span class="hidden sm:inline-flex items-center gap-2 px-2.5 py-1 rounded-full bg-[var(--color-paper)] border border-[var(--color-rule)]">
                <span class="w-2 h-2 rounded-full {{ $resolved['dot'] }} animate-pulse"></span>
                <span class="text-[10px] font-semibold tracking-[0.15em] {{ $resolved['color'] }}">{{ $statusLabel ?? $resolved['text'] }}</span>
            </span>
        @endif

        <div class="flex items-center gap-2">
            @isset($actions){{ $actions }}@endisset
            @stack('topbar-actions')
        </div>

        <div x-data="{ open: false }" class="relative">
            <button
                type="button"
                @click="open = !open"
                @click.away="open = false"
                class="min-tap w-10 h-10 rounded-full bg-[var(--color-ink)] text-[var(--color-paper)] font-display font-bold text-base flex items-center justify-center hover:bg-[var(--color-ink-muted)] transition-colors"
                aria-label="Menu pengguna"
            >
                {{ $userInitial }}
            </button>
            <div
                x-show="open"
                x-transition:enter="transition ease-out duration-150"
                x-transition:enter-start="opacity-0 scale-95"
                x-transition:enter-end="opacity-100 scale-100"
                x-transition:leave="transition ease-in duration-100"
                x-transition:leave-start="opacity-100 scale-100"
                x-transition:leave-end="opacity-0 scale-95"
                class="absolute right-0 mt-2 w-56 bg-[var(--color-card)] border border-[var(--color-ink)] rounded-md shadow-stamp-sm overflow-hidden"
                style="display: none;"
            >
                <div class="px-4 py-3 border-b border-[var(--color-rule)]">
                    <div class="eyebrow">SIGNED IN AS</div>
                    <div class="font-display font-bold text-sm text-[var(--color-ink)] truncate">
                        {{ auth()->user()?->name ?? session('user_name', 'Operator') }}
                    </div>
                </div>
                <form action="{{ route('logout') }}" method="POST" class="block">
                    @csrf
                    <button
                        type="submit"
                        class="w-full text-left px-4 py-3 text-sm font-medium text-[var(--color-danger)] hover:bg-[var(--color-danger-soft)] transition-colors"
                    >
                        Keluar
                    </button>
                </form>
            </div>
        </div>
    </div>
</header>
