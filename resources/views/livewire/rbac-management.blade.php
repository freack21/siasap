<div class="space-y-8">
    <!-- Header Banner -->
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 bg-white dark:bg-slate-900 p-6 rounded-2xl border border-slate-200/80 dark:border-slate-800 shadow-sm">
        <div class="space-y-1">
            <div class="flex items-center gap-2">
                <h1 class="text-2xl font-extrabold tracking-tight text-slate-900 dark:text-white">
                    Manajemen Pengguna & RBAC
                </h1>
                <span class="px-2.5 py-0.5 rounded-full text-xs font-bold bg-sky-500/10 text-sky-600 dark:bg-sky-400/10 dark:text-sky-400 border border-sky-500/20">
                    Role-Based Access Control
                </span>
            </div>
            <p class="text-sm text-slate-500 dark:text-slate-400">
                Pengaturan entitas pengguna, peranan (*Roles*), dan hak akses (*Permissions*) sistem SIASAP.
            </p>
        </div>
    </div>

    <!-- Users Table -->
    <div class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200/80 dark:border-slate-800 shadow-sm overflow-hidden space-y-4">
        <div class="p-6 border-b border-slate-100 dark:border-slate-800 flex items-center justify-between">
            <h2 class="text-lg font-bold text-slate-900 dark:text-white flex items-center gap-2">
                <x-heroicon-o-users class="w-5 h-5 text-sky-500" />
                <span>Daftar Pengguna Terdaftar</span>
            </h2>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-slate-50/70 dark:bg-slate-800/40 text-[11px] font-bold uppercase tracking-wider text-slate-400 dark:text-slate-500 border-b border-slate-100 dark:border-slate-800">
                        <th class="py-3.5 px-6">Nama Pengguna</th>
                        <th class="py-3.5 px-4">Email</th>
                        <th class="py-3.5 px-4">Role Akses</th>
                        <th class="py-3.5 px-6 text-right">Tanggal Terdaftar</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-800/60 text-xs">
                    @foreach ($users as $u)
                        <tr class="hover:bg-slate-50/50 dark:hover:bg-slate-800/30 transition-colors">
                            <td class="py-3.5 px-6 font-bold text-slate-900 dark:text-white flex items-center gap-3">
                                <div class="w-8 h-8 rounded-full bg-gradient-to-tr from-sky-600 to-indigo-600 text-white font-bold flex items-center justify-center text-xs">
                                    {{ strtoupper(substr($u->name, 0, 2)) }}
                                </div>
                                <span>{{ $u->name }}</span>
                            </td>
                            <td class="py-3.5 px-4 font-mono text-slate-600 dark:text-slate-300">
                                {{ $u->email }}
                            </td>
                            <td class="py-3.5 px-4">
                                @foreach ($u->roles as $role)
                                    <span class="px-2.5 py-0.5 rounded-full text-[11px] font-bold uppercase bg-indigo-500/10 text-indigo-600 dark:bg-indigo-400/15 dark:text-indigo-300 border border-indigo-500/20">
                                        {{ $role->display_name }}
                                    </span>
                                @endforeach
                            </td>
                            <td class="py-3.5 px-6 text-right text-slate-500">
                                {{ $u->created_at->format('d M Y, H:i') }}
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    <!-- Roles & Permissions Grid -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- Roles List -->
        <div class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200/80 dark:border-slate-800 shadow-sm p-6 space-y-4">
            <h2 class="text-lg font-bold text-slate-900 dark:text-white flex items-center gap-2">
                <x-heroicon-o-shield-check class="w-5 h-5 text-indigo-500" />
                <span>Peranan Sistem (*Roles*)</span>
            </h2>
            <div class="space-y-3">
                @foreach ($roles as $role)
                    <div class="p-4 rounded-xl bg-slate-50 dark:bg-slate-800/40 border border-slate-100 dark:border-slate-800 space-y-2">
                        <div class="flex items-center justify-between">
                            <span class="font-bold text-slate-900 dark:text-white text-sm">{{ $role->display_name }} (<code>{{ $role->name }}</code>)</span>
                            <span class="text-xs font-semibold text-slate-500">{{ $role->permissions->count() }} Hak Akses</span>
                        </div>
                        <p class="text-xs text-slate-500 dark:text-slate-400">{{ $role->description }}</p>
                    </div>
                @endforeach
            </div>
        </div>

        <!-- Permissions List -->
        <div class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200/80 dark:border-slate-800 shadow-sm p-6 space-y-4">
            <h2 class="text-lg font-bold text-slate-900 dark:text-white flex items-center gap-2">
                <x-heroicon-o-key class="w-5 h-5 text-amber-500" />
                <span>Hak Akses Sistem (*Permissions*)</span>
            </h2>
            <div class="space-y-4">
                @foreach ($permissions as $group => $perms)
                    <div class="space-y-2">
                        <span class="text-xs font-bold uppercase tracking-wider text-slate-400">{{ $group }}</span>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                            @foreach ($perms as $p)
                                <div class="p-2.5 rounded-lg bg-slate-50 dark:bg-slate-800/40 border border-slate-100 dark:border-slate-800 text-xs font-medium text-slate-700 dark:text-slate-300">
                                    {{ $p->display_name }}
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
</div>
