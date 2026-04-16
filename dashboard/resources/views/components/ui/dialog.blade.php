@props([
    'name' => 'dialog',
    'title' => null,
    'description' => null,
    'maxWidth' => 'max-w-lg',
    'editorial' => true,
])

{{--
    Usage:
    <x-ui.dialog name="confirm" title="Hapus item?">
        Apakah kamu yakin ingin menghapus?
        <x-slot:footer>
            <x-ui.button variant="ghost" x-on:click="$dispatch('close-dialog', { name: 'confirm' })">Batal</x-ui.button>
            <x-ui.button variant="danger" x-on:click="submit">Hapus</x-ui.button>
        </x-slot:footer>
    </x-ui.dialog>

    Open dari mana saja:
        $dispatch('open-dialog', { name: 'confirm' })
--}}

<template x-teleport="body">
    <div
        x-data="{ open: false }"
        @open-dialog.window="if ($event.detail.name === '{{ $name }}') { open = true; $nextTick(() => $refs.panel?.focus()); }"
        @close-dialog.window="if ($event.detail.name === '{{ $name }}') open = false;"
        @keydown.escape.window="open = false"
        x-show="open"
        x-transition.opacity
        class="fixed inset-0 z-[70] flex items-end md:items-center justify-center p-0 md:p-4"
        style="display: none;"
        x-trap.noscroll="open"
        role="dialog"
        aria-modal="true"
        @if ($title) aria-labelledby="dialog-{{ $name }}-title" @endif
    >
        <div class="absolute inset-0 bg-black/50" @click="open = false"></div>

        <div
            x-ref="panel"
            tabindex="-1"
            x-show="open"
            x-transition:enter="transition ease-out duration-200"
            x-transition:enter-start="opacity-0 translate-y-4 md:translate-y-0 md:scale-95"
            x-transition:enter-end="opacity-100 translate-y-0 md:scale-100"
            x-transition:leave="transition ease-in duration-150"
            x-transition:leave-start="opacity-100 translate-y-0 md:scale-100"
            x-transition:leave-end="opacity-0 translate-y-4 md:translate-y-0 md:scale-95"
            class="relative bg-[var(--color-card)] border-2 border-[var(--color-ink)] rounded-t-2xl md:rounded-md w-full {{ $maxWidth }} {{ $editorial ? 'shadow-stamp' : 'shadow-elevate' }} overflow-hidden focus:outline-none"
        >
            @if ($title)
                <header class="px-5 py-4 border-b border-[var(--color-ink)]">
                    <h2 id="dialog-{{ $name }}-title" class="font-display font-extrabold text-xl text-[var(--color-ink)]">{{ $title }}</h2>
                    @if ($description)
                        <p class="display-italic text-sm mt-1">{{ $description }}</p>
                    @endif
                </header>
            @endif

            <div class="p-5 max-h-[70vh] overflow-y-auto">
                {{ $slot }}
            </div>

            @isset($footer)
                <footer class="px-5 py-4 border-t border-[var(--color-rule)] bg-[var(--color-card-muted)] flex flex-wrap justify-end gap-2">
                    {{ $footer }}
                </footer>
            @endisset
        </div>
    </div>
</template>
