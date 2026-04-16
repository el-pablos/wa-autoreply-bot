@props([
    'data' => [],   // 7x24 array atau dict {weekday(1-7) => {hour(0-23) => count}}
    'max' => null,  // optional cap; default auto-detect
])

@php
    $weekdays = ['Sen', 'Sel', 'Rab', 'Kam', 'Jum', 'Sab', 'Min'];
    // Normalisasi data ke matrix 7x24
    $matrix = array_fill(0, 7, array_fill(0, 24, 0));
    foreach ($data as $wd => $hours) {
        $row = ((int) $wd - 1);
        if ($row < 0 || $row > 6) continue;
        foreach ((array) $hours as $h => $count) {
            $h = (int) $h;
            if ($h < 0 || $h > 23) continue;
            $matrix[$row][$h] = (int) $count;
        }
    }
    $autoMax = max(array_map('max', $matrix));
    $cap = $max ?? max($autoMax, 1);

    $cellColor = function (int $value) use ($cap): string {
        if ($value === 0) return 'bg-[var(--color-paper)] border border-[var(--color-rule)]';
        $intensity = min(1.0, $value / $cap);
        // 5 step gradient brass → ink
        if ($intensity < 0.2) return 'bg-[var(--color-brass-50)] border border-[var(--color-brass-100)]';
        if ($intensity < 0.4) return 'bg-[var(--color-brass-100)] border border-[var(--color-brass-200)]';
        if ($intensity < 0.6) return 'bg-[var(--color-brass-200)] border border-[var(--color-brass-400)]';
        if ($intensity < 0.8) return 'bg-[var(--color-brass-400)] border border-[var(--color-brass)]';
        return 'bg-[var(--color-ink)] border border-[var(--color-ink)]';
    };
@endphp

<div {{ $attributes->merge(['class' => 'w-full overflow-x-auto']) }}>
    <div class="inline-block min-w-full">
        {{-- Hour header row --}}
        <div class="flex gap-0.5 ml-9 mb-1">
            @for ($h = 0; $h < 24; $h++)
                <div class="w-5 md:w-6 text-[9px] font-mono text-[var(--color-ink-muted)] text-center">
                    @if ($h % 3 === 0){{ str_pad((string) $h, 2, '0', STR_PAD_LEFT) }}@endif
                </div>
            @endfor
        </div>
        @for ($d = 0; $d < 7; $d++)
            <div class="flex gap-0.5 mb-0.5 items-center">
                <div class="w-9 text-[10px] font-display font-bold text-[var(--color-ink)] uppercase tracking-wider">{{ $weekdays[$d] }}</div>
                @for ($h = 0; $h < 24; $h++)
                    <div
                        class="w-5 md:w-6 h-5 md:h-6 rounded-sm {{ $cellColor($matrix[$d][$h]) }}"
                        title="{{ $weekdays[$d] }} jam {{ $h }}: {{ $matrix[$d][$h] }} pesan"
                    ></div>
                @endfor
            </div>
        @endfor
        {{-- Legend --}}
        <div class="flex items-center gap-2 mt-3 text-[10px] font-mono text-[var(--color-ink-muted)]">
            <span>Sedikit</span>
            <span class="w-3 h-3 rounded-sm bg-[var(--color-brass-50)] border border-[var(--color-brass-100)]"></span>
            <span class="w-3 h-3 rounded-sm bg-[var(--color-brass-100)] border border-[var(--color-brass-200)]"></span>
            <span class="w-3 h-3 rounded-sm bg-[var(--color-brass-200)] border border-[var(--color-brass-400)]"></span>
            <span class="w-3 h-3 rounded-sm bg-[var(--color-brass-400)] border border-[var(--color-brass)]"></span>
            <span class="w-3 h-3 rounded-sm bg-[var(--color-ink)]"></span>
            <span>Banyak</span>
        </div>
    </div>
</div>
