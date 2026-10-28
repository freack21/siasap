<div class="space-y-10">
    <x-ui.page-header
        :icon="'tabler-book-2'"
        title="Panduan Sistem"
        description="Penjelasan lengkap cara kerja SIMKU, isi setiap halaman, dan cara membaca kualitas udara ruangan.">
        <x-slot:eyebrow>Panduan</x-slot:eyebrow>
        <x-slot:actions>
            <a href="{{ route('home') }}" wire:navigate class="btn btn-secondary">
                <x-tabler-home-2 class="size-4" /> Lihat kondisi ruangan
            </a>
            <a href="{{ route('recommendations') }}" wire:navigate class="btn btn-secondary">
                <x-tabler-bulb class="size-4" /> Rekomendasi tindakan
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
                    SIMKU (Sistem Monitoring Kualitas Udara) membantu Anda memantau kualitas udara di dalam ruangan.
                    Perangkat pemantau di ruangan mengukur kadar empat jenis gas &mdash; karbon dioksida (CO₂), amonia (NH₃),
                    karbon monoksida (CO), dan hidrogen (H₂) &mdash; lalu mengirimkannya ke SIMKU setiap {{ $sendInterval }} detik.
                </p>
                <p>
                    SIMKU mengubah angka-angka tersebut menjadi satu kategori yang mudah dipahami, yaitu Baik, Sedang, Tidak Sehat, atau Berbahaya,
                    lengkap dengan saran tindakan yang sesuai. Dengan begitu Anda cukup melihat satu kategori untuk mengetahui apakah udara ruangan masih nyaman.
                </p>
            </div>
        </div>
    </section>

    {{-- Cara kerja --}}
    @php
        $steps = [
            ['icon' => 'tabler-cpu', 'title' => 'Perangkat membaca udara', 'text' => 'Perangkat pemantau memiliki dua sensor gas: MQ-135 membaca CO₂ dan NH₃, sedangkan MQ-2 membaca CO dan H₂. Bacaan sensor diubah perangkat menjadi angka kadar gas dalam satuan ppm (bagian per sejuta).'],
            ['icon' => 'tabler-send', 'title' => 'Data dikirim ke SIMKU', 'text' => 'Setiap '.$sendInterval.' detik perangkat mengirim kadar keempat gas, kode perangkat, dan waktu pengukuran ke SIMKU melalui internet.'],
            ['icon' => 'tabler-list-check', 'title' => 'Data diperiksa', 'text' => 'SIMKU memeriksa kelengkapan data: keempat kadar gas harus berupa angka yang tidak negatif, kode perangkat harus terdaftar, dan waktu pengukuran harus jelas. Data yang tidak lengkap tidak dipakai, tetapi tetap dicatat agar dapat ditelusuri.'],
            ['icon' => 'tabler-filter', 'title' => 'Kategori ditentukan', 'text' => 'Kadar setiap gas dibandingkan dengan tabel ambang batas di bawah. Kategori ruangan diambil dari gas yang kondisinya paling buruk, dan gas tersebut ditampilkan sebagai penyebab utama.'],
            ['icon' => 'tabler-bulb', 'title' => 'Saran disiapkan dan disimpan', 'text' => 'Sesuai kategori ruangan, SIMKU memilih satu saran tindakan. Kadar gas, kategori, dan saran disimpan sebagai riwayat.'],
            ['icon' => 'tabler-device-desktop-analytics', 'title' => 'Hasil ditampilkan', 'text' => 'Beranda, grafik, riwayat, dan rekomendasi diperbarui sendiri tanpa perlu memuat ulang halaman.'],
        ];
        $pages = [
            ['icon' => 'tabler-home-2', 'title' => 'Beranda', 'text' => 'Kondisi udara ruangan saat ini: kategori, penyebab utama, kadar keempat gas, dan saran tindakan.'],
            ['icon' => 'tabler-chart-line', 'title' => 'Detail Ruangan', 'text' => 'Grafik kadar gas dalam 1 jam, 24 jam, atau 7 hari terakhir, beserta rata-rata, nilai terendah, dan nilai tertinggi.'],
            ['icon' => 'tabler-history', 'title' => 'Riwayat', 'text' => 'Seluruh catatan kualitas udara yang dapat disaring menurut ruangan, kategori, atau tanggal, dan dapat diunduh sebagai berkas CSV.'],
            ['icon' => 'tabler-bulb', 'title' => 'Rekomendasi', 'text' => 'Saran tindakan untuk kondisi saat ini dan daftar saran untuk setiap kategori.'],
            ['icon' => 'tabler-cpu', 'title' => 'Perangkat', 'text' => 'Apakah perangkat pemantau sedang mengirim data dan kapan data terakhir diterima.'],
            ['icon' => 'tabler-book-2', 'title' => 'Panduan', 'text' => 'Halaman ini: cara kerja SIMKU dan cara membaca kualitas udara.'],
            ['icon' => 'tabler-clipboard-check', 'title' => 'Kuesioner', 'text' => 'Formulir penilaian pengalaman Anda menggunakan SIMKU untuk keperluan penelitian.'],
        ];
    @endphp
    <section id="cara-kerja" class="scroll-mt-24 space-y-4">
        <div>
            <h2 class="text-lg font-semibold tracking-tight text-slate-900">Cara kerja SIMKU</h2>
            <p class="mt-1 max-w-3xl text-sm text-slate-500">Perjalanan data dari perangkat di ruangan sampai tampil di halaman SIMKU.</p>
        </div>

        <ol class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
            @foreach ($steps as $step)
                <li class="card card-body flex gap-4">
                    <span class="icon-chip shrink-0">
                        <x-dynamic-component :component="$step['icon']" class="size-5" />
                    </span>
                    <div class="min-w-0">
                        <p class="text-xs font-semibold text-slate-400">Langkah {{ $loop->iteration }}</p>
                        <h3 class="font-semibold text-slate-900">{{ $step['title'] }}</h3>
                        <p class="mt-1 text-sm leading-relaxed text-slate-600">{{ $step['text'] }}</p>
                    </div>
                </li>
            @endforeach
        </ol>
    </section>

    {{-- Isi setiap halaman --}}
    <section class="space-y-4">
        <div>
            <h2 class="text-lg font-semibold tracking-tight text-slate-900">Isi setiap halaman</h2>
            <p class="mt-1 max-w-3xl text-sm text-slate-500">Semua halaman dapat dibuka dari menu di bagian atas tanpa perlu masuk akun.</p>
        </div>

        <div class="card divide-y divide-slate-100">
            @foreach ($pages as $page)
                <div class="flex gap-3 px-5 py-3.5 sm:px-6">
                    <x-dynamic-component :component="$page['icon']" class="mt-0.5 size-4.5 shrink-0 text-slate-400" />
                    <p class="text-sm text-slate-600"><strong class="font-semibold text-slate-900">{{ $page['title'] }}.</strong> {{ $page['text'] }}</p>
                </div>
            @endforeach
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
                    <p class="mt-3 flex-1 text-sm text-slate-600">{{ $cat['description'] }}</p>
                    <div class="mt-4 rounded-xl {{ $cat['soft'] }} px-3.5 py-3 text-sm text-slate-700">
                        <p class="eyebrow mb-1 text-[11px]">{{ $recommendations[$key]['level'] }}</p>
                        {{ $recommendations[$key]['text'] }}
                    </div>
                </article>
            @endforeach
        </div>
    </section>

    {{-- Tabel ambang --}}
    <section class="space-y-4">
        <div>
            <h2 class="text-lg font-semibold tracking-tight text-slate-900">Tabel ambang batas</h2>
            <p class="mt-1 max-w-3xl text-sm text-slate-500">
                Kadar setiap gas dibandingkan secara berurutan dengan batas di bawah ini. Batas bawah termasuk dalam kategori,
                batas atas tidak (misalnya CO₂ 50 ppm sudah termasuk Sedang).
            </p>
        </div>

        <div class="card overflow-x-auto">
            <table class="table">
                <thead>
                    <tr>
                        <th>Kategori</th>
                        @foreach ($gases as $gas)
                            <th class="whitespace-nowrap">{{ $gas['symbol'] }}</th>
                        @endforeach
                    </tr>
                </thead>
                <tbody>
                    @foreach ($categories as $status => $category)
                        <tr>
                            <td><x-ui.ku-badge :status="$status" size="sm" /></td>
                            @foreach ($gases as $key => $gas)
                                <td class="num whitespace-nowrap">{{ \App\Support\AirQuality::thresholdLabel($key, $status) }}</td>
                            @endforeach
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <p class="flex max-w-3xl gap-2 text-xs leading-relaxed text-slate-500">
            <x-tabler-book class="mt-px size-4 shrink-0 text-slate-400" />
            <span>
                Sumber ambang: {{ $source['label'] }}
                <a href="https://doi.org/{{ $source['doi'] }}" target="_blank" rel="noopener noreferrer" class="text-accent-700 hover:underline">doi:{{ $source['doi'] }}</a>
            </span>
        </p>
    </section>

    {{-- Gas --}}
    <section class="space-y-4">
        <div>
            <h2 class="text-lg font-semibold tracking-tight text-slate-900">Gas yang dipantau</h2>
            <p class="mt-1 max-w-3xl text-sm text-slate-500">Empat gas dibaca oleh dua sensor: MQ-135 (CO₂ dan NH₃) serta MQ-2 (CO dan H₂).</p>
        </div>

        <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
            @foreach ($gases as $key => $gas)
                <article class="card card-body">
                    <div class="flex items-center gap-3">
                        <span class="flex size-11 shrink-0 items-center justify-center rounded-xl bg-slate-100" style="color: {{ $gas['color'] }}">
                            <x-dynamic-component :component="$gas['icon']" class="size-6" />
                        </span>
                        <div>
                            <h3 class="font-semibold text-slate-900">{{ $gas['name'] }} <span class="font-bold">({{ $gas['symbol'] }})</span></h3>
                            <p class="text-xs text-slate-500">Sensor {{ $gas['sensor'] }}</p>
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
                </article>
            @endforeach
        </div>
    </section>

    {{-- Dasar saran --}}
    <section class="space-y-4">
        <div>
            <h2 class="text-lg font-semibold tracking-tight text-slate-900">Dasar saran tindakan</h2>
            <p class="mt-1 max-w-3xl text-sm text-slate-500">Saran untuk setiap kategori disusun peneliti dengan dua acuan berikut.</p>
        </div>

        <div class="card card-body space-y-5">
            <ol class="space-y-3 text-sm text-slate-600">
                @foreach ($basis as $point)
                    <li class="flex gap-3">
                        <span class="flex size-6 shrink-0 items-center justify-center rounded-full bg-slate-100 text-xs font-semibold text-slate-600">{{ $loop->iteration }}</span>
                        <span class="leading-relaxed">{{ $point }}</span>
                    </li>
                @endforeach
            </ol>

            <div class="border-t border-slate-100 pt-4">
                <p class="eyebrow mb-2 text-[11px]">Sumber</p>
                <ul class="space-y-2 text-sm">
                    @foreach ($sources as $reference)
                        <li>
                            <a href="{{ $reference['url'] }}" target="_blank" rel="noopener noreferrer"
                               class="inline-flex items-start gap-1.5 text-accent-700 hover:underline">
                                <x-tabler-external-link class="mt-0.5 size-4 shrink-0" />
                                {{ $reference['label'] }}
                            </a>
                        </li>
                    @endforeach
                </ul>
            </div>
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
                    'a' => 'Perangkat mengirim pengukuran setiap '.$sendInterval.' detik, dan halaman memperbarui tampilannya secara otomatis tanpa perlu dimuat ulang.',
                ],
                [
                    'q' => 'Apa artinya perangkat berstatus "Terputus"?',
                    'a' => 'Perangkat dianggap Terputus bila tidak ada data baru yang masuk lebih dari '.$onlineSeconds.' detik, misalnya karena listrik padam atau jaringan WiFi terputus. Kondisi yang tampil saat itu adalah data terakhir, bukan kondisi terkini. Status perangkat dapat dilihat di halaman Perangkat.',
                ],
                [
                    'q' => 'Apakah nilai ppm sama dengan hasil alat ukur laboratorium?',
                    'a' => 'Tidak. Angka ppm dari sensor menunjukkan perubahan kadar gas dibandingkan dengan kondisi udara saat perangkat mulai dinyalakan. Angka ini cocok untuk memantau naik-turunnya kualitas udara, tetapi bukan hasil pengukuran alat laboratorium.',
                ],
                [
                    'q' => 'Apa yang harus dilakukan jika status Tidak Sehat atau Berbahaya?',
                    'a' => 'Ikuti rekomendasi yang tampil untuk kategori tersebut (lihat halaman Rekomendasi) dan perhatikan gas pada "Penyebab utama" untuk mencari sumbernya.',
                ],
                [
                    'q' => 'Apakah SIMKU dapat dipakai untuk menilai kondisi kesehatan?',
                    'a' => 'Tidak. Kategori dan rekomendasi bersifat informatif untuk menjaga kenyamanan udara ruangan dan bukan diagnosis medis. Bila mengalami keluhan kesehatan, hubungi tenaga kesehatan.',
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

    {{-- Batasan --}}
    <section class="space-y-4">
        <h2 class="text-lg font-semibold tracking-tight text-slate-900">Batasan</h2>
        <div class="card card-body">
            <ul class="space-y-3 text-sm leading-relaxed text-slate-600">
                <li class="flex gap-3">
                    <x-tabler-info-circle class="mt-0.5 size-4 shrink-0 text-slate-400" />
                    <span>Angka ppm menunjukkan perubahan kadar gas dibandingkan dengan kondisi udara saat perangkat dinyalakan (indikator relatif). Angka ini dihitung oleh perangkat, sehingga bukan hasil pengukuran alat laboratorium.</span>
                </li>
                <li class="flex gap-3">
                    <x-tabler-info-circle class="mt-0.5 size-4 shrink-0 text-slate-400" />
                    <span>Ambang batas pada tabel di atas adalah acuan kategori di SIMKU, bukan baku mutu udara resmi dari pemerintah.</span>
                </li>
                <li class="flex gap-3">
                    <x-tabler-info-circle class="mt-0.5 size-4 shrink-0 text-slate-400" />
                    <span>{{ config('recommendations.disclaimer') }}</span>
                </li>
            </ul>
        </div>
    </section>
</div>
