{{-- Halaman hasil ekspor pengujian kondisi ruangan: tanpa menu SIMKU dan tidak diindeks mesin pencari. --}}
<!DOCTYPE html>
<html lang="id">
<head>
    @include('layouts.partials.head', ['title' => 'Hasil Pengujian Ruangan · SIMKU'])
    <meta name="robots" content="noindex, nofollow">
</head>
<body class="min-h-screen">
    <header class="border-b border-slate-200 bg-white">
        <div class="mx-auto flex h-16 max-w-5xl items-center justify-between gap-4 px-4 sm:px-6">
            <x-ui.brand />
            <span class="inline-flex items-center gap-1.5 rounded-full border border-amber-200 bg-amber-50 px-3 py-1 text-xs font-semibold text-amber-800">
                <x-tabler-lock class="size-3.5" /> Halaman peneliti, tidak untuk publik
            </span>
        </div>
    </header>

    <main class="mx-auto max-w-5xl space-y-6 px-4 py-8 sm:px-6 sm:py-10">
        <div class="space-y-1">
            <h1 class="text-2xl font-bold text-slate-900">Hasil Pengujian Kondisi Ruangan</h1>
            <p class="text-sm text-slate-600">Hasil perintah <code class="rounded bg-slate-100 px-1">php artisan telemetry:ekspor-uji-ruangan</code>, dikelompokkan per tanggal pengujian.</p>
        </div>

        @forelse ($exports as $export)
            <section class="space-y-4 rounded-xl border border-slate-200 bg-white p-5">
                <div class="flex flex-wrap items-center justify-between gap-3">
                    <h2 class="text-lg font-semibold text-slate-900">{{ \Illuminate\Support\Carbon::parse($export['date'])->translatedFormat('l, j F Y') }}</h2>
                    <div class="flex flex-wrap gap-2">
                        @foreach ($export['files'] as $file)
                            <a href="{{ route('uji-ruangan.download', ['date' => $export['date'], 'file' => $file]) }}"
                               class="inline-flex items-center gap-1.5 rounded-lg border border-slate-200 px-3 py-1.5 text-sm font-medium text-slate-700 hover:bg-slate-50">
                                <x-tabler-download class="size-4" /> {{ $file }}
                            </a>
                        @endforeach
                    </div>
                </div>
                @if ($export['summary'])
                    <pre class="overflow-x-auto whitespace-pre rounded-lg bg-slate-50 p-4 text-xs leading-relaxed text-slate-800">{{ $export['summary'] }}</pre>
                @endif
            </section>
        @empty
            <div class="rounded-xl border border-dashed border-slate-300 bg-white p-8 text-center text-sm text-slate-600">
                Belum ada hasil ekspor. Jalankan perintah ekspor di server, lalu muat ulang halaman ini.
            </div>
        @endforelse
    </main>
</body>
</html>
