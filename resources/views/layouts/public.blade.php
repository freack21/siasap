{{-- Layout halaman pengguna (penghuni ruangan): navigasi atas, tanpa sidebar. --}}
@php
    $links = [
        ['route' => 'home', 'label' => 'Beranda', 'icon' => 'tabler-home-2', 'active' => ['home', 'rooms.show']],
        ['route' => 'history', 'label' => 'Riwayat', 'icon' => 'tabler-history', 'active' => ['history']],
        ['route' => 'guide', 'label' => 'Panduan', 'icon' => 'tabler-book-2', 'active' => ['guide']],
        ['route' => 'survey', 'label' => 'Kuesioner', 'icon' => 'tabler-clipboard-check', 'active' => ['survey']],
    ];
@endphp
<!DOCTYPE html>
<html lang="id">
<head>
    @include('layouts.partials.head')
</head>
<body class="min-h-screen">
    <header x-data="{ open: false }" class="sticky top-0 z-40 border-b border-slate-200 bg-white/85 backdrop-blur">
        <div class="mx-auto grid h-16 max-w-6xl grid-cols-[1fr_auto] items-center gap-4 px-4 sm:px-6 md:grid-cols-[1fr_auto_1fr]">
            <a href="{{ route('home') }}" wire:navigate class="justify-self-start">
                <x-ui.brand />
            </a>

            <nav class="hidden items-center gap-1 md:flex" aria-label="Navigasi utama">
                @foreach ($links as $link)
                    <a href="{{ route($link['route']) }}" wire:navigate
                       @class(['nav-link', 'nav-link-active' => request()->routeIs(...$link['active'])])>
                        <x-dynamic-component :component="$link['icon']" class="size-4" />
                        {{ $link['label'] }}
                    </a>
                @endforeach
            </nav>

            <div class="hidden justify-self-end md:block">
                @auth
                    <a href="{{ route('admin.overview') }}" class="btn btn-secondary btn-sm">
                        <x-tabler-layout-dashboard class="size-4" /> Panel Admin
                    </a>
                @else
                    <a href="{{ route('login') }}" class="btn btn-ghost btn-sm"><x-tabler-login-2 class="size-4" /> Masuk Admin</a>
                @endauth
            </div>

            <button type="button" class="btn btn-ghost btn-sm justify-self-end md:hidden" @click="open = !open" :aria-expanded="open" aria-label="Buka menu">
                <x-tabler-menu-2 class="size-5" x-show="!open" />
                <x-tabler-x class="size-5" x-show="open" x-cloak />
            </button>
        </div>

        <nav x-show="open" x-cloak x-transition.opacity class="border-t border-slate-100 bg-white px-4 py-3 md:hidden" aria-label="Navigasi seluler">
            <div class="flex flex-col gap-1">
                @foreach ($links as $link)
                    <a href="{{ route($link['route']) }}" wire:navigate
                       @class(['nav-link', 'nav-link-active' => request()->routeIs(...$link['active'])])>
                        <x-dynamic-component :component="$link['icon']" class="size-4" />
                        {{ $link['label'] }}
                    </a>
                @endforeach
                <div class="my-2 border-t border-slate-100"></div>
                @auth
                    <a href="{{ route('admin.overview') }}" class="nav-link"><x-tabler-layout-dashboard class="size-4" /> Panel Admin</a>
                @else
                    <a href="{{ route('login') }}" class="nav-link"><x-tabler-login-2 class="size-4" /> Masuk Admin</a>
                @endauth
            </div>
        </nav>
    </header>

    <main class="mx-auto max-w-6xl px-4 py-8 sm:px-6 sm:py-10">
        {{ $slot }}
    </main>

    <footer class="border-t border-slate-200 bg-white">
        <div class="mx-auto flex max-w-6xl flex-col gap-1 px-4 py-6 text-xs text-slate-500 sm:flex-row sm:items-center sm:justify-between sm:px-6">
            <p class="inline-flex items-center gap-1.5"><x-tabler-wind class="size-4 text-accent-600" /> SIASAP — Sistem Monitoring Kualitas Udara Ruangan Berbasis IoT</p>
            <p class="inline-flex items-center gap-1.5"><x-tabler-refresh class="size-3.5" /> &copy; {{ now()->year }} · Data diperbarui otomatis setiap beberapa detik</p>
        </div>
    </footer>

    @livewireScripts
</body>
</html>
