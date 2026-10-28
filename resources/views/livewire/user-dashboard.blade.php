<div wire:poll.5s class="space-y-8">
    @if ($cards->isEmpty())
        <x-ui.page-header :icon="'tabler-home-2'" title="Kualitas udara ruangan saat ini"
                          description="Pantau kondisi udara ruangan secara langsung." />

        <div class="card">
            <x-ui.empty-state icon="tabler-antenna-bars-off"
                              title="Belum ada ruangan yang dipantau"
                              description="Perangkat pemantau belum terpasang atau belum aktif. Halaman ini akan terisi sendiri setelah perangkat mulai mengirim data." >
                <a href="{{ route('guide') }}" wire:navigate class="btn btn-secondary btn-sm">
                    <x-tabler-book-2 class="size-4" /> Pelajari kategori kualitas udara
                </a>
            </x-ui.empty-state>
        </div>
    @elseif ($focus)
        {{-- Satu ruangan: tampilkan ruangan itu secara langsung --}}
        @php
            $log = $focus['log'];
            $overall = \App\Support\AirQuality::category($log?->overall_category);
        @endphp

        <div class="space-y-4">
            <x-ui.page-header :icon="'tabler-door'" :title="$focus['room']->name" :description="$focus['room']->description">
                <x-slot:eyebrow>Beranda · Kualitas udara saat ini</x-slot:eyebrow>
                <x-slot:actions>
                    <a href="{{ route('rooms.show', $focus['room']) }}" wire:navigate class="btn btn-secondary btn-sm">
                        <x-tabler-chart-line class="size-4" /> Grafik sensor
                    </a>
                </x-slot:actions>
            </x-ui.page-header>

            @include('livewire.partials.room-status', ['card' => $focus])
        </div>

        @if (! $log)
            <div class="card">
                <x-ui.empty-state icon="tabler-hourglass-high"
                                  title="Menunggu data dari perangkat"
                                  description="Perangkat di ruangan ini belum mengirim data. Halaman akan diperbarui sendiri begitu data pertama masuk." />
            </div>
        @else
            @if (! $focus['online'] && $focus['updated_at'])
                @include('livewire.partials.stale-data-banner', ['updatedAt' => $focus['updated_at']])
            @endif

            <section class="grid grid-cols-1 gap-4 lg:grid-cols-5">
                {{-- Kategori keseluruhan --}}
                <div class="card card-body flex flex-col gap-5 lg:col-span-3">
                    <div class="flex items-start gap-4">
                        <span class="flex size-14 shrink-0 items-center justify-center rounded-2xl {{ $overall['soft'] }}"
                              style="color: {{ $overall['color'] }}">
                            <x-dynamic-component :component="$overall['icon']" class="size-7" />
                        </span>
                        <div class="min-w-0">
                            <p class="text-xs font-medium text-slate-500">Kategori keseluruhan</p>
                            <p class="mt-1 text-3xl font-bold tracking-tight text-slate-900 sm:text-4xl">{{ $overall['label'] }}</p>
                            <p class="mt-1 text-sm text-slate-500">{{ $overall['description'] }}</p>
                        </div>
                    </div>

                    <div class="rounded-xl bg-slate-50 px-4 py-3 text-sm">
                        @if ($focus['dominant'] !== [])
                            <p class="text-xs font-medium text-slate-500">Penyebab utama</p>
                            <p class="mt-0.5 font-semibold text-slate-900">
                                {{ collect($focus['dominant'])->map(fn ($key) => $gases[$key]['symbol'].' ('.$gases[$key]['name'].')')->implode(', ') }}
                            </p>
                        @else
                            <p class="flex items-center gap-1.5 text-slate-600">
                                <x-tabler-circle-check class="size-4 text-emerald-600" />
                                Seluruh gas berada pada kategori Baik.
                            </p>
                        @endif
                    </div>
                </div>

                {{-- Rekomendasi --}}
                @include('livewire.partials.recommendation-card', ['recommendation' => $focus['recommendation'], 'class' => 'lg:col-span-2'])
            </section>

            {{-- Kartu per gas --}}
            <section class="grid grid-cols-2 gap-4 lg:grid-cols-4" aria-label="Konsentrasi gas">
                @foreach ($gases as $key => $gas)
                    <div class="card card-body flex flex-col gap-3" wire:key="gas-{{ $key }}">
                        <div class="flex items-center gap-2.5">
                            <span class="icon-chip icon-chip-sm bg-slate-50" style="color: {{ $gas['color'] }}">
                                <x-dynamic-component :component="$gas['icon']" class="size-4" />
                            </span>
                            <div class="min-w-0 leading-tight">
                                <p class="text-sm font-semibold text-slate-900">{{ $gas['symbol'] }}</p>
                                <p class="truncate text-xs text-slate-500">{{ $gas['name'] }}</p>
                            </div>
                        </div>
                        <p class="num text-2xl font-bold tracking-tight text-slate-900">
                            {{ number_format($log->{$key}, 2, ',', '.') }}<span class="ml-1 text-sm font-medium text-slate-400">ppm</span>
                        </p>
                        <div class="flex flex-wrap items-center gap-x-2 gap-y-1">
                            <x-ui.ku-badge :status="$focus['categories'][$key]" size="sm" />
                            <span class="num text-[11px] text-slate-400">{{ \App\Support\AirQuality::thresholdLabel($key, $focus['categories'][$key]) }}</span>
                        </div>
                    </div>
                @endforeach
            </section>

            <p class="text-xs text-slate-400">
                Angka ppm menunjukkan perubahan kadar gas dibandingkan saat perangkat mulai menyala, bukan hasil alat ukur laboratorium.
            </p>
        @endif
    @else
        {{-- Lebih dari satu ruangan: ringkasan + kartu per ruangan --}}
        @php($worst = \App\Support\AirQuality::category($worstCard['log']?->overall_category ?? null))

        <section class="card card-body">
            <div class="flex flex-col gap-6 lg:flex-row lg:items-center lg:justify-between">
                <div class="flex min-w-0 items-start gap-5">
                    <span class="hidden size-14 shrink-0 items-center justify-center rounded-2xl sm:flex {{ $worst['soft'] }}"
                          style="color: {{ $worst['color'] }}">
                        <x-dynamic-component :component="$worst['icon']" class="size-7" />
                    </span>
                    <div class="min-w-0 space-y-3">
                        <p class="eyebrow">Beranda</p>
                        <h1 class="text-2xl font-bold tracking-tight text-slate-900 sm:text-3xl">Kualitas udara ruangan saat ini</h1>
                        <div class="flex flex-wrap items-center gap-3">
                            <x-ui.ku-badge :status="$worstCard['log']?->overall_category ?? null" size="lg" :icon="true" />
                            @if ($worstCard)
                                <span class="text-sm text-slate-500">Kondisi terburuk di <span class="font-medium text-slate-700">{{ $worstCard['room']->name }}</span></span>
                            @endif
                        </div>
                    </div>
                </div>

                <dl class="grid shrink-0 grid-cols-3 gap-4 border-t border-slate-100 pt-5 sm:gap-8 lg:border-t-0 lg:border-l lg:pt-0 lg:pl-8">
                    <div>
                        <dt class="flex items-center gap-1.5 text-xs text-slate-500"><x-tabler-door class="size-4 text-slate-400" /> Ruangan</dt>
                        <dd class="num mt-1 text-xl font-bold text-slate-900">{{ $cards->count() }}</dd>
                    </div>
                    <div>
                        <dt class="flex items-center gap-1.5 text-xs text-slate-500"><x-tabler-cpu class="size-4 text-slate-400" /> Terhubung</dt>
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

        <section class="space-y-4">
            <div>
                <h2 class="text-base font-semibold text-slate-900">Kondisi per ruangan</h2>
                <p class="mt-0.5 text-sm text-slate-500">Pilih ruangan untuk melihat grafik dan rincian datanya.</p>
            </div>

            <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                @foreach ($cards as $card)
                    @php($log = $card['log'])
                    <article class="card flex flex-col p-5" wire:key="room-{{ $card['room']->id }}">
                        <div class="flex items-start justify-between gap-3">
                            <div class="flex min-w-0 items-start gap-3">
                                <span class="icon-chip icon-chip-sm"><x-tabler-door class="size-4" /></span>
                                <div class="min-w-0">
                                    <h3 class="truncate text-sm font-semibold text-slate-900">
                                        <a href="{{ route('rooms.show', $card['room']) }}" wire:navigate class="hover:text-accent-700">{{ $card['room']->name }}</a>
                                    </h3>
                                    @include('livewire.partials.room-status', ['card' => $card, 'compact' => true])
                                </div>
                            </div>
                            <x-ui.ku-badge :status="$log?->overall_category" size="sm" :icon="true" />
                        </div>

                        @if ($log)
                            @if ($card['dominant'] !== [])
                                <p class="mt-4 text-xs text-slate-500">
                                    Penyebab utama:
                                    <span class="font-semibold text-slate-800">{{ collect($card['dominant'])->map(fn ($key) => $gases[$key]['symbol'])->implode(', ') }}</span>
                                </p>
                            @endif

                            <dl class="mt-4 grid grid-cols-2 gap-2 sm:grid-cols-4">
                                @foreach ($gases as $key => $gas)
                                    <div class="rounded-xl bg-slate-50 px-3 py-2">
                                        <dt class="flex items-center gap-1 text-[11px] font-medium text-slate-500">
                                            <x-dynamic-component :component="$gas['icon']" class="size-3.5" style="color: {{ $gas['color'] }}" />
                                            {{ $gas['symbol'] }}
                                        </dt>
                                        <dd class="num text-sm font-semibold text-slate-800">{{ number_format($log->{$key}, 2, ',', '.') }}</dd>
                                        <dd class="mt-1"><x-ui.ku-badge :status="$card['categories'][$key]" size="sm" /></dd>
                                    </div>
                                @endforeach
                            </dl>

                            @include('livewire.partials.recommendation-card', ['recommendation' => $card['recommendation'], 'compact' => true, 'class' => 'mt-4'])
                        @else
                            <div class="mt-5 flex flex-1 flex-col items-center justify-center rounded-xl bg-slate-50 px-4 py-8 text-center">
                                <x-tabler-hourglass-high class="size-5 text-slate-400" />
                                <p class="mt-2 text-sm font-medium text-slate-700">Menunggu data dari perangkat IoT</p>
                            </div>
                        @endif
                    </article>
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
                        <p class="text-xs font-semibold text-slate-800">{{ $category['label'] }}</p>
                    </div>
                @endforeach
            </div>
            <a href="{{ route('guide') }}" wire:navigate class="btn btn-ghost btn-sm self-start md:self-auto">
                Arti setiap kategori <x-tabler-arrow-right class="size-4" />
            </a>
        </div>
    </section>
</div>
