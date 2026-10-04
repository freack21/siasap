<div class="space-y-8">

    <!-- Header & Action Toolbar -->
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 bg-white dark:bg-slate-900 p-6 rounded-2xl border border-slate-200/80 dark:border-slate-800 shadow-sm">
        <div class="space-y-1">
            <div class="flex items-center gap-2">
                <h1 class="text-2xl font-extrabold tracking-tight text-slate-900 dark:text-white">
                    Riwayat Kualitas Udara
                </h1>
                <span class="px-2.5 py-0.5 rounded-full text-xs font-bold bg-sky-500/10 text-sky-600 dark:bg-sky-400/10 dark:text-sky-400 border border-sky-500/20">
                    Catatan Histori
                </span>
            </div>
            <p class="text-sm text-slate-500 dark:text-slate-400">
                Laporan dan rekam pencatatan kondisi kualitas udara lingkungan berdasarkan lokasi dan waktu.
            </p>
        </div>

        <a href="{{ route('user.dashboard') }}" 
           class="px-4 py-2 bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-200 border border-slate-200 dark:border-slate-700 rounded-xl text-xs font-bold transition-all flex items-center gap-2 self-start md:self-auto">
            <x-heroicon-o-arrow-left class="w-4 h-4" />
            <span>Kembali ke Dashboard Utama</span>
        </a>
    </div>

    <!-- User History Card Table -->
    <div class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200/80 dark:border-slate-800 shadow-sm overflow-hidden space-y-4">
        
        <!-- Search & Filter Controls -->
        <div class="p-6 border-b border-slate-100 dark:border-slate-800 flex flex-col md:flex-row md:items-center justify-between gap-4">
            <div>
                <h2 class="text-lg font-bold text-slate-900 dark:text-white flex items-center gap-2">
                    <x-heroicon-o-clock class="w-5 h-5 text-sky-500" />
                    <span>Daftar Rekam Kualitas Udara</span>
                </h2>
            </div>

            <!-- Filters Grid -->
            <div class="flex flex-wrap items-center gap-3 text-xs">
                <!-- Filter Area -->
                <div class="space-y-1">
                    <select wire:model.live="selectedArea" class="px-3 py-2 bg-slate-100 dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700 text-slate-700 dark:text-slate-200 font-semibold focus:outline-none">
                        <option value="all">Semua Lokasi Area</option>
                        <option value="1">Lobby & Ruang Utama</option>
                        <option value="2">Halaman Terbuka</option>
                        <option value="3">Laboratorium / Produksi</option>
                    </select>
                </div>

                <!-- Filter Status -->
                <div class="space-y-1">
                    <select wire:model.live="selectedStatus" class="px-3 py-2 bg-slate-100 dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700 text-slate-700 dark:text-slate-200 font-semibold focus:outline-none">
                        <option value="all">Semua Status Kualitas</option>
                        <option value="Baik">Baik</option>
                        <option value="Sedang">Sedang</option>
                        <option value="Tidak Sehat">Tidak Sehat</option>
                        <option value="Berbahaya">Berbahaya</option>
                    </select>
                </div>

                <!-- Filter Tanggal -->
                <div class="space-y-1">
                    <input type="date" wire:model.live="searchDate" class="px-3 py-1.5 bg-slate-100 dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700 text-slate-700 dark:text-slate-200 font-semibold focus:outline-none">
                </div>
            </div>
        </div>

        <!-- Non-Technical User Friendly Table -->
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-slate-50/70 dark:bg-slate-800/40 text-[11px] font-bold uppercase tracking-wider text-slate-400 dark:text-slate-500 border-b border-slate-100 dark:border-slate-800">
                        <th class="py-3.5 px-6">Waktu & Tanggal</th>
                        <th class="py-3.5 px-4">Lokasi Area</th>
                        <th class="py-3.5 px-4 text-center">Kategori Udara</th>
                        <th class="py-3.5 px-6">Catatan Kesehatan / Rekomendasi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-800/60 text-xs">
                    @forelse ($logs as $log)
                        @php 
                            $badge = $log->status_badge;
                            $areaName = $areaNames[$log->node_id] ?? "Area Sensor #{$log->node_id}";
                        @endphp
                        <tr class="hover:bg-slate-50/50 dark:hover:bg-slate-800/30 transition-colors">
                            <td class="py-4 px-6 font-semibold text-slate-900 dark:text-white">
                                {{ $log->created_at->format('d M Y, H:i') }} WIB
                            </td>
                            <td class="py-4 px-4 font-bold text-slate-700 dark:text-slate-200">
                                {{ $areaName }}
                            </td>
                            <td class="py-4 px-4 text-center">
                                <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full font-extrabold text-xs border {{ $badge['bg'] }}">
                                    <span class="w-2 h-2 rounded-full {{ $badge['dot'] }}"></span>
                                    {{ $log->ku_status }} ({{ $log->ku_value }} PPM)
                                </span>
                            </td>
                            <td class="py-4 px-6 text-slate-600 dark:text-slate-300">
                                @if ($log->ku_status === 'Baik')
                                    <span class="text-emerald-600 dark:text-emerald-400 font-semibold">✓ Udara Segar & Safe! Ideal untuk aktivitas luar.</span>
                                @elseif ($log->ku_status === 'Sedang')
                                    <span class="text-amber-600 dark:text-amber-400 font-medium">! Kualitas cukup baik. Kelompok sensitif disarankan waspada.</span>
                                @elseif ($log->ku_status === 'Tidak Sehat')
                                    <span class="text-orange-600 dark:text-orange-400 font-bold">⚠ Waspada! Disarankan memakai masker di lokasi ini.</span>
                                @else
                                    <span class="text-rose-600 dark:text-rose-400 font-extrabold">🚨 PERINGATAN BAHAYA! Gunakan masker N95 & hindari lokasi.</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="py-12 text-center text-slate-400">
                                Tidak ada data riwayat kualitas udara yang sesuai dengan filter pencarian.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Pagination Links -->
        <div class="p-4 border-t border-slate-100 dark:border-slate-800">
            {{ $logs->links() }}
        </div>
    </div>

</div>
