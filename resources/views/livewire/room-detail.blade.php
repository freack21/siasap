<div wire:poll.5s class="space-y-6">
    {{-- Kepala halaman --}}
    <div class="space-y-4">
        <a href="{{ route('home') }}" wire:navigate class="inline-flex items-center gap-1.5 text-sm font-medium text-slate-500 hover:text-slate-900">
            <x-tabler-arrow-left class="size-4" /> Kembali ke beranda
        </a>

        <x-ui.page-header :icon="'tabler-door'" :title="$room->name" :description="$room->description">
            <x-slot:eyebrow>Detail ruangan</x-slot:eyebrow>
            <x-slot:actions>
                <x-ui.ku-badge :status="$current?->overall_category" size="lg" :icon="true" />
            </x-slot:actions>
        </x-ui.page-header>

        <div class="flex flex-wrap items-center gap-x-5 gap-y-2 text-xs text-slate-500">
            <span class="inline-flex items-center gap-1.5">
                <x-tabler-clock class="size-4 text-slate-400" />
                Diperbarui {{ $lastUpdate ? $lastUpdate->locale('id')->diffForHumans() : 'belum pernah' }}
            </span>
            @forelse ($devices as $device)
                <span class="inline-flex items-center gap-1.5">
                    @if ($device['online'])
                        <x-tabler-wifi class="size-4 text-accent-600" />
                    @else
                        <x-tabler-wifi-off class="size-4 text-slate-400" />
                    @endif
                    {{ $device['name'] }}: {{ $device['online'] ? 'terhubung' : 'terputus' }}
                </span>
            @empty
                <span class="inline-flex items-center gap-1.5">
                    <x-tabler-cpu class="size-4 text-slate-400" />
                    Belum ada sensor aktif di ruangan ini
                </span>
            @endforelse
        </div>
    </div>

    @if (! $current)
        <div class="card">
            <x-ui.empty-state icon="tabler-antenna-bars-off"
                              title="Belum ada data dari ruangan ini"
                              description="Sensor belum mengirim pembacaan. Halaman akan diperbarui otomatis begitu data pertama masuk." />
        </div>
    @else
        {{-- Nilai terkini --}}
        <section class="grid grid-cols-1 gap-4 lg:grid-cols-3">
            <div class="card card-body flex flex-col justify-between gap-4">
                <div class="flex items-start justify-between gap-3">
                    <div class="flex items-start gap-3">
                        <span class="flex size-12 shrink-0 items-center justify-center rounded-2xl {{ $currentCategory['soft'] }}"
                              style="color: {{ $currentCategory['color'] }}">
                            <x-dynamic-component :component="$currentCategory['icon']" class="size-6" />
                        </span>
                        <div>
                            <p class="text-xs font-medium text-slate-500">Kategori keseluruhan</p>
                            <p class="mt-1 text-3xl font-bold tracking-tight text-slate-900">{{ $current->overall_category }}</p>
                        </div>
                    </div>
                    @if ($dominant !== [])
                        <div class="text-right text-xs text-slate-500">
                            <p>Penyebab utama</p>
                            <p class="mt-0.5 text-sm font-semibold text-slate-800">
                                {{ collect($dominant)->map(fn ($key) => $gases[$key]['symbol'] ?? strtoupper($key))->implode(', ') }}
                            </p>
                        </div>
                    @endif
                </div>
                <div class="rounded-xl {{ $currentCategory['soft'] }} px-4 py-3">
                    <p class="flex items-center gap-1.5 text-xs font-semibold text-slate-800">
                        <x-tabler-bulb class="size-4" style="color: {{ $currentCategory['color'] }}" />
                        Rekomendasi
                    </p>
                    <p class="mt-0.5 text-xs font-semibold uppercase tracking-wide text-slate-500">{{ $recommendation['level'] }}</p>
                    <p class="mt-0.5 text-sm text-slate-700">{{ $recommendation['text'] }}</p>
                </div>
            </div>

            <div class="grid grid-cols-2 gap-4 lg:col-span-2">
                @foreach ($gases as $key => $gas)
                    @php
                        $ppm = (float) $current->{$key};
                        $gasCategory = $current->categories()[$key];
                        $subCategory = \App\Support\AirQuality::category($gasCategory);
                    @endphp
                    <div class="card card-body" wire:key="gas-{{ $key }}">
                        <div class="flex items-start justify-between gap-2">
                            <div class="flex min-w-0 items-center gap-3">
                                <span class="icon-chip icon-chip-sm bg-slate-50" style="color: {{ $gas['color'] }}">
                                    <x-dynamic-component :component="$gas['icon']" class="size-4" />
                                </span>
                                <div class="min-w-0">
                                    <p class="text-sm font-semibold text-slate-900">{{ $gas['symbol'] }}</p>
                                    <p class="truncate text-xs text-slate-500">{{ $gas['name'] }}</p>
                                </div>
                            </div>
                            <span class="size-2.5 shrink-0 rounded-full {{ $subCategory['dot'] }}" title="{{ $subCategory['label'] }}"></span>
                        </div>
                        <p class="num mt-3 text-2xl font-bold tracking-tight text-slate-900">
                            {{ number_format($ppm, 2, ',', '.') }}<span class="ml-1 text-sm font-medium text-slate-400">ppm</span>
                        </p>
                        <p class="mt-1 text-xs text-slate-500">
                            <span class="font-medium" style="color: {{ $subCategory['color'] }}">{{ $subCategory['label'] }}</span>
                            · <span class="num">{{ \App\Support\AirQuality::thresholdLabel($key, $gasCategory) }}</span>
                        </p>
                    </div>
                @endforeach
            </div>
        </section>
    @endif

    {{-- Tren --}}
    <section class="space-y-4">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <h2 class="text-base font-semibold text-slate-900">Tren kualitas udara</h2>
                <p class="mt-0.5 text-sm text-slate-500">Nilai rata-rata per interval waktu untuk rentang yang dipilih.</p>
            </div>
            <div class="inline-flex self-start rounded-xl border border-slate-200 bg-white p-1" role="group" aria-label="Rentang waktu">
                @foreach ($ranges as $key => $option)
                    <button type="button" wire:click="setRange('{{ $key }}')" aria-pressed="{{ $range === $key ? 'true' : 'false' }}"
                            @class([
                                'inline-flex items-center gap-1.5 rounded-lg px-3 py-1.5 text-xs font-semibold transition',
                                'bg-slate-900 text-white' => $range === $key,
                                'text-slate-600 hover:bg-slate-100' => $range !== $key,
                            ])>
                        <x-dynamic-component :component="$key === '7d' ? 'tabler-calendar' : 'tabler-clock'" class="size-4" />
                        {{ $option['label'] }}
                    </button>
                @endforeach
            </div>
        </div>

        <div class="grid grid-cols-1 gap-4 lg:grid-cols-2">
            <x-ui.card class="lg:col-span-2" :icon="'tabler-chart-line'" title="Konsentrasi gas" description="Satuan ppm">
                <x-ui.line-chart :chart="$gasChart" height="h-64" />
            </x-ui.card>
        </div>

        {{-- Ringkasan rentang --}}
        <div class="grid grid-cols-1 gap-4 lg:grid-cols-3">
            <x-ui.card class="lg:col-span-2" :icon="'tabler-chart-pie-2'" title="Proporsi waktu per kategori"
                       :description="$stats['count'] ? 'Berdasarkan '.number_format($stats['count'], 0, ',', '.').' pembacaan dalam '.$ranges[$range]['label'].' terakhir' : 'Belum ada pembacaan dalam rentang ini'">
                @if ($stats['count'])
                    <div class="flex h-3 w-full overflow-hidden rounded-full bg-slate-100">
                        @foreach ($categories as $name => $category)
                            @if ($stats['distribution'][$name] > 0)
                                <div class="{{ $category['dot'] }}" style="width: {{ $stats['distribution'][$name] }}%" title="{{ $name }}"></div>
                            @endif
                        @endforeach
                    </div>
                    <dl class="mt-5 grid grid-cols-2 gap-4 sm:grid-cols-4">
                        @foreach ($categories as $name => $category)
                            <div>
                                <dt class="flex items-center gap-1.5 text-xs text-slate-500">
                                    <x-dynamic-component :component="$category['icon']" class="size-4" style="color: {{ $category['color'] }}" />{{ $category['label'] }}
                                </dt>
                                <dd class="num mt-1 text-lg font-semibold text-slate-900">{{ number_format($stats['distribution'][$name], 1, ',', '.') }}%</dd>
                            </div>
                        @endforeach
                    </dl>
                @else
                    <p class="text-sm text-slate-500">Pilih rentang waktu yang lebih panjang atau tunggu data baru masuk.</p>
                @endif
            </x-ui.card>
        </div>
    </section>

    {{-- Pembacaan terakhir --}}
    <x-ui.card :icon="'tabler-list'" title="10 pembacaan terakhir" :padded="false">
        @if ($recent->isEmpty())
            <x-ui.empty-state icon="tabler-history" title="Belum ada pembacaan" description="Data akan muncul di sini setelah sensor mengirim pembacaan." />
        @else
            <div class="overflow-x-auto">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Waktu</th>
                            @if ($multiNode)<th>Sensor</th>@endif
                            <th>Status</th>
                                                        @foreach ($gases as $gas)
                                <th class="text-right">{{ $gas['symbol'] }} <span class="normal-case">(ppm)</span></th>
                            @endforeach
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($recent as $log)
                            <tr wire:key="log-{{ $log->id }}">
                                <td class="num whitespace-nowrap">{{ ($log->recorded_at ?? $log->created_at)->locale('id')->translatedFormat('d M H:i:s') }}</td>
                                @if ($multiNode)<td class="whitespace-nowrap">{{ $log->node?->name }}</td>@endif
                                <td><x-ui.ku-badge :status="$log->overall_category" size="sm" /></td>
                                @foreach ($gases as $key => $gas)
                                    <td class="num text-right">{{ number_format($log->{$key}, 2, ',', '.') }}</td>
                                @endforeach
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </x-ui.card>
</div>
