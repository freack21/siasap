<div wire:poll.5s class="space-y-10">
    <x-ui.page-header
        :icon="'tabler-cpu'"
        title="Status Perangkat"
        description="Lihat apakah perangkat pemantau udara sedang mengirim data ke SIMKU.">
        <x-slot:eyebrow>Perangkat</x-slot:eyebrow>
    </x-ui.page-header>

    <section class="space-y-4">
        @if ($devices->isEmpty())
            <div class="card">
                <x-ui.empty-state icon="tabler-antenna-bars-off"
                                  title="Belum ada perangkat"
                                  description="Belum ada perangkat pemantau yang terhubung ke SIMKU." />
            </div>
        @else
            <div class="grid grid-cols-1 gap-4 lg:grid-cols-2">
                @foreach ($devices as $device)
                    <article wire:key="device-{{ $device['node']->id }}" class="card card-body space-y-4">
                        <div class="flex items-start justify-between gap-3">
                            <div class="flex min-w-0 items-center gap-3">
                                <span class="icon-chip">
                                    <x-tabler-cpu class="size-5" />
                                </span>
                                <div class="min-w-0">
                                    <h2 class="text-base font-semibold text-slate-900">{{ $device['node']->name }}</h2>
                                    <p class="truncate text-xs text-slate-500">
                                        {{ $device['node']->room?->name ?? 'Belum ditempatkan di ruangan' }}
                                        · Kode {{ $device['node']->code }}
                                        @unless ($device['node']->is_active)
                                            · Nonaktif
                                        @endunless
                                    </p>
                                </div>
                            </div>
                            @if ($device['connected'])
                                <span class="inline-flex items-center gap-1.5 rounded-full bg-emerald-50 px-2.5 py-1 text-xs font-medium text-emerald-700 ring-1 ring-emerald-600/20 ring-inset">
                                    <x-tabler-plug-connected class="size-3.5" /> Terhubung
                                </span>
                            @else
                                <span class="inline-flex items-center gap-1.5 rounded-full bg-slate-100 px-2.5 py-1 text-xs font-medium text-slate-600 ring-1 ring-slate-500/20 ring-inset">
                                    <x-tabler-plug-connected-x class="size-3.5" /> Terputus
                                </span>
                            @endif
                        </div>

                        <p class="rounded-xl {{ $device['connected'] ? 'bg-emerald-50 text-emerald-800' : 'bg-slate-50 text-slate-600' }} px-3.5 py-3 text-sm">
                            @if ($device['connected'])
                                Perangkat sedang mengirim data. Kondisi udara yang tampil adalah kondisi terkini.
                            @elseif ($device['last_seen'])
                                Tidak ada data baru lebih dari {{ $onlineSeconds }} detik. Kondisi yang tampil adalah data terakhir, bukan kondisi terkini.
                            @else
                                Perangkat belum pernah mengirim data.
                            @endif
                        </p>

                        <dl class="space-y-4 border-t border-slate-100 pt-4 text-sm">
                            <div>
                                <dt class="flex items-center gap-1.5 text-xs text-slate-500"><x-tabler-clock class="size-4 text-slate-400" /> Data terakhir diterima</dt>
                                <dd class="mt-1 font-medium text-slate-900">
                                    @if ($device['last_seen'])
                                        {{ $device['last_seen']->locale('id')->diffForHumans() }}
                                        <span class="num block text-xs font-normal text-slate-500">{{ $device['last_seen']->translatedFormat('d M Y H:i:s') }}</span>
                                    @else
                                        <span class="text-slate-500">Belum pernah mengirim data</span>
                                    @endif
                                </dd>
                            </div>
                            <div>
                                <dt class="mb-2 text-xs text-slate-500">Gas yang dipantau</dt>
                                <dd class="flex flex-wrap gap-2">
                                    @foreach ($gases as $key => $gas)
                                        <span class="inline-flex items-center gap-1.5 rounded-lg bg-slate-50 px-2.5 py-1 text-xs ring-1 ring-slate-200 ring-inset">
                                            <x-dynamic-component :component="$gas['icon']" class="size-3.5" style="color: {{ $gas['color'] }}" />
                                            <span class="font-semibold text-slate-700">{{ $gas['symbol'] }}</span>
                                            <span class="text-slate-500">{{ $gas['name'] }}</span>
                                        </span>
                                    @endforeach
                                </dd>
                            </div>
                        </dl>
                    </article>
                @endforeach
            </div>
        @endif
    </section>

    <section class="card card-body flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div class="flex items-start gap-3">
            <span class="icon-chip shrink-0"><x-tabler-info-circle class="size-5" /></span>
            <p class="max-w-2xl text-sm leading-relaxed text-slate-600">
                Perangkat dianggap <strong class="font-semibold text-slate-900">Terputus</strong> bila tidak ada data baru selama lebih dari {{ $onlineSeconds }} detik,
                misalnya karena listrik padam atau WiFi terputus. Ingin tahu bagaimana data dari perangkat sampai ke halaman ini?
            </p>
        </div>
        <a href="{{ route('guide') }}#cara-kerja" wire:navigate class="btn btn-secondary shrink-0">
            <x-tabler-book-2 class="size-4" /> Baca cara kerja SIMKU
        </a>
    </section>
</div>
