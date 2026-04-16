@props([
    'paginator' => null, // Laravel paginator instance
])

@if ($paginator && $paginator->hasPages())
    <nav class="flex items-center justify-between gap-3 mt-4" aria-label="Pagination">
        <div class="text-xs text-[var(--color-ink-muted)] font-mono">
            Hal. <strong class="text-[var(--color-ink)]">{{ $paginator->currentPage() }}</strong>
            / {{ $paginator->lastPage() }} ·
            <strong class="text-[var(--color-ink)]">{{ $paginator->total() }}</strong> total
        </div>
        <div class="flex items-center gap-2">
            @if ($paginator->onFirstPage())
                <span class="min-tap inline-flex items-center justify-center px-3 h-10 rounded-md border border-[var(--color-rule)] text-[var(--color-ink-muted)] cursor-not-allowed">‹ Prev</span>
            @else
                <a
                    href="{{ $paginator->previousPageUrl() }}"
                    rel="prev"
                    class="min-tap inline-flex items-center justify-center px-3 h-10 rounded-md border border-[var(--color-ink)] text-[var(--color-ink)] hover:bg-[var(--color-paper)] transition-colors"
                >‹ Prev</a>
            @endif

            @if ($paginator->hasMorePages())
                <a
                    href="{{ $paginator->nextPageUrl() }}"
                    rel="next"
                    class="min-tap inline-flex items-center justify-center px-3 h-10 rounded-md border border-[var(--color-ink)] bg-[var(--color-ink)] text-[var(--color-paper)] hover:bg-[var(--color-ink-muted)] transition-colors"
                >Next ›</a>
            @else
                <span class="min-tap inline-flex items-center justify-center px-3 h-10 rounded-md border border-[var(--color-rule)] text-[var(--color-ink-muted)] cursor-not-allowed">Next ›</span>
            @endif
        </div>
    </nav>
@endif
