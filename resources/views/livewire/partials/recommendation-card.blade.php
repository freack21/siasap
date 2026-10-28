{{--
    Kartu rekomendasi tindakan preventif (Tabel 3.3 naskah).
    $recommendation = SensorLog::recommendation(); $compact = versi ringkas di kartu ruangan.
--}}
@php
    $compact ??= false;
    $style = \App\Support\AirQuality::category($recommendation['category']);
@endphp

<div @class([$class ?? '', 'rounded-xl px-4 py-3' => $compact, 'card card-body flex flex-col gap-3' => ! $compact, $style['soft'] => $compact])>
    <div class="flex items-center justify-between gap-3">
        <p class="flex items-center gap-2 text-xs font-semibold text-slate-800">
            @unless ($compact)
                <span class="icon-chip icon-chip-sm {{ $style['soft'] }}" style="color: {{ $style['color'] }}">
                    <x-tabler-bulb class="size-4" />
                </span>
            @else
                <x-tabler-bulb class="size-4" style="color: {{ $style['color'] }}" />
            @endunless
            Rekomendasi tindakan
        </p>
        <span class="text-xs font-semibold uppercase tracking-wide" style="color: {{ $style['color'] }}">{{ $recommendation['level'] }}</span>
    </div>
    <p @class(['text-slate-700', 'mt-1 text-xs' => $compact, 'text-sm leading-relaxed' => ! $compact])>{{ $recommendation['text'] }}</p>
    <a href="{{ route('recommendations') }}" wire:navigate @class([
        'inline-flex items-center gap-1 self-start text-xs font-semibold text-accent-700 hover:text-accent-800',
        'mt-2' => $compact,
        'mt-auto' => ! $compact,
    ])>
        Lihat semua saran <x-tabler-arrow-right class="size-3.5" />
    </a>
</div>
