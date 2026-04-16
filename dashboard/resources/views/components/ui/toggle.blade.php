@props([
    'name' => null,
    'id' => null,
    'label' => null,
    'description' => null,
    'checked' => false,
    'value' => '1',
])

@php
    $id = $id ?? $name ?? uniqid('toggle-');
    $isChecked = (bool) (old($name, $checked));
@endphp

<label for="{{ $id }}" {{ $attributes->merge(['class' => 'flex items-start justify-between gap-4 cursor-pointer min-tap py-2']) }}>
    <div class="flex-1 min-w-0">
        @if ($label)
            <div class="font-display font-bold text-sm text-[var(--color-ink)]">{{ $label }}</div>
        @endif
        @if ($description)
            <div class="text-xs text-[var(--color-ink-muted)] mt-0.5">{{ $description }}</div>
        @endif
    </div>

    <span class="relative inline-flex items-center" x-data="{ on: {{ $isChecked ? 'true' : 'false' }} }">
        <input
            id="{{ $id }}"
            type="checkbox"
            @if ($name) name="{{ $name }}" @endif
            value="{{ $value }}"
            @if ($isChecked) checked @endif
            x-model="on"
            class="sr-only peer"
        >
        <span
            class="w-11 h-6 rounded-full border-2 border-[var(--color-ink)] bg-[var(--color-paper)] transition-colors peer-focus-visible:outline peer-focus-visible:outline-2 peer-focus-visible:outline-[var(--color-ink)]"
            :class="on ? 'bg-[var(--color-verified)] border-[var(--color-verified)]' : 'bg-[var(--color-paper)]'"
        ></span>
        <span
            class="absolute left-0.5 top-1/2 -translate-y-1/2 w-4 h-4 bg-[var(--color-card)] rounded-full border border-[var(--color-ink)] transition-transform"
            :class="on ? 'translate-x-5 bg-[var(--color-paper)]' : ''"
        ></span>
    </span>
</label>
