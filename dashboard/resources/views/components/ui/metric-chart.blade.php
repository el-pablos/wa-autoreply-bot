@props([
    'id' => null,
    'type' => 'line',     // line | bar | doughnut
    'data' => [],         // ChartJS data object
    'options' => [],      // ChartJS options object
    'height' => '260',
])

@php
    $id = $id ?? 'chart-' . uniqid();
    $payload = json_encode([
        'type' => $type,
        'data' => $data,
        'options' => array_merge([
            'responsive' => true,
            'maintainAspectRatio' => false,
            'plugins' => [
                'legend' => ['labels' => ['font' => ['family' => 'Inter, system-ui', 'size' => 12]]],
            ],
            'scales' => $type === 'doughnut' ? null : [
                'x' => ['ticks' => ['font' => ['family' => 'JetBrains Mono', 'size' => 10]]],
                'y' => ['ticks' => ['font' => ['family' => 'JetBrains Mono', 'size' => 10]], 'beginAtZero' => true],
            ],
        ], $options),
    ], JSON_UNESCAPED_UNICODE);
@endphp

<div {{ $attributes->merge(['class' => 'w-full']) }}>
    <div style="height: {{ $height }}px; position: relative;">
        <canvas
            id="{{ $id }}"
            data-chart="{{ htmlspecialchars($payload, ENT_QUOTES, 'UTF-8') }}"
            x-data
            x-init="if (window.Chart) { new window.Chart($el.getContext('2d'), JSON.parse($el.dataset.chart)); }"
        ></canvas>
    </div>
</div>
