@props(['title', 'description' => null, 'icon' => 'tabler-inbox'])

<div {{ $attributes->class('flex flex-col items-center justify-center px-6 py-12 text-center') }}>
    <div class="mb-3 flex size-12 items-center justify-center rounded-2xl bg-slate-100">
        <x-dynamic-component :component="$icon" class="size-6 text-slate-400" />
    </div>
    <p class="text-sm font-semibold text-slate-900">{{ $title }}</p>
    @if ($description)
        <p class="mt-1 max-w-sm text-sm text-slate-500">{{ $description }}</p>
    @endif
    @if (! $slot->isEmpty())
        <div class="mt-4">{{ $slot }}</div>
    @endif
</div>
