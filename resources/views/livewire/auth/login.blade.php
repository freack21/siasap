<div class="space-y-6">
    <div class="flex justify-center">
        <a href="{{ route('home') }}"><x-ui.brand /></a>
    </div>

    <div class="card card-body">
        <div class="mb-6">
            <h1 class="text-xl font-bold tracking-tight text-slate-900">Masuk Panel Pengelola</h1>
            <p class="mt-1 text-sm text-slate-500">Khusus pengelola dan peneliti. Pengunjung dapat melihat kualitas udara tanpa masuk.</p>
        </div>

        <form wire:submit="authenticate" class="space-y-4">
            <div>
                <label for="email" class="label">Email</label>
                <div class="relative">
                    <x-tabler-mail class="pointer-events-none absolute top-1/2 left-3 size-4 -translate-y-1/2 text-slate-400" />
                    <input id="email" type="email" wire:model="email" class="input pl-10" placeholder="nama@domain.com" autocomplete="email" autofocus required>
                </div>
                @error('email')
                    <p class="mt-1 flex items-center gap-1 text-xs text-rose-600">
                        <x-tabler-alert-circle class="size-3.5 shrink-0" />
                        {{ $message }}
                    </p>
                @enderror
            </div>

            <div>
                <label for="password" class="label">Kata sandi</label>
                <div class="relative">
                    <x-tabler-lock class="pointer-events-none absolute top-1/2 left-3 size-4 -translate-y-1/2 text-slate-400" />
                    <input id="password" type="password" wire:model="password" class="input pl-10" autocomplete="current-password" required>
                </div>
                @error('password')
                    <p class="mt-1 flex items-center gap-1 text-xs text-rose-600">
                        <x-tabler-alert-circle class="size-3.5 shrink-0" />
                        {{ $message }}
                    </p>
                @enderror
            </div>

            <label class="flex items-center gap-2 text-sm text-slate-600">
                <input type="checkbox" wire:model="remember" class="size-4 rounded border-slate-300 text-accent-600 focus:ring-accent-500">
                Ingat saya
            </label>

            <button type="submit" class="btn btn-primary w-full" wire:loading.attr="disabled" wire:target="authenticate">
                <span wire:loading.remove wire:target="authenticate" class="inline-flex items-center gap-2">
                    <x-tabler-login-2 class="size-4" />
                    Masuk
                </span>
                <span wire:loading wire:target="authenticate">Memproses…</span>
            </button>
        </form>
    </div>

    <p class="text-center">
        <a href="{{ route('home') }}" class="inline-flex items-center gap-1.5 text-sm font-medium text-slate-500 hover:text-slate-900">
            <x-tabler-arrow-left class="size-4" />
            Kembali ke halaman utama
        </a>
    </p>
</div>
