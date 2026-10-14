{{--
    Grafik garis (Chart.js) yang ikut diperbarui saat Livewire polling.
    $chart = ['labels' => [...], 'datasets' => [['label' => .., 'data' => [..], 'color' => '#hex']], 'yTitle' => ?string]
--}}
@props(['chart', 'height' => 'h-72'])

<div x-data="lineChart" {{ $attributes->class($height.' relative') }}>
    <div x-ref="source" data-chart="{{ json_encode($chart) }}" hidden></div>
    <div wire:ignore class="absolute inset-0">
        <canvas x-ref="canvas"></canvas>
    </div>
</div>
