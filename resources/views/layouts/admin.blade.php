{{-- Layout panel admin/pengelola: sidebar kiri, menu dari config/navigation.php sesuai hak akses. --}}
@php
    $user = auth()->user();
    $sections = collect(config('navigation'))
        ->filter(fn ($item) => $item['permission'] === null || ($user && $user->hasPermission($item['permission'])))
        ->groupBy('section');
@endphp
<!DOCTYPE html>
<html lang="id">
<head>
    @include('layouts.partials.head')
</head>
<body class="min-h-screen" x-data="{ sidebar: false }">
    <div x-show="sidebar" x-cloak x-transition.opacity @click="sidebar = false" class="fixed inset-0 z-40 bg-slate-900/40 lg:hidden"></div>

    <aside class="fixed inset-y-0 left-0 z-50 flex w-64 -translate-x-full flex-col border-r border-slate-200 bg-white transition-transform lg:translate-x-0"
           :class="sidebar && 'translate-x-0'">
        <div class="flex h-16 shrink-0 items-center justify-between px-5">
            <a href="{{ route('admin.overview') }}" wire:navigate><x-ui.brand :subtitle="false" /></a>
            <span class="rounded-md bg-slate-100 px-2 py-0.5 text-[10px] font-semibold uppercase tracking-wider text-slate-500">Admin</span>
        </div>

        <nav class="flex-1 space-y-6 overflow-y-auto px-3 py-4" aria-label="Navigasi admin">
            @foreach ($sections as $section => $items)
                <div>
                    <p class="eyebrow mb-2 px-3">{{ $section }}</p>
                    <div class="space-y-0.5">
                        @foreach ($items as $item)
                            <a href="{{ route($item['route']) }}" wire:navigate
                               @class(['side-link', 'side-link-active' => request()->routeIs($item['active'] ?? $item['route'])])>
                                <x-dynamic-component :component="$item['icon']" class="size-4.5 shrink-0" />
                                {{ $item['title'] }}
                            </a>
                        @endforeach
                    </div>
                </div>
            @endforeach

            <div>
                <p class="eyebrow mb-2 px-3">Situs Publik</p>
                <a href="{{ route('home') }}" class="side-link">
                    <x-tabler-external-link class="size-4.5 shrink-0" />
                    Lihat Halaman Pengguna
                </a>
            </div>
        </nav>

        @if ($user)
            <div class="shrink-0 border-t border-slate-100 p-3">
                <div class="flex items-center gap-3 rounded-xl px-2 py-2">
                    <span class="flex size-8 shrink-0 items-center justify-center rounded-full bg-slate-900 text-xs font-semibold text-white">
                        {{ str($user->name)->substr(0, 1)->upper() }}
                    </span>
                    <div class="min-w-0 flex-1">
                        <p class="truncate text-sm font-medium text-slate-900">{{ $user->name }}</p>
                        <p class="truncate text-xs text-slate-500">{{ $user->email }}</p>
                    </div>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="btn btn-ghost btn-sm" title="Keluar" aria-label="Keluar">
                            <x-tabler-logout class="size-4" />
                        </button>
                    </form>
                </div>
            </div>
        @endif
    </aside>

    <div class="lg:pl-64">
        <header class="sticky top-0 z-30 flex h-14 items-center gap-3 border-b border-slate-200 bg-white/85 px-4 backdrop-blur lg:hidden">
            <button type="button" class="btn btn-ghost btn-sm" @click="sidebar = true" aria-label="Buka menu">
                <x-tabler-menu-2 class="size-5" />
            </button>
            <x-ui.brand :subtitle="false" />
        </header>

        <main class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
            {{ $slot }}
        </main>
    </div>

    @livewireScripts
</body>
</html>
