@props([
    'shape' => 'line', // line | block | circle
    'lines' => 1,
])

@php
    $shapes = [
        'line' => 'h-4 rounded-md',
        'block' => 'h-24 rounded-md',
        'circle' => 'w-10 h-10 rounded-full',
    ];
    $shapeClass = $shapes[$shape] ?? $shapes['line'];
@endphp

@if ($shape === 'line' && $lines > 1)
    <div {{ $attributes->merge(['class' => 'space-y-2']) }}>
        @for ($i = 0; $i < $lines; $i++)
            <div class="skeleton {{ $shapeClass }}" style="width: {{ $i === $lines - 1 ? '60%' : '100%' }}"></div>
        @endfor
    </div>
@else
    <div {{ $attributes->merge(['class' => "skeleton $shapeClass"]) }}></div>
@endif
