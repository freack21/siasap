<div wire:poll.5s="refreshAnalytics" class="space-y-6">
    @php
        $fmt = fn ($v, $suffix = '') => $v === null ? '—' : $v.$suffix;
        $topologies = [
            ['key' => 'star', 'icon' => 'tabler-topology-star', 'qos' => $starQos, 'title' => 'Topologi Star', 'description' => 'Setiap node mengirim langsung ke gateway.'],
            ['key' => 'mesh', 'icon' => 'tabler-topology-ring-3', 'qos' => $meshQos, 'title' => 'Topologi Mesh', 'description' => 'Node dapat meneruskan paket node lain (multi-hop).'],
        ];
    @endphp

    <x-ui.page-header icon="tabler-topology-star-3" title="Pengujian Jaringan"
                      description="Perbandingan kualitas layanan (QoS) jaringan sensor dengan topologi Star dan Mesh berdasarkan data sesi pengujian. Modul ini mendukung penelitian sisi perangkat IoT.">
        <x-slot:eyebrow>Modul Pendukung</x-slot:eyebrow>
        <x-slot:actions>
            <button type="button" wire:click="exportCsv" wire:loading.attr="disabled" wire:target="exportCsv" class="btn btn-secondary">
                <x-tabler-download class="size-4" />
                Ekspor CSV {{ $filterSessionId ? '(sesi terpilih)' : '(semua sesi)' }}
            </button>
        </x-slot:actions>
    </x-ui.page-header>

    <x-ui.flash />

    {{-- Panel sesi --}}
    <div @class(['grid gap-6', 'lg:grid-cols-5' => $canManageSessions])>
        <x-ui.card icon="tabler-flask" title="Sesi berjalan" :class="$canManageSessions ? 'lg:col-span-2' : ''">
            @if ($activeSession)
                <div class="space-y-4">
                    <div class="flex items-start justify-between gap-3">
                        <div class="min-w-0">
                            <p class="font-semibold text-slate-900">{{ $activeSession->name }}</p>
                            <p class="mt-0.5 text-xs text-slate-500">
                                Dimulai {{ $activeSession->started_at?->format('d/m/Y H:i') ?? '—' }}
                                @if ($activeSession->started_at)
                                    · {{ $activeSession->started_at->diffForHumans() }}
                                @endif
                            </p>
                        </div>
                        <span class="inline-flex shrink-0 items-center gap-1.5 rounded-full bg-accent-50 px-2 py-0.5 text-xs font-medium text-accent-700 ring-1 ring-inset ring-accent-100">
                            <span class="size-1.5 rounded-full bg-accent-500"></span>
                            {{ ucfirst($activeSession->topology_type) }}
                        </span>
                    </div>
                    @if ($activeSession->description)
                        <p class="text-sm text-slate-600">{{ $activeSession->description }}</p>
                    @endif
                    @if ($canManageSessions)
                        <button type="button" wire:click="stopActiveSession"
                                wire:confirm="Hentikan sesi &quot;{{ $activeSession->name }}&quot;?"
                                class="btn btn-secondary btn-sm text-rose-600 hover:bg-rose-50">
                            <x-tabler-player-stop class="size-4" />
                            Hentikan Sesi
                        </button>
                    @endif
                </div>
            @else
                <div class="flex items-center gap-3 text-sm text-slate-500">
                    <span class="flex size-9 shrink-0 items-center justify-center rounded-full bg-slate-100">
                        <x-tabler-player-pause class="size-4 text-slate-400" />
                    </span>
                    Tidak ada sesi pengujian yang sedang berjalan.
                </div>
            @endif
        </x-ui.card>

        @if ($canManageSessions)
            <x-ui.card icon="tabler-player-play" title="Mulai sesi baru" description="Memulai sesi baru akan menyelesaikan sesi yang sedang berjalan." class="lg:col-span-3">
                <form wire:submit="startSession" class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <label for="sessionName" class="label">Nama sesi</label>
                        <input id="sessionName" type="text" wire:model="sessionName" class="input" placeholder="Contoh: Skenario Mesh #1">
                        @error('sessionName') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label for="sessionTopology" class="label">Topologi</label>
                        <select id="sessionTopology" wire:model="sessionTopology" class="input">
                            <option value="star">Star (langsung ke gateway)</option>
                            <option value="mesh">Mesh (multi-hop)</option>
                        </select>
                        @error('sessionTopology') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                    </div>
                    <div class="sm:col-span-2">
                        <label for="sessionDescription" class="label">Catatan skenario <span class="text-slate-400">(opsional)</span></label>
                        <input id="sessionDescription" type="text" wire:model="sessionDescription" class="input" placeholder="Contoh: Node 3 terhalang dinding beton">
                        @error('sessionDescription') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                    </div>
                    <div class="flex justify-end sm:col-span-2">
                        <button type="submit" class="btn btn-primary" wire:loading.attr="disabled" wire:target="startSession">
                            <x-tabler-player-play class="size-4" />
                            Mulai Sesi Pengujian
                        </button>
                    </div>
                </form>
            </x-ui.card>
        @endif
    </div>

    {{-- Filter --}}
    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h2 class="text-base font-semibold text-slate-900">Perbandingan Star dan Mesh</h2>
            <p class="text-xs text-slate-500">Diperbarui otomatis setiap 5 detik.</p>
        </div>
        <div class="relative sm:w-72">
            <label for="filterSessionId" class="sr-only">Filter sesi</label>
            <x-tabler-filter class="pointer-events-none absolute top-1/2 left-3 size-4 -translate-y-1/2 text-slate-400" />
            <select id="filterSessionId" wire:model.live="filterSessionId" class="input pl-10">
                <option value="">Semua sesi pengujian</option>
                @foreach ($sessionsList as $sess)
                    <option value="{{ $sess->id }}">{{ $sess->name }} ({{ ucfirst($sess->topology_type) }})</option>
                @endforeach
            </select>
        </div>
    </div>

    {{-- Kartu topologi --}}
    <div class="grid gap-6 lg:grid-cols-2">
        @foreach ($topologies as $topology)
            @php($q = $topology['qos'])
            <x-ui.card :icon="$topology['icon']" :title="$topology['title']" :description="$topology['description']" :padded="false" wire:key="topology-{{ $topology['key'] }}">
                <x-slot:actions>
                    <span class="num text-xs text-slate-500">{{ $q['sessions_count'] }} sesi</span>
                </x-slot:actions>

                <div class="card-body space-y-5">
                    @if ($q['sessions_count'] === 0)
                        <p class="rounded-xl bg-slate-50 px-4 py-3 text-center text-sm text-slate-500">
                            Belum ada sesi {{ ucfirst($topology['key']) }} untuk filter ini.
                        </p>
                    @endif

                    <div>
                        <div class="flex items-baseline justify-between gap-3">
                            <p class="inline-flex items-center gap-1.5 text-xs font-medium text-slate-500">
                                <x-tabler-package class="size-4 text-slate-400" />
                                Packet Delivery Ratio (PDR)
                            </p>
                            <p class="num text-2xl font-bold tracking-tight text-slate-900">{{ $fmt($q['pdr'], '%') }}</p>
                        </div>
                        <div class="mt-2 h-1.5 overflow-hidden rounded-full bg-slate-100">
                            <div class="h-full rounded-full bg-accent-500 transition-all" style="width: {{ min(100, max(0, $q['pdr'] ?? 0)) }}%"></div>
                        </div>
                        <p class="num mt-1.5 text-xs text-slate-500">
                            {{ $q['total_received'] }} dari {{ $q['expected_packets'] }} paket diterima{{ $q['has_estimated'] ? '*' : '' }}
                        </p>
                    </div>

                    <dl class="grid grid-cols-3 gap-3 border-t border-slate-100 pt-4">
                        <div>
                            <dt class="inline-flex items-center gap-1.5 text-xs text-slate-500">
                                <x-tabler-package-off class="size-4 shrink-0 text-slate-400" />
                                Packet loss
                            </dt>
                            <dd class="num mt-1 font-semibold text-slate-900">{{ $fmt($q['loss_rate'], '%') }}</dd>
                        </div>
                        <div>
                            <dt class="inline-flex items-center gap-1.5 text-xs text-slate-500">
                                <x-tabler-antenna-bars-5 class="size-4 shrink-0 text-slate-400" />
                                RSSI rata-rata
                            </dt>
                            <dd class="num mt-1 font-semibold text-slate-900">
                                {{ $fmt($q['avg_rssi']) }}@if ($q['std_rssi'] !== null)<span class="font-normal text-slate-500"> ± {{ $q['std_rssi'] }}</span>@endif
                                <span class="text-xs font-normal text-slate-400">dBm</span>
                            </dd>
                            <dd class="num text-xs text-slate-400">n = {{ $q['rssi_samples'] }}</dd>
                        </div>
                        <div>
                            <dt class="inline-flex items-center gap-1.5 text-xs text-slate-500">
                                <x-tabler-route class="size-4 shrink-0 text-slate-400" />
                                Rata-rata hop
                            </dt>
                            <dd class="num mt-1 font-semibold text-slate-900">{{ $fmt($q['avg_hop']) }}</dd>
                        </div>
                    </dl>
                </div>

                <div class="overflow-x-auto border-t border-slate-100">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Node</th>
                                <th class="text-right">PDR</th>
                                <th class="text-right">Diterima</th>
                                <th class="text-right">RSSI (dBm)</th>
                                <th class="text-right">Hop</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($q['per_node'] as $nodeId => $nodeM)
                                <tr wire:key="{{ $topology['key'] }}-node-{{ $nodeId }}">
                                    <td class="font-medium text-slate-900">{{ $nodeNames[$nodeId] ?? 'Node #'.$nodeId }}</td>
                                    <td @class(['num text-right font-medium', 'text-rose-600' => $nodeM['pdr'] !== null && $nodeM['pdr'] < 70, 'text-slate-900' => ! ($nodeM['pdr'] !== null && $nodeM['pdr'] < 70)])>
                                        {{ $fmt($nodeM['pdr'], '%') }}
                                    </td>
                                    <td class="num text-right whitespace-nowrap">
                                        {{ $nodeM['received'] }}/{{ $nodeM['expected'] }}@if ($nodeM['expected_estimated'])<sup class="ml-0.5 text-slate-400" title="Jumlah paket diharapkan diestimasi dari durasi sesi">*</sup>@endif
                                    </td>
                                    <td class="num text-right whitespace-nowrap">
                                        {{ $fmt($nodeM['avg_rssi']) }}@if ($nodeM['std_rssi'] !== null)<span class="text-slate-400"> ± {{ $nodeM['std_rssi'] }}</span>@endif
                                    </td>
                                    <td class="num text-right">{{ $fmt($nodeM['avg_hop']) }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="py-6 text-center text-slate-400">Tidak ada data node.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </x-ui.card>
        @endforeach
    </div>

    {{-- Catatan metodologi --}}
    <div class="rounded-2xl border border-slate-200 bg-slate-100/60 p-5 text-xs leading-relaxed text-slate-600 sm:p-6">
        <p class="mb-2 inline-flex items-center gap-1.5 font-semibold text-slate-700">
            <x-tabler-info-circle class="size-4 text-slate-400" />
            Catatan metodologi
        </p>
        <ul class="list-disc space-y-1 pl-4">
            <li>Hanya data yang terikat pada sesi pengujian yang dihitung. Data di luar sesi tidak termasuk dalam analisis QoS.</li>
            <li>Paket diharapkan dihitung per sesi dan per node dari rentang nomor urut (seq), lalu dijumlahkan per topologi. Paket duplikat (seq sama, mis. diterima langsung dan melalui relay) dihitung sekali; reset seq akibat node menyala ulang memulai segmen baru.</li>
            <li>Tanda * berarti node tidak mengirim satu paket pun dalam sebuah sesi. Node tetap dihitung (PDR 0%) dengan jumlah paket diharapkan diestimasi dari durasi sesi dibagi interval kirim ({{ $sendInterval }} detik), dibulatkan ke bawah.</li>
            <li>RSSI ditampilkan sebagai rata-rata ± simpangan baku sampel per paket unik; RSSI yang tidak terukur dikecualikan.</li>
        </ul>
    </div>

    {{-- Hipotesis --}}
    <x-ui.card icon="tabler-bulb" title="Hipotesis penelitian" description="Dugaan awal yang diuji; bandingkan dengan angka QoS hasil sesi pengujian di atas.">
        <div class="grid gap-5 text-sm leading-relaxed text-slate-600 md:grid-cols-2">
            <div>
                <p class="mb-1 inline-flex items-center gap-1.5 font-medium text-slate-900">
                    <x-tabler-topology-star class="size-4 text-slate-400" />
                    Star
                </p>
                <p>Optimal pada jarak dekat tanpa halangan fisik karena paket langsung menuju gateway. PDR diperkirakan turun pada node yang berada di area terhalang dinding (<em>blind spot</em>).</p>
            </div>
            <div>
                <p class="mb-1 inline-flex items-center gap-1.5 font-medium text-slate-900">
                    <x-tabler-topology-ring-3 class="size-4 text-slate-400" />
                    Mesh
                </p>
                <p>Diperkirakan mempertahankan PDR tinggi di area <em>blind spot</em> karena node lain dapat meneruskan paket (multi-hop), dengan konsekuensi rata-rata hop yang lebih besar.</p>
            </div>
        </div>
    </x-ui.card>
</div>
