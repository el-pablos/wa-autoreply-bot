@props([
    'align' => 'right', // left | right
    'width' => 'w-56',
])

@php
    $alignClass = $align === 'left' ? 'left-0 origin-top-left' : 'right-0 origin-top-right';
@endphp

<div {{ $attributes->merge(['class' => 'relative inline-block']) }} x-data="{ open: false }" @click.away="open = false" @keydown.escape.window="open = false">
    <div @click="open = !open">
        {{ $trigger }}
    </div>

    <div
        x-show="open"
        x-transition:enter="transition ease-out duration-150"
        x-transition:enter-start="opacity-0 scale-95"
        x-transition:enter-end="opacity-100 scale-100"
        x-transition:leave="transition ease-in duration-100"
        x-transition:leave-start="opacity-100 scale-100"
        x-transition:leave-end="opacity-0 scale-95"
        class="absolute z-30 mt-2 {{ $width }} {{ $alignClass }} bg-[var(--color-card)] border border-[var(--color-ink)] rounded-md shadow-stamp-sm overflow-hidden"
        role="menu"
        style="display: none;"
    >
        {{ $slot }}
    </div>
</div>
