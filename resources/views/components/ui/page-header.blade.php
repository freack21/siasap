@props(['title', 'description' => null, 'icon' => null])

<div {{ $attributes->class('flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between') }}>
    <div class="flex min-w-0 items-start gap-4">
        @if ($icon)
            <span class="icon-chip icon-chip-lg mt-0.5 hidden sm:flex">
                <x-dynamic-component :component="$icon" class="size-6" />
            </span>
        @endif
        <div class="min-w-0">
            @isset($eyebrow)
                <p class="eyebrow mb-1">{{ $eyebrow }}</p>
            @endisset
            <h1 class="text-2xl font-bold tracking-tight text-slate-900">{{ $title }}</h1>
            @if ($description)
                <p class="mt-1 max-w-2xl text-sm text-slate-500">{{ $description }}</p>
            @endif
        </div>
    </div>
    @isset($actions)
        <div class="flex shrink-0 flex-wrap items-center gap-2">{{ $actions }}</div>
    @endisset
</div>
