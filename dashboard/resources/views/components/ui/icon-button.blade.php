@props([
    'icon' => 'lucide-circle',
    'label' => 'Aksi',
    'variant' => 'ghost', // ghost | outline | solid | danger
    'size' => 'md',       // sm | md | lg
    'href' => null,
    'type' => 'button',
])

@php
    $variants = [
        'ghost' => 'bg-transparent text-[var(--color-ink-muted)] hover:bg-[var(--color-paper)] hover:text-[var(--color-ink)]',
        'outline' => 'bg-transparent text-[var(--color-ink)] border border-[var(--color-rule)] hover:border-[var(--color-ink)]',
        'solid' => 'bg-[var(--color-ink)] text-[var(--color-paper)] hover:bg-[var(--color-ink-muted)]',
        'danger' => 'bg-transparent text-[var(--color-danger)] hover:bg-[var(--color-danger-soft)]',
    ];
    $sizes = [
        'sm' => 'w-8 h-8',
        'md' => 'w-10 h-10 min-tap',
        'lg' => 'w-12 h-12 min-tap',
    ];
    $iconSize = ['sm' => 'w-4 h-4', 'md' => 'w-5 h-5', 'lg' => 'w-6 h-6'][$size] ?? 'w-5 h-5';
    $classes = 'inline-flex items-center justify-center rounded-md transition-colors '
        . ($variants[$variant] ?? $variants['ghost']) . ' '
        . ($sizes[$size] ?? $sizes['md']);
@endphp

@if ($href)
    <a href="{{ $href }}" aria-label="{{ $label }}" title="{{ $label }}" {{ $attributes->merge(['class' => $classes]) }}>
        @svg($icon, $iconSize)
    </a>
@else
    <button type="{{ $type }}" aria-label="{{ $label }}" title="{{ $label }}" {{ $attributes->merge(['class' => $classes]) }}>
        @svg($icon, $iconSize)
    </button>
@endif
