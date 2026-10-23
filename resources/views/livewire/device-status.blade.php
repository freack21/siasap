<div wire:poll.5s class="space-y-10">
    <x-ui.page-header
        :icon="'tabler-cpu'"
        title="Status Perangkat"
        description="Kondisi perangkat IoT yang mengirim data kualitas udara, alur data, dan data yang ditolak server.">
        <x-slot:eyebrow>Perangkat</x-slot:eyebrow>
    </x-ui.page-header>

    {{-- Perangkat --}}
    <section class="space-y-4">
        <div>
            <h2 class="text-lg font-semibold tracking-tight text-slate-900">Perangkat terdaftar</h2>
            <p class="mt-1 max-w-3xl text-sm text-slate-500">
                Perangkat dianggap terhubung bila data terakhir diterima paling lama {{ $onlineSeconds }} detik yang lalu.
                Interval terukur adalah rata-rata selisih waktu {{ \App\Livewire\DeviceStatus::INTERVAL_SAMPLE_SIZE }} data terakhir (target {{ $sendInterval }} detik).
            </p>
        </div>

        @if ($devices->isEmpty())
            <div class="card">
                <x-ui.empty-state icon="tabler-antenna-bars-off"
                                  title="Belum ada perangkat terdaftar"
                                  description="Perangkat perlu didaftarkan di tabel perangkat dengan kode yang sama dengan device_id pada firmware." />
            </div>
        @else
            <div class="grid grid-cols-1 gap-4 lg:grid-cols-2">
                @foreach ($devices as $device)
                    <article wire:key="device-{{ $device['node']->id }}" class="card card-body space-y-4">
                        <div class="flex items-start justify-between gap-3">
                            <div class="flex min-w-0 items-center gap-3">
                                <span class="icon-chip">
                                    <x-tabler-cpu class="size-5" />
                                </span>
                                <div class="min-w-0">
                                    <h3 class="font-mono text-base font-semibold text-slate-900">{{ $device['node']->code }}</h3>
                                    <p class="truncate text-xs text-slate-500">
                                        {{ $device['node']->room?->name ?? 'Belum ditempatkan di ruangan' }}
                                        @unless ($device['node']->is_active)
                                            · Nonaktif
                                        @endunless
                                    </p>
                                </div>
                            </div>
                            @if ($device['connected'])
                                <span class="inline-flex items-center gap-1.5 rounded-full bg-emerald-50 px-2.5 py-1 text-xs font-medium text-emerald-700 ring-1 ring-emerald-600/20 ring-inset">
                                    <x-tabler-plug-connected class="size-3.5" /> Terhubung
                                </span>
                            @else
                                <span class="inline-flex items-center gap-1.5 rounded-full bg-slate-100 px-2.5 py-1 text-xs font-medium text-slate-600 ring-1 ring-slate-500/20 ring-inset">
                                    <x-tabler-plug-connected-x class="size-3.5" /> Terputus
                                </span>
                            @endif
                        </div>

                        <dl class="grid grid-cols-2 gap-4 border-t border-slate-100 pt-4 text-sm">
                            <div>
                                <dt class="flex items-center gap-1.5 text-xs text-slate-500"><x-tabler-clock class="size-4 text-slate-400" /> Data terakhir</dt>
                                <dd class="mt-1 font-medium text-slate-900">
                                    @if ($device['last_seen'])
                                        <span class="num">{{ $device['last_seen']->format('d/m/Y H:i:s') }}</span>
                                        <span class="block text-xs font-normal text-slate-500">{{ $device['last_seen']->locale('id')->diffForHumans() }}</span>
                                    @else
                                        <span class="text-slate-500">Belum pernah mengirim data</span>
                                    @endif
                                </dd>
                            </div>
                            <div>
                                <dt class="flex items-center gap-1.5 text-xs text-slate-500"><x-tabler-activity class="size-4 text-slate-400" /> Interval terukur</dt>
                                <dd class="mt-1 font-medium text-slate-900">
                                    @if ($device['interval'] !== null)
                                        <span class="num">{{ number_format($device['interval'], 1, ',', '.') }} detik</span>
                                    @else
                                        <span class="text-slate-500">Belum cukup data</span>
                                    @endif
                                </dd>
                            </div>
                        </dl>

                        <div>
                            <p class="mb-2 text-xs text-slate-500">Gas yang dikirim</p>
                            <div class="flex flex-wrap gap-2">
                                @foreach ($gases as $key => $gas)
                                    <span class="inline-flex items-center gap-1.5 rounded-lg bg-slate-50 px-2.5 py-1 text-xs ring-1 ring-slate-200 ring-inset">
                                        <x-dynamic-component :component="$gas['icon']" class="size-3.5" style="color: {{ $gas['color'] }}" />
                                        <span class="font-semibold text-slate-700">{{ $gas['symbol'] }}</span>
                                        @if ($device['latest'])
                                            <span class="num text-slate-500">{{ number_format($device['latest']->{$key}, 2, ',', '.') }} ppm</span>
                                        @endif
                                    </span>
                                @endforeach
                            </div>
                        </div>
                    </article>
                @endforeach
            </div>
        @endif
    </section>

    {{-- Alur data --}}
    @php
        $steps = [
            ['icon' => 'tabler-cpu', 'title' => 'Sensor membaca gas', 'text' => 'ESP32 membaca sensor MQ-135 (CO₂, NH₃) dan MQ-2 (CO, H₂) lalu menghitung nilai ppm.'],
            ['icon' => 'tabler-send', 'title' => 'Dikirim ke server', 'text' => 'Perangkat mengirim JSON (device_id, timestamp, co2_ppm, nh3_ppm, co_ppm, h2_ppm) ke POST /api/telemetry setiap '.$sendInterval.' detik.'],
            ['icon' => 'tabler-list-check', 'title' => 'Validasi format', 'text' => 'Semua field wajib, ppm berupa angka ≥ 0, device_id terdaftar, dan timestamp berformat Y-m-d H:i:s. Data tidak valid ditolak dan dicatat.'],
            ['icon' => 'tabler-filter', 'title' => 'Klasifikasi', 'text' => 'Setiap gas dibandingkan dengan ambang batas; kategori keseluruhan adalah kategori gas yang paling buruk.'],
            ['icon' => 'tabler-database', 'title' => 'Disimpan', 'text' => 'Nilai ppm, kategori tiap gas, kategori keseluruhan, penyebab utama, dan tingkat rekomendasi disimpan ke basis data.'],
            ['icon' => 'tabler-device-desktop-analytics', 'title' => 'Ditampilkan', 'text' => 'Beranda, grafik, riwayat, dan rekomendasi diperbarui otomatis di halaman web.'],
        ];
    @endphp
    <section class="space-y-4">
        <div>
            <h2 class="text-lg font-semibold tracking-tight text-slate-900">Alur data</h2>
            <p class="mt-1 max-w-3xl text-sm text-slate-500">Perjalanan satu data pengukuran dari perangkat hingga tampil di halaman.</p>
        </div>

        <ol class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
            @foreach ($steps as $step)
                <li class="card card-body flex gap-4">
                    <span class="icon-chip shrink-0">
                        <x-dynamic-component :component="$step['icon']" class="size-5" />
                    </span>
                    <div class="min-w-0">
                        <p class="text-xs font-semibold text-slate-400">Langkah {{ $loop->iteration }}</p>
                        <h3 class="font-semibold text-slate-900">{{ $step['title'] }}</h3>
                        <p class="mt-1 text-sm leading-relaxed text-slate-600">{{ $step['text'] }}</p>
                    </div>
                </li>
            @endforeach
        </ol>
    </section>

    {{-- Penolakan --}}
    <section class="space-y-4">
        <div>
            <h2 class="text-lg font-semibold tracking-tight text-slate-900">Penolakan data terakhir</h2>
            <p class="mt-1 max-w-3xl text-sm text-slate-500">{{ \App\Livewire\DeviceStatus::REJECTION_LIMIT }} data terakhir yang ditolak karena tidak lolos validasi format.</p>
        </div>

        <div class="card overflow-x-auto">
            @if ($rejections->isEmpty())
                <x-ui.empty-state icon="tabler-shield-check"
                                  title="Belum ada data yang ditolak"
                                  description="Seluruh data yang diterima dari perangkat lolos validasi format." />
            @else
                <table class="table">
                    <thead>
                        <tr>
                            <th>Waktu</th>
                            <th>device_id</th>
                            <th>Galat</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($rejections as $rejection)
                            <tr wire:key="rejection-{{ $rejection->id }}">
                                <td class="num whitespace-nowrap align-top">{{ $rejection->created_at->format('d/m/Y H:i:s') }}</td>
                                <td class="whitespace-nowrap align-top font-mono text-xs">
                                    @if ($rejection->device_id !== null)
                                        {{ $rejection->device_id }}
                                    @else
                                        <span class="font-sans text-slate-400">Tidak ada</span>
                                    @endif
                                </td>
                                <td class="align-top text-slate-600">
                                    {{ \Illuminate\Support\Str::limit(collect($rejection->errors)->flatten()->implode(' '), 200) }}
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @endif
        </div>
    </section>
</div>
