@props([
    'title' => 'Untitled',
    'subtitle' => null,
    'phone' => null,
    'status' => 'verified', // verified | pending | danger | muted
    'statusLabel' => null,
    'meta' => null,         // misal countdown / duration
])

@php
    $statusColorMap = [
        'verified' => 'bg-[var(--color-verified)]',
        'pending' => 'bg-[var(--color-pending)]',
        'danger' => 'bg-[var(--color-danger)]',
        'muted' => 'bg-[var(--color-ink-muted)]',
    ];
    $statusTextMap = [
        'verified' => 'text-[var(--color-verified)]',
        'pending' => 'text-[var(--color-brass-700)]',
        'danger' => 'text-[var(--color-danger)]',
        'muted' => 'text-[var(--color-ink-muted)]',
    ];
    $statusBar = $statusColorMap[$status] ?? $statusColorMap['muted'];
    $statusText = $statusTextMap[$status] ?? $statusTextMap['muted'];
    $resolvedLabel = $statusLabel ?? strtoupper($status);
@endphp

<article {{ $attributes->merge(['class' => 'bg-[var(--color-card)] border border-[var(--color-rule)] rounded-md p-3 md:p-4 hover:border-[var(--color-ink)] hover:shadow-stamp-sm transition-all']) }}>
    <div class="flex items-start gap-3">
        <span class="status-bar h-12 self-stretch {{ $statusBar }}"></span>
        <div class="flex-1 min-w-0">
            <div class="font-display font-bold text-base md:text-lg text-[var(--color-ink)] truncate">{{ $title }}</div>
            @if ($subtitle)
                <div class="text-xs text-[var(--color-ink-muted)] mt-0.5 truncate">{{ $subtitle }}</div>
            @endif
            @if ($phone)
                <div class="font-mono text-xs text-[var(--color-ink-muted)] mt-1 truncate">{{ $phone }}</div>
            @endif
        </div>
        <div class="flex flex-col items-end gap-1 shrink-0">
            <span class="eyebrow {{ $statusText }}">{{ $resolvedLabel }}</span>
            @if ($meta)
                <span class="font-mono text-xs text-[var(--color-ink-muted)]">{{ $meta }}</span>
            @endif
        </div>
    </div>

    @isset($body)
        <div class="mt-3 text-sm text-[var(--color-ink)]">{{ $body }}</div>
    @endisset

    @isset($actions)
        <div class="mt-3 flex flex-wrap gap-2 justify-end">{{ $actions }}</div>
    @endisset
</article>
