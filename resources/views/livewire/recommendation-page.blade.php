<div wire:poll.5s class="space-y-10">
    <x-ui.page-header
        :icon="'tabler-bulb'"
        title="Rekomendasi Tindakan"
        description="Saran tindakan pencegahan yang sesuai dengan kondisi udara ruangan saat ini.">
        <x-slot:eyebrow>Rekomendasi</x-slot:eyebrow>
    </x-ui.page-header>

    {{-- Kondisi saat ini --}}
    <section class="space-y-4">
        <div>
            <h2 class="text-lg font-semibold tracking-tight text-slate-900">Kondisi saat ini</h2>
            <p class="mt-1 max-w-3xl text-sm text-slate-500">Berdasarkan data terakhir dari perangkat di setiap ruangan.</p>
        </div>

        @if ($conditions->isEmpty())
            <div class="card">
                <x-ui.empty-state icon="tabler-antenna-bars-off"
                                  title="Belum ada ruangan yang dipantau"
                                  description="Saran akan muncul setelah perangkat pemantau terpasang dan mulai mengirim data." />
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
                            <p class="text-sm text-slate-500">Saran belum tersedia karena belum ada data yang diterima.</p>
                        @endif
                    </article>
                @endforeach
            </div>
        @endif
    </section>

    {{-- Aturan rekomendasi --}}
    <section class="space-y-4">
        <div>
            <h2 class="text-lg font-semibold tracking-tight text-slate-900">Saran untuk setiap kategori</h2>
            <p class="mt-1 max-w-3xl text-sm text-slate-500">Setiap kategori kualitas udara memiliki satu saran tindakan. Makin buruk kategorinya, makin tegas sarannya.</p>
        </div>

        <div class="card overflow-x-auto">
            <table class="table">
                <thead>
                    <tr>
                        <th>Kategori</th>
                        <th>Tingkat</th>
                        <th>Saran tindakan</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($rules as $rule)
                        <tr wire:key="rule-{{ $rule['category'] }}">
                            <td class="align-top"><x-ui.ku-badge :status="$rule['category']" size="sm" /></td>
                            <td class="align-top whitespace-nowrap font-medium text-slate-700">{{ $rule['level'] }}</td>
                            <td class="min-w-64 align-top whitespace-normal text-slate-600">{{ $rule['text'] }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </section>

    <section class="card card-body flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <p class="flex max-w-2xl gap-3 text-sm leading-relaxed text-slate-600">
            <x-tabler-info-circle class="mt-0.5 size-5 shrink-0 text-slate-400" />
            <span>{{ $disclaimer }} Saran disusun berdasarkan panduan kualitas udara dalam ruangan dari U.S. EPA.</span>
        </p>
        <a href="{{ route('guide') }}" wire:navigate class="btn btn-secondary shrink-0">
            <x-tabler-book-2 class="size-4" /> Dasar penyusunan saran
        </a>
    </section>
</div>
