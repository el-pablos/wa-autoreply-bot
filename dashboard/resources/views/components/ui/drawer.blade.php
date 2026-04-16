@props([
    'name' => 'drawer',
    'title' => null,
    'description' => null,
    'side' => 'right', // right | bottom (mobile default = bottom-sheet)
    'width' => 'w-full md:w-[440px]',
])

@php
    $position = $side === 'bottom'
        ? 'inset-x-0 bottom-0 max-h-[85vh] rounded-t-2xl border-t-2'
        : 'inset-y-0 right-0 h-full md:max-w-md ' . $width . ' border-l-2';
    $enterStart = $side === 'bottom' ? 'translate-y-full' : 'translate-x-full';
    $enterEnd = $side === 'bottom' ? 'translate-y-0' : 'translate-x-0';
@endphp

<template x-teleport="body">
    <div
        x-data="{ open: false }"
        @open-drawer.window="if ($event.detail.name === '{{ $name }}') open = true"
        @close-drawer.window="if ($event.detail.name === '{{ $name }}') open = false"
        @keydown.escape.window="open = false"
        x-show="open"
        x-transition.opacity
        class="fixed inset-0 z-[60]"
        style="display: none;"
        x-trap.noscroll="open"
        role="dialog"
        aria-modal="true"
    >
        <div class="absolute inset-0 bg-black/50" @click="open = false"></div>

        <aside
            x-show="open"
            x-transition:enter="transition ease-out duration-250"
            x-transition:enter-start="{{ $enterStart }}"
            x-transition:enter-end="{{ $enterEnd }}"
            x-transition:leave="transition ease-in duration-200"
            x-transition:leave-start="{{ $enterEnd }}"
            x-transition:leave-end="{{ $enterStart }}"
            class="absolute {{ $position }} bg-[var(--color-paper)] border-[var(--color-ink)] flex flex-col"
        >
            @if ($title)
                <header class="px-5 py-4 border-b border-[var(--color-ink)] bg-[var(--color-card)]">
                    <div class="flex items-start justify-between gap-3">
                        <div>
                            <h2 class="font-display font-extrabold text-xl text-[var(--color-ink)]">{{ $title }}</h2>
                            @if ($description)
                                <p class="display-italic text-sm mt-1">{{ $description }}</p>
                            @endif
                        </div>
                        <button type="button" @click="open = false" class="min-tap w-10 h-10 inline-flex items-center justify-center text-[var(--color-ink-muted)] hover:text-[var(--color-ink)]" aria-label="Tutup">×</button>
                    </div>
                </header>
            @endif

            <div class="flex-1 overflow-y-auto p-5">
                {{ $slot }}
            </div>

            @isset($footer)
                <footer class="px-5 py-4 border-t border-[var(--color-rule)] bg-[var(--color-card)] flex flex-wrap justify-end gap-2">
                    {{ $footer }}
                </footer>
            @endisset
        </aside>
    </div>
</template>
