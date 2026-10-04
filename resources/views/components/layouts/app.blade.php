<!DOCTYPE html>
<html lang="id" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $title ?? 'SIASAP — Dashboard Pemantauan IoT & Benchmark QoS' }}</title>

    <!-- Plus Jakarta Sans Font -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:ital,wght@0,300;0,400;0,500;0,600;0,700;0,800;1,400&display=swap" rel="stylesheet">

    <!-- Anti-FOUC Dark Mode Initialization Script -->
    <script>
        if (localStorage.getItem('color-theme') === 'dark' || (!('color-theme' in localStorage) && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
            document.documentElement.classList.add('dark');
        } else {
            document.documentElement.classList.remove('dark');
        }
    </script>

    @if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    @endif
    @livewireStyles
</head>
<body class="h-full bg-slate-50 dark:bg-slate-950 text-slate-800 dark:text-slate-100 font-sans antialiased transition-colors duration-200"
      x-data="{ sidebarOpen: false }">

    <div class="min-h-full flex flex-col lg:flex-row">
        
        <!-- Mobile Sidebar Overlay Backdrop -->
        <div x-show="sidebarOpen" 
             x-transition:enter="transition-opacity ease-linear duration-300"
             x-transition:enter-start="opacity-0"
             x-transition:enter-end="opacity-100"
             x-transition:leave="transition-opacity ease-linear duration-300"
             x-transition:leave-start="opacity-100"
             x-transition:leave-end="opacity-0"
             @click="sidebarOpen = false"
             class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm z-40 lg:hidden">
        </div>

        <!-- Collapsible Sidebar -->
        <aside class="fixed inset-y-0 left-0 z-50 w-72 bg-white dark:bg-slate-900 border-r border-slate-200/80 dark:border-slate-800 flex flex-col justify-between transform transition-transform duration-300 ease-in-out lg:translate-x-0 lg:static lg:w-64 shrink-0 shadow-sm"
               :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full'">
            
            <div class="flex flex-col h-full">
                <!-- Sidebar Header & Brand Logo -->
                <div class="h-16 px-6 border-b border-slate-100 dark:border-slate-800 flex items-center justify-between">
                    <a href="{{ route('telemetry') }}" class="flex items-center gap-3 group">
                        <div class="w-9 h-9 rounded-xl bg-gradient-to-tr from-sky-600 to-indigo-600 flex items-center justify-center text-white shadow-md shadow-sky-500/20 group-hover:scale-105 transition-transform">
                            <x-heroicon-o-signal class="w-5 h-5 stroke-[2.2]" />
                        </div>
                        <div>
                            <div class="flex items-center gap-1.5">
                                <span class="font-extrabold text-lg tracking-tight bg-gradient-to-r from-slate-900 via-sky-800 to-indigo-900 dark:from-white dark:via-sky-300 dark:to-indigo-300 bg-clip-text text-transparent">
                                    SIASAP
                                </span>
                                <span class="px-1.5 py-0.5 text-[9px] font-extrabold uppercase rounded bg-sky-500/10 text-sky-600 dark:bg-sky-400/10 dark:text-sky-400 border border-sky-500/20">
                                    v2.0
                                </span>
                            </div>
                            <p class="text-[10px] text-slate-400 font-semibold tracking-wider uppercase">
                                IoT & QoS Benchmark
                            </p>
                        </div>
                    </a>

                    <!-- Close Button for Mobile Drawer -->
                    <button @click="sidebarOpen = false" class="lg:hidden text-slate-400 hover:text-slate-600">
                        <x-heroicon-o-x-mark class="w-6 h-6" />
                    </button>
                </div>

                <!-- User Profile Card Component in Sidebar -->
                <div class="p-4 mx-3 my-3 rounded-xl bg-slate-50 dark:bg-slate-800/60 border border-slate-100 dark:border-slate-800/80 flex items-center gap-3">
                    <div class="w-10 h-10 rounded-full bg-gradient-to-tr from-sky-600 to-indigo-600 text-white font-black flex items-center justify-center text-sm shadow-md">
                        AD
                    </div>
                    <div class="overflow-hidden">
                        <h4 class="text-xs font-extrabold text-slate-900 dark:text-white truncate">Administrator SIASAP</h4>
                        <span class="inline-block text-[10px] font-bold text-sky-600 dark:text-sky-400 uppercase tracking-wider">Super Admin</span>
                    </div>
                </div>

                <!-- Dynamic Navigation Menu Items -->
                <nav class="flex-grow px-3 py-2 space-y-6 overflow-y-auto">
                    @php
                        $navSections = \App\Models\SystemNavigation::active()->get()->groupBy('section');
                    @endphp

                    @foreach ($navSections as $section => $navs)
                        <div class="space-y-1">
                            <span class="px-3 text-[10px] font-extrabold tracking-wider uppercase text-slate-400 dark:text-slate-500">
                                {{ $section }}
                            </span>
                            <div class="mt-1 space-y-0.5">
                                @foreach ($navs as $nav)
                                    @php
                                        $isActive = $nav->route_name && request()->routeIs($nav->route_name);
                                        $url = $nav->route_name ? route($nav->route_name) : ($nav->url ?? '#');
                                    @endphp
                                    <a href="{{ $url }}" 
                                       class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-xs font-semibold transition-all group {{ $isActive ? 'bg-sky-500/10 text-sky-700 dark:bg-sky-400/15 dark:text-sky-300 font-bold border-l-4 border-sky-500 shadow-sm' : 'text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white hover:bg-slate-100 dark:hover:bg-slate-800/60' }}">
                                        
                                        <x-dynamic-component :component="$nav->icon" class="w-4 h-4 shrink-0 transition-transform group-hover:scale-110 {{ $isActive ? 'text-sky-600 dark:text-sky-400' : 'text-slate-400 group-hover:text-slate-600 dark:group-hover:text-slate-200' }}" />
                                        
                                        <span class="truncate">{{ $nav->title }}</span>
                                    </a>
                                @endforeach
                            </div>
                        </div>
                    @endforeach
                </nav>

                <!-- Sidebar Footer Status -->
                <div class="p-4 border-t border-slate-100 dark:border-slate-800 text-[11px] text-slate-400 flex items-center justify-between bg-slate-50/50 dark:bg-slate-900/50">
                    <div class="flex items-center gap-2">
                        <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
                        <span class="font-semibold text-slate-600 dark:text-slate-300">Gateway Active</span>
                    </div>
                    <span class="font-mono text-[10px]">3 Nodes</span>
                </div>
            </div>
        </aside>

        <!-- Main Content Area -->
        <div class="flex-1 flex flex-col min-w-0 overflow-hidden">
            
            <!-- Top Navbar Header Bar -->
            <header class="sticky top-0 z-30 h-16 bg-white/80 dark:bg-slate-900/80 backdrop-blur-md border-b border-slate-200/80 dark:border-slate-800 px-4 sm:px-6 flex items-center justify-between gap-4">
                
                <div class="flex items-center gap-3">
                    <!-- Mobile Hamburger Menu Button -->
                    <button @click="sidebarOpen = !sidebarOpen" 
                            class="lg:hidden p-2 rounded-xl text-slate-500 hover:bg-slate-100 dark:hover:bg-slate-800 focus:outline-none">
                        <x-heroicon-o-bars-3 class="w-6 h-6" />
                    </button>

                    <!-- Breadcrumbs / Page Title Indicator -->
                    <div class="flex items-center gap-2 text-xs font-semibold text-slate-500 dark:text-slate-400">
                        <span>SIASAP</span>
                        <x-heroicon-o-chevron-right class="w-3.5 h-3.5 text-slate-300" />
                        <span class="text-slate-900 dark:text-white font-bold">{{ $title ?? 'Dashboard Monitoring' }}</span>
                    </div>
                </div>

                <!-- Right Action Bar: Theme Switcher & Quick Status -->
                <div class="flex items-center gap-3">
                    <!-- Live Rest API Ingestion Pill -->
                    <div class="hidden sm:flex items-center gap-2 px-3 py-1.5 rounded-full bg-emerald-500/10 text-emerald-600 dark:bg-emerald-400/10 dark:text-emerald-400 border border-emerald-500/20 text-xs font-bold">
                        <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
                        <span>REST API Ready (`/api/telemetry`)</span>
                    </div>

                    <!-- Theme Switcher Toggle (Light default / Dark mode) -->
                    <button id="theme-toggle" type="button" 
                            class="p-2 rounded-xl text-slate-500 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800 focus:outline-none transition-all border border-slate-200/80 dark:border-slate-800"
                            title="Beralih Tema Terang / Gelap">
                        <!-- Sun Icon (shown in dark mode) -->
                        <svg id="theme-toggle-light-icon" class="hidden w-5 h-5 text-amber-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 3v2.25m0 13.5V21m8.966-8.966h-2.25M4.5 12H2.25m15.364-6.364l-1.591 1.591M6.758 17.242l-1.591 1.591m12.728 0l-1.591-1.591M6.758 6.758L5.167 5.167M12 7.5a4.5 4.5 0 100 9 4.5 4.5 0 000-9z" />
                        </svg>
                        <!-- Moon Icon (shown in light mode) -->
                        <svg id="theme-toggle-dark-icon" class="w-5 h-5 text-indigo-600 dark:text-indigo-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M21.752 15.002A9.718 9.718 0 0118 15.75c-5.385 0-9.75-4.365-9.75-9.75 0-1.33.266-2.597.748-3.752A9.753 9.753 0 003 11.25C3 16.635 7.365 21 12.75 21a9.753 9.753 0 009.002-5.998z" />
                        </svg>
                    </button>
                </div>
            </header>

            <!-- Main Page View Slot -->
            <main class="flex-grow p-4 sm:p-6 lg:p-8 max-w-7xl w-full mx-auto space-y-6">
                {{ $slot }}
            </main>

            <!-- Footer -->
            <footer class="mt-auto border-t border-slate-200/80 dark:border-slate-800 py-4 px-6 text-xs text-slate-500 dark:text-slate-400 bg-white/50 dark:bg-slate-900/50 flex flex-col sm:flex-row items-center justify-between gap-2">
                <div>
                    <strong>SIASAP IoT Dashboard</strong> — Air Quality Sensor Monitoring & QoS Network Benchmarking
                </div>
                <div class="font-mono text-[11px]">
                    SetupRbacSeeder & SystemNavigation Active
                </div>
            </footer>
        </div>
    </div>

    @livewireScripts

    <!-- Theme Toggle Script -->
    <script>
        const themeToggleBtn = document.getElementById('theme-toggle');
        const themeToggleDarkIcon = document.getElementById('theme-toggle-dark-icon');
        const themeToggleLightIcon = document.getElementById('theme-toggle-light-icon');

        function updateThemeIcons() {
            if (document.documentElement.classList.contains('dark')) {
                themeToggleLightIcon.classList.remove('hidden');
                themeToggleDarkIcon.classList.add('hidden');
            } else {
                themeToggleLightIcon.classList.add('hidden');
                themeToggleDarkIcon.classList.remove('hidden');
            }
        }

        updateThemeIcons();

        themeToggleBtn.addEventListener('click', function() {
            if (document.documentElement.classList.contains('dark')) {
                document.documentElement.classList.remove('dark');
                localStorage.setItem('color-theme', 'light');
            } else {
                document.documentElement.classList.add('dark');
                localStorage.setItem('color-theme', 'dark');
            }
            updateThemeIcons();
        });
    </script>
</body>
</html>
