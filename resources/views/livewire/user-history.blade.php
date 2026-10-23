<div class="space-y-6">
    <x-ui.page-header
        :icon="'tabler-history'"
        title="Riwayat Kualitas Udara"
        description="Catatan hasil pengukuran sensor di setiap ruangan. Gunakan filter untuk melihat ruangan, kategori, atau tanggal tertentu.">
        <x-slot:eyebrow>Riwayat</x-slot:eyebrow>
        <x-slot:actions>
            <button type="button" wire:click="downloadCsv" wire:loading.attr="disabled" wire:target="downloadCsv" class="btn btn-secondary">
                <x-tabler-download class="size-4" />
                <span wire:loading.remove wire:target="downloadCsv">Unduh CSV</span>
                <span wire:loading wire:target="downloadCsv">Menyiapkan…</span>
            </button>
        </x-slot:actions>
    </x-ui.page-header>

    {{-- Filter --}}
    <section class="card card-body">
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-[1fr_1fr_1fr_1fr_auto] lg:items-end">
            <div>
                <label for="filter-room" class="label inline-flex items-center gap-1.5"><x-tabler-door class="size-3.5 text-slate-400" /> Ruangan</label>
                <select id="filter-room" wire:model.live="room" class="input">
                    <option value="">Semua ruangan</option>
                    @foreach ($rooms as $r)
                        <option value="{{ $r->id }}">{{ $r->name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label for="filter-category" class="label inline-flex items-center gap-1.5"><x-tabler-category class="size-3.5 text-slate-400" /> Kategori</label>
                <select id="filter-category" wire:model.live="category" class="input">
                    <option value="">Semua kategori</option>
                    @foreach ($categories as $key => $cat)
                        <option value="{{ $key }}">{{ $cat['label'] }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label for="filter-from" class="label inline-flex items-center gap-1.5"><x-tabler-calendar class="size-3.5 text-slate-400" /> Tanggal dari</label>
                <input id="filter-from" type="date" wire:model.live="from" @if ($to) max="{{ $to }}" @endif class="input">
            </div>
            <div>
                <label for="filter-to" class="label inline-flex items-center gap-1.5"><x-tabler-calendar class="size-3.5 text-slate-400" /> Tanggal sampai</label>
                <input id="filter-to" type="date" wire:model.live="to" @if ($from) min="{{ $from }}" @endif class="input">
            </div>
            <div class="sm:col-span-2 lg:col-span-1">
                <button type="button" wire:click="resetFilters" class="btn btn-ghost w-full lg:w-auto" @disabled(! $this->hasFilters())>
                    <x-tabler-filter-off class="size-4" /> Reset filter
                </button>
            </div>
        </div>
    </section>

    {{-- Ringkasan --}}
    <x-ui.card :icon="'tabler-chart-pie'" title="Ringkasan" description="Periode: {{ $period }}">
        <div class="grid grid-cols-1 gap-6 md:grid-cols-[minmax(0,1fr)_minmax(0,1.4fr)]">
            <dl class="grid grid-cols-2 gap-4">
                <div>
                    <dt class="text-xs font-medium text-slate-500">Jumlah data</dt>
                    <dd class="mt-1 text-2xl font-bold tracking-tight text-slate-900 num">{{ number_format($summary['count'], 0, ',', '.') }}</dd>
                </div>
                <div>
                    <dt class="text-xs font-medium text-slate-500">Kondisi terburuk</dt>
                    <dd class="mt-2">
                        @if ($summary['worst'])
                            <x-ui.ku-badge :status="$summary['worst']" />
                        @else
                            <span class="text-2xl font-bold text-slate-900">—</span>
                        @endif
                    </dd>
                </div>
            </dl>

            <div>
                <p class="text-xs font-medium text-slate-500">Sebaran kategori</p>
                <div class="mt-2.5 flex h-2 w-full overflow-hidden rounded-full bg-slate-100" role="img" aria-label="Sebaran kategori kualitas udara">
                    @foreach ($summary['distribution'] as $status => $row)
                        @if ($row['count'] > 0)
                            <span class="h-full" style="width: {{ $row['percent'] }}%; background-color: {{ $categories[$status]['color'] }}" title="{{ $status }}: {{ $row['percent'] }}%"></span>
                        @endif
                    @endforeach
                </div>
                <ul class="mt-3 grid grid-cols-2 gap-x-4 gap-y-2 sm:grid-cols-4">
                    @foreach ($summary['distribution'] as $status => $row)
                        <li class="text-xs">
                            <span class="flex items-center gap-1.5 text-slate-500">
                                <x-dynamic-component :component="$categories[$status]['icon']" class="size-4 shrink-0" style="color: {{ $categories[$status]['color'] }}" />
                                {{ $categories[$status]['label'] }}
                            </span>
                            <span class="mt-0.5 block font-semibold text-slate-900 num">
                                {{ number_format($row['count'], 0, ',', '.') }}
                                <span class="font-normal text-slate-400">({{ number_format($row['percent'], 1, ',', '.') }}%)</span>
                            </span>
                        </li>
                    @endforeach
                </ul>
            </div>
        </div>
    </x-ui.card>

    {{-- Tabel riwayat (diperbarui otomatis) --}}
    <section wire:poll.10s class="card overflow-hidden">
        <header class="card-header">
            <div class="flex min-w-0 items-center gap-3">
                <span class="icon-chip">
                    <x-tabler-table class="size-4.5" />
                </span>
                <div class="min-w-0">
                    <h2 class="card-title">Data pengukuran</h2>
                    <p class="mt-0.5 text-xs text-slate-500">Diperbarui otomatis setiap 10 detik. Nilai gas dalam ppm.</p>
                </div>
            </div>
        </header>

        @if ($logs->isEmpty())
            <x-ui.empty-state
                title="Belum ada data"
                :description="$this->hasFilters() ? 'Tidak ada pengukuran yang cocok dengan filter. Coba ubah atau reset filter.' : 'Data akan muncul setelah sensor mulai mengirim pengukuran.'"
                icon="tabler-clock">
                @if ($this->hasFilters())
                    <button type="button" wire:click="resetFilters" class="btn btn-secondary btn-sm"><x-tabler-filter-off class="size-4" /> Reset filter</button>
                @endif
            </x-ui.empty-state>
        @else
            <div class="overflow-x-auto">
                <table class="table whitespace-nowrap">
                    <thead>
                        <tr>
                            <th>Waktu</th>
                            <th>Ruangan</th>
                            <th>Kategori</th>
                            <th>Penyebab utama</th>
                            @foreach ($gases as $gas)
                                <th class="text-right">{{ $gas['symbol'] }}</th>
                            @endforeach
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($logs as $log)
                            <tr wire:key="log-{{ $log->id }}">
                                <td class="num text-slate-600">{{ ($log->recorded_at ?? $log->created_at)->translatedFormat('d M Y H:i:s') }}</td>
                                <td class="font-medium text-slate-900">{{ $log->node?->room?->name ?? $log->node?->name ?? 'Perangkat #'.$log->node_id }}</td>
                                <td><x-ui.ku-badge :status="$log->overall_category" size="sm" /></td>
                                <td>
                                    @if ($log->dominantGases() !== [])
                                        <span class="font-medium text-slate-900">{{ collect($log->dominantGases())->map(fn ($key) => $gases[$key]['symbol'] ?? strtoupper($key))->implode(', ') }}</span>
                                    @else
                                        <span class="text-slate-400">—</span>
                                    @endif
                                </td>
                                @foreach ($gases as $key => $gas)
                                    <td class="text-right num">{{ number_format($log->{$key}, 2, ',', '.') }}</td>
                                @endforeach
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            @if ($logs->hasPages())
                <div class="border-t border-slate-100 px-5 py-4 sm:px-6">
                    {{ $logs->links() }}
                </div>
            @endif
        @endif
    </section>
</div>
