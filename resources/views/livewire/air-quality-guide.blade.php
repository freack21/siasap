<div class="space-y-10">
    @php
        // Format angka gaya Indonesia tanpa nol desimal berlebih (mis. 1.000 / 4,4).
        $fmt = function (float $value, int $decimals = 1): string {
            $text = number_format($value, $decimals, ',', '.');

            return str_contains($text, ',') ? rtrim(rtrim($text, '0'), ',') : $text;
        };
        $dominant = $gases[$result['dominant']];
    @endphp

    <x-ui.page-header
        :icon="'tabler-book-2'"
        title="Panduan Kualitas Udara"
        description="Penjelasan singkat cara kerja SIASAP dan cara membaca status kualitas udara di ruangan Anda.">
        <x-slot:eyebrow>Panduan</x-slot:eyebrow>
        <x-slot:actions>
            <a href="{{ route('home') }}" wire:navigate class="btn btn-secondary">
                <x-tabler-home-2 class="size-4" /> Lihat kondisi ruangan
            </a>
        </x-slot:actions>
    </x-ui.page-header>

    {{-- Pengantar --}}
    <section class="card card-body">
        <div class="flex flex-col gap-5 md:flex-row md:items-start">
            <span class="icon-chip icon-chip-lg">
                <x-tabler-info-circle class="size-6" />
            </span>
            <div class="max-w-3xl space-y-2 text-sm leading-relaxed text-slate-600">
                <p>
                    Di setiap ruangan terpasang perangkat sensor yang mengukur kadar empat jenis gas di udara
                    &mdash; karbon dioksida (CO₂), amonia (NH₃), karbon monoksida (CO), dan hidrogen (H₂) &mdash;
                    setiap beberapa detik.
                </p>
                <p>
                    Hasil pengukuran dikirim ke server lalu diringkas menjadi satu angka, yaitu <strong class="font-semibold text-slate-900">Indeks KU</strong>
                    (Kualitas Udara), beserta kategorinya. Dengan begitu Anda cukup melihat satu status untuk mengetahui apakah udara ruangan masih nyaman.
                </p>
            </div>
        </div>
    </section>

    {{-- Cara membaca Indeks KU --}}
    <section class="space-y-4">
        <div>
            <h2 class="text-lg font-semibold tracking-tight text-slate-900">Cara membaca Indeks KU</h2>
            <p class="mt-1 max-w-3xl text-sm text-slate-500">Indeks KU bernilai 0 sampai 500. Semakin kecil angkanya, semakin baik udaranya.</p>
        </div>

        <div class="grid grid-cols-1 gap-4 lg:grid-cols-2">
            <div class="card card-body">
                <ol class="space-y-4 text-sm text-slate-600">
                    <li class="flex gap-3">
                        <span class="icon-chip icon-chip-sm"><x-tabler-math-function class="size-4" /></span>
                        <span><strong class="font-semibold text-slate-900">Setiap gas dinilai sendiri.</strong> Kadar tiap gas (dalam ppm) diubah menjadi <em>sub-indeks</em> 0–500 berdasarkan ambang batasnya masing-masing.</span>
                    </li>
                    <li class="flex gap-3">
                        <span class="icon-chip icon-chip-sm"><x-tabler-arrow-big-up-lines class="size-4" /></span>
                        <span><strong class="font-semibold text-slate-900">Diambil yang tertinggi.</strong> Indeks KU ruangan adalah sub-indeks yang paling tinggi di antara keempat gas. Jadi satu gas yang buruk sudah cukup untuk menurunkan status ruangan.</span>
                    </li>
                    <li class="flex gap-3">
                        <span class="icon-chip icon-chip-sm"><x-tabler-target class="size-4" /></span>
                        <span><strong class="font-semibold text-slate-900">Penyebab utama.</strong> Gas yang menghasilkan sub-indeks tertinggi disebut <em>parameter dominan</em> &mdash; ditampilkan sebagai "Penyebab utama" agar Anda tahu sumber masalahnya.</span>
                    </li>
                </ol>
            </div>

            <x-ui.card :icon="'tabler-calculator'" title="Contoh perhitungan" description="Dihitung langsung oleh sistem dari contoh nilai pengukuran." :padded="false">
                <div class="overflow-x-auto">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Gas</th>
                                <th class="text-right">Kadar</th>
                                <th class="text-right">Sub-indeks</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($gases as $key => $gas)
                                @php $isDominant = $key === $result['dominant']; @endphp
                                <tr>
                                    <td @class(['font-semibold text-slate-900' => $isDominant])>
                                        {{ $gas['symbol'] }}
                                        @if ($isDominant)
                                            <span class="ml-1.5 rounded-md bg-slate-100 px-1.5 py-0.5 text-[11px] font-medium text-slate-600">tertinggi</span>
                                        @endif
                                    </td>
                                    <td class="text-right num">{{ $fmt($example[$key]) }} ppm</td>
                                    <td @class(['text-right num', 'font-semibold text-slate-900' => $isDominant])>{{ $fmt($result['sub_indices'][$key]) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <div class="flex flex-wrap items-center justify-between gap-3 border-t border-slate-100 px-5 py-4 sm:px-6">
                    <p class="text-sm text-slate-600">
                        Indeks KU = <span class="font-semibold text-slate-900 num">{{ $fmt($result['value']) }}</span>,
                        penyebab utama <span class="font-semibold text-slate-900">{{ $dominant['symbol'] }}</span>
                    </p>
                    <x-ui.ku-badge :status="$result['status']" />
                </div>
            </x-ui.card>
        </div>
    </section>

    {{-- Kategori --}}
    <section class="space-y-4">
        <div>
            <h2 class="text-lg font-semibold tracking-tight text-slate-900">Arti setiap kategori</h2>
            <p class="mt-1 max-w-3xl text-sm text-slate-500">Indeks KU dikelompokkan menjadi empat kategori. Warna yang sama dipakai di seluruh halaman.</p>
        </div>

        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
            @foreach ($categories as $key => $cat)
                <article class="card card-body flex flex-col">
                    <div class="flex items-center justify-between gap-3">
                        <span class="flex items-center gap-2.5">
                            <span class="flex size-8 shrink-0 items-center justify-center rounded-lg {{ $cat['soft'] }}" style="color: {{ $cat['color'] }}">
                                <x-dynamic-component :component="$cat['icon']" class="size-4.5" />
                            </span>
                            <span class="font-semibold text-slate-900">{{ $cat['label'] }}</span>
                        </span>
                        <span class="text-xs text-slate-500 num">Indeks {{ $cat['range'] }}</span>
                    </div>
                    <p class="mt-3 text-sm text-slate-600">{{ $cat['description'] }}</p>
                    <div class="mt-4 rounded-xl {{ $cat['soft'] }} px-3.5 py-3 text-sm text-slate-700 sm:mt-auto">
                        <p class="eyebrow mb-1 text-[11px]">Yang perlu dilakukan</p>
                        {{ $cat['advice'] }}
                    </div>
                </article>
            @endforeach
        </div>
    </section>

    {{-- Gas --}}
    <section class="space-y-4">
        <div>
            <h2 class="text-lg font-semibold tracking-tight text-slate-900">Gas yang dipantau</h2>
            <p class="mt-1 max-w-3xl text-sm text-slate-500">Setiap gas memiliki ambang batas sendiri. Tabel di tiap kartu menunjukkan kadar maksimum untuk masing-masing kategori.</p>
        </div>

        <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
            @foreach ($gases as $key => $gas)
                <article class="card card-body">
                    <div class="flex items-start justify-between gap-3">
                        <div class="flex items-center gap-3">
                            <span class="flex size-11 shrink-0 items-center justify-center rounded-xl bg-slate-100" style="color: {{ $gas['color'] }}">
                                <x-dynamic-component :component="$gas['icon']" class="size-6" />
                            </span>
                            <div>
                                <h3 class="font-semibold text-slate-900">{{ $gas['name'] }} <span class="font-bold">({{ $gas['symbol'] }})</span></h3>
                                <p class="text-xs text-slate-500">Sensor {{ $gas['sensor'] }}</p>
                            </div>
                        </div>
                    </div>

                    <dl class="mt-4 space-y-3 text-sm">
                        <div>
                            <dt class="text-xs font-medium text-slate-500">Sumber umum</dt>
                            <dd class="mt-0.5 text-slate-700">{{ $gas['source'] }}</dd>
                        </div>
                        <div>
                            <dt class="text-xs font-medium text-slate-500">Dampak</dt>
                            <dd class="mt-0.5 text-slate-700">{{ $gas['description'] }}</dd>
                        </div>
                    </dl>

                    <div class="mt-4 overflow-hidden rounded-xl border border-slate-200">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Kadar {{ $gas['symbol'] }}</th>
                                    <th class="text-right">Indeks</th>
                                    <th class="text-right">Kategori</th>
                                </tr>
                            </thead>
                            <tbody>
                                @php $previous = null; @endphp
                                @foreach ($gas['thresholds'] as $status => $ppm)
                                    <tr>
                                        <td class="num">
                                            @if ($ppm === null)
                                                —
                                            @elseif ($previous === null)
                                                0 – {{ $fmt($ppm) }} ppm
                                            @else
                                                &gt; {{ $fmt($previous) }} – {{ $fmt($ppm) }} ppm
                                            @endif
                                        </td>
                                        <td class="text-right num text-slate-500">{{ $categories[$status]['range'] }}</td>
                                        <td class="text-right"><x-ui.ku-badge :status="$status" size="sm" /></td>
                                    </tr>
                                    @php $previous = $ppm; @endphp
                                @endforeach
                                <tr>
                                    <td class="num">&gt; {{ $previous !== null ? $fmt($previous) : '—' }} ppm</td>
                                    <td class="text-right num text-slate-500">{{ $categories['Berbahaya']['range'] }}</td>
                                    <td class="text-right"><x-ui.ku-badge status="Berbahaya" size="sm" /></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </article>
            @endforeach
        </div>
    </section>

    {{-- Tindakan preventif --}}
    <section class="space-y-4">
        <div class="flex items-start gap-3">
            <span class="icon-chip">
                <x-tabler-shield-check class="size-5" />
            </span>
            <div>
                <h2 class="text-lg font-semibold tracking-tight text-slate-900">Tindakan preventif</h2>
                <div class="mt-1 max-w-3xl space-y-2 text-sm leading-relaxed text-slate-500">
                    <p>
                        Untuk CO, SIASAP memakai AQI resmi U.S. EPA yang terdiri atas 6 level. Sub-indeks CO di aplikasi memakai
                        breakpoint yang sama, sehingga level EPA dan anjurannya dapat ditampilkan langsung. EPA menulis anjuran
                        ini untuk udara luar, jadi SIASAP menyesuaikannya untuk ruangan dan menambahkan langkah pengendalian sumber CO
                        dari panduan EPA Indoor Air Quality.
                    </p>
                    <p>
                        CO₂, NH₃, dan H₂ tidak termasuk AQI EPA. Langkahnya mengikuti strategi EPA Indoor Air Quality
                        (kendalikan sumber, perbaiki ventilasi) sesuai kategori KU aplikasi. Untuk H₂, langkahnya berfokus pada
                        keselamatan dari kebakaran karena gas ini mudah terbakar mulai kadar 4% di udara (LEL).
                    </p>
                </div>
            </div>
        </div>

        <x-ui.card :icon="'tabler-table'" title="Level AQI EPA untuk CO" description="Titik warna menunjukkan kategori KU aplikasi untuk rentang indeks yang sama." :padded="false">
            <div class="overflow-x-auto">
                <table class="table">
                    <thead>
                        <tr>
                            <th class="whitespace-nowrap">Rentang indeks</th>
                            <th>Level</th>
                            <th>Anjuran EPA</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($coAqiLevels as $level)
                            @php $levelCategory = $categories[$level['ku_status']]; @endphp
                            <tr>
                                <td class="whitespace-nowrap align-top num text-slate-500">{{ $level['range'] }}</td>
                                <td class="min-w-48 align-top">
                                    <span class="flex items-start gap-2">
                                        <span class="mt-1.5 size-2 shrink-0 rounded-full {{ $levelCategory['dot'] }}" title="Kategori KU: {{ $levelCategory['label'] }}"></span>
                                        <span>
                                            <span class="block font-medium text-slate-900">{{ $level['label'] }}</span>
                                            <span class="block text-xs text-slate-500" lang="en">{{ $level['epa_label'] }}</span>
                                        </span>
                                    </span>
                                </td>
                                <td class="min-w-64 align-top text-slate-600">{{ $level['cautionary'] }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </x-ui.card>

        <div>
            <h3 class="text-sm font-semibold text-slate-900">Contoh langkah pada tingkat paling serius</h3>
            <p class="mt-0.5 text-sm text-slate-500">Rekomendasi pada halaman ruangan menyesuaikan level setiap gas; berikut contoh untuk level tertinggi.</p>
        </div>

        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">
            @foreach ($gases as $key => $gas)
                <article class="card card-body">
                    <div class="flex items-center gap-2.5">
                        <span class="flex size-8 shrink-0 items-center justify-center rounded-lg bg-slate-100" style="color: {{ $gas['color'] }}">
                            <x-dynamic-component :component="$gas['icon']" class="size-4.5" />
                        </span>
                        <div>
                            <h4 class="text-sm font-semibold text-slate-900">{{ $gas['symbol'] }}</h4>
                            <p class="text-xs text-slate-500">{{ $key === 'co' ? 'Level EPA: Berbahaya' : 'Kategori KU: Berbahaya' }}</p>
                        </div>
                    </div>
                    <ul class="mt-3 space-y-2 text-sm text-slate-600">
                        @foreach ($preventiveExamples[$key] as $step)
                            <li class="flex gap-2">
                                <x-tabler-check class="mt-0.5 size-4 shrink-0 text-slate-400" />
                                <span>{{ $step }}</span>
                            </li>
                        @endforeach
                    </ul>
                </article>
            @endforeach
        </div>

        <div class="card card-body">
            <p class="eyebrow">Sumber rujukan</p>
            <ul class="mt-3 space-y-2 text-sm">
                @foreach ($preventiveSources as $source)
                    <li class="flex gap-2">
                        <x-tabler-external-link class="mt-0.5 size-4 shrink-0 text-slate-400" />
                        <a href="{{ $source['url'] }}" target="_blank" rel="noopener" class="text-slate-700 underline decoration-slate-300 underline-offset-2 hover:text-slate-900">{{ $source['label'] }}</a>
                    </li>
                @endforeach
            </ul>
            <p class="mt-4 flex gap-2 border-t border-slate-100 pt-4 text-xs text-slate-500">
                <x-tabler-info-circle class="mt-px size-4 shrink-0 text-slate-400" />
                Rekomendasi bersifat informatif dan bukan diagnosis medis.
            </p>
        </div>
    </section>

    {{-- FAQ --}}
    <section class="space-y-4">
        <h2 class="text-lg font-semibold tracking-tight text-slate-900">Pertanyaan umum</h2>

        @php
            $faqs = [
                [
                    'q' => 'Kenapa status ruangan bisa berubah-ubah?',
                    'a' => 'Kadar gas di udara memang naik-turun mengikuti aktivitas di ruangan, misalnya jumlah orang, pintu atau jendela yang dibuka, memasak, atau penggunaan cairan pembersih. Perubahan sesaat adalah hal wajar; yang perlu diperhatikan adalah status buruk yang bertahan lama.',
                ],
                [
                    'q' => 'Seberapa sering data diperbarui?',
                    'a' => 'Sensor mengirim pengukuran sekitar setiap '.$sendInterval.' detik, dan halaman ini memperbarui tampilannya secara otomatis tanpa perlu dimuat ulang.',
                ],
                [
                    'q' => 'Apa artinya perangkat "offline"?',
                    'a' => 'Perangkat dianggap offline bila tidak ada data baru yang masuk selama lebih dari '.$onlineSeconds.' detik, misalnya karena listrik padam atau sinyal jaringan terputus. Status yang tampil saat itu adalah data terakhir, bukan kondisi terkini.',
                ],
                [
                    'q' => 'Apa yang harus dilakukan jika status Tidak Sehat atau Berbahaya?',
                    'a' => 'Ikuti saran pada kategori di atas: tambah ventilasi, kurangi aktivitas di ruangan, dan cari sumber gas sesuai "Penyebab utama". Bila status Berbahaya, segera tinggalkan ruangan dan hubungi pengelola gedung.',
                ],
            ];
        @endphp

        <div class="card divide-y divide-slate-100">
            @foreach ($faqs as $faq)
                <details class="group px-5 py-4 sm:px-6">
                    <summary class="flex cursor-pointer list-none items-center justify-between gap-4 text-sm font-semibold text-slate-900 [&::-webkit-details-marker]:hidden">
                        <span class="flex items-start gap-2.5">
                            <x-tabler-help-circle class="mt-px size-4 shrink-0 text-slate-400 transition group-open:text-accent-600" />
                            {{ $faq['q'] }}
                        </span>
                        <x-tabler-chevron-down class="size-4 shrink-0 text-slate-400 transition group-open:rotate-180" />
                    </summary>
                    <p class="mt-2 max-w-3xl pl-6.5 text-sm leading-relaxed text-slate-600">{{ $faq['a'] }}</p>
                </details>
            @endforeach
        </div>
    </section>

    {{-- Catatan --}}
    <p class="flex max-w-3xl gap-2 border-t border-slate-200 pt-6 text-xs leading-relaxed text-slate-500">
        <x-tabler-info-circle class="mt-px size-4 shrink-0 text-slate-400" />
        <span>Catatan: ambang batas setiap gas disusun dari sumber regulasi dan pedoman (antara lain Permenkes RI No. 1077/2011,
        US EPA, OSHA, dan NIOSH) sebagaimana tercantum pada konfigurasi sistem. Nilai ini merupakan acuan penelitian untuk
        memantau kenyamanan udara ruangan, bukan diagnosis medis.</span>
    </p>
</div>
