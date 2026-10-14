<div class="space-y-6">
    <x-ui.page-header icon="tabler-table" title="Data Sensor" description="Seluruh data mentah yang dikirim node sensor beserta hasil perhitungan Indeks KU dan parameter jaringan. Gunakan filter untuk menelusuri, lalu ekspor ke CSV untuk analisis lanjutan.">
        <x-slot:eyebrow>Panel Pengelola</x-slot:eyebrow>
        <x-slot:actions>
            <button type="button" wire:click="export" wire:loading.attr="disabled" wire:target="export" class="btn btn-primary">
                <x-tabler-download class="size-4" />
                Ekspor CSV
            </button>
        </x-slot:actions>
    </x-ui.page-header>

    {{-- Filter --}}
    <x-ui.card>
        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-5">
            <div>
                <label for="filter-node" class="label flex items-center gap-1.5"><x-tabler-cpu class="size-4 text-slate-400" />Ruangan / node</label>
                <select id="filter-node" wire:model.live="node" class="input">
                    <option value="">Semua node</option>
                    @foreach ($nodes as $item)
                        <option value="{{ $item->id }}">{{ $item->room?->name ?? 'Tanpa ruangan' }} — {{ $item->name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label for="filter-category" class="label flex items-center gap-1.5"><x-tabler-gauge class="size-4 text-slate-400" />Kategori</label>
                <select id="filter-category" wire:model.live="category" class="input">
                    <option value="">Semua kategori</option>
                    @foreach ($categories as $key => $meta)
                        <option value="{{ $key }}">{{ $meta['label'] }} ({{ $meta['range'] }})</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label for="filter-session" class="label flex items-center gap-1.5"><x-tabler-flask class="size-4 text-slate-400" />Sesi pengujian</label>
                <select id="filter-session" wire:model.live="session" class="input">
                    <option value="">Semua data</option>
                    <option value="{{ \App\Livewire\TelemetryDashboard::OUTSIDE_SESSION }}">Di luar sesi</option>
                    @foreach ($sessions as $item)
                        <option value="{{ $item->id }}">{{ $item->name }} ({{ ucfirst($item->topology_type) }}{{ $item->status === 'active' ? ', aktif' : '' }})</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label for="filter-from" class="label flex items-center gap-1.5"><x-tabler-calendar class="size-4 text-slate-400" />Dari tanggal</label>
                <input id="filter-from" type="date" wire:model.live="dateFrom" class="input" />
            </div>
            <div>
                <label for="filter-to" class="label flex items-center gap-1.5"><x-tabler-calendar class="size-4 text-slate-400" />Sampai tanggal</label>
                <input id="filter-to" type="date" wire:model.live="dateTo" class="input" />
            </div>
        </div>
        @if ($hasFilters)
            <div class="mt-4 flex justify-end">
                <button type="button" wire:click="resetFilters" class="btn btn-ghost btn-sm">
                    <x-tabler-filter-off class="size-4" />
                    Reset filter
                </button>
            </div>
        @endif
    </x-ui.card>

    {{-- Tabel data (diperbarui setiap 10 detik) --}}
    <div wire:poll.10s>
        <x-ui.card :padded="false">
            <div class="flex flex-wrap items-center gap-x-6 gap-y-1 border-b border-slate-100 px-5 py-3 text-sm">
                <p class="inline-flex items-center gap-1.5 text-slate-500"><x-tabler-database class="size-4 text-slate-400" />Total data: <span class="num font-semibold text-slate-900">{{ number_format($total, 0, ',', '.') }}</span></p>
                <p class="inline-flex flex-wrap items-center gap-1.5 text-slate-500"><x-tabler-calendar class="size-4 text-slate-400" />Rentang waktu:
                    @if ($firstAt)
                        <span class="num font-medium text-slate-900">{{ $firstAt->format('d/m/Y H:i:s') }} – {{ $lastAt->format('d/m/Y H:i:s') }}</span>
                    @else
                        <span class="text-slate-900">—</span>
                    @endif
                </p>
                <p class="inline-flex items-center gap-1 text-xs text-slate-400 sm:ml-auto"><x-tabler-refresh class="size-3.5" />Diperbarui otomatis setiap 10 detik</p>
            </div>

            @if ($logs->isEmpty())
                <x-ui.empty-state title="Tidak ada data" :description="$hasFilters ? 'Tidak ada data yang cocok dengan filter. Ubah atau reset filter.' : 'Belum ada data sensor yang masuk.'" icon="tabler-table-off" />
            @else
                <div class="overflow-x-auto">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Waktu</th>
                                <th>Node / Ruangan</th>
                                <th>Indeks KU</th>
                                <th>Dominan</th>
                                <th class="text-right">{{ $gases['co2']['symbol'] }} <span class="font-normal normal-case">(ppm)</span></th>
                                <th class="text-right">{{ $gases['nh3']['symbol'] }} <span class="font-normal normal-case">(ppm)</span></th>
                                <th class="text-right">{{ $gases['co']['symbol'] }} <span class="font-normal normal-case">(ppm)</span></th>
                                <th class="text-right">{{ $gases['h2']['symbol'] }} <span class="font-normal normal-case">(ppm)</span></th>
                                <th class="text-right">RSSI</th>
                                <th class="text-right">Hop</th>
                                <th class="text-right">Seq</th>
                                <th>Topologi</th>
                                <th>Sesi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($logs as $log)
                                <tr wire:key="log-{{ $log->id }}">
                                    <td class="num whitespace-nowrap">
                                        {{ $log->created_at->format('d/m/Y') }}
                                        <span class="text-slate-900">{{ $log->created_at->format('H:i:s') }}</span>
                                    </td>
                                    <td class="whitespace-nowrap">
                                        <p class="font-medium text-slate-900">{{ $log->node?->name ?? 'Node #'.$log->node_id }}</p>
                                        <p class="text-xs text-slate-500">{{ $log->node?->room?->name ?? '—' }}</p>
                                    </td>
                                    <td class="whitespace-nowrap">
                                        <div class="flex items-center gap-2">
                                            <span class="num w-8 font-semibold text-slate-900">{{ number_format($log->ku_value, 0) }}</span>
                                            <x-ui.ku-badge :status="$log->ku_status" size="sm" />
                                        </div>
                                    </td>
                                    <td>{{ $log->ku_dominant ? ($gases[$log->ku_dominant]['symbol'] ?? $log->ku_dominant) : '—' }}</td>
                                    <td class="num text-right">{{ number_format($log->co2, 2) }}</td>
                                    <td class="num text-right">{{ number_format($log->nh3, 2) }}</td>
                                    <td class="num text-right">{{ number_format($log->co, 2) }}</td>
                                    <td class="num text-right">{{ number_format($log->h2, 2) }}</td>
                                    <td class="num text-right whitespace-nowrap">{{ $log->rssi !== null ? $log->rssi.' dBm' : '—' }}</td>
                                    <td class="num text-right">{{ $log->hop_count }}</td>
                                    <td class="num text-right">{{ $log->seq_number }}</td>
                                    <td>{{ ucfirst($log->topology_type) }}</td>
                                    <td class="whitespace-nowrap">{{ $log->testingSession?->name ?? '—' }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                @if ($logs->hasPages())
                    <div class="border-t border-slate-100 px-5 py-3">
                        {{ $logs->links() }}
                    </div>
                @endif
            @endif
        </x-ui.card>
    </div>
</div>
