@props([
    'variant' => 'primary', // primary | secondary | outline | danger | ghost
    'size' => 'md',         // sm | md | lg
    'type' => 'button',
    'href' => null,
    'loading' => false,
    'icon' => null,
    'iconRight' => null,
    'block' => false,
])

@php
    $variants = [
        'primary' => 'bg-[var(--color-ink)] text-[var(--color-paper)] hover:bg-[var(--color-ink-muted)] border-[var(--color-ink)] shadow-stamp-sm hover:shadow-none hover:translate-x-0.5 hover:translate-y-0.5',
        'secondary' => 'bg-[var(--color-paper)] text-[var(--color-ink)] hover:bg-[var(--color-brass-100)] border-[var(--color-rule)]',
        'outline' => 'bg-transparent text-[var(--color-ink)] hover:bg-[var(--color-paper)] border-[var(--color-ink)]',
        'danger' => 'bg-[var(--color-danger)] text-white hover:opacity-90 border-[var(--color-danger)] shadow-stamp-sm hover:shadow-none hover:translate-x-0.5 hover:translate-y-0.5',
        'ghost' => 'bg-transparent text-[var(--color-ink-muted)] hover:bg-[var(--color-paper)] border-transparent',
    ];

    $sizes = [
        'sm' => 'h-9 px-3 text-xs rounded-md',
        'md' => 'h-11 px-4 text-sm rounded-md min-tap',
        'lg' => 'h-12 px-6 text-base rounded-md min-tap',
    ];

    $variantClass = $variants[$variant] ?? $variants['primary'];
    $sizeClass = $sizes[$size] ?? $sizes['md'];
    $blockClass = $block ? 'w-full' : '';
    $base = 'btn-base border';
    $classes = trim("$base $variantClass $sizeClass $blockClass");
@endphp

@if ($href)
    <a
        href="{{ $href }}"
        {{ $attributes->merge(['class' => $classes]) }}
        @if ($loading) aria-busy="true" @endif
    >
        @if ($loading)
            <span class="w-4 h-4 border-2 border-current border-t-transparent rounded-full animate-spin"></span>
        @elseif ($icon)
            <span class="inline-flex items-center">@svg($icon, 'w-4 h-4')</span>
        @endif
        <span>{{ $slot }}</span>
        @if ($iconRight)
            <span class="inline-flex items-center">@svg($iconRight, 'w-4 h-4')</span>
        @endif
    </a>
@else
    <button
        type="{{ $type }}"
        {{ $attributes->merge(['class' => $classes]) }}
        @if ($loading) aria-busy="true" disabled @endif
    >
        @if ($loading)
            <span class="w-4 h-4 border-2 border-current border-t-transparent rounded-full animate-spin"></span>
        @elseif ($icon)
            <span class="inline-flex items-center">@svg($icon, 'w-4 h-4')</span>
        @endif
        <span>{{ $slot }}</span>
        @if ($iconRight)
            <span class="inline-flex items-center">@svg($iconRight, 'w-4 h-4')</span>
        @endif
    </button>
@endif
