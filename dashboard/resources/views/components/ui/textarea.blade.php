@props([
    'name' => null,
    'id' => null,
    'label' => null,
    'value' => null,
    'placeholder' => null,
    'hint' => null,
    'error' => null,
    'required' => false,
    'rows' => 4,
    'maxlength' => null,
    'counter' => false,
])

@php
    $id = $id ?? $name ?? uniqid('textarea-');
    $hasError = !empty($error);
    $borderClass = $hasError ? 'border-[var(--color-danger)]' : 'border-[var(--color-rule)] focus-within:border-[var(--color-ink)]';
@endphp

<div {{ $attributes->only('class') }} @if ($counter && $maxlength) x-data="{ len: ($refs && $refs.ta) ? $refs.ta.value.length : ({{ strlen((string)($value ?? '')) }}) }" @endif>
    @if ($label)
        <label for="{{ $id }}" class="block eyebrow mb-1.5">
            {{ $label }}@if ($required)<span class="text-[var(--color-danger)] ml-1">*</span>@endif
        </label>
    @endif

    <div class="border bg-[var(--color-card)] rounded-md transition-colors {{ $borderClass }}">
        <textarea
            id="{{ $id }}"
            x-ref="ta"
            @if ($counter && $maxlength) @input="len = $event.target.value.length" @endif
            @if ($name) name="{{ $name }}" @endif
            @if ($placeholder) placeholder="{{ $placeholder }}" @endif
            @if ($required) required aria-required="true" @endif
            @if ($maxlength) maxlength="{{ $maxlength }}" @endif
            @if ($hasError) aria-invalid="true" aria-describedby="{{ $id }}-error" @endif
            rows="{{ $rows }}"
            {{ $attributes->except('class')->merge([
                'class' => 'w-full bg-transparent px-3 py-2.5 text-sm text-[var(--color-ink)] placeholder:text-[var(--color-ink-muted)]/60 focus:outline-none font-body resize-y',
            ]) }}
        >{{ $value ?? $slot }}</textarea>
    </div>

    <div class="flex justify-between gap-3 mt-1">
        <div class="flex-1">
            @if ($hasError)
                <p id="{{ $id }}-error" class="text-xs text-[var(--color-danger)] font-medium">{{ $error }}</p>
            @elseif ($hint)
                <p class="text-xs text-[var(--color-ink-muted)]">{{ $hint }}</p>
            @endif
        </div>
        @if ($counter && $maxlength)
            <p class="text-xs text-[var(--color-ink-muted)] font-mono whitespace-nowrap">
                <span x-text="len"></span> / {{ $maxlength }}
            </p>
        @endif
    </div>
</div>
