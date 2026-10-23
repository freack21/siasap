@props(['recommendation'])

@php($gases = \App\Support\AirQuality::gases())

<x-ui.card {{ $attributes }} :icon="'tabler-shield-check'" title="Tindakan Preventif"
           description="Berdasarkan pembacaan terakhir, mengacu pada standar U.S. EPA">
    @if ($recommendation['all_good'])
        <div class="flex items-start gap-3">
            <span class="icon-chip icon-chip-sm bg-emerald-50 text-emerald-600">
                <x-tabler-leaf class="size-4" />
            </span>
            <div class="min-w-0">
                <p class="text-sm font-semibold text-slate-900">Semua gas berada pada kategori Baik</p>
                <p class="mt-0.5 text-sm text-slate-500">Tidak ada tindakan khusus yang diperlukan. Langkah pemeliharaan berikut membantu menjaga kondisi ini.</p>
                <ul class="mt-3 space-y-2">
                    @foreach ($recommendation['maintenance'] as $line)
                        <li class="flex items-start gap-2 text-sm text-slate-700">
                            <x-tabler-circle-check class="mt-0.5 size-4 shrink-0 text-emerald-600" />
                            <span>{{ $line }}</span>
                        </li>
                    @endforeach
                </ul>
                @if ($recommendation['maintenance_sources'])
                    <p class="mt-3 text-xs leading-relaxed text-slate-500">
                        <span class="font-medium text-slate-600">Sumber:</span>
                        @foreach ($recommendation['maintenance_sources'] as $source)
                            <a href="{{ $source['url'] }}" target="_blank" rel="noopener" class="underline decoration-slate-300 underline-offset-2 hover:text-slate-800">{{ $source['label'] }}</a>@if (! $loop->last)<span class="text-slate-300"> · </span>@endif
                        @endforeach
                    </p>
                @endif
            </div>
        </div>
    @else
        <div class="divide-y divide-slate-100">
            @foreach ($recommendation['items'] as $item)
                @php($gas = $gases[$item['gas']] ?? null)
                <article class="py-5 first:pt-0 last:pb-0" wire:key="preventive-{{ $item['gas'] }}">
                    <div class="flex flex-wrap items-start justify-between gap-3">
                        <div class="flex min-w-0 items-center gap-3">
                            <span class="icon-chip icon-chip-sm"
                                  style="color: {{ $gas['color'] ?? '#64748b' }}; background-color: {{ ($gas['color'] ?? '#64748b').'1a' }}">
                                <x-dynamic-component :component="$gas['icon'] ?? 'tabler-cloud'" class="size-4" />
                            </span>
                            <div class="min-w-0">
                                <p class="text-sm font-semibold text-slate-900">
                                    {{ $gas['symbol'] ?? strtoupper($item['gas']) }}
                                    <span class="font-normal text-slate-500">· {{ $gas['name'] ?? '' }}</span>
                                </p>
                                <p class="mt-0.5 text-xs text-slate-500">
                                    @if ($item['gas'] === 'co')
                                        Level AQI EPA: <span class="font-medium text-slate-700">{{ $item['level_label'] }}</span>
                                    @else
                                        Sub-indeks <span class="num font-medium text-slate-700">{{ number_format($item['sub_index'], 0) }}</span>
                                    @endif
                                </p>
                            </div>
                        </div>
                        <x-ui.ku-badge :status="$item['category']" size="sm" :icon="true" />
                    </div>

                    @if ($item['cautionary'])
                        <div class="mt-4 rounded-r-xl border-l-2 border-slate-300 bg-slate-50 px-4 py-3">
                            <p class="text-xs font-semibold text-slate-500">Anjuran EPA</p>
                            <p class="mt-0.5 text-sm text-slate-700">{{ $item['cautionary'] }}</p>
                        </div>
                    @endif

                    @if ($item['actions'])
                        <ul class="mt-4 space-y-2">
                            @foreach ($item['actions'] as $action)
                                <li class="flex items-start gap-2 text-sm text-slate-700">
                                    <x-tabler-circle-check class="mt-0.5 size-4 shrink-0 text-accent-600" />
                                    <span>{{ $action }}</span>
                                </li>
                            @endforeach
                        </ul>
                    @endif

                    @if ($item['sources'])
                        <p class="mt-3 text-xs leading-relaxed text-slate-500">
                            <span class="font-medium text-slate-600">Sumber:</span>
                            @foreach ($item['sources'] as $source)
                                <a href="{{ $source['url'] }}" target="_blank" rel="noopener" class="underline decoration-slate-300 underline-offset-2 hover:text-slate-800">{{ $source['label'] }}</a>@if (! $loop->last)<span class="text-slate-300"> · </span>@endif
                            @endforeach
                        </p>
                    @endif
                </article>
            @endforeach
        </div>
    @endif

    <p class="mt-5 flex items-center gap-1.5 border-t border-slate-100 pt-4 text-xs text-slate-500">
        <x-tabler-info-circle class="size-4 shrink-0 text-slate-400" />
        Rekomendasi bersifat informatif dan bukan diagnosis medis.
    </p>
</x-ui.card>
