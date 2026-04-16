@props([
    'variant' => 'muted', // verified | pending | danger | info | brass | muted | ink
    'size' => 'md',       // sm | md
    'dot' => false,
])

@php
    $variants = [
        'verified' => 'bg-[var(--color-verified-soft)] text-[var(--color-verified)] border-[var(--color-verified)]',
        'pending' => 'bg-[var(--color-pending-soft)] text-[var(--color-brass-700)] border-[var(--color-pending)]',
        'danger' => 'bg-[var(--color-danger-soft)] text-[var(--color-danger)] border-[var(--color-danger)]',
        'info' => 'bg-[var(--color-info-soft)] text-[var(--color-info)] border-[var(--color-info)]',
        'brass' => 'bg-[var(--color-brass-100)] text-[var(--color-brass-700)] border-[var(--color-brass)]',
        'muted' => 'bg-[var(--color-paper)] text-[var(--color-ink-muted)] border-[var(--color-rule)]',
        'ink' => 'bg-[var(--color-ink)] text-[var(--color-paper)] border-[var(--color-ink)]',
    ];
    $sizes = [
        'sm' => 'text-[10px] px-2 py-0.5',
        'md' => 'text-xs px-2.5 py-1',
    ];
    $classes = 'inline-flex items-center gap-1.5 rounded-full border font-semibold tracking-[0.05em] uppercase font-body '
        . ($variants[$variant] ?? $variants['muted']) . ' '
        . ($sizes[$size] ?? $sizes['md']);
@endphp

<span {{ $attributes->merge(['class' => $classes]) }}>
    @if ($dot)
        <span class="w-1.5 h-1.5 rounded-full bg-current"></span>
    @endif
    {{ $slot }}
</span>
