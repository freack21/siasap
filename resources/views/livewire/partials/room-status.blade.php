{{--
    Status perangkat dan waktu pembaruan satu ruangan.
    $card = hasil UserDashboard::summarizeRoom(); $compact = tampilan satu baris kecil.
--}}
@php($compact ??= false)

@if ($compact)
    <p class="mt-1 flex flex-wrap items-center gap-x-3 gap-y-0.5 text-xs text-slate-500">
        <span class="inline-flex items-center gap-1">
            @if ($card['online'])
                <x-tabler-wifi class="size-3.5 text-accent-600" /> Perangkat terhubung
            @else
                <x-tabler-wifi-off class="size-3.5 text-slate-400" /> Perangkat terputus
            @endif
        </span>
        @if ($card['updated_at'])
            <span class="inline-flex items-center gap-1">
                <x-tabler-clock class="size-3.5 text-slate-400" /> {{ $card['updated_at']->locale('id')->diffForHumans() }}
            </span>
        @endif
    </p>
@else
    <div class="flex flex-wrap items-center gap-x-5 gap-y-2 text-xs text-slate-500">
        <span @class([
            'inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 font-medium ring-1 ring-inset',
            'bg-emerald-50 text-emerald-700 ring-emerald-600/20' => $card['online'],
            'bg-slate-100 text-slate-600 ring-slate-500/20' => ! $card['online'],
        ])>
            @if ($card['online'])
                <x-tabler-wifi class="size-3.5" /> Perangkat terhubung
            @else
                <x-tabler-wifi-off class="size-3.5" /> Perangkat terputus
            @endif
        </span>
        <span class="inline-flex items-center gap-1.5">
            <x-tabler-clock class="size-4 text-slate-400" />
            @if ($card['updated_at'])
                Diperbarui {{ $card['updated_at']->locale('id')->diffForHumans() }}
                <span class="num text-slate-400">({{ $card['updated_at']->locale('id')->translatedFormat('d M Y H:i:s') }})</span>
            @else
                Belum pernah menerima data
            @endif
        </span>
    </div>
@endif
