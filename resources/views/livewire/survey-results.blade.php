@php
    $fmt = fn ($value, int $decimals = 1) => $value === null ? '–' : number_format($value, $decimals, ',', '.');
    $maxBand = max(1, collect($summary['bands'])->max('count'));
@endphp
<div class="space-y-6">
    <x-ui.page-header
        icon="tabler-clipboard-check"
        title="Hasil Kuesioner SUS"
        description="Rekap uji coba produk menggunakan System Usability Scale untuk mengukur tingkat kegunaan website.">
        <x-slot:eyebrow>Uji Coba Produk</x-slot:eyebrow>
        <x-slot:actions>
            <label for="group-filter" class="sr-only">Filter kelompok responden</label>
            <select id="group-filter" wire:model.live="group" class="input w-auto py-1.5">
                <option value="">Semua kelompok</option>
                @foreach ($groups as $value => $label)
                    <option value="{{ $value }}">{{ $label }}</option>
                @endforeach
            </select>
            <button type="button" wire:click="export" class="btn btn-primary btn-sm" @disabled($summary['count'] === 0)>
                <x-tabler-download class="size-4" /> Unduh CSV
            </button>
        </x-slot:actions>
    </x-ui.page-header>

    <x-ui.flash />

    <div class="grid grid-cols-2 gap-4 lg:grid-cols-4">
        <x-ui.stat label="Jumlah responden" :value="$summary['count']" unit="orang" icon="tabler-users"
                   :hint="$group !== '' ? $groups[$group] : 'Semua kelompok'" />
        <x-ui.stat label="Rata-rata skor SUS" :value="$fmt($summary['mean'], 2)" icon="tabler-star"
                   :hint="'Acuan rata-rata global: '.$fmt($benchmark, 0)" />
        <x-ui.stat label="Grade" :value="$summary['grade']['grade'] ?? '–'" icon="tabler-award"
                   :hint="$summary['grade'] ? $summary['grade']['adjective'].' · '.$summary['acceptability']['label'] : 'Belum ada data'" />
        <x-ui.stat label="Standar deviasi" :value="$fmt($summary['sd'], 2)" icon="tabler-chart-dots"
                   hint="Simpangan baku sampel (n − 1)" />
    </div>

    @if ($summary['count'] === 0)
        <div class="card">
            <x-ui.empty-state title="Belum ada respons" icon="tabler-clipboard-list"
                              description="Bagikan halaman kuesioner kepada responden uji coba. Hasilnya akan tampil di sini.">
                <a href="{{ route('survey') }}" class="btn btn-secondary btn-sm" target="_blank">
                    <x-tabler-external-link class="size-4" /> Buka Kuesioner
                </a>
            </x-ui.empty-state>
        </div>
    @else
        <div class="grid gap-6 lg:grid-cols-3">
            <x-ui.card class="lg:col-span-2" icon="tabler-list-numbers" title="Analisis per butir"
                       description="Kontribusi = (rata-rata − 1) untuk butir ganjil, (5 − rata-rata) untuk butir genap, × 2,5. Jumlah kontribusi = rata-rata skor SUS."
                       :padded="false">
                <div class="overflow-x-auto">
                    <table class="table">
                        <thead>
                            <tr>
                                <th class="w-12">No</th>
                                <th>Pernyataan</th>
                                <th class="text-right">Rata-rata</th>
                                <th class="w-40">Kontribusi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($summary['items'] as $item)
                                <tr wire:key="item-{{ $item['number'] }}">
                                    <td class="num font-semibold text-slate-900">{{ $item['number'] }}</td>
                                    <td class="min-w-64">
                                        <p class="text-slate-700">{{ $item['statement'] }}</p>
                                        <p class="mt-0.5 text-xs text-slate-400">{{ $item['positive'] ? 'Pernyataan positif' : 'Pernyataan negatif' }}</p>
                                    </td>
                                    <td class="num text-right">{{ $fmt($item['mean'], 2) }}</td>
                                    <td>
                                        <div class="flex items-center gap-2">
                                            <div class="h-1.5 flex-1 overflow-hidden rounded-full bg-slate-100" aria-hidden="true">
                                                <div class="h-full rounded-full bg-accent-500" style="width: {{ max(0, min(100, ($item['contribution'] ?? 0) * 10)) }}%"></div>
                                            </div>
                                            <span class="num w-10 text-right text-xs text-slate-600">{{ $fmt($item['contribution'], 2) }}</span>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </x-ui.card>

            <div class="space-y-6">
                <x-ui.card icon="tabler-chart-bar" title="Sebaran skor" description="Jumlah responden per pita grade.">
                    <ul class="space-y-3">
                        @foreach ($summary['bands'] as $band)
                            <li wire:key="band-{{ $band['grade'] }}">
                                <div class="mb-1 flex items-baseline justify-between gap-2 text-xs">
                                    <span class="font-semibold text-slate-900">{{ $band['grade'] }} <span class="font-normal text-slate-500">· {{ $band['adjective'] }} ({{ $band['range'] }})</span></span>
                                    <span class="num text-slate-600">{{ $band['count'] }}</span>
                                </div>
                                <div class="h-2 overflow-hidden rounded-full bg-slate-100" aria-hidden="true">
                                    <div class="h-full rounded-full bg-accent-500" style="width: {{ round($band['count'] / $maxBand * 100) }}%"></div>
                                </div>
                            </li>
                        @endforeach
                    </ul>
                </x-ui.card>

                <x-ui.card icon="tabler-info-circle" title="Catatan metodologi">
                    <div class="space-y-3 text-xs leading-relaxed text-slate-600">
                        <p>Instrumen menggunakan <strong class="font-semibold text-slate-800">System Usability Scale</strong> (Brooke, 1996) berisi 10 butir dengan skala Likert 1–5, adaptasi bahasa Indonesia oleh Sharfina &amp; Santoso (2016).</p>
                        <p class="rounded-lg bg-slate-50 px-3 py-2 font-mono text-[11px] text-slate-700">
                            Skor = 2,5 × [ Σ(ganjil − 1) + Σ(5 − genap) ]
                        </p>
                        <p>Interpretasi mengikuti Bangor dkk. (2009): A ≥ 80,3 (Excellent), B/C 68–80,3 (Good), D 51–68 (OK), F &lt; 51 (Poor). Rentang penerimaan: Acceptable ≥ 70, Marginal 50–70, Not Acceptable &lt; 50. Rata-rata skor global ≈ 68 (Sauro).</p>
                    </div>
                </x-ui.card>
            </div>
        </div>

        <x-ui.card icon="tabler-message-circle" title="Daftar respons" :description="$responses->total().' respons'" :padded="false">
            <div class="overflow-x-auto">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Waktu</th>
                            <th>Nama</th>
                            <th>Kelompok</th>
                            <th class="text-right">Skor</th>
                            <th>Grade</th>
                            <th>Komentar</th>
                            <th class="text-right"><span class="sr-only">Aksi</span></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($responses as $response)
                            @php($rowGrade = $response->grade())
                            <tr wire:key="response-{{ $response->id }}">
                                <td class="num whitespace-nowrap text-xs text-slate-500">{{ $response->created_at?->translatedFormat('d M Y, H:i') }}</td>
                                <td class="whitespace-nowrap">
                                    @if ($response->respondent_name)
                                        <span class="text-slate-900">{{ $response->respondent_name }}</span>
                                    @else
                                        <span class="text-slate-400">Anonim</span>
                                    @endif
                                </td>
                                <td class="whitespace-nowrap text-xs">{{ $response->groupLabel() }}</td>
                                <td class="num text-right font-semibold text-slate-900">{{ $fmt($response->sus_score, 1) }}</td>
                                <td class="whitespace-nowrap">
                                    <span class="inline-flex items-center rounded-md bg-slate-100 px-2 py-0.5 text-xs font-semibold text-slate-700">{{ $rowGrade['grade'] }}</span>
                                    <span class="ml-1 text-xs text-slate-500">{{ $rowGrade['adjective'] }}</span>
                                </td>
                                <td class="max-w-xs">
                                    @if ($response->comment)
                                        <p class="truncate text-xs text-slate-600" title="{{ $response->comment }}">{{ \Illuminate\Support\Str::limit($response->comment, 80) }}</p>
                                    @else
                                        <span class="text-xs text-slate-400">–</span>
                                    @endif
                                </td>
                                <td class="text-right">
                                    <button type="button" class="btn btn-ghost btn-sm text-rose-600 hover:bg-rose-50 hover:text-rose-700"
                                            wire:click="delete({{ $response->id }})"
                                            wire:confirm="Hapus respons ini? Tindakan ini tidak dapat dibatalkan."
                                            aria-label="Hapus respons">
                                        <x-tabler-trash class="size-4" />
                                    </button>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @if ($responses->hasPages())
                <div class="border-t border-slate-100 px-5 py-3 sm:px-6">
                    {{ $responses->links() }}
                </div>
            @endif
        </x-ui.card>
    @endif
</div>
