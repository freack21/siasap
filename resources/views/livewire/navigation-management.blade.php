<div class="space-y-8">
    <!-- Header Banner -->
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 bg-white dark:bg-slate-900 p-6 rounded-2xl border border-slate-200/80 dark:border-slate-800 shadow-sm">
        <div class="space-y-1">
            <div class="flex items-center gap-2">
                <h1 class="text-2xl font-extrabold tracking-tight text-slate-900 dark:text-white">
                    Navigasi Sistem
                </h1>
                <span class="px-2.5 py-0.5 rounded-full text-xs font-bold bg-indigo-500/10 text-indigo-600 dark:bg-indigo-400/10 dark:text-indigo-400 border border-indigo-500/20">
                    SystemNavigationSeeder
                </span>
            </div>
            <p class="text-sm text-slate-500 dark:text-slate-400">
                Kelola menu navigasi sidebar dan Blade Icon provider yang terintegrasi secara terstruktur.
            </p>
        </div>
    </div>

    <!-- Navigations Table -->
    <div class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200/80 dark:border-slate-800 shadow-sm overflow-hidden space-y-4">
        <div class="p-6 border-b border-slate-100 dark:border-slate-800 flex items-center justify-between">
            <h2 class="text-lg font-bold text-slate-900 dark:text-white flex items-center gap-2">
                <x-heroicon-o-bars-3-center-left class="w-5 h-5 text-indigo-500" />
                <span>Menu Navigasi Sidebar Aktif</span>
            </h2>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-slate-50/70 dark:bg-slate-800/40 text-[11px] font-bold uppercase tracking-wider text-slate-400 dark:text-slate-500 border-b border-slate-100 dark:border-slate-800">
                        <th class="py-3.5 px-6">Urutan</th>
                        <th class="py-3.5 px-4">Judul Menu</th>
                        <th class="py-3.5 px-4">Blade Icon Provider</th>
                        <th class="py-3.5 px-4">Seksi / Group</th>
                        <th class="py-3.5 px-4">Route / URL</th>
                        <th class="py-3.5 px-6 text-center">Status</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-800/60 text-xs">
                    @foreach ($navigations as $nav)
                        <tr class="hover:bg-slate-50/50 dark:hover:bg-slate-800/30 transition-colors">
                            <td class="py-3.5 px-6 font-mono font-bold text-slate-400">
                                #{{ $nav->order }}
                            </td>
                            <td class="py-3.5 px-4 font-bold text-slate-900 dark:text-white flex items-center gap-2.5">
                                <div class="w-7 h-7 rounded bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300 flex items-center justify-center">
                                    <x-dynamic-component :component="$nav->icon" class="w-4 h-4" />
                                </div>
                                <span>{{ $nav->title }}</span>
                            </td>
                            <td class="py-3.5 px-4 font-mono text-slate-500">
                                <code>{{ $nav->icon }}</code>
                            </td>
                            <td class="py-3.5 px-4">
                                <span class="px-2 py-0.5 rounded text-[10px] font-bold uppercase bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300">
                                    {{ $nav->section }}
                                </span>
                            </td>
                            <td class="py-3.5 px-4 font-mono text-sky-600 dark:text-sky-400 font-semibold">
                                {{ $nav->route_name ? route($nav->route_name, [], false) : $nav->url }}
                            </td>
                            <td class="py-3.5 px-6 text-center">
                                <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-500/10 text-emerald-600 dark:bg-emerald-400/10 dark:text-emerald-400 border border-emerald-500/20">
                                    Aktif
                                </span>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
