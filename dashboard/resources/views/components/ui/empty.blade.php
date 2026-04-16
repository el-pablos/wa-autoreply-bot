@props([
    'title' => 'Belum ada data',
    'description' => null,
    'icon' => 'lucide-inbox',
])

<div {{ $attributes->merge(['class' => 'text-center py-12 px-4']) }}>
    <div class="inline-flex items-center justify-center w-16 h-16 rounded-full bg-[var(--color-paper)] border border-[var(--color-rule)] text-[var(--color-ink-muted)] mb-4">
        @svg($icon, 'w-8 h-8')
    </div>
    <h3 class="font-display font-extrabold text-xl text-[var(--color-ink)]">{{ $title }}</h3>
    @if ($description)
        <p class="display-italic text-sm mt-1 max-w-md mx-auto">{{ $description }}</p>
    @endif
    @if (isset($action))
        <div class="mt-5">{{ $action }}</div>
    @endif
</div>
