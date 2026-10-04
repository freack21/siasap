<div wire:poll.3s="refreshTelemetry" class="space-y-8">

    <!-- Top Header Banner & Quick Actions -->
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 bg-white dark:bg-slate-900 p-6 rounded-2xl border border-slate-200/80 dark:border-slate-800 shadow-sm relative overflow-hidden">
        <div class="absolute -right-10 -bottom-10 w-48 h-48 bg-sky-500/10 dark:bg-sky-500/5 rounded-full blur-3xl pointer-events-none"></div>
        
        <div class="space-y-1 relative z-10">
            <div class="flex items-center gap-2">
                <h1 class="text-2xl font-extrabold tracking-tight text-slate-900 dark:text-white">
                    Dashboard Telemetry Real-time
                </h1>
                <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-semibold bg-emerald-500/10 text-emerald-600 dark:bg-emerald-400/10 dark:text-emerald-400 border border-emerald-500/20">
                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-pulse"></span>
                    Live Sync (3s)
                </span>
            </div>
            <p class="text-sm text-slate-500 dark:text-slate-400">
                Pemantauan kualitas udara multi-sensor ($\text{CO}_2, \text{NH}_3, \text{CO}, \text{H}_2$) dan telemetri sinyal ESP32.
            </p>
        </div>

        <!-- Sesi Aktif Indicator & Action Buttons -->
        <div class="flex flex-wrap items-center gap-3 relative z-10">
            @if ($activeSession)
                <div class="px-3.5 py-2 rounded-xl bg-indigo-500/10 dark:bg-indigo-400/10 border border-indigo-500/20 text-indigo-700 dark:text-indigo-300 text-xs font-semibold flex items-center gap-2">
                    <svg class="w-4 h-4 text-indigo-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                    </svg>
                    <span>Sesi Uji: <strong>{{ $activeSession->name }}</strong> ({{ strtoupper($activeSession->topology_type) }})</span>
                </div>
            @else
                <div class="px-3.5 py-2 rounded-xl bg-slate-100 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-slate-600 dark:text-slate-400 text-xs font-medium flex items-center gap-2">
                    <span class="w-2 h-2 rounded-full bg-slate-400"></span>
                    <span>Modus Operasional Standar (Tanpa Sesi Uji)</span>
                </div>
            @endif

            <button wire:click="generateSimulatedData" 
                    wire:loading.attr="disabled"
                    class="px-4 py-2 bg-gradient-to-r from-sky-600 to-indigo-600 hover:from-sky-500 hover:to-indigo-500 text-white rounded-xl text-xs font-bold shadow-md shadow-sky-500/20 active:scale-95 transition-all flex items-center gap-2">
                <svg wire:loading.remove wire:target="generateSimulatedData" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M5.25 5.653c0-.856.917-1.398 1.667-.986l11.54 6.347a1.125 1.125 0 010 1.972l-11.54 6.347a1.125 1.125 0 01-1.667-.986V5.653z" />
                </svg>
                <svg wire:loading wire:target="generateSimulatedData" class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0l3.181 3.183a8.25 8.25 0 0013.803-3.7M4.031 9.865a8.25 8.25 0 0113.803-3.7l3.181 3.182m0-4.991v4.99" />
                </svg>
                <span>Simulasi Transmisi ESP32</span>
            </button>
        </div>
    </div>

    <!-- Quick Stats Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <!-- Card 1: Node Status Count -->
        <div class="bg-white dark:bg-slate-900 p-5 rounded-2xl border border-slate-200/80 dark:border-slate-800 shadow-sm flex items-center justify-between">
            <div class="space-y-1">
                <span class="text-xs font-semibold uppercase tracking-wider text-slate-400 dark:text-slate-500">Node Aktif</span>
                <div class="text-2xl font-extrabold text-slate-900 dark:text-white flex items-baseline gap-2">
                    <span>{{ collect($nodesData)->where('is_online', true)->count() }}</span>
                    <span class="text-xs font-normal text-slate-400">/ 3 Node</span>
                </div>
            </div>
            <div class="w-12 h-12 rounded-xl bg-emerald-500/10 text-emerald-600 dark:bg-emerald-400/10 dark:text-emerald-400 flex items-center justify-center">
                <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 3v1.5M4.5 8.25H3m18 0h-1.5M4.5 12H3m18 0h-1.5m-15 3.75H3m18 0h-1.5M8.25 19.5V21M15.75 3v1.5m0 15V21" />
                </svg>
            </div>
        </div>

        <!-- Card 2: Rata-rata KU Today -->
        <div class="bg-white dark:bg-slate-900 p-5 rounded-2xl border border-slate-200/80 dark:border-slate-800 shadow-sm flex items-center justify-between">
            <div class="space-y-1">
                <span class="text-xs font-semibold uppercase tracking-wider text-slate-400 dark:text-slate-500">Rata-rata Indeks KU</span>
                <div class="text-2xl font-extrabold text-slate-900 dark:text-white flex items-baseline gap-1.5">
                    <span>{{ $avgKuToday }}</span>
                    <span class="text-xs font-medium text-slate-400">PPM</span>
                </div>
            </div>
            <div class="w-12 h-12 rounded-xl bg-sky-500/10 text-sky-600 dark:bg-sky-400/10 dark:text-sky-400 flex items-center justify-center">
                <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 18L9 11.25l4.306 4.307a11.95 11.95 0 005.814-5.519l2.74-1.22m0 0l-5.94-2.28m5.94 2.28l-2.28 5.941" />
                </svg>
            </div>
        </div>

        <!-- Card 3: Total Paket Ingested -->
        <div class="bg-white dark:bg-slate-900 p-5 rounded-2xl border border-slate-200/80 dark:border-slate-800 shadow-sm flex items-center justify-between">
            <div class="space-y-1">
                <span class="text-xs font-semibold uppercase tracking-wider text-slate-400 dark:text-slate-500">Paket Diterima (Hari Ini)</span>
                <div class="text-2xl font-extrabold text-slate-900 dark:text-white">
                    {{ number_format($totalPacketsToday) }}
                </div>
            </div>
            <div class="w-12 h-12 rounded-xl bg-indigo-500/10 text-indigo-600 dark:bg-indigo-400/10 dark:text-indigo-400 flex items-center justify-center">
                <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M20.25 6.375c0 2.278-3.694 4.125-8.25 4.125S3.75 8.653 3.75 6.375m16.5 0c0-2.278-3.694-4.125-8.25-4.125S3.75 4.097 3.75 6.375m16.5 0v11.25c0 2.278-3.694 4.125-8.25 4.125s-8.25-1.847-8.25-4.125V6.375m16.5 5.625c0 2.278-3.694 4.125-8.25 4.125s-8.25-1.847-8.25-4.125" />
                </svg>
            </div>
        </div>

        <!-- Card 4: Topologi Aktif -->
        <div class="bg-white dark:bg-slate-900 p-5 rounded-2xl border border-slate-200/80 dark:border-slate-800 shadow-sm flex items-center justify-between">
            <div class="space-y-1">
                <span class="text-xs font-semibold uppercase tracking-wider text-slate-400 dark:text-slate-500">Mode Topologi</span>
                <div class="text-xl font-extrabold text-slate-900 dark:text-white flex items-center gap-2">
                    @if ($activeSession)
                        <span class="uppercase text-indigo-600 dark:text-indigo-400">{{ $activeSession->topology_type }} Topology</span>
                    @else
                        <span class="text-slate-600 dark:text-slate-400">Star Mode (Default)</span>
                    @endif
                </div>
            </div>
            <div class="w-12 h-12 rounded-xl bg-purple-500/10 text-purple-600 dark:bg-purple-400/10 dark:text-purple-400 flex items-center justify-center">
                <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M7.5 21L3 16.5m0 0L7.5 12M3 16.5h13.5m0-13.5L21 7.5m0 0L16.5 12M21 7.5H7.5" />
                </svg>
            </div>
        </div>
    </div>

    <!-- Node Status Grid (3 Cards) -->
    <div>
        <div class="flex items-center justify-between mb-4">
            <h2 class="text-lg font-bold text-slate-900 dark:text-white flex items-center gap-2">
                <svg class="w-5 h-5 text-sky-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9.75 3.104v1.244m0 0a6.002 6.002 0 00-4.72 4.72M9.75 4.348V5.5m0 0a4.5 4.5 0 00-3.256 3.256M9.75 5.5v1.244M12 21v-3.25m0 0a6 6 0 005.657-5.657M12 17.75v-1.244m0 0a4.5 4.5 0 003.256-3.256M12 16.506V15.26" />
                </svg>
                <span>Status Node Sensor ESP32</span>
            </h2>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            @foreach ($nodesData as $nodeId => $node)
                @php
                    $latest = $node['latest'];
                    $isOnline = $node['is_online'];
                @endphp

                <div class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200/80 dark:border-slate-800 shadow-sm overflow-hidden flex flex-col justify-between hover:border-slate-300 dark:hover:border-slate-700 transition-all">
                    
                    <!-- Node Header -->
                    <div class="p-5 border-b border-slate-100 dark:border-slate-800/80 flex items-center justify-between bg-slate-50/50 dark:bg-slate-900/50">
                        <div class="flex items-center gap-3">
                            <div class="w-9 h-9 rounded-lg bg-slate-100 dark:bg-slate-800 font-bold text-slate-700 dark:text-slate-200 flex items-center justify-center text-sm border border-slate-200 dark:border-slate-700">
                                N{{ $nodeId }}
                            </div>
                            <div>
                                <h3 class="font-bold text-slate-900 dark:text-white text-sm">Node Sensor #{{ $nodeId }}</h3>
                                <span class="text-xs text-slate-500 dark:text-slate-400">
                                    @if ($latest)
                                        Update: {{ $latest->created_at->diffForHumans() }}
                                    @else
                                        Belum Ada Data
                                    @endif
                                </span>
                            </div>
                        </div>

                        <!-- Online Status Badge -->
                        @if ($isOnline)
                            <span class="px-2.5 py-1 rounded-full text-xs font-semibold bg-emerald-500/15 text-emerald-600 dark:bg-emerald-400/15 dark:text-emerald-400 border border-emerald-500/30 flex items-center gap-1.5">
                                <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
                                Online
                            </span>
                        @else
                            <span class="px-2.5 py-1 rounded-full text-xs font-medium bg-slate-100 dark:bg-slate-800 text-slate-500 dark:text-slate-400 border border-slate-200 dark:border-slate-700 flex items-center gap-1.5">
                                <span class="w-2 h-2 rounded-full bg-slate-400"></span>
                                Offline
                            </span>
                        @endif
                    </div>

                    <!-- Node Body: KU Badge & Gas Grid -->
                    <div class="p-5 space-y-5">
                        @if ($latest)
                            @php
                                $badge = $latest->status_badge;
                            @endphp

                            <!-- Air Quality Badge (KU Index) -->
                            <div class="p-4 rounded-xl border {{ $badge['bg'] }} flex items-center justify-between">
                                <div>
                                    <span class="text-xs uppercase tracking-wider font-semibold opacity-75">Status Kualitas Udara</span>
                                    <div class="text-xl font-extrabold flex items-center gap-2 mt-0.5">
                                        <span>{{ $latest->ku_status }}</span>
                                    </div>
                                </div>
                                <div class="text-right">
                                    <span class="text-xs font-medium opacity-75">Indeks KU (Max)</span>
                                    <div class="text-2xl font-black">{{ $latest->ku_value }} <span class="text-xs font-normal">PPM</span></div>
                                </div>
                            </div>

                            <!-- 4 Gas Parameters Grid -->
                            <div class="grid grid-cols-2 gap-3">
                                <!-- CO2 -->
                                <div class="p-3 rounded-xl bg-slate-50 dark:bg-slate-800/50 border border-slate-100 dark:border-slate-800">
                                    <span class="text-xs text-slate-500 dark:text-slate-400 font-medium">Gas $\text{CO}_2$</span>
                                    <div class="text-base font-bold text-slate-900 dark:text-white mt-0.5">
                                        {{ $latest->co2 }} <span class="text-[10px] text-slate-400 font-normal">PPM</span>
                                    </div>
                                </div>

                                <!-- NH3 -->
                                <div class="p-3 rounded-xl bg-slate-50 dark:bg-slate-800/50 border border-slate-100 dark:border-slate-800">
                                    <span class="text-xs text-slate-500 dark:text-slate-400 font-medium">Gas $\text{NH}_3$</span>
                                    <div class="text-base font-bold text-slate-900 dark:text-white mt-0.5">
                                        {{ $latest->nh3 }} <span class="text-[10px] text-slate-400 font-normal">PPM</span>
                                    </div>
                                </div>

                                <!-- CO -->
                                <div class="p-3 rounded-xl bg-slate-50 dark:bg-slate-800/50 border border-slate-100 dark:border-slate-800">
                                    <span class="text-xs text-slate-500 dark:text-slate-400 font-medium">Gas $\text{CO}$</span>
                                    <div class="text-base font-bold text-slate-900 dark:text-white mt-0.5">
                                        {{ $latest->co }} <span class="text-[10px] text-slate-400 font-normal">PPM</span>
                                    </div>
                                </div>

                                <!-- H2 -->
                                <div class="p-3 rounded-xl bg-slate-50 dark:bg-slate-800/50 border border-slate-100 dark:border-slate-800">
                                    <span class="text-xs text-slate-500 dark:text-slate-400 font-medium">Gas $\text{H}_2$</span>
                                    <div class="text-base font-bold text-slate-900 dark:text-white mt-0.5">
                                        {{ $latest->h2 }} <span class="text-[10px] text-slate-400 font-normal">PPM</span>
                                    </div>
                                </div>
                            </div>
                        @else
                            <div class="py-8 text-center text-slate-400 text-sm">
                                Membutuhkan transmisi data pertama dari ESP32 Node #{{ $nodeId }}...
                            </div>
                        @endif
                    </div>

                    <!-- Node Footer: RSSI & Hop info -->
                    @if ($latest)
                        <div class="px-5 py-3 bg-slate-50/80 dark:bg-slate-900/80 border-t border-slate-100 dark:border-slate-800 text-xs text-slate-500 dark:text-slate-400 flex items-center justify-between">
                            <div class="flex items-center gap-1.5">
                                <svg class="w-4 h-4 text-sky-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.286 16.286a6 6 0 018.428 0m-11.25-3a10.5 10.5 0 0114.072 0m-16.892-3a15 15 0 0119.712 0M12 20h.008v.008H12V20z" />
                                </svg>
                                <span>RSSI: <strong class="text-slate-700 dark:text-slate-200">{{ $latest->rssi }} dBm</strong></span>
                            </div>
                            <div class="flex items-center gap-3">
                                <span>Seq: <strong class="text-slate-700 dark:text-slate-200">#{{ $latest->seq_number }}</strong></span>
                                <span class="px-2 py-0.5 rounded bg-slate-200 dark:bg-slate-800 text-[10px] font-bold text-slate-600 dark:text-slate-300">
                                    Hop: {{ $latest->hop_count }}
                                </span>
                            </div>
                        </div>
                    @endif

                </div>
            @endforeach
        </div>
    </div>

    <!-- Live Telemetry Feed Table Section -->
    <div class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200/80 dark:border-slate-800 shadow-sm overflow-hidden space-y-4">
        
        <!-- Table Header & Filters -->
        <div class="p-6 border-b border-slate-100 dark:border-slate-800 flex flex-col md:flex-row md:items-center justify-between gap-4">
            <div>
                <h2 class="text-lg font-bold text-slate-900 dark:text-white flex items-center gap-2">
                    <svg class="w-5 h-5 text-indigo-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 12h16.5m-16.5 3.75h16.5M3.75 19.5h16.5M3.75 4.5h16.5m-16.5 3.75h16.5" />
                    </svg>
                    <span>Live Telemetry Packet Log</span>
                </h2>
                <p class="text-xs text-slate-500 dark:text-slate-400">Riwayat pengiriman data sensor berkala dari seluruh node.</p>
            </div>

            <!-- Filters -->
            <div class="flex flex-wrap items-center gap-2 text-xs">
                <!-- Topology Filter -->
                <div class="flex items-center bg-slate-100 dark:bg-slate-800 p-1 rounded-xl border border-slate-200 dark:border-slate-700">
                    <button wire:click="setTopologyFilter('all')" 
                            class="px-3 py-1.5 rounded-lg font-semibold transition-all {{ $selectedTopology === 'all' ? 'bg-white dark:bg-slate-700 text-slate-900 dark:text-white shadow-sm' : 'text-slate-500 dark:text-slate-400 hover:text-slate-900' }}">
                        Semua Topologi
                    </button>
                    <button wire:click="setTopologyFilter('star')" 
                            class="px-3 py-1.5 rounded-lg font-semibold transition-all {{ $selectedTopology === 'star' ? 'bg-white dark:bg-slate-700 text-sky-600 dark:text-sky-400 shadow-sm' : 'text-slate-500 dark:text-slate-400 hover:text-slate-900' }}">
                        Star
                    </button>
                    <button wire:click="setTopologyFilter('mesh')" 
                            class="px-3 py-1.5 rounded-lg font-semibold transition-all {{ $selectedTopology === 'mesh' ? 'bg-white dark:bg-slate-700 text-indigo-600 dark:text-indigo-400 shadow-sm' : 'text-slate-500 dark:text-slate-400 hover:text-slate-900' }}">
                        Mesh
                    </button>
                </div>

                <!-- Node Filter -->
                <select wire:model.live="selectedNode" class="px-3 py-1.5 bg-slate-100 dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700 text-slate-700 dark:text-slate-200 font-semibold focus:outline-none">
                    <option value="all">Semua Node (1-3)</option>
                    <option value="1">Node 1</option>
                    <option value="2">Node 2</option>
                    <option value="3">Node 3</option>
                </select>
            </div>
        </div>

        <!-- Table Data -->
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-slate-50/70 dark:bg-slate-800/40 text-[11px] font-bold uppercase tracking-wider text-slate-400 dark:text-slate-500 border-b border-slate-100 dark:border-slate-800">
                        <th class="py-3 px-6">Waktu Transmisi</th>
                        <th class="py-3 px-4">Node</th>
                        <th class="py-3 px-4">Topologi</th>
                        <th class="py-3 px-4">Gas Readings ($\text{CO}_2, \text{NH}_3, \text{CO}, \text{H}_2$)</th>
                        <th class="py-3 px-4 text-center">Status KU</th>
                        <th class="py-3 px-4 text-right">RSSI</th>
                        <th class="py-3 px-4 text-right">Seq #</th>
                        <th class="py-3 px-6 text-center">Hop</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-800/60 text-xs">
                    @forelse ($logs as $log)
                        @php $badge = $log->status_badge; @endphp
                        <tr class="hover:bg-slate-50/50 dark:hover:bg-slate-800/30 transition-colors">
                            <td class="py-3.5 px-6 font-medium text-slate-600 dark:text-slate-300">
                                {{ $log->created_at->format('H:i:s') }}
                                <span class="text-[10px] text-slate-400 block">{{ $log->created_at->format('d M Y') }}</span>
                            </td>
                            <td class="py-3.5 px-4 font-bold text-slate-900 dark:text-white">
                                Node #{{ $log->node_id }}
                            </td>
                            <td class="py-3.5 px-4">
                                <span class="px-2 py-0.5 rounded text-[10px] font-bold uppercase {{ $log->topology_type === 'mesh' ? 'bg-indigo-500/10 text-indigo-600 dark:text-indigo-400' : 'bg-sky-500/10 text-sky-600 dark:text-sky-400' }}">
                                    {{ $log->topology_type }}
                                </span>
                            </td>
                            <td class="py-3.5 px-4">
                                <div class="flex items-center gap-3 font-mono">
                                    <span>$\text{CO}_2$: <strong class="text-slate-800 dark:text-slate-200">{{ $log->co2 }}</strong></span>
                                    <span>$\text{NH}_3$: <strong class="text-slate-800 dark:text-slate-200">{{ $log->nh3 }}</strong></span>
                                    <span>$\text{CO}$: <strong class="text-slate-800 dark:text-slate-200">{{ $log->co }}</strong></span>
                                    <span>$\text{H}_2$: <strong class="text-slate-800 dark:text-slate-200">{{ $log->h2 }}</strong></span>
                                </div>
                            </td>
                            <td class="py-3.5 px-4 text-center">
                                <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full font-bold text-[11px] border {{ $badge['bg'] }}">
                                    <span class="w-1.5 h-1.5 rounded-full {{ $badge['dot'] }}"></span>
                                    {{ $log->ku_status }} ({{ $log->ku_value }})
                                </span>
                            </td>
                            <td class="py-3.5 px-4 text-right font-mono font-medium text-slate-600 dark:text-slate-300">
                                {{ $log->rssi }} dBm
                            </td>
                            <td class="py-3.5 px-4 text-right font-mono font-semibold text-slate-700 dark:text-slate-200">
                                #{{ $log->seq_number }}
                            </td>
                            <td class="py-3.5 px-6 text-center">
                                <span class="px-2 py-0.5 bg-slate-100 dark:bg-slate-800 rounded font-semibold text-slate-600 dark:text-slate-300 text-[11px]">
                                    {{ $log->hop_count }}
                                </span>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="py-12 text-center text-slate-400">
                                Belum ada log data sensor. Klik tombol <strong>"Simulasi Transmisi ESP32"</strong> di atas atau kirim payload dari ESP32!
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

</div>
