<div class="bg-white dark:bg-slate-900 rounded-3xl border border-slate-200/90 dark:border-slate-800 shadow-xl p-8 space-y-6 relative overflow-hidden">
    
    <div class="absolute -right-12 -top-12 w-40 h-40 bg-sky-500/10 dark:bg-sky-500/5 rounded-full blur-3xl pointer-events-none"></div>

    <!-- Header & Brand Logo -->
    <div class="text-center space-y-2">
        <div class="inline-flex items-center justify-center w-12 h-12 rounded-2xl bg-gradient-to-tr from-sky-600 to-indigo-600 text-white shadow-lg shadow-sky-500/25 mb-2">
            <x-heroicon-o-signal class="w-6 h-6 stroke-[2.2]" />
        </div>
        <h1 class="text-2xl font-black text-slate-900 dark:text-white tracking-tight">
            Selamat Datang di SIASAP
        </h1>
        <p class="text-xs text-slate-500 dark:text-slate-400 font-medium">
            Silakan masuk untuk mengakses Sistem Pemantauan IoT & QoS
        </p>
    </div>

    <!-- Quick Demo Login Presets Card -->
    <div class="p-4 rounded-2xl bg-slate-50 dark:bg-slate-800/50 border border-slate-200/80 dark:border-slate-800 space-y-2.5">
        <span class="text-[11px] font-extrabold uppercase tracking-wider text-slate-500 dark:text-slate-400 block text-center">
            Pilih Akses Cepat (Uji Coba Demo):
        </span>
        <div class="grid grid-cols-2 gap-2">
            <button wire:click="quickLogin('admin')" type="button" 
                    class="py-2.5 px-3 bg-white dark:bg-slate-700 hover:bg-slate-100 dark:hover:bg-slate-600 text-slate-800 dark:text-white rounded-xl text-xs font-bold border border-slate-200 dark:border-slate-600 shadow-sm transition-all flex items-center justify-center gap-1.5">
                <x-heroicon-o-shield-check class="w-4 h-4 text-indigo-500" />
                <span>Masuk Admin</span>
            </button>
            <button wire:click="quickLogin('user')" type="button" 
                    class="py-2.5 px-3 bg-white dark:bg-slate-700 hover:bg-slate-100 dark:hover:bg-slate-600 text-slate-800 dark:text-white rounded-xl text-xs font-bold border border-slate-200 dark:border-slate-600 shadow-sm transition-all flex items-center justify-center gap-1.5">
                <x-heroicon-o-user class="w-4 h-4 text-emerald-500" />
                <span>Masuk User</span>
            </button>
        </div>
    </div>

    <div class="relative flex py-1 items-center">
        <div class="flex-grow border-t border-slate-200 dark:border-slate-800"></div>
        <span class="flex-shrink mx-3 text-[10px] uppercase font-bold text-slate-400">Atau Masuk Email</span>
        <div class="flex-grow border-t border-slate-200 dark:border-slate-800"></div>
    </div>

    <!-- Login Form -->
    <form wire:submit.prevent="authenticate" class="space-y-4">
        <!-- Email Input -->
        <div class="space-y-1">
            <label class="text-xs font-bold text-slate-700 dark:text-slate-300">Alamat Email</label>
            <div class="relative">
                <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
                    <x-heroicon-o-envelope class="w-4 h-4" />
                </div>
                <input type="email" wire:model="email" placeholder="admin@siasap.test" 
                       class="w-full pl-10 pr-4 py-2.5 text-xs rounded-xl bg-slate-50 dark:bg-slate-800/80 border border-slate-200 dark:border-slate-700 text-slate-900 dark:text-white focus:ring-2 focus:ring-sky-500 focus:outline-none font-medium">
            </div>
            @error('email') <span class="text-[11px] text-rose-500 font-semibold block mt-1">{{ $message }}</span> @enderror
        </div>

        <!-- Password Input -->
        <div class="space-y-1">
            <label class="text-xs font-bold text-slate-700 dark:text-slate-300">Kata Sandi</label>
            <div class="relative">
                <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
                    <x-heroicon-o-lock-closed class="w-4 h-4" />
                </div>
                <input type="password" wire:model="password" placeholder="••••••••" 
                       class="w-full pl-10 pr-4 py-2.5 text-xs rounded-xl bg-slate-50 dark:bg-slate-800/80 border border-slate-200 dark:border-slate-700 text-slate-900 dark:text-white focus:ring-2 focus:ring-sky-500 focus:outline-none font-medium">
            </div>
            @error('password') <span class="text-[11px] text-rose-500 font-semibold block mt-1">{{ $message }}</span> @enderror
        </div>

        <!-- Remember Me Checkbox -->
        <div class="flex items-center justify-between text-xs pt-1">
            <label class="flex items-center gap-2 text-slate-600 dark:text-slate-400 cursor-pointer">
                <input type="checkbox" wire:model="remember" class="rounded border-slate-300 text-sky-600 focus:ring-sky-500">
                <span class="font-medium">Ingat Saya</span>
            </label>
            <span class="text-[11px] text-slate-400">Password: <code>password</code></span>
        </div>

        <!-- Submit Button -->
        <button type="submit" 
                class="w-full py-3 bg-gradient-to-r from-sky-600 to-indigo-600 hover:from-sky-500 hover:to-indigo-500 text-white rounded-xl text-xs font-bold shadow-lg shadow-sky-500/25 active:scale-[0.99] transition-all flex items-center justify-center gap-2">
            <x-heroicon-o-arrow-right-on-rectangle class="w-4 h-4" />
            <span>Masuk Sekarang</span>
        </button>
    </form>

    <div class="text-center pt-2">
        <span class="text-[11px] text-slate-400">SIASAP IoT Gateway & QoS Lab System</span>
    </div>

</div>
