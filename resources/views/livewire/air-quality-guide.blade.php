<div class="space-y-10">
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
                    Hasil pengukuran dikirim ke server lalu diklasifikasikan menjadi empat kategori: Baik, Sedang, Tidak Sehat, dan Berbahaya.
                    Dengan begitu Anda cukup melihat satu kategori untuk mengetahui apakah udara ruangan masih nyaman.
                </p>
            </div>
        </div>
    </section>

    {{-- Cara membaca kategori --}}
    <section class="space-y-4">
        <div>
            <h2 class="text-lg font-semibold tracking-tight text-slate-900">Cara membaca kategori</h2>
            <p class="mt-1 max-w-3xl text-sm text-slate-500">Kategori ruangan ditentukan dari kategori gas yang paling buruk.</p>
        </div>

        <div class="card card-body">
            <ol class="space-y-4 text-sm text-slate-600">
                <li class="flex gap-3">
                    <span class="icon-chip icon-chip-sm"><x-tabler-math-function class="size-4" /></span>
                    <span><strong class="font-semibold text-slate-900">Setiap gas dinilai sendiri.</strong> Kadar tiap gas (dalam ppm) dibandingkan dengan ambang batasnya masing-masing.</span>
                </li>
                <li class="flex gap-3">
                    <span class="icon-chip icon-chip-sm"><x-tabler-arrow-big-up-lines class="size-4" /></span>
                    <span><strong class="font-semibold text-slate-900">Diambil yang terburuk.</strong> Kategori keseluruhan adalah kategori gas yang paling buruk. Jadi satu gas yang buruk sudah cukup untuk menurunkan status ruangan.</span>
                </li>
                <li class="flex gap-3">
                    <span class="icon-chip icon-chip-sm"><x-tabler-target class="size-4" /></span>
                    <span><strong class="font-semibold text-slate-900">Penyebab utama.</strong> Gas yang berada pada kategori terburuk ditampilkan sebagai "Penyebab utama" agar Anda tahu sumber masalahnya.</span>
                </li>
            </ol>
        </div>
    </section>

    {{-- Kategori --}}
    <section class="space-y-4">
        <div>
            <h2 class="text-lg font-semibold tracking-tight text-slate-900">Arti setiap kategori</h2>
            <p class="mt-1 max-w-3xl text-sm text-slate-500">Kualitas udara dikelompokkan menjadi empat kategori. Warna yang sama dipakai di seluruh halaman.</p>
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
            <p class="mt-1 max-w-3xl text-sm text-slate-500">Setiap gas memiliki ambang batas sendiri. Tabel di tiap kartu menunjukkan rentang kadar untuk masing-masing kategori.</p>
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
                                    <th class="text-right">Kategori</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($categories as $status => $category)
                                    <tr>
                                        <td class="num">{{ \App\Support\AirQuality::thresholdLabel($key, $status) }}</td>
                                        <td class="text-right"><x-ui.ku-badge :status="$status" size="sm" /></td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </article>
            @endforeach
        </div>
    </section>

    {{-- Rekomendasi tindakan --}}
    <section class="space-y-4">
        <div class="flex items-start gap-3">
            <span class="icon-chip">
                <x-tabler-shield-check class="size-5" />
            </span>
            <div>
                <h2 class="text-lg font-semibold tracking-tight text-slate-900">Rekomendasi tindakan</h2>
                <p class="mt-1 max-w-3xl text-sm leading-relaxed text-slate-500">
                    Rekomendasi mengikuti kategori keseluruhan ruangan dan disusun berdasarkan aturan tetap (rule-based).
                </p>
            </div>
        </div>

        <div class="card divide-y divide-slate-100">
            @foreach ($recommendations as $category => $rule)
                <div class="flex flex-col gap-2 px-5 py-4 sm:flex-row sm:items-start sm:gap-6 sm:px-6">
                    <div class="flex shrink-0 items-center gap-2 sm:w-56">
                        <x-ui.ku-badge :status="$category" size="sm" />
                        <span class="text-xs font-medium text-slate-500">{{ $rule['level'] }}</span>
                    </div>
                    <p class="text-sm text-slate-600">{{ $rule['text'] }}</p>
                </div>
            @endforeach
        </div>

        <p class="flex gap-2 text-xs text-slate-500">
            <x-tabler-info-circle class="mt-px size-4 shrink-0 text-slate-400" />
            {{ config('recommendations.disclaimer') }}
        </p>
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
        <span>Catatan: ambang batas setiap gas mengacu pada {{ $source['label'] }} (doi: {{ $source['doi'] }}).
        Nilai ppm dari sensor MQ merupakan indikator relatif, bukan pengukuran presisi, sehingga hasilnya digunakan untuk
        memantau kenyamanan udara ruangan, bukan diagnosis medis.</span>
    </p>
</div>
