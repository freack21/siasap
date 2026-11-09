<div class="space-y-6" wire:poll.5s>
    <x-ui.page-header
        :icon="'tabler-topology-star-3'"
        title="Uji Topologi Star & Mesh"
        description="Data tiga perangkat IoT untuk pengujian topologi. Data di halaman ini terpisah dari halaman SIMKU dan tidak tampil di Beranda maupun Riwayat.">
        <x-slot:eyebrow>Uji jaringan</x-slot:eyebrow>
        <x-slot:actions>
            <button type="button" wire:click="downloadCsv" wire:loading.attr="disabled" wire:target="downloadCsv" class="btn btn-secondary">
                <x-tabler-download class="size-4" />
                <span wire:loading.remove wire:target="downloadCsv">Unduh CSV</span>
                <span wire:loading wire:target="downloadCsv">Menyiapkan…</span>
            </button>
        </x-slot:actions>
    </x-ui.page-header>

    <section class="card card-body flex flex-col gap-2 text-sm text-slate-600">
        <p class="inline-flex items-center gap-2 font-medium text-slate-900"><x-tabler-plug-connected class="size-4 text-accent-600" /> Alamat pengiriman data</p>
        <code class="w-fit rounded-lg bg-slate-100 px-3 py-1.5 font-mono text-xs text-slate-800">POST {{ $endpoint }}</code>
        <code class="w-fit rounded-lg bg-slate-100 px-3 py-1.5 font-mono text-xs text-slate-800">POST {{ $endpoint }}/handshake</code>
        <p>Setelah tersambung ke WiFi, setiap perangkat yang punya IP (node star dan gateway mesh) mengirim handshake berisi device_id, ip, mac, topology_type, dan rssi sebelum mengirim data.</p>
        <p>Isi sama dengan pengiriman ke SIMKU (device_id, timestamp, co2_ppm, nh3_ppm, co_ppm, h2_ppm), ditambah rssi, seq_number, hop_count, dan topology_type ("star" atau "mesh"). Perangkat yang diterima: {{ collect(config('topology_test.devices'))->keys()->implode(', ') }}.</p>
    </section>

    {{-- Filter --}}
    <section class="card card-body">
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-3 lg:grid-cols-[1fr_1fr_1fr_auto] lg:items-end">
            <div>
                <label for="filter-topology" class="label">Topologi</label>
                <select id="filter-topology" wire:model.live="topology" class="input">
                    <option value="">Semua topologi</option>
                    @foreach ($topologies as $item)
                        <option value="{{ $item }}">{{ ucfirst($item) }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label for="filter-from" class="label">Diterima dari</label>
                <input id="filter-from" type="datetime-local" wire:model.live="from" class="input">
            </div>
            <div>
                <label for="filter-to" class="label">Diterima sampai</label>
                <input id="filter-to" type="datetime-local" wire:model.live="to" class="input">
            </div>
            <div>
                <button type="button" wire:click="resetFilters" class="btn btn-ghost w-full lg:w-auto">
                    <x-tabler-filter-off class="size-4" /> Reset filter
                </button>
            </div>
        </div>
        <p class="mt-3 text-xs text-slate-500">Isi rentang waktu dengan jam mulai dan selesai satu sesi pengujian agar ringkasan QoS hanya menghitung sesi tersebut.</p>
    </section>

    {{-- Status perangkat --}}
    <section class="grid grid-cols-1 gap-4 md:grid-cols-3">
        @foreach ($devices as $device)
            <article wire:key="device-{{ $device['id'] }}" class="card card-body flex flex-col gap-3">
                <div class="flex items-start justify-between gap-3">
                    <div>
                        <h2 class="font-semibold text-slate-900">{{ $device['name'] }}</h2>
                        <p class="text-xs text-slate-500">{{ $device['id'] }}</p>
                    </div>
                    @if ($device['connected'])
                        <span class="inline-flex items-center gap-1.5 rounded-full border border-emerald-200 bg-emerald-50 px-2.5 py-1 text-xs font-semibold text-emerald-700"><x-tabler-plug-connected class="size-3.5" /> Terhubung</span>
                    @else
                        <span class="inline-flex items-center gap-1.5 rounded-full border border-slate-200 bg-slate-50 px-2.5 py-1 text-xs font-semibold text-slate-600"><x-tabler-plug-connected-x class="size-3.5" /> Terputus</span>
                    @endif
                </div>

                @if ($device['handshake'])
                    @php($handshake = $device['handshake'])
                    <div class="rounded-lg bg-slate-50 px-3 py-2 text-xs text-slate-600">
                        <p><span class="text-slate-500">IP perangkat</span> <span class="font-mono font-semibold text-slate-900">{{ $handshake->ip }}</span>@if ($handshake->remote_ip && $handshake->remote_ip !== $handshake->ip) <span class="text-slate-500">· terlihat server</span> <span class="font-mono">{{ $handshake->remote_ip }}</span>@endif</p>
                        <p class="mt-0.5">Handshake {{ $handshake->last_handshake_at->locale('id')->diffForHumans() }} · {{ $handshake->handshake_count }}× @if ($handshake->topology)· {{ ucfirst($handshake->topology) }}@endif @if ($handshake->mac)· <span class="font-mono">{{ $handshake->mac }}</span>@endif</p>
                    </div>
                @else
                    <p class="rounded-lg bg-slate-50 px-3 py-2 text-xs text-slate-500">Belum melakukan handshake.</p>
                @endif

                @if ($device['latest'])
                    @php($latest = $device['latest'])
                    <dl class="grid grid-cols-2 gap-x-4 gap-y-2 text-sm">
                        <div class="col-span-2">
                            <dt class="text-xs text-slate-500">Data terakhir diterima</dt>
                            <dd class="font-medium text-slate-900">{{ $latest->created_at->locale('id')->diffForHumans() }} <span class="text-xs font-normal text-slate-500 num">({{ $latest->created_at->translatedFormat('d M Y H:i:s') }})</span></dd>
                        </div>
                        <div><dt class="text-xs text-slate-500">Topologi</dt><dd class="font-medium text-slate-900">{{ $latest->topology ? ucfirst($latest->topology) : '—' }}</dd></div>
                        <div><dt class="text-xs text-slate-500">Kategori</dt><dd><x-ui.ku-badge :status="$latest->overall_category" size="sm" /></dd></div>
                        <div><dt class="text-xs text-slate-500">RSSI</dt><dd class="font-medium text-slate-900 num">{{ $latest->rssi !== null ? $latest->rssi.' dBm' : '—' }}</dd></div>
                        <div><dt class="text-xs text-slate-500">Hop / Seq</dt><dd class="font-medium text-slate-900 num">{{ $latest->hop_count ?? '—' }} / {{ $latest->seq_number ?? '—' }}</dd></div>
                        <div class="col-span-2">
                            <dt class="text-xs text-slate-500">Kadar gas (ppm)</dt>
                            <dd class="num text-slate-900">CO₂ {{ number_format($latest->co2, 2, ',', '.') }} · NH₃ {{ number_format($latest->nh3, 2, ',', '.') }} · CO {{ number_format($latest->co, 2, ',', '.') }} · H₂ {{ number_format($latest->h2, 2, ',', '.') }}</dd>
                        </div>
                    </dl>
                @else
                    <p class="text-sm text-slate-500">Belum pernah mengirim data{{ $topology !== '' ? ' pada topologi '.$topology : '' }}.</p>
                @endif
            </article>
        @endforeach
    </section>
    <p class="-mt-3 text-xs text-slate-500">Perangkat dianggap terhubung bila data terakhir diterima paling lama {{ $onlineSeconds }} detik yang lalu.</p>

    {{-- Ringkasan QoS --}}
    <x-ui.card :icon="'tabler-chart-dots-3'" title="Ringkasan QoS per perangkat dan topologi" description="Dihitung dari data sesuai filter." :padded="false">
        @if ($qos->isEmpty())
            <x-ui.empty-state title="Belum ada data" description="Ringkasan muncul setelah perangkat mengirim data ke alamat di atas." icon="tabler-clock" />
        @else
            <div class="overflow-x-auto">
                <table class="table whitespace-nowrap">
                    <thead>
                        <tr>
                            <th>Perangkat</th>
                            <th>Topologi</th>
                            <th class="text-right">Diterima</th>
                            <th class="text-right">Diharapkan</th>
                            <th class="text-right">PDR</th>
                            <th class="text-right">Packet loss</th>
                            <th class="text-right">Delay</th>
                            <th class="text-right">Jitter</th>
                            <th class="text-right">Throughput</th>
                            <th class="text-right">Duplikat</th>
                            <th class="text-right">RSSI rata-rata</th>
                            <th class="text-right">RSSI terlemah</th>
                            <th class="text-right">Hop rata-rata</th>
                            <th class="text-right">Selang rata-rata</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($qos as $row)
                            <tr wire:key="qos-{{ $row['device_id'] }}-{{ $row['topology'] }}">
                                <td class="font-medium text-slate-900">{{ $row['device_id'] }}</td>
                                <td>{{ $row['topology'] === '-' ? '—' : ucfirst($row['topology']) }}</td>
                                <td class="text-right num">{{ $row['unique'] }}</td>
                                <td class="text-right num">{{ $row['expected'] ?? '—' }}</td>
                                <td class="text-right num font-semibold text-slate-900">{{ $row['pdr'] !== null ? number_format($row['pdr'], 1, ',', '.').'%' : '—' }}</td>
                                <td class="text-right num">{{ $row['loss'] !== null ? number_format($row['loss'], 1, ',', '.').'%' : '—' }}</td>
                                <td class="text-right num font-semibold text-slate-900">{{ $row['delay_avg'] !== null ? number_format($row['delay_avg'], 1, ',', '.').' ms' : '—' }}</td>
                                <td class="text-right num font-semibold text-slate-900">{{ $row['jitter'] !== null ? number_format($row['jitter'], 1, ',', '.').' ms' : '—' }}</td>
                                <td class="text-right num font-semibold text-slate-900">{{ $row['throughput_bps'] !== null ? number_format($row['throughput_bps'], 1, ',', '.').' bit/s' : '—' }}</td>
                                <td class="text-right num">{{ $row['duplicates'] }}</td>
                                <td class="text-right num">{{ $row['rssi_avg'] !== null ? number_format($row['rssi_avg'], 1, ',', '.').' dBm' : '—' }}</td>
                                <td class="text-right num">{{ $row['rssi_min'] !== null ? $row['rssi_min'].' dBm' : '—' }}</td>
                                <td class="text-right num">{{ $row['hop_avg'] !== null ? number_format($row['hop_avg'], 2, ',', '.') : '—' }}</td>
                                <td class="text-right num">{{ $row['interval_avg'] !== null ? number_format($row['interval_avg'], 1, ',', '.').' dtk' : '—' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <ul class="space-y-1 border-t border-slate-100 px-5 py-4 text-xs text-slate-500 sm:px-6">
                <li><b class="text-slate-700">Diterima</b>: paket unik (seq_number berbeda). <b class="text-slate-700">Diharapkan</b>: rentang seq_number setiap sesi; sesi baru dimulai saat seq_number kembali kecil karena perangkat dinyalakan ulang.</li>
                <li><b class="text-slate-700">PDR</b> = diterima ÷ diharapkan; <b class="text-slate-700">packet loss</b> = 100% − PDR. Paket yang hilang tepat di awal atau akhir rentang waktu tidak terhitung, jadi beri jeda sebelum dan sesudah sesi saat mengisi filter.</li>
                <li><b class="text-slate-700">Delay</b>: rata-rata waktu terima server dikurangi waktu kirim perangkat (timestamp + timestamp_ms). Ketelitiannya bergantung pada sinkronisasi jam NTP perangkat dan server; tanpa timestamp_ms ketelitiannya hanya detik. Pada mesh, waktu kirim Node 2 dan Node 3 dihitung gateway dari waktu terima dikurangi lama paket ditahan di relay dan gateway.</li>
                <li><b class="text-slate-700">Jitter</b>: rata-rata selisih mutlak delay dua paket berurutan. <b class="text-slate-700">Throughput</b>: total bit payload yang diterima dibagi rentang waktu paket pertama sampai terakhir.</li>
            </ul>
        @endif
    </x-ui.card>

    {{-- Daftar paket --}}
    <x-ui.card :icon="'tabler-list-details'" title="Paket diterima" description="Terbaru di atas, 20 baris per halaman." :padded="false">
        @if ($packets->isEmpty())
            <x-ui.empty-state title="Belum ada paket" description="Paket akan muncul setelah perangkat mengirim data." icon="tabler-clock" />
        @else
            <div class="overflow-x-auto">
                <table class="table whitespace-nowrap">
                    <thead>
                        <tr>
                            <th>Waktu terima</th>
                            <th>Timestamp</th>
                            <th>Perangkat</th>
                            <th>Topologi</th>
                            <th class="text-right">Seq</th>
                            <th class="text-right">Hop</th>
                            <th class="text-right">RSSI</th>
                            <th class="text-right">CO₂</th>
                            <th class="text-right">NH₃</th>
                            <th class="text-right">CO</th>
                            <th class="text-right">H₂</th>
                            <th>Kategori</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($packets as $packet)
                            <tr wire:key="packet-{{ $packet->id }}">
                                <td class="num text-slate-600">{{ $packet->created_at->translatedFormat('d M Y H:i:s') }}</td>
                                <td class="num text-slate-600">{{ $packet->recorded_at->format('H:i:s') }}</td>
                                <td class="font-medium text-slate-900">{{ $packet->device_id }}</td>
                                <td>{{ $packet->topology ? ucfirst($packet->topology) : '—' }}</td>
                                <td class="text-right num">{{ $packet->seq_number ?? '—' }}</td>
                                <td class="text-right num">{{ $packet->hop_count ?? '—' }}</td>
                                <td class="text-right num">{{ $packet->rssi ?? '—' }}</td>
                                <td class="text-right num">{{ number_format($packet->co2, 2, ',', '.') }}</td>
                                <td class="text-right num">{{ number_format($packet->nh3, 2, ',', '.') }}</td>
                                <td class="text-right num">{{ number_format($packet->co, 2, ',', '.') }}</td>
                                <td class="text-right num">{{ number_format($packet->h2, 2, ',', '.') }}</td>
                                <td><x-ui.ku-badge :status="$packet->overall_category" size="sm" /></td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @if ($packets->hasPages())
                <div class="border-t border-slate-100 px-5 py-4 sm:px-6">
                    {{ $packets->links() }}
                </div>
            @endif
        @endif
    </x-ui.card>
</div>
