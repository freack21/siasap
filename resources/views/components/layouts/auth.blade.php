<!DOCTYPE html>
<html lang="id">
<head>
    @include('layouts.partials.head')
</head>
<body class="flex min-h-screen items-center justify-center bg-slate-50 px-4 py-12">
    <main class="w-full max-w-sm">
        {{ $slot }}
    </main>

    @livewireScripts
</body>
</html>
