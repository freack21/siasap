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
                <input id="email" type="email" wire:model="email" class="input" placeholder="nama@domain.com" autocomplete="email" autofocus required>
                @error('email') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
            </div>

            <div>
                <label for="password" class="label">Kata sandi</label>
                <input id="password" type="password" wire:model="password" class="input" autocomplete="current-password" required>
                @error('password') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
            </div>

            <label class="flex items-center gap-2 text-sm text-slate-600">
                <input type="checkbox" wire:model="remember" class="size-4 rounded border-slate-300 text-accent-600 focus:ring-accent-500">
                Ingat saya
            </label>

            <button type="submit" class="btn btn-primary w-full" wire:loading.attr="disabled" wire:target="authenticate">
                <span wire:loading.remove wire:target="authenticate">Masuk</span>
                <span wire:loading wire:target="authenticate">Memproses…</span>
            </button>
        </form>
    </div>

    <p class="text-center">
        <a href="{{ route('home') }}" class="text-sm font-medium text-slate-500 hover:text-slate-900">← Kembali ke halaman utama</a>
    </p>
</div>
