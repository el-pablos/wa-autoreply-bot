@props([
    'name' => null,
    'id' => null,
    'label' => null,
    'type' => 'text',
    'value' => null,
    'placeholder' => null,
    'hint' => null,
    'error' => null,
    'required' => false,
    'prefix' => null,
    'suffix' => null,
    'mono' => false,
])

@php
    $id = $id ?? $name ?? uniqid('input-');
    $hasError = !empty($error);
    $borderClass = $hasError ? 'border-[var(--color-danger)]' : 'border-[var(--color-rule)] focus-within:border-[var(--color-ink)]';
    $monoClass = $mono ? 'font-mono' : 'font-body';
@endphp

<div {{ $attributes->only('class') }}>
    @if ($label)
        <label for="{{ $id }}" class="block eyebrow mb-1.5">
            {{ $label }}@if ($required)<span class="text-[var(--color-danger)] ml-1">*</span>@endif
        </label>
    @endif

    <div class="flex items-stretch border bg-[var(--color-card)] rounded-md transition-colors {{ $borderClass }}">
        @if ($prefix)
            <span class="flex items-center px-3 text-[var(--color-ink-muted)] text-sm border-r border-[var(--color-rule)]">{{ $prefix }}</span>
        @endif

        <input
            type="{{ $type }}"
            id="{{ $id }}"
            @if ($name) name="{{ $name }}" @endif
            @if (!is_null($value)) value="{{ $value }}" @endif
            @if ($placeholder) placeholder="{{ $placeholder }}" @endif
            @if ($required) required aria-required="true" @endif
            @if ($hasError) aria-invalid="true" aria-describedby="{{ $id }}-error" @endif
            {{ $attributes->except('class')->merge([
                'class' => "flex-1 bg-transparent px-3 py-2.5 text-sm text-[var(--color-ink)] placeholder:text-[var(--color-ink-muted)]/60 focus:outline-none $monoClass min-h-[44px]",
            ]) }}
        />

        @if ($suffix)
            <span class="flex items-center px-3 text-[var(--color-ink-muted)] text-sm border-l border-[var(--color-rule)]">{{ $suffix }}</span>
        @endif
    </div>

    @if ($hasError)
        <p id="{{ $id }}-error" class="mt-1 text-xs text-[var(--color-danger)] font-medium">{{ $error }}</p>
    @elseif ($hint)
        <p class="mt-1 text-xs text-[var(--color-ink-muted)]">{{ $hint }}</p>
    @endif
</div>
