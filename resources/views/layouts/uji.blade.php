{{-- Layout halaman uji tersembunyi: tanpa menu SIMKU dan tidak diindeks mesin pencari. --}}
<!DOCTYPE html>
<html lang="id">
<head>
    @include('layouts.partials.head')
    <meta name="robots" content="noindex, nofollow">
</head>
<body class="min-h-screen">
    <header class="border-b border-slate-200 bg-white">
        <div class="mx-auto flex h-16 max-w-7xl items-center justify-between gap-4 px-4 sm:px-6">
            <x-ui.brand />
            <span class="inline-flex items-center gap-1.5 rounded-full border border-amber-200 bg-amber-50 px-3 py-1 text-xs font-semibold text-amber-800">
                <x-tabler-flask class="size-3.5" /> Halaman uji, tidak untuk publik
            </span>
        </div>
    </header>

    <main class="mx-auto max-w-7xl px-4 py-8 sm:px-6 sm:py-10">
        {{ $slot }}
    </main>

    @livewireScripts
</body>
</html>
