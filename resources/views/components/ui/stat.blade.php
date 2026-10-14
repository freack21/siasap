@props(['label', 'value', 'unit' => null, 'hint' => null, 'icon' => null])

<div {{ $attributes->class('card card-body') }}>
    <div class="flex items-center justify-between gap-3">
        <p class="text-xs font-medium text-slate-500">{{ $label }}</p>
        @if ($icon)
            <x-dynamic-component :component="$icon" class="size-4 text-slate-400" />
        @endif
    </div>
    <p class="mt-2 text-2xl font-bold tracking-tight text-slate-900 num">
        {{ $value }}@if ($unit)<span class="ml-1 text-sm font-medium text-slate-400">{{ $unit }}</span>@endif
    </p>
    @if ($hint)
        <p class="mt-1 text-xs text-slate-500">{{ $hint }}</p>
    @endif
</div>
