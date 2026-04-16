@props([
    'editorial' => false, // hard-shadow stamp signature
    'padding' => 'md',    // none | sm | md | lg
    'as' => 'div',
])

@php
    $paddings = [
        'none' => 'p-0',
        'sm' => 'p-3',
        'md' => 'p-4 md:p-5',
        'lg' => 'p-6 md:p-8',
    ];
    $paddingClass = $paddings[$padding] ?? $paddings['md'];
    $shadowClass = $editorial ? 'shadow-stamp' : 'shadow-soft';
    $borderClass = $editorial ? 'border-[var(--color-ink)]' : 'border-[var(--color-rule)]';
    $base = "bg-[var(--color-card)] border $borderClass rounded-md $shadowClass";
@endphp

<{{ $as }} {{ $attributes->merge(['class' => "$base"]) }}>
    @isset($header)
        <div class="flex items-start justify-between gap-3 px-4 md:px-5 pt-4 pb-3 border-b border-[var(--color-rule)]">
            <div class="flex-1 min-w-0">{{ $header }}</div>
            @isset($headerActions)
                <div class="flex items-center gap-2 shrink-0">{{ $headerActions }}</div>
            @endisset
        </div>
        <div class="{{ $paddingClass }}">
            {{ $slot }}
        </div>
    @else
        <div class="{{ $padding === 'none' ? '' : $paddingClass }}">
            {{ $slot }}
        </div>
    @endisset

    @isset($footer)
        <div class="px-4 md:px-5 py-3 border-t border-[var(--color-rule)] bg-[var(--color-card-muted)] rounded-b-md">
            {{ $footer }}
        </div>
    @endisset
</{{ $as }}>
