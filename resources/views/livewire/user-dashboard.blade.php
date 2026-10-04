<div wire:poll.3s="refreshData" class="space-y-8">

    <!-- Top Header Greeting -->
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 bg-white dark:bg-slate-900 p-6 rounded-2xl border border-slate-200/80 dark:border-slate-800 shadow-sm relative overflow-hidden">
        <div class="space-y-1 z-10">
            <div class="flex items-center gap-2">
                <h1 class="text-2xl font-extrabold tracking-tight text-slate-900 dark:text-white">
                    Pemantauan Kualitas Udara Lingkungan
                </h1>
                <span class="px-2.5 py-1 rounded-full text-xs font-bold bg-emerald-500/10 text-emerald-600 dark:bg-emerald-400/10 dark:text-emerald-400 border border-emerald-500/20 flex items-center gap-1.5">
                    <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
                    Update Real-time
                </span>
            </div>
            <p class="text-sm text-slate-500 dark:text-slate-400">
                Informasi kondisi udara lingkungan sekitar untuk keamanan dan kenyamanan aktivitas Anda.
            </p>
        </div>

        <a href="{{ route('user.history') }}" 
           class="px-4 py-2.5 bg-sky-600 hover:bg-sky-500 text-white rounded-xl text-xs font-bold shadow-md shadow-sky-500/20 transition-all flex items-center gap-2 self-start md:self-auto z-10">
            <x-heroicon-o-clock class="w-4 h-4" />
            <span>Lihat Riwayat Kualitas Udara</span>
        </a>
    </div>

    <!-- Overall Health & Air Quality Status Hero Card -->
    <div class="p-6 md:p-8 rounded-3xl bg-gradient-to-br from-sky-600 via-indigo-600 to-sky-700 dark:from-slate-900 dark:via-sky-950 dark:to-slate-900 text-white shadow-xl shadow-sky-500/20 space-y-6 relative overflow-hidden">
        <div class="absolute right-0 top-0 w-96 h-96 bg-white/10 dark:bg-sky-500/10 rounded-full blur-3xl pointer-events-none"></div>

        <div class="flex flex-col md:flex-row md:items-center justify-between gap-6 relative z-10">
            <div class="space-y-3">
                <span class="px-3 py-1 rounded-full text-xs font-bold uppercase tracking-wider bg-white/10 text-slate-200 border border-white/10">
                    Status Lingkungan Saat Ini
                </span>
                <div class="flex items-center gap-3">
                    <h2 class="text-3xl md:text-4xl font-black tracking-tight">
                        {{ $worstStatus }}
                    </h2>
                    <span class="text-xs text-slate-300 font-medium bg-white/10 px-3 py-1 rounded-lg">
                        Indeks: <strong>{{ $maxKuOverall }} PPM</strong>
                    </span>
                </div>
            </div>

            <!-- Health Icon -->
            <div class="w-16 h-16 rounded-2xl bg-white/10 border border-white/20 flex items-center justify-center text-white shrink-0 shadow-inner">
                <x-dynamic-component :component="$healthAdvice['icon']" class="w-8 h-8" />
            </div>
        </div>

        <!-- Health Recommendation Alert Box -->
        <div class="p-4 rounded-2xl bg-white/10 border border-white/10 space-y-1 relative z-10">
            <h3 class="font-bold text-sm text-sky-300 flex items-center gap-2">
                <x-heroicon-o-heart class="w-4 h-4 text-rose-400" />
                <span>Rekomendasi Kesehatan: {{ $healthAdvice['title'] }}</span>
            </h3>
            <p class="text-xs text-slate-200 leading-relaxed">
                {{ $healthAdvice['desc'] }}
            </p>
        </div>
    </div>

    <!-- Area Status Grid (3 Simple Area Cards) -->
    <div class="space-y-4">
        <div class="flex items-center justify-between">
            <h2 class="text-lg font-bold text-slate-900 dark:text-white flex items-center gap-2">
                <x-heroicon-o-building-office-2 class="w-5 h-5 text-sky-500" />
                <span>Kondisi Udara Per Lokasi Area</span>
            </h2>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            @foreach ($areasData as $areaId => $area)
                @php
                    $latest = $area['latest'];
                    $isOnline = $area['is_online'];
                @endphp

                <div class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200/80 dark:border-slate-800 shadow-sm p-6 space-y-5 hover:border-slate-300 dark:hover:border-slate-700 transition-all flex flex-col justify-between">
                    
                    <div class="space-y-4">
                        <!-- Area Name & Status -->
                        <div class="flex items-start justify-between gap-2">
                            <div>
                                <span class="text-[10px] uppercase font-bold text-slate-400 tracking-wider">Lokasi Sensor #{{ $areaId }}</span>
                                <h3 class="font-extrabold text-slate-900 dark:text-white text-base mt-0.5">{{ $area['name'] }}</h3>
                            </div>
                            @if ($isOnline)
                                <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-500/15 text-emerald-600 dark:bg-emerald-400/15 dark:text-emerald-400 border border-emerald-500/30 flex items-center gap-1">
                                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-pulse"></span>
                                    Aktif
                                </span>
                            @else
                                <span class="px-2 py-0.5 rounded-full text-[10px] font-medium bg-slate-100 dark:bg-slate-800 text-slate-400 border border-slate-200 dark:border-slate-700">
                                    Offline
                                </span>
                            @endif
                        </div>

                        <!-- Big Air Quality Badge for this Area -->
                        @if ($latest)
                            @php $badge = $latest->status_badge; @endphp
                            <div class="p-4 rounded-2xl border {{ $badge['bg'] }} space-y-1">
                                <span class="text-[10px] font-bold uppercase tracking-wider opacity-75">Tingkat Kebersihan Udara</span>
                                <div class="text-xl font-black flex items-center justify-between">
                                    <span>{{ $latest->ku_status }}</span>
                                    <span class="text-sm font-bold opacity-90">{{ $latest->ku_value }} PPM</span>
                                </div>
                            </div>
                        @else
                            <div class="p-4 rounded-2xl bg-slate-50 dark:bg-slate-800/40 text-center text-slate-400 text-xs">
                                Belum ada data kualitas udara terrekam.
                            </div>
                        @endif
                    </div>

                    <!-- Simplified Footer -->
                    @if ($latest)
                        <div class="pt-4 border-t border-slate-100 dark:border-slate-800/80 text-xs text-slate-500 dark:text-slate-400 flex items-center justify-between">
                            <span class="text-[11px]">Diperbarui: {{ $latest->created_at->diffForHumans() }}</span>
                            <span class="font-bold text-sky-600 dark:text-sky-400 text-[11px]">Kondisi Terpantau</span>
                        </div>
                    @endif

                </div>
            @endforeach
        </div>
    </div>

    <!-- Friendly Air Quality Level Guide Section -->
    <div class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200/80 dark:border-slate-800 shadow-sm p-6 space-y-4">
        <h2 class="text-base font-bold text-slate-900 dark:text-white flex items-center gap-2">
            <x-heroicon-o-information-circle class="w-5 h-5 text-indigo-500" />
            <span>Panduan Kategori Kualitas Udara Lingkungan</span>
        </h2>

        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 text-xs">
            <div class="p-4 rounded-xl bg-emerald-500/10 border border-emerald-500/20 space-y-1">
                <span class="font-black text-emerald-700 dark:text-emerald-400 text-sm">1. Baik (0 - 50 PPM)</span>
                <p class="text-slate-600 dark:text-slate-300">Udara sangat bersih dan tidak menimbulkan dampak kesehatan sama sekali.</p>
            </div>

            <div class="p-4 rounded-xl bg-amber-500/10 border border-amber-500/20 space-y-1">
                <span class="font-black text-amber-700 dark:text-amber-400 text-sm">2. Sedang (51 - 150 PPM)</span>
                <p class="text-slate-600 dark:text-slate-300">Kualitas udara masih dapat diterima oleh manusia pada umumnya.</p>
            </div>

            <div class="p-4 rounded-xl bg-orange-500/10 border border-orange-500/20 space-y-1">
                <span class="font-black text-orange-700 dark:text-orange-400 text-sm">3. Tidak Sehat (151 - 300 PPM)</span>
                <p class="text-slate-600 dark:text-slate-300">Dapat memicu gangguan pada mata, tenggorokan, dan kelompok sensitif.</p>
            </div>

            <div class="p-4 rounded-xl bg-rose-500/10 border border-rose-500/20 space-y-1">
                <span class="font-black text-rose-700 dark:text-rose-400 text-sm">4. Berbahaya (> 300 PPM)</span>
                <p class="text-slate-600 dark:text-slate-300">Tingkat polutan tinggi! Disarankan evakuasi dan penggunaan masker khusus.</p>
            </div>
        </div>
    </div>

</div>
