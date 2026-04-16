@props([
    'tabs' => [],     // ['key' => 'general', 'label' => 'General']
    'active' => null,
    'storage' => null, // optional localStorage key
])

@php
    $defaultActive = $active ?? ($tabs[0]['key'] ?? null);
    $stateExpr = $storage
        ? "active: localStorage.getItem('$storage') || '$defaultActive'"
        : "active: '$defaultActive'";
@endphp

<div
    {{ $attributes->only('class') }}
    x-data="{ {{ $stateExpr }}, set(k){ this.active = k; @if ($storage) localStorage.setItem('{{ $storage }}', k); @endif } }"
>
    <div class="border-b border-[var(--color-ink)] flex flex-wrap gap-0 -mb-px overflow-x-auto" role="tablist">
        @foreach ($tabs as $tab)
            <button
                type="button"
                role="tab"
                :aria-selected="active === '{{ $tab['key'] }}'"
                @click="set('{{ $tab['key'] }}')"
                :class="active === '{{ $tab['key'] }}'
                    ? 'border-b-2 border-[var(--color-ink)] text-[var(--color-ink)] font-bold'
                    : 'border-b-2 border-transparent text-[var(--color-ink-muted)] hover:text-[var(--color-ink)]'"
                class="px-4 py-3 text-sm font-display tracking-[0.05em] transition-colors min-tap whitespace-nowrap"
            >
                {{ $tab['label'] }}
                @if (!empty($tab['count']))
                    <x-ui.badge size="sm" variant="muted" class="ml-1">{{ $tab['count'] }}</x-ui.badge>
                @endif
            </button>
        @endforeach
    </div>

    <div class="pt-4">
        {{ $slot }}
    </div>
</div>
