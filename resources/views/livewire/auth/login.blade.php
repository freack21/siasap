<div class="w-full max-w-sm sm:max-w-md mx-auto bg-white dark:bg-slate-900 rounded-3xl border border-slate-200/90 dark:border-slate-800 shadow-xl p-6 sm:p-8 space-y-6 relative overflow-hidden transition-all">
    
    <div class="absolute -right-12 -top-12 w-40 h-40 bg-sky-500/10 dark:bg-sky-500/5 rounded-full blur-3xl pointer-events-none"></div>

    <!-- Brand Header -->
    <div class="text-center space-y-2">
        <div class="inline-flex items-center justify-center w-12 h-12 rounded-2xl bg-gradient-to-tr from-sky-600 to-indigo-600 text-white shadow-lg shadow-sky-500/25 mb-1">
            <x-heroicon-o-sparkles class="w-6 h-6 stroke-[2.2]" />
        </div>
        <h1 class="text-2xl font-black text-slate-900 dark:text-white tracking-tight">
            Masuk ke SIASAP
        </h1>
        <p class="text-xs text-slate-500 dark:text-slate-400 font-medium">
            Sistem Pemantauan Kualitas Udara Lingkungan
        </p>
    </div>

    <!-- Professional Single-Door Login Form -->
    <form wire:submit.prevent="authenticate" class="space-y-4">
        <!-- Email Input -->
        <div class="space-y-1.5">
            <label class="text-xs font-extrabold text-slate-700 dark:text-slate-300">Alamat Email</label>
            <div class="relative">
                <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
                    <x-heroicon-o-envelope class="w-4 h-4" />
                </div>
                <input type="email" wire:model="email" placeholder="nama@domain.com" 
                       class="w-full pl-10 pr-4 py-2.5 text-xs rounded-xl bg-slate-50 dark:bg-slate-800/80 border border-slate-200 dark:border-slate-700 text-slate-900 dark:text-white focus:ring-2 focus:ring-sky-500 focus:outline-none font-medium">
            </div>
            @error('email') <span class="text-[11px] text-rose-500 font-semibold block mt-0.5">{{ $message }}</span> @enderror
        </div>

        <!-- Password Input -->
        <div class="space-y-1.5">
            <label class="text-xs font-extrabold text-slate-700 dark:text-slate-300">Kata Sandi</label>
            <div class="relative">
                <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
                    <x-heroicon-o-lock-closed class="w-4 h-4" />
                </div>
                <input type="password" wire:model="password" placeholder="••••••••" 
                       class="w-full pl-10 pr-4 py-2.5 text-xs rounded-xl bg-slate-50 dark:bg-slate-800/80 border border-slate-200 dark:border-slate-700 text-slate-900 dark:text-white focus:ring-2 focus:ring-sky-500 focus:outline-none font-medium">
            </div>
            @error('password') <span class="text-[11px] text-rose-500 font-semibold block mt-0.5">{{ $message }}</span> @enderror
        </div>

        <!-- Remember Me Checkbox -->
        <div class="flex items-center justify-between text-xs pt-1">
            <label class="flex items-center gap-2 text-slate-600 dark:text-slate-400 cursor-pointer">
                <input type="checkbox" wire:model="remember" class="rounded border-slate-300 text-sky-600 focus:ring-sky-500">
                <span class="font-medium">Ingat Sesi Saya</span>
            </label>
        </div>

        <!-- Submit Button -->
        <button type="submit" 
                class="w-full py-3 bg-gradient-to-r from-sky-600 to-indigo-600 hover:from-sky-500 hover:to-indigo-500 text-white rounded-xl text-xs font-bold shadow-lg shadow-sky-500/25 active:scale-[0.99] transition-all flex items-center justify-center gap-2">
            <x-heroicon-o-arrow-right-on-rectangle class="w-4 h-4" />
            <span>Masuk Sekarang</span>
        </button>
    </form>

    <div class="text-center text-[11px] text-slate-400 font-medium pt-2">
        SIASAP — Sistem Pemantauan Kualitas Udara Lingkungan
    </div>

</div>
