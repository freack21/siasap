<div wire:poll.5s class="space-y-6">
    @php($gases = \App\Support\AirQuality::gases())
    <x-ui.page-header title="Ringkasan" description="Kondisi sistem secara langsung: data masuk, status perangkat, dan peringatan kualitas udara. Diperbarui otomatis setiap 5 detik.">
        <x-slot:eyebrow>Panel Pengelola</x-slot:eyebrow>
    </x-ui.page-header>

    {{-- Statistik utama --}}
    <div class="grid grid-cols-2 gap-4 lg:grid-cols-5">
        <x-ui.stat label="Data masuk hari ini" :value="number_format($stats['today'], 0, ',', '.')" unit="data" icon="heroicon-o-inbox-arrow-down" />
        <x-ui.stat label="Perangkat daring" :value="$stats['online'].' / '.$stats['activeTotal']" hint="dari perangkat aktif" icon="heroicon-o-signal" />
        <x-ui.stat label="Ruangan dipantau" :value="$stats['rooms']" unit="ruangan" icon="heroicon-o-home-modern" />
        <x-ui.stat label="Rata-rata Indeks KU (24 jam)" :value="$stats['avgKu'] === null ? '—' : number_format($stats['avgKu'], 0)"
                   :hint="$stats['avgKu'] === null ? 'Belum ada data' : 'Kategori '.\App\Models\SensorLog::computeKuStatus($stats['avgKu'])"
                   icon="heroicon-o-chart-bar" />
        <x-ui.stat class="col-span-2 lg:col-span-1" label="Sesi pengujian aktif"
                   :value="$activeSession ? $activeSession->name : 'Tidak ada'"
                   :hint="$activeSession ? 'Topologi '.ucfirst($activeSession->topology_type).' · sejak '.$activeSession->started_at?->format('H:i') : 'Data masuk tanpa sesi'"
                   icon="heroicon-o-beaker" />
    </div>

    {{-- Status perangkat --}}
    <x-ui.card title="Status Perangkat" description="Perangkat dianggap daring bila data terakhir masuk ≤ {{ \App\Support\AirQuality::onlineThresholdSeconds() }} detik yang lalu." :padded="false">
        @if ($devices->isEmpty())
            <x-ui.empty-state title="Belum ada perangkat" description="Tambahkan node sensor pada menu Ruangan & Perangkat." icon="heroicon-o-cpu-chip" />
        @else
            <div class="overflow-x-auto">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Perangkat</th>
                            <th>Ruangan</th>
                            <th>Status</th>
                            <th>Terakhir terlihat</th>
                            <th class="text-right">RSSI</th>
                            <th class="text-right">Hop</th>
                            <th class="text-right">Seq terakhir</th>
                            <th>Kualitas udara</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($devices as $device)
                            @php($node = $device['node'])
                            @php($log = $device['log'])
                            <tr wire:key="device-{{ $node->id }}">
                                <td>
                                    <p class="font-medium text-slate-900">{{ $node->name }}</p>
                                    <p class="text-xs text-slate-500">{{ $node->code }}@unless ($node->is_active) · nonaktif @endunless</p>
                                </td>
                                <td>{{ $node->room?->name ?? '—' }}</td>
                                <td>
                                    @if ($device['online'])
                                        <span class="inline-flex items-center gap-1.5 text-xs font-medium text-accent-700">
                                            <span class="size-1.5 rounded-full bg-accent-500"></span>Daring
                                        </span>
                                    @else
                                        <span class="inline-flex items-center gap-1.5 text-xs font-medium text-slate-500">
                                            <span class="size-1.5 rounded-full bg-slate-300"></span>Luring
                                        </span>
                                    @endif
                                </td>
                                <td class="whitespace-nowrap">
                                    @if ($log)
                                        <span title="{{ $log->created_at->format('d/m/Y H:i:s') }}">{{ $log->created_at->diffForHumans() }}</span>
                                    @else
                                        <span class="text-slate-400">Belum pernah</span>
                                    @endif
                                </td>
                                <td class="num text-right whitespace-nowrap">{{ $log?->rssi !== null ? $log->rssi.' dBm' : '—' }}</td>
                                <td class="num text-right">{{ $log?->hop_count ?? '—' }}</td>
                                <td class="num text-right">{{ $log?->seq_number ?? '—' }}</td>
                                <td>
                                    @if ($log)
                                        <div class="flex items-center gap-2">
                                            <span class="num font-semibold text-slate-900">{{ number_format($log->ku_value, 0) }}</span>
                                            <x-ui.ku-badge :status="$log->ku_status" size="sm" />
                                        </div>
                                    @else
                                        <x-ui.ku-badge :status="null" size="sm" />
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </x-ui.card>

    {{-- Grafik 24 jam --}}
    <div class="grid gap-6 lg:grid-cols-2">
        <x-ui.card title="Data Masuk per Jam" description="Jumlah data sensor yang diterima, 24 jam terakhir.">
            <x-ui.line-chart :chart="$countChart" height="h-64" />
        </x-ui.card>
        <x-ui.card title="Rata-rata Indeks KU per Jam" description="Per ruangan, 24 jam terakhir.">
            @if (empty($kuChart['datasets']))
                <x-ui.empty-state title="Belum ada data" description="Belum ada data sensor dalam 24 jam terakhir." icon="heroicon-o-chart-bar" class="h-64 py-0" />
            @else
                <x-ui.line-chart :chart="$kuChart" height="h-64" />
            @endif
        </x-ui.card>
    </div>

    <div class="grid gap-6 lg:grid-cols-3">
        {{-- Peringatan terbaru --}}
        <x-ui.card class="lg:col-span-2" title="Peringatan Terbaru" description="Pembacaan dengan kategori Tidak Sehat atau Berbahaya." :padded="false">
            @if ($alerts->isEmpty())
                <x-ui.empty-state title="Tidak ada peringatan" description="Belum ada pembacaan berkategori Tidak Sehat atau Berbahaya." icon="heroicon-o-shield-check" />
            @else
                <ul class="divide-y divide-slate-100">
                    @foreach ($alerts as $alert)
                        <li wire:key="alert-{{ $alert->id }}" class="flex flex-wrap items-center gap-x-4 gap-y-1 px-5 py-3 text-sm">
                            <span class="num w-32 shrink-0 text-slate-500">{{ $alert->created_at->format('d/m H:i:s') }}</span>
                            <span class="min-w-0 flex-1 truncate font-medium text-slate-900">{{ $alert->node?->room?->name ?? $alert->node?->name ?? 'Node #'.$alert->node_id }}</span>
                            <span class="text-xs text-slate-500">
                                Dominan: {{ $alert->ku_dominant ? ($gases[$alert->ku_dominant]['symbol'] ?? $alert->ku_dominant) : '—' }}
                            </span>
                            <span class="num w-10 text-right font-semibold text-slate-900">{{ number_format($alert->ku_value, 0) }}</span>
                            <x-ui.ku-badge :status="$alert->ku_status" size="sm" />
                        </li>
                    @endforeach
                </ul>
            @endif
        </x-ui.card>

        {{-- Tautan cepat --}}
        <x-ui.card title="Tautan Cepat">
            <div class="space-y-2">
                @foreach ([
                    ['route' => 'admin.data', 'title' => 'Data Sensor', 'text' => 'Telusuri, saring, dan ekspor data mentah.', 'icon' => 'heroicon-o-table-cells'],
                    ['route' => 'admin.rooms', 'title' => 'Ruangan & Perangkat', 'text' => 'Kelola ruangan dan node sensor.', 'icon' => 'heroicon-o-home-modern'],
                    ['route' => 'admin.qos', 'title' => 'Pengujian Jaringan', 'text' => 'Sesi pengujian dan analisis QoS topologi.', 'icon' => 'heroicon-o-signal'],
                ] as $link)
                    <a href="{{ route($link['route']) }}" wire:navigate class="flex items-start gap-3 rounded-xl border border-slate-200 p-3 transition hover:border-accent-300 hover:bg-accent-50">
                        <x-dynamic-component :component="$link['icon']" class="mt-0.5 size-5 shrink-0 text-accent-600" />
                        <span class="min-w-0">
                            <span class="block text-sm font-semibold text-slate-900">{{ $link['title'] }}</span>
                            <span class="block text-xs text-slate-500">{{ $link['text'] }}</span>
                        </span>
                        <x-heroicon-o-chevron-right class="ml-auto mt-0.5 size-4 shrink-0 text-slate-400" />
                    </a>
                @endforeach
            </div>
        </x-ui.card>
    </div>
</div>
