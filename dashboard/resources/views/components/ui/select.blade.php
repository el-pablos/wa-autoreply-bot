@props([
    'name' => null,
    'id' => null,
    'label' => null,
    'options' => [],
    'value' => null,
    'placeholder' => null,
    'hint' => null,
    'error' => null,
    'required' => false,
])

@php
    $id = $id ?? $name ?? uniqid('select-');
    $hasError = !empty($error);
    $borderClass = $hasError ? 'border-[var(--color-danger)]' : 'border-[var(--color-rule)] focus-within:border-[var(--color-ink)]';
    $current = old($name, $value);
@endphp

<div {{ $attributes->only('class') }}>
    @if ($label)
        <label for="{{ $id }}" class="block eyebrow mb-1.5">
            {{ $label }}@if ($required)<span class="text-[var(--color-danger)] ml-1">*</span>@endif
        </label>
    @endif

    <div class="relative border bg-[var(--color-card)] rounded-md transition-colors {{ $borderClass }}">
        <select
            id="{{ $id }}"
            @if ($name) name="{{ $name }}" @endif
            @if ($required) required aria-required="true" @endif
            @if ($hasError) aria-invalid="true" aria-describedby="{{ $id }}-error" @endif
            {{ $attributes->except('class')->merge([
                'class' => 'w-full appearance-none bg-transparent px-3 py-2.5 pr-9 text-sm text-[var(--color-ink)] focus:outline-none min-h-[44px]',
            ]) }}
        >
            @if ($placeholder)
                <option value="" disabled @if (is_null($current) || $current === '') selected @endif>{{ $placeholder }}</option>
            @endif

            @if (!empty($options))
                @foreach ($options as $optValue => $optLabel)
                    <option value="{{ $optValue }}" @if ((string) $current === (string) $optValue) selected @endif>{{ $optLabel }}</option>
                @endforeach
            @else
                {{ $slot }}
            @endif
        </select>
        <span class="pointer-events-none absolute right-3 top-1/2 -translate-y-1/2 text-[var(--color-ink-muted)]">▾</span>
    </div>

    @if ($hasError)
        <p id="{{ $id }}-error" class="mt-1 text-xs text-[var(--color-danger)] font-medium">{{ $error }}</p>
    @elseif ($hint)
        <p class="mt-1 text-xs text-[var(--color-ink-muted)]">{{ $hint }}</p>
    @endif
</div>
