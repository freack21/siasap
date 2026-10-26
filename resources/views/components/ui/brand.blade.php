@props(['subtitle' => true])

<span {{ $attributes->class('flex items-center gap-2.5') }}>
    <span class="flex size-8 items-center justify-center rounded-lg bg-accent-600 text-white">
        <x-tabler-wind class="size-5" stroke-width="2" />
    </span>
    <span class="leading-tight">
        <span class="block text-sm font-bold tracking-tight text-slate-900">SIMKU</span>
        @if ($subtitle)
            <span class="block text-[11px] text-slate-500">Monitoring Kualitas Udara Ruangan</span>
        @endif
    </span>
</span>
