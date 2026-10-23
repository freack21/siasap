<div wire:poll.5s class="space-y-10">
    <x-ui.page-header
        :icon="'tabler-bulb'"
        title="Rekomendasi Tindakan"
        description="Saran tindakan preventif mengikuti kategori keseluruhan kualitas udara ruangan saat ini.">
        <x-slot:eyebrow>Rekomendasi</x-slot:eyebrow>
    </x-ui.page-header>

    {{-- Kondisi saat ini --}}
    <section class="space-y-4">
        <div>
            <h2 class="text-lg font-semibold tracking-tight text-slate-900">Kondisi saat ini</h2>
            <p class="mt-1 max-w-3xl text-sm text-slate-500">Berdasarkan pembacaan terakhir dari perangkat di setiap ruangan.</p>
        </div>

        @if ($conditions->isEmpty())
            <div class="card">
                <x-ui.empty-state icon="tabler-antenna-bars-off"
                                  title="Belum ada ruangan yang dipantau"
                                  description="Rekomendasi akan muncul setelah perangkat sensor terpasang dan mulai mengirim data." />
            </div>
        @else
            <div class="grid grid-cols-1 gap-4 lg:grid-cols-2">
                @foreach ($conditions as $condition)
                    @php($category = \App\Support\AirQuality::category($condition['log']?->overall_category))
                    <article wire:key="condition-{{ $condition['room']->id }}" class="card card-body flex flex-col gap-4">
                        <div class="flex items-start justify-between gap-3">
                            <div class="flex min-w-0 items-center gap-3">
                                <span class="flex size-10 shrink-0 items-center justify-center rounded-xl {{ $category['soft'] }}" style="color: {{ $category['color'] }}">
                                    <x-dynamic-component :component="$category['icon']" class="size-5" />
                                </span>
                                <div class="min-w-0">
                                    <h3 class="truncate font-semibold text-slate-900">{{ $condition['room']->name }}</h3>
                                    <p class="text-xs text-slate-500">
                                        @if ($condition['log'])
                                            Diperbarui {{ $condition['log']->created_at->locale('id')->diffForHumans() }}
                                        @else
                                            Belum ada data dari perangkat
                                        @endif
                                    </p>
                                </div>
                            </div>
                            <x-ui.ku-badge :status="$condition['log']?->overall_category" />
                        </div>

                        @if ($condition['recommendation'])
                            <div class="rounded-xl {{ $category['soft'] }} px-4 py-3">
                                <p class="eyebrow mb-1 text-[11px]">{{ $condition['recommendation']['level'] }}</p>
                                <p class="text-sm text-slate-700">{{ $condition['recommendation']['text'] }}</p>
                            </div>
                            @if ($condition['log']->dominantGases() !== [])
                                <p class="text-xs text-slate-500">
                                    Penyebab utama:
                                    <span class="font-medium text-slate-700">
                                        {{ collect($condition['log']->dominantGases())->map(fn ($gas) => \App\Support\AirQuality::gases()[$gas]['symbol'] ?? $gas)->implode(', ') }}
                                    </span>
                                </p>
                            @endif
                        @else
                            <p class="text-sm text-slate-500">Rekomendasi belum tersedia karena belum ada data yang diterima.</p>
                        @endif
                    </article>
                @endforeach
            </div>
        @endif
    </section>

    {{-- Aturan rekomendasi --}}
    <section class="space-y-4">
        <div>
            <h2 class="text-lg font-semibold tracking-tight text-slate-900">Aturan rekomendasi</h2>
            <p class="mt-1 max-w-3xl text-sm text-slate-500">Satu rekomendasi untuk setiap kategori keseluruhan (rule-based).</p>
        </div>

        <div class="card overflow-x-auto">
            <table class="table">
                <thead>
                    <tr>
                        <th>Kategori</th>
                        <th>Tingkat</th>
                        <th>Rekomendasi</th>
                        <th>Strategi EPA</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($rules as $rule)
                        <tr wire:key="rule-{{ $rule['category'] }}">
                            <td class="align-top"><x-ui.ku-badge :status="$rule['category']" size="sm" /></td>
                            <td class="align-top whitespace-nowrap font-medium text-slate-700">{{ $rule['level'] }}</td>
                            <td class="min-w-64 align-top whitespace-normal text-slate-600">{{ $rule['text'] }}</td>
                            <td class="align-top">
                                <div class="flex flex-wrap gap-1.5">
                                    @foreach ($rule['strategies'] as $strategy)
                                        <span class="inline-flex whitespace-nowrap rounded-md bg-slate-100 px-2 py-0.5 text-xs font-medium text-slate-600">{{ $strategyLabels[$strategy] ?? $strategy }}</span>
                                    @endforeach
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </section>

    {{-- Dasar rekomendasi --}}
    <section class="space-y-4">
        <div class="flex items-start gap-3">
            <span class="icon-chip">
                <x-tabler-shield-check class="size-5" />
            </span>
            <div>
                <h2 class="text-lg font-semibold tracking-tight text-slate-900">Dasar rekomendasi</h2>
                <p class="mt-1 max-w-3xl text-sm text-slate-500">Pemetaan kategori ke tindakan disusun peneliti dengan dua acuan berikut.</p>
            </div>
        </div>

        <div class="card card-body space-y-5">
            <ol class="space-y-3 text-sm text-slate-600">
                @foreach ($basis as $point)
                    <li class="flex gap-3">
                        <span class="flex size-6 shrink-0 items-center justify-center rounded-full bg-slate-100 text-xs font-semibold text-slate-600">{{ $loop->iteration }}</span>
                        <span class="leading-relaxed">{{ $point }}</span>
                    </li>
                @endforeach
            </ol>

            <div class="border-t border-slate-100 pt-4">
                <p class="eyebrow mb-2 text-[11px]">Sumber</p>
                <ul class="space-y-2 text-sm">
                    @foreach ($sources as $source)
                        <li>
                            <a href="{{ $source['url'] }}" target="_blank" rel="noopener noreferrer"
                               class="inline-flex items-start gap-1.5 text-accent-700 hover:underline">
                                <x-tabler-external-link class="mt-0.5 size-4 shrink-0" />
                                {{ $source['label'] }}
                            </a>
                        </li>
                    @endforeach
                </ul>
            </div>
        </div>

        <p class="flex gap-2 text-xs text-slate-500">
            <x-tabler-info-circle class="mt-px size-4 shrink-0 text-slate-400" />
            {{ $disclaimer }}
        </p>
    </section>
</div>
