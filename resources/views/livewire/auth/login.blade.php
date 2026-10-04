<div class="w-full max-w-sm sm:max-w-md mx-auto bg-white dark:bg-slate-900 rounded-3xl border border-slate-200/90 dark:border-slate-800 shadow-xl p-6 sm:p-7 space-y-5 relative overflow-hidden transition-all">
    
    <div class="absolute -right-10 -top-10 w-36 h-36 bg-sky-500/10 dark:bg-sky-500/5 rounded-full blur-2xl pointer-events-none"></div>

    <!-- Compact Brand Header -->
    <div class="text-center space-y-1.5">
        <div class="inline-flex items-center justify-center w-11 h-11 rounded-2xl bg-gradient-to-tr from-sky-600 to-indigo-600 text-white shadow-md shadow-sky-500/20 mb-1">
            <x-heroicon-o-sparkles class="w-5 h-5 stroke-[2.2]" />
        </div>
        <h1 class="text-xl font-black text-slate-900 dark:text-white tracking-tight">
            Masuk ke SIASAP
        </h1>
        <p class="text-xs text-slate-500 dark:text-slate-400 font-medium">
            Pemantauan Kualitas Udara Lingkungan
        </p>
    </div>

    <!-- Compact Quick Login Presets -->
    <div class="p-3 rounded-2xl bg-slate-50 dark:bg-slate-800/60 border border-slate-200/80 dark:border-slate-800 space-y-2">
        <span class="text-[10px] font-extrabold uppercase tracking-wider text-slate-400 dark:text-slate-500 block text-center">
            Pilihan Akses Cepat:
        </span>
        <div class="grid grid-cols-2 gap-2">
            <button wire:click="quickLogin('admin')" type="button" 
                    class="py-2 px-2.5 bg-white dark:bg-slate-700 hover:bg-slate-100 dark:hover:bg-slate-600 text-slate-800 dark:text-white rounded-xl text-[11px] font-bold border border-slate-200 dark:border-slate-600 shadow-sm transition-all flex items-center justify-center gap-1.5 truncate">
                <x-heroicon-o-shield-check class="w-3.5 h-3.5 text-indigo-500 shrink-0" />
                <span class="truncate">Masuk Pengelola</span>
            </button>
            <button wire:click="quickLogin('user')" type="button" 
                    class="py-2 px-2.5 bg-white dark:bg-slate-700 hover:bg-slate-100 dark:hover:bg-slate-600 text-slate-800 dark:text-white rounded-xl text-[11px] font-bold border border-slate-200 dark:border-slate-600 shadow-sm transition-all flex items-center justify-center gap-1.5 truncate">
                <x-heroicon-o-user class="w-3.5 h-3.5 text-emerald-500 shrink-0" />
                <span class="truncate">Masuk Pengguna</span>
            </button>
        </div>
    </div>

    <div class="relative flex py-0.5 items-center">
        <div class="flex-grow border-t border-slate-200 dark:border-slate-800"></div>
        <span class="flex-shrink mx-3 text-[10px] uppercase font-extrabold text-slate-400">Atau Masuk Email</span>
        <div class="flex-grow border-t border-slate-200 dark:border-slate-800"></div>
    </div>

    <!-- Login Form -->
    <form wire:submit.prevent="authenticate" class="space-y-3.5">
        <!-- Email Input -->
        <div class="space-y-1">
            <label class="text-[11px] font-extrabold text-slate-700 dark:text-slate-300">Email</label>
            <div class="relative">
                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-slate-400">
                    <x-heroicon-o-envelope class="w-4 h-4" />
                </div>
                <input type="email" wire:model="email" placeholder="admin@siasap.test" 
                       class="w-full pl-9 pr-3 py-2 text-xs rounded-xl bg-slate-50 dark:bg-slate-800/80 border border-slate-200 dark:border-slate-700 text-slate-900 dark:text-white focus:ring-2 focus:ring-sky-500 focus:outline-none font-medium">
            </div>
            @error('email') <span class="text-[10px] text-rose-500 font-semibold block mt-0.5">{{ $message }}</span> @enderror
        </div>

        <!-- Password Input -->
        <div class="space-y-1">
            <label class="text-[11px] font-extrabold text-slate-700 dark:text-slate-300">Kata Sandi</label>
            <div class="relative">
                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-slate-400">
                    <x-heroicon-o-lock-closed class="w-4 h-4" />
                </div>
                <input type="password" wire:model="password" placeholder="••••••••" 
                       class="w-full pl-9 pr-3 py-2 text-xs rounded-xl bg-slate-50 dark:bg-slate-800/80 border border-slate-200 dark:border-slate-700 text-slate-900 dark:text-white focus:ring-2 focus:ring-sky-500 focus:outline-none font-medium">
            </div>
            @error('password') <span class="text-[10px] text-rose-500 font-semibold block mt-0.5">{{ $message }}</span> @enderror
        </div>

        <!-- Remember Me Checkbox -->
        <div class="flex items-center justify-between text-xs pt-0.5">
            <label class="flex items-center gap-1.5 text-slate-600 dark:text-slate-400 cursor-pointer text-[11px]">
                <input type="checkbox" wire:model="remember" class="rounded border-slate-300 text-sky-600 focus:ring-sky-500">
                <span class="font-medium">Ingat Saya</span>
            </label>
            <span class="text-[10px] text-slate-400 font-mono">Password: password</span>
        </div>

        <!-- Submit Button -->
        <button type="submit" 
                class="w-full py-2.5 bg-gradient-to-r from-sky-600 to-indigo-600 hover:from-sky-500 hover:to-indigo-500 text-white rounded-xl text-xs font-bold shadow-md shadow-sky-500/20 active:scale-[0.99] transition-all flex items-center justify-center gap-2">
            <x-heroicon-o-arrow-right-on-rectangle class="w-4 h-4" />
            <span>Masuk Sekarang</span>
        </button>
    </form>

    <div class="text-center text-[10px] text-slate-400">
        SIASAP — Pemantauan Kualitas Udara Lingkungan
    </div>

</div>
