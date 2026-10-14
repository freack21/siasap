@props(['subtitle' => true])

<span {{ $attributes->class('flex items-center gap-2.5') }}>
    <span class="flex size-8 items-center justify-center rounded-lg bg-accent-600 text-white">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="size-4.5" aria-hidden="true">
            <path d="M3 8h11a3 3 0 1 0-3-3" /><path d="M3 12h15a3 3 0 1 1-3 3" /><path d="M3 16h7" />
        </svg>
    </span>
    <span class="leading-tight">
        <span class="block text-sm font-bold tracking-tight text-slate-900">SIASAP</span>
        @if ($subtitle)
            <span class="block text-[11px] text-slate-500">Monitoring Kualitas Udara Ruangan</span>
        @endif
    </span>
</span>
