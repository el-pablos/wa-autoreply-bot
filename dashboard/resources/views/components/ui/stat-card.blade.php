@props([
    'eyebrow' => null,
    'value' => 0,
    'label' => null,
    'trend' => null,        // 'up' | 'down' | null
    'trendValue' => null,   // contoh "+12%"
    'icon' => null,
    'editorial' => true,
    'href' => null,
])

@php
    $trendColor = $trend === 'up'
        ? 'text-[var(--color-verified)]'
        : ($trend === 'down' ? 'text-[var(--color-danger)]' : 'text-[var(--color-ink-muted)]');
    $trendArrow = $trend === 'up' ? '↑' : ($trend === 'down' ? '↓' : '·');
@endphp

<x-ui.card :editorial="$editorial" padding="md">
    <div class="flex items-start gap-3">
        @if ($icon)
            <span class="inline-flex items-center justify-center w-10 h-10 rounded-md bg-[var(--color-paper)] border border-[var(--color-rule)] text-[var(--color-ink)] shrink-0">
                @svg($icon, 'w-5 h-5')
            </span>
        @endif

        <div class="flex-1 min-w-0">
            @if ($eyebrow)
                <div class="eyebrow truncate">{{ $eyebrow }}</div>
            @endif
            <div class="font-display font-extrabold text-3xl md:text-4xl text-[var(--color-ink)] leading-none mt-1">
                @if ($href)
                    <a href="{{ $href }}" class="hover:text-[var(--color-info)] transition-colors">{{ $value }}</a>
                @else
                    {{ $value }}
                @endif
            </div>
            @if ($label)
                <div class="display-italic text-sm mt-1.5">{{ $label }}</div>
            @endif
            @if ($trend && $trendValue)
                <div class="mt-2 inline-flex items-center gap-1 text-xs font-mono font-semibold {{ $trendColor }}">
                    <span>{{ $trendArrow }}</span>
                    <span>{{ $trendValue }}</span>
                </div>
            @endif
        </div>
    </div>
</x-ui.card>
