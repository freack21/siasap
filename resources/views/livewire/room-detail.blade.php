<div wire:poll.5s class="space-y-6">
    {{-- Kepala halaman --}}
    <div class="space-y-4">
        <a href="{{ route('home') }}" wire:navigate class="inline-flex items-center gap-1.5 text-sm font-medium text-slate-500 hover:text-slate-900">
            <x-tabler-arrow-left class="size-4" /> Kembali ke beranda
        </a>

        <x-ui.page-header :icon="'tabler-door'" :title="$room->name" :description="$room->description">
            <x-slot:eyebrow>Detail ruangan · Grafik sensor</x-slot:eyebrow>
            <x-slot:actions>
                <x-ui.ku-badge :status="$current?->overall_category" size="lg" :icon="true" />
            </x-slot:actions>
        </x-ui.page-header>

        <div class="flex flex-wrap items-center gap-x-5 gap-y-2 text-xs text-slate-500">
            <span class="inline-flex items-center gap-1.5">
                <x-tabler-clock class="size-4 text-slate-400" />
                @if ($lastUpdate)
                    Diperbarui {{ $lastUpdate->locale('id')->diffForHumans() }}
                    <span class="num text-slate-400">({{ $lastUpdate->locale('id')->translatedFormat('d M Y H:i:s') }})</span>
                @else
                    Belum pernah menerima data
                @endif
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
                    Belum ada perangkat aktif di ruangan ini
                </span>
            @endforelse
        </div>
    </div>

    @if (! $current)
        <div class="card">
            <x-ui.empty-state icon="tabler-hourglass-high"
                              title="Menunggu data dari perangkat IoT"
                              description="Perangkat belum mengirim pembacaan. Halaman diperbarui otomatis setiap 5 detik begitu data pertama masuk." />
        </div>
    @else
        {{-- Nilai terkini --}}
        <section class="grid grid-cols-1 gap-4 lg:grid-cols-3">
            <div class="card card-body flex flex-col gap-4">
                <div class="flex items-start gap-3">
                    <span class="flex size-12 shrink-0 items-center justify-center rounded-2xl {{ $currentCategory['soft'] }}"
                          style="color: {{ $currentCategory['color'] }}">
                        <x-dynamic-component :component="$currentCategory['icon']" class="size-6" />
                    </span>
                    <div>
                        <p class="text-xs font-medium text-slate-500">Kategori keseluruhan</p>
                        <p class="mt-1 text-3xl font-bold tracking-tight text-slate-900">{{ $currentCategory['label'] }}</p>
                    </div>
                </div>
                @if ($dominant !== [])
                    <p class="rounded-xl bg-slate-50 px-4 py-3 text-xs text-slate-500">
                        Penyebab utama:
                        <span class="text-sm font-semibold text-slate-900">{{ collect($dominant)->map(fn ($key) => $gases[$key]['symbol'])->implode(', ') }}</span>
                    </p>
                @endif
            </div>

            @include('livewire.partials.recommendation-card', ['recommendation' => $recommendation, 'class' => 'lg:col-span-2'])
        </section>

        <section class="grid grid-cols-2 gap-4 lg:grid-cols-4" aria-label="Nilai gas terkini">
            @foreach ($gases as $key => $g)
                @php($gasCategory = $current->categories()[$key])
                <button type="button" wire:click="setGas('{{ $key }}')" wire:key="gas-{{ $key }}" aria-pressed="{{ $gas === $key ? 'true' : 'false' }}"
                        @class([
                            'card card-body flex flex-col gap-3 text-left transition focus:outline-none focus-visible:ring-2 focus-visible:ring-accent-500',
                            'ring-2 ring-accent-500' => $gas === $key,
                            'hover:border-slate-300' => $gas !== $key,
                        ])>
                    <span class="flex items-center gap-2.5">
                        <span class="icon-chip icon-chip-sm bg-slate-50" style="color: {{ $g['color'] }}">
                            <x-dynamic-component :component="$g['icon']" class="size-4" />
                        </span>
                        <span class="min-w-0 leading-tight">
                            <span class="block text-sm font-semibold text-slate-900">{{ $g['symbol'] }}</span>
                            <span class="block truncate text-xs text-slate-500">{{ $g['name'] }}</span>
                        </span>
                    </span>
                    <span class="num text-2xl font-bold tracking-tight text-slate-900">
                        {{ number_format($current->{$key}, 2, ',', '.') }}<span class="ml-1 text-sm font-medium text-slate-400">ppm</span>
                    </span>
                    <span class="flex flex-wrap items-center gap-x-2 gap-y-1">
                        <x-ui.ku-badge :status="$gasCategory" size="sm" />
                        <span class="num text-[11px] text-slate-400">{{ \App\Support\AirQuality::thresholdLabel($key, $gasCategory) }}</span>
                    </span>
                </button>
            @endforeach
        </section>
    @endif

    {{-- Grafik sensor --}}
    <section class="space-y-4">
        <div class="flex flex-col gap-3 lg:flex-row lg:items-end lg:justify-between">
            <div>
                <h2 class="text-base font-semibold text-slate-900">Grafik sensor</h2>
                <p class="mt-0.5 text-sm text-slate-500">Rata-rata per interval waktu untuk gas dan rentang yang dipilih.</p>
            </div>
            <div class="flex flex-wrap gap-2">
                <div class="inline-flex rounded-xl border border-slate-200 bg-white p-1" role="group" aria-label="Pilih gas">
                    @foreach ($gases as $key => $g)
                        <button type="button" wire:click="setGas('{{ $key }}')" aria-pressed="{{ $gas === $key ? 'true' : 'false' }}"
                                @class([
                                    'rounded-lg px-3 py-1.5 text-xs font-semibold transition',
                                    'bg-slate-900 text-white' => $gas === $key,
                                    'text-slate-600 hover:bg-slate-100' => $gas !== $key,
                                ])>
                            {{ $g['symbol'] }}
                        </button>
                    @endforeach
                </div>
                <div class="inline-flex rounded-xl border border-slate-200 bg-white p-1" role="group" aria-label="Rentang waktu">
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
        </div>

        <x-ui.card :icon="$selectedGas['icon']" :title="$selectedGas['symbol'].' — '.$selectedGas['name']"
                   description="Garis putus-putus menandai batas atas kategori Baik, Sedang, dan Tidak Sehat (Solihat dkk., 2026).">
            <x-ui.line-chart :chart="$gasChart" height="h-72" />
        </x-ui.card>

        {{-- Ringkasan rentang --}}
        <div class="space-y-4">
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                <x-ui.stat label="Rata-rata" :icon="'tabler-math-avg'" unit="ppm"
                           :value="$stats['avg'] === null ? '—' : number_format($stats['avg'], 2, ',', '.')" />
                <x-ui.stat label="Minimum" :icon="'tabler-arrow-down'" unit="ppm"
                           :value="$stats['min'] === null ? '—' : number_format($stats['min'], 2, ',', '.')" />
                <x-ui.stat label="Maksimum" :icon="'tabler-arrow-up'" unit="ppm"
                           :value="$stats['max'] === null ? '—' : number_format($stats['max'], 2, ',', '.')" />
            </div>

            <x-ui.card :icon="'tabler-chart-pie-2'" title="Proporsi kategori keseluruhan"
                       :description="$stats['count'] ? 'Berdasarkan '.number_format($stats['count'], 0, ',', '.').' pembacaan dalam '.$ranges[$range]['label'].' terakhir' : 'Belum ada pembacaan dalam rentang ini'">
                @if ($stats['count'])
                    <div class="flex h-3 w-full overflow-hidden rounded-full bg-slate-100" role="img" aria-label="Proporsi kategori keseluruhan">
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
    <x-ui.card :icon="'tabler-list'" title="10 pembacaan terakhir" description="Nilai gas dalam ppm (indikator relatif)." :padded="false">
        @if ($recent->isEmpty())
            <x-ui.empty-state icon="tabler-history" title="Belum ada pembacaan" description="Data akan muncul di sini setelah perangkat mengirim pembacaan." />
        @else
            <div class="overflow-x-auto">
                <table class="table whitespace-nowrap">
                    <thead>
                        <tr>
                            <th>Waktu</th>
                            @if ($multiNode)<th>Perangkat</th>@endif
                            <th>Kategori keseluruhan</th>
                            @foreach ($gases as $g)
                                <th class="text-right">{{ $g['symbol'] }}</th>
                            @endforeach
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($recent as $log)
                            <tr wire:key="log-{{ $log->id }}">
                                <td class="num">{{ ($log->recorded_at ?? $log->created_at)->locale('id')->translatedFormat('d M H:i:s') }}</td>
                                @if ($multiNode)<td>{{ $log->node?->name }}</td>@endif
                                <td><x-ui.ku-badge :status="$log->overall_category" size="sm" /></td>
                                @foreach ($gases as $key => $g)
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
