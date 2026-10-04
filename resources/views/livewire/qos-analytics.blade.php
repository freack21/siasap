<div class="space-y-8">

    <!-- Header & Action Toolbar -->
    <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-4 bg-white dark:bg-slate-900 p-6 rounded-2xl border border-slate-200/80 dark:border-slate-800 shadow-sm">
        <div class="space-y-1">
            <div class="flex items-center gap-2">
                <h1 class="text-2xl font-extrabold tracking-tight text-slate-900 dark:text-white">
                    Modul Analisis QoS & Benchmark Topologi
                </h1>
                <span class="px-2.5 py-0.5 rounded-full text-xs font-bold bg-indigo-500/10 text-indigo-600 dark:bg-indigo-400/10 dark:text-indigo-400 border border-indigo-500/20">
                    Star vs Mesh
                </span>
            </div>
            <p class="text-sm text-slate-500 dark:text-slate-400">
                Komparasi performa jaringan berbasis data empiris ($PDR$, $Packet\ Loss$, $RSSI$, $Hop\ Count$).
            </p>
        </div>

        <div class="flex flex-wrap items-center gap-3">
            @if ($activeSession)
                <button wire:click="stopActiveSession" 
                        class="px-4 py-2 bg-rose-500/10 hover:bg-rose-500/20 text-rose-600 dark:text-rose-400 border border-rose-500/20 rounded-xl text-xs font-bold transition-all flex items-center gap-2">
                    <x-heroicon-o-stop class="w-4 h-4" />
                    <span>Hentikan Sesi Aktif ({{ $activeSession->name }})</span>
                </button>
            @endif

            <button wire:click="populateResearchBenchmarkData" 
                    wire:loading.attr="disabled"
                    class="px-4 py-2 bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-200 border border-slate-200 dark:border-slate-700 rounded-xl text-xs font-bold transition-all flex items-center gap-2">
                <x-heroicon-o-document-chart-bar wire:loading.remove wire:target="populateResearchBenchmarkData" class="w-4 h-4 text-amber-500" />
                <x-heroicon-o-arrow-path wire:loading wire:target="populateResearchBenchmarkData" class="w-4 h-4 animate-spin text-amber-500" />
                <span>Generate Dataset Uji Skenario</span>
            </button>
        </div>
    </div>

    <!-- Flash Alert Notification -->
    @if (session()->has('message'))
        <div class="p-4 rounded-xl bg-emerald-500/10 border border-emerald-500/20 text-emerald-700 dark:text-emerald-300 text-xs font-semibold flex items-center gap-2">
            <x-heroicon-o-check-circle class="w-5 h-5 text-emerald-500" />
            <span>{{ session('message') }}</span>
        </div>
    @endif

    <!-- Start New Session Form Card -->
    <div x-data="{ openForm: false }" class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200/80 dark:border-slate-800 shadow-sm overflow-hidden">
        <div class="p-5 border-b border-slate-100 dark:border-slate-800 flex items-center justify-between cursor-pointer" @click="openForm = !openForm">
            <div class="flex items-center gap-3">
                <div class="w-8 h-8 rounded-lg bg-sky-500/10 text-sky-600 dark:bg-sky-400/10 dark:text-sky-400 flex items-center justify-center">
                    <x-heroicon-o-plus class="w-5 h-5" />
                </div>
                <div>
                    <h2 class="font-bold text-slate-900 dark:text-white text-sm">Manajemen Sesi Uji Coba Baru</h2>
                    <p class="text-xs text-slate-500 dark:text-slate-400">Buat sesi baru untuk mengisolasi pengukuran topologi tertentu.</p>
                </div>
            </div>
            <button type="button" class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-200">
                <x-heroicon-o-chevron-down class="w-5 h-5 transition-transform" ::class="openForm ? 'rotate-180' : ''" />
            </button>
        </div>

        <div x-show="openForm" x-collapse class="p-6 border-t border-slate-100 dark:border-slate-800 bg-slate-50/50 dark:bg-slate-900/50">
            <form wire:submit.prevent="startSession" class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div class="space-y-1">
                    <label class="text-xs font-bold text-slate-700 dark:text-slate-300">Nama Sesi Pengujian</label>
                    <input type="text" wire:model="sessionName" placeholder="Contoh: Skenario Blind Spot Mesh #1" 
                           class="w-full px-3.5 py-2 text-xs rounded-xl bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-slate-900 dark:text-white focus:ring-2 focus:ring-sky-500 focus:outline-none">
                    @error('sessionName') <span class="text-[10px] text-rose-500 font-semibold">{{ $message }}</span> @enderror
                </div>

                <div class="space-y-1">
                    <label class="text-xs font-bold text-slate-700 dark:text-slate-300">Mode Topologi</label>
                    <select wire:model="sessionTopology" class="w-full px-3.5 py-2 text-xs rounded-xl bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-slate-900 dark:text-white focus:ring-2 focus:ring-sky-500 focus:outline-none font-semibold">
                        <option value="star">Star Topology (Direct Connection)</option>
                        <option value="mesh">Mesh Topology (Multi-Hop Relay)</option>
                    </select>
                </div>

                <div class="space-y-1">
                    <label class="text-xs font-bold text-slate-700 dark:text-slate-300">Deskripsi / Catatan Skenario</label>
                    <input type="text" wire:model="sessionDescription" placeholder="Contoh: Node 3 berada di balik tembok beton 20cm" 
                           class="w-full px-3.5 py-2 text-xs rounded-xl bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-slate-900 dark:text-white focus:ring-2 focus:ring-sky-500 focus:outline-none">
                </div>

                <div class="md:col-span-3 flex justify-end">
                    <button type="submit" class="px-5 py-2.5 bg-sky-600 hover:bg-sky-500 text-white rounded-xl text-xs font-bold shadow-md shadow-sky-500/20 transition-all">
                        Mulai Sesi Pengujian Sekarang
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Head-to-Head QoS Comparative Board -->
    <div>
        <div class="flex items-center justify-between mb-4">
            <h2 class="text-lg font-bold text-slate-900 dark:text-white flex items-center gap-2">
                <x-heroicon-o-scale class="w-5 h-5 text-indigo-500" />
                <span>Side-by-Side Head-to-Head QoS Board</span>
            </h2>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            
            <!-- Card Topologi Star -->
            <div class="bg-white dark:bg-slate-900 rounded-2xl border-2 border-sky-500/30 shadow-md overflow-hidden relative">
                <div class="p-6 bg-gradient-to-r from-sky-500/10 via-sky-500/5 to-transparent border-b border-slate-100 dark:border-slate-800 flex items-center justify-between">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-xl bg-sky-500 text-white font-black flex items-center justify-center shadow-md shadow-sky-500/30 text-lg">
                            ★
                        </div>
                        <div>
                            <h3 class="text-lg font-extrabold text-slate-900 dark:text-white">Topologi Star</h3>
                            <span class="text-xs text-slate-500 dark:text-slate-400">Transmisi Langsung ke Gateway</span>
                        </div>
                    </div>
                    <span class="px-3 py-1 bg-sky-500/15 text-sky-700 dark:text-sky-300 font-bold text-xs rounded-full border border-sky-500/30">
                        Direct Star
                    </span>
                </div>

                <div class="p-6 space-y-6">
                    <!-- PDR Metric -->
                    <div class="space-y-2">
                        <div class="flex justify-between items-baseline text-xs">
                            <span class="font-bold text-slate-700 dark:text-slate-300">Packet Delivery Ratio (PDR)</span>
                            <span class="text-xl font-black text-sky-600 dark:text-sky-400">{{ $starQos['pdr'] }}%</span>
                        </div>
                        <div class="w-full h-3 rounded-full bg-slate-100 dark:bg-slate-800 overflow-hidden">
                            <div class="h-full bg-sky-500 rounded-full transition-all duration-500" style="width: {{ $starQos['pdr'] }}%"></div>
                        </div>
                        <p class="text-[11px] text-slate-400">Paket Sukses: {{ $starQos['total_received'] }} dari {{ $starQos['expected_packets'] }} paket yang dipancarkan.</p>
                    </div>

                    <!-- 3 Grid Metrics -->
                    <div class="grid grid-cols-3 gap-3">
                        <div class="p-3.5 rounded-xl bg-slate-50 dark:bg-slate-800/50 border border-slate-100 dark:border-slate-800 text-center">
                            <span class="text-[10px] uppercase font-bold text-slate-400">Packet Loss Rate</span>
                            <div class="text-lg font-extrabold text-slate-900 dark:text-white mt-1">{{ $starQos['loss_rate'] }}%</div>
                        </div>
                        <div class="p-3.5 rounded-xl bg-slate-50 dark:bg-slate-800/50 border border-slate-100 dark:border-slate-800 text-center">
                            <span class="text-[10px] uppercase font-bold text-slate-400">Rata-rata RSSI</span>
                            <div class="text-lg font-extrabold text-slate-900 dark:text-white mt-1">{{ $starQos['avg_rssi'] }} <span class="text-xs font-normal">dBm</span></div>
                        </div>
                        <div class="p-3.5 rounded-xl bg-slate-50 dark:bg-slate-800/50 border border-slate-100 dark:border-slate-800 text-center">
                            <span class="text-[10px] uppercase font-bold text-slate-400">Avg Hop Count</span>
                            <div class="text-lg font-extrabold text-slate-900 dark:text-white mt-1">{{ $starQos['avg_hop'] }}</div>
                        </div>
                    </div>

                    <!-- Node Breakdown -->
                    <div class="space-y-2">
                        <span class="text-xs font-bold text-slate-700 dark:text-slate-300">Rincian Per Node (Star):</span>
                        <div class="space-y-1.5 text-xs">
                            @foreach ([1, 2, 3] as $nodeId)
                                @php $nodeM = $starQos['per_node'][$nodeId] ?? null; @endphp
                                <div class="p-2.5 rounded-lg bg-slate-50/80 dark:bg-slate-800/40 flex items-center justify-between">
                                    <span class="font-semibold text-slate-800 dark:text-slate-200">Node #{{ $nodeId }}</span>
                                    @if ($nodeM)
                                        <div class="flex items-center gap-3 text-slate-600 dark:text-slate-400 font-mono">
                                            <span>PDR: <strong class="{{ $nodeM['pdr'] < 70 ? 'text-rose-500' : 'text-emerald-500' }}">{{ $nodeM['pdr'] }}%</strong></span>
                                            <span>RSSI: {{ $nodeM['avg_rssi'] }} dBm</span>
                                        </div>
                                    @else
                                        <span class="text-slate-400 italic">No Data</span>
                                    @endif
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>

            <!-- Card Topologi Mesh -->
            <div class="bg-white dark:bg-slate-900 rounded-2xl border-2 border-indigo-500/30 shadow-md overflow-hidden relative">
                <div class="p-6 bg-gradient-to-r from-indigo-500/10 via-indigo-500/5 to-transparent border-b border-slate-100 dark:border-slate-800 flex items-center justify-between">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-xl bg-indigo-600 text-white font-black flex items-center justify-center shadow-md shadow-indigo-500/30 text-lg">
                            🕸
                        </div>
                        <div>
                            <h3 class="text-lg font-extrabold text-slate-900 dark:text-white">Topologi Mesh</h3>
                            <span class="text-xs text-slate-500 dark:text-slate-400">Transmisi Estafet Multi-Hop Node</span>
                        </div>
                    </div>
                    <span class="px-3 py-1 bg-indigo-500/15 text-indigo-700 dark:text-indigo-300 font-bold text-xs rounded-full border border-indigo-500/30">
                        Multi-Hop Relay
                    </span>
                </div>

                <div class="p-6 space-y-6">
                    <!-- PDR Metric -->
                    <div class="space-y-2">
                        <div class="flex justify-between items-baseline text-xs">
                            <span class="font-bold text-slate-700 dark:text-slate-300">Packet Delivery Ratio (PDR)</span>
                            <span class="text-xl font-black text-indigo-600 dark:text-indigo-400">{{ $meshQos['pdr'] }}%</span>
                        </div>
                        <div class="w-full h-3 rounded-full bg-slate-100 dark:bg-slate-800 overflow-hidden">
                            <div class="h-full bg-indigo-500 rounded-full transition-all duration-500" style="width: {{ $meshQos['pdr'] }}%"></div>
                        </div>
                        <p class="text-[11px] text-slate-400">Paket Sukses: {{ $meshQos['total_received'] }} dari {{ $meshQos['expected_packets'] }} paket yang dipancarkan.</p>
                    </div>

                    <!-- 3 Grid Metrics -->
                    <div class="grid grid-cols-3 gap-3">
                        <div class="p-3.5 rounded-xl bg-slate-50 dark:bg-slate-800/50 border border-slate-100 dark:border-slate-800 text-center">
                            <span class="text-[10px] uppercase font-bold text-slate-400">Packet Loss Rate</span>
                            <div class="text-lg font-extrabold text-slate-900 dark:text-white mt-1">{{ $meshQos['loss_rate'] }}%</div>
                        </div>
                        <div class="p-3.5 rounded-xl bg-slate-50 dark:bg-slate-800/50 border border-slate-100 dark:border-slate-800 text-center">
                            <span class="text-[10px] uppercase font-bold text-slate-400">Rata-rata RSSI</span>
                            <div class="text-lg font-extrabold text-slate-900 dark:text-white mt-1">{{ $meshQos['avg_rssi'] }} <span class="text-xs font-normal">dBm</span></div>
                        </div>
                        <div class="p-3.5 rounded-xl bg-slate-50 dark:bg-slate-800/50 border border-slate-100 dark:border-slate-800 text-center">
                            <span class="text-[10px] uppercase font-bold text-slate-400">Avg Hop Count</span>
                            <div class="text-lg font-extrabold text-slate-900 dark:text-white mt-1">{{ $meshQos['avg_hop'] }}</div>
                        </div>
                    </div>

                    <!-- Node Breakdown -->
                    <div class="space-y-2">
                        <span class="text-xs font-bold text-slate-700 dark:text-slate-300">Rincian Per Node (Mesh):</span>
                        <div class="space-y-1.5 text-xs">
                            @foreach ([1, 2, 3] as $nodeId)
                                @php $nodeM = $meshQos['per_node'][$nodeId] ?? null; @endphp
                                <div class="p-2.5 rounded-lg bg-slate-50/80 dark:bg-slate-800/40 flex items-center justify-between">
                                    <span class="font-semibold text-slate-800 dark:text-slate-200">Node #{{ $nodeId }}</span>
                                    @if ($nodeM)
                                        <div class="flex items-center gap-3 text-slate-600 dark:text-slate-400 font-mono">
                                            <span>PDR: <strong class="{{ $nodeM['pdr'] < 70 ? 'text-rose-500' : 'text-emerald-500' }}">{{ $nodeM['pdr'] }}%</strong></span>
                                            <span>RSSI: {{ $nodeM['avg_rssi'] }} dBm</span>
                                            <span class="px-1.5 py-0.5 rounded bg-slate-200 dark:bg-slate-700 text-[10px] font-bold">Hop: {{ $nodeM['avg_hop'] }}</span>
                                        </div>
                                    @else
                                        <span class="text-slate-400 italic">No Data</span>
                                    @endif
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>

    <!-- Kesimpulan Analitis Penelitian -->
    <div class="p-6 rounded-2xl bg-gradient-to-r from-slate-900 via-indigo-950 to-slate-900 text-white shadow-xl space-y-4">
        <div class="flex items-center gap-3">
            <div class="w-10 h-10 rounded-xl bg-indigo-500/20 text-indigo-300 flex items-center justify-center">
                <x-heroicon-o-light-bulb class="w-6 h-6" />
            </div>
            <div>
                <h3 class="text-lg font-bold">Ringkasan & Kesimpulan Analitis Topologi</h3>
                <p class="text-xs text-slate-300">Rekomendasi teknis berbasis komparasi data empiris QoS.</p>
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-xs leading-relaxed">
            <div class="p-4 rounded-xl bg-white/5 border border-white/10 space-y-2">
                <h4 class="font-bold text-sky-400 flex items-center gap-2">
                    <span>★ Karakteristik Topologi Star</span>
                </h4>
                <p class="text-slate-300">
                    Sangat optimal untuk kondisi <strong>jarak dekat dan tanpa halangan fisik (Line-of-Sight)</strong>. Latensi pengiriman minimal karena langsung ke gateway. Namun, mengalami penurunan PDR tajam pada Node 3 saat berada di area <em>blind spot</em> terhalang tembok.
                </p>
            </div>

            <div class="p-4 rounded-xl bg-white/5 border border-white/10 space-y-2">
                <h4 class="font-bold text-indigo-400 flex items-center gap-2">
                    <span>🕸 Karakteristik Topologi Mesh</span>
                </h4>
                <p class="text-slate-300">
                    Mampu mempertahankan <strong>PDR tetap tinggi (>90%) pada area blind spot</strong> melalui mekanisme multi-hop relay. Node 2 secara otomatis bertindak sebagai jembatan estafet sinyal untuk Node 3, memberikan ketahanan jangkauan fisik yang jauh lebih stabil.
                </p>
            </div>
        </div>
    </div>

</div>
