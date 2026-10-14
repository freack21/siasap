<div wire:poll.5s class="space-y-8">
    @if ($cards->isEmpty())
        <x-ui.page-header :icon="'tabler-home-2'" title="Kualitas udara ruangan saat ini"
                          description="Pantau kondisi udara di setiap ruangan secara langsung dari sensor." />

        <div class="card">
            <x-ui.empty-state icon="tabler-antenna-bars-off"
                              title="Belum ada ruangan yang dipantau"
                              description="Perangkat sensor belum dipasang atau belum diaktifkan oleh pengelola. Halaman ini akan terisi otomatis setelah sensor mulai mengirim data." >
                <a href="{{ route('guide') }}" wire:navigate class="btn btn-secondary btn-sm">
                    <x-tabler-book-2 class="size-4" /> Pelajari kategori kualitas udara
                </a>
            </x-ui.empty-state>
        </div>
    @else
        {{-- Ringkasan keseluruhan --}}
        <section class="card card-body">
            <div class="flex flex-col gap-6 lg:flex-row lg:items-center lg:justify-between">
                <div class="flex min-w-0 items-start gap-5">
                    <span class="hidden size-14 shrink-0 items-center justify-center rounded-2xl sm:flex {{ $overallCategory['soft'] }}"
                          style="color: {{ $overallCategory['color'] }}">
                        <x-dynamic-component :component="$overallCategory['icon']" class="size-7" />
                    </span>
                    <div class="min-w-0 space-y-3">
                        <p class="eyebrow">Beranda</p>
                        <h1 class="text-2xl font-bold tracking-tight text-slate-900 sm:text-3xl">Kualitas udara ruangan saat ini</h1>
                        <div class="flex flex-wrap items-center gap-3">
                            <x-ui.ku-badge :status="$overallStatus" size="lg" :icon="true" />
                            @if ($worstRoom && $cards->count() > 1)
                                <span class="text-sm text-slate-500">Kondisi terburuk di <span class="font-medium text-slate-700">{{ $worstRoom->name }}</span></span>
                            @endif
                        </div>
                        <p class="max-w-xl text-sm text-slate-600">{{ $overallCategory['advice'] }}</p>
                    </div>
                </div>

                <dl class="grid shrink-0 grid-cols-3 gap-4 border-t border-slate-100 pt-5 sm:gap-8 lg:border-t-0 lg:border-l lg:pt-0 lg:pl-8">
                    <div>
                        <dt class="flex items-center gap-1.5 text-xs text-slate-500"><x-tabler-door class="size-4 text-slate-400" /> Ruangan</dt>
                        <dd class="num mt-1 text-xl font-bold text-slate-900">{{ $cards->count() }}</dd>
                    </div>
                    <div>
                        <dt class="flex items-center gap-1.5 text-xs text-slate-500"><x-tabler-cpu class="size-4 text-slate-400" /> Sensor aktif</dt>
                        <dd class="num mt-1 text-xl font-bold text-slate-900">
                            {{ $devicesOnline }}<span class="text-sm font-medium text-slate-400">/{{ $devicesTotal }}</span>
                        </dd>
                    </div>
                    <div>
                        <dt class="flex items-center gap-1.5 text-xs text-slate-500"><x-tabler-clock class="size-4 text-slate-400" /> Diperbarui</dt>
                        <dd class="mt-1 text-sm font-semibold text-slate-900">
                            {{ $lastUpdate ? $lastUpdate->locale('id')->diffForHumans() : 'Belum ada' }}
                        </dd>
                    </div>
                </dl>
            </div>
        </section>

        {{-- Kartu ruangan --}}
        <section class="space-y-4">
            <div class="flex items-end justify-between gap-3">
                <div>
                    <h2 class="text-base font-semibold text-slate-900">Kondisi per ruangan</h2>
                    <p class="mt-0.5 text-sm text-slate-500">Pilih ruangan untuk melihat grafik dan rincian pembacaan.</p>
                </div>
            </div>

            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
                @foreach ($cards as $card)
                    @php($log = $card['log'])
                    <a href="{{ route('rooms.show', $card['room']) }}" wire:navigate wire:key="room-{{ $card['room']->id }}"
                       class="card group flex flex-col p-5 transition hover:border-slate-300 focus:outline-none focus-visible:ring-2 focus-visible:ring-accent-500">
                        <div class="flex items-start justify-between gap-3">
                            <div class="flex min-w-0 items-start gap-3">
                                <span class="icon-chip icon-chip-sm">
                                    <x-tabler-door class="size-4" />
                                </span>
                                <div class="min-w-0">
                                    <h3 class="truncate text-sm font-semibold text-slate-900 group-hover:text-accent-700">{{ $card['room']->name }}</h3>
                                    <p class="mt-1 flex items-center gap-1.5 text-xs {{ $card['online'] ? 'text-slate-500' : 'text-slate-400' }}">
                                        @if ($card['online'])
                                            <x-tabler-wifi class="size-3.5 text-accent-600" />
                                        @else
                                            <x-tabler-wifi-off class="size-3.5" />
                                        @endif
                                        {{ $card['online'] ? 'Sensor terhubung' : 'Sensor terputus' }}
                                    </p>
                                </div>
                            </div>
                            <x-ui.ku-badge :status="$log?->ku_status" size="sm" :icon="true" />
                        </div>

                        @if ($log)
                            <div class="mt-5 flex items-end justify-between gap-3">
                                <div>
                                    <p class="text-xs text-slate-500">Indeks KU</p>
                                    <p class="num text-4xl font-bold tracking-tight text-slate-900">{{ number_format($log->ku_value, 0) }}</p>
                                </div>
                                @if ($card['dominant'])
                                    @php($dominantGas = $gases[$card['dominant']] ?? null)
                                    <div class="text-right text-xs text-slate-500">
                                        <p>Penyebab utama</p>
                                        <p class="mt-0.5 inline-flex items-center gap-1.5 text-sm font-semibold text-slate-800">
                                            @if ($dominantGas)
                                                <x-dynamic-component :component="$dominantGas['icon']" class="size-4" style="color: {{ $dominantGas['color'] }}" />
                                            @endif
                                            {{ $dominantGas['symbol'] ?? strtoupper($card['dominant']) }}
                                        </p>
                                    </div>
                                @endif
                            </div>

                            <dl class="mt-4 grid grid-cols-2 gap-2">
                                @foreach ($gases as $key => $gas)
                                    <div class="rounded-xl bg-slate-50 px-3 py-2">
                                        <dt class="flex items-center gap-1 text-[11px] font-medium text-slate-500">
                                            <x-dynamic-component :component="$gas['icon']" class="size-3.5" style="color: {{ $gas['color'] }}" />
                                            {{ $gas['symbol'] }}
                                        </dt>
                                        <dd class="num text-sm font-semibold text-slate-800">
                                            {{ number_format($log->{$key}, 1, ',', '.') }}<span class="ml-0.5 text-[11px] font-normal text-slate-400">ppm</span>
                                        </dd>
                                    </div>
                                @endforeach
                            </dl>

                            <p class="mt-4 flex items-center gap-1.5 text-xs text-slate-400">
                                <x-tabler-clock class="size-3.5" />
                                Diperbarui {{ $card['updated_at']->locale('id')->diffForHumans() }}
                            </p>
                        @else
                            <div class="mt-5 flex flex-1 flex-col items-center justify-center rounded-xl bg-slate-50 px-4 py-8 text-center">
                                <x-tabler-antenna-bars-off class="size-5 text-slate-400" />
                                <p class="mt-2 text-sm font-medium text-slate-700">Belum ada data</p>
                                <p class="mt-0.5 text-xs text-slate-500">Sensor di ruangan ini belum mengirim pembacaan.</p>
                            </div>
                        @endif
                    </a>
                @endforeach
            </div>
        </section>
    @endif

    {{-- Legenda kategori --}}
    <section class="card card-body">
        <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
            <div class="grid flex-1 grid-cols-2 gap-3 sm:grid-cols-4">
                @foreach ($categories as $category)
                    <div class="flex items-center gap-2.5">
                        <span class="flex size-8 shrink-0 items-center justify-center rounded-lg {{ $category['soft'] }}" style="color: {{ $category['color'] }}">
                            <x-dynamic-component :component="$category['icon']" class="size-4" />
                        </span>
                        <div class="leading-tight">
                            <p class="text-xs font-semibold text-slate-800">{{ $category['label'] }}</p>
                            <p class="num text-[11px] text-slate-500">{{ $category['range'] }}</p>
                        </div>
                    </div>
                @endforeach
            </div>
            <a href="{{ route('guide') }}" wire:navigate class="btn btn-ghost btn-sm self-start md:self-auto">
                Arti setiap kategori <x-tabler-arrow-right class="size-4" />
            </a>
        </div>
    </section>
</div>
