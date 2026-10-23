<div class="mx-auto max-w-3xl space-y-6">
    <x-ui.page-header
        :icon="'tabler-clipboard-check'"
        title="Kuesioner Kegunaan Website"
        description="Bantu kami menilai seberapa mudah website SIASAP digunakan. Penilaian ini merupakan bagian dari uji coba produk penelitian skripsi.">
        <x-slot:eyebrow>Uji Coba Produk</x-slot:eyebrow>
    </x-ui.page-header>

    @if ($submitted)
        <section class="card card-body text-center" role="status" aria-live="polite">
            <span class="icon-chip icon-chip-lg mx-auto mb-4 rounded-full">
                <x-tabler-circle-check class="size-7" />
            </span>
            <h2 class="text-lg font-semibold text-slate-900">Terima kasih atas penilaian Anda</h2>
            <p class="mx-auto mt-1 max-w-md text-sm text-slate-500">
                Jawaban Anda sudah tersimpan dan akan membantu kami menyempurnakan website ini.
            </p>

            <div class="mx-auto mt-6 grid max-w-sm grid-cols-2 divide-x divide-slate-100 rounded-2xl border border-slate-200">
                <div class="px-4 py-4">
                    <p class="eyebrow inline-flex items-center gap-1"><x-tabler-star class="size-3.5" /> Skor kegunaan</p>
                    <p class="mt-1 text-3xl font-bold tracking-tight text-slate-900 num">{{ number_format($resultScore, 1, ',', '.') }}</p>
                    <p class="text-xs text-slate-400">dari 100</p>
                </div>
                <div class="px-4 py-4">
                    <p class="eyebrow inline-flex items-center gap-1"><x-tabler-award class="size-3.5" /> Penilaian</p>
                    <p class="mt-1 text-3xl font-bold tracking-tight text-accent-700">{{ $grade['grade'] }}</p>
                    <p class="text-xs text-slate-500">{{ $grade['label'] }}</p>
                </div>
            </div>

            <div class="mt-6">
                <a href="{{ route('home') }}" wire:navigate class="btn btn-secondary">
                    <x-tabler-home-2 class="size-4" /> Kembali ke Beranda
                </a>
            </div>
        </section>
    @else
        <div class="alert alert-info">
            <x-tabler-info-circle class="size-5 shrink-0 text-slate-500" />
            <div class="space-y-0.5">
                <p>Kuesioner bersifat <strong class="font-semibold text-slate-900">anonim</strong> (nama boleh dikosongkan) dan hanya membutuhkan sekitar <strong class="font-semibold text-slate-900">2 menit</strong>.</p>
                <p class="text-slate-500">Tidak ada jawaban benar atau salah. Pilih yang paling sesuai dengan pengalaman Anda.</p>
            </div>
        </div>

        <form wire:submit="submit" class="space-y-6" novalidate>
            @error('rateLimit')
                <div class="alert alert-error" role="alert">
                    <x-tabler-clock class="size-5 shrink-0" />
                    <span>{{ $message }}</span>
                </div>
            @enderror

            <x-ui.card :icon="'tabler-user'" title="Tentang Anda" description="Opsional.">
                <div class="max-w-sm">
                        <label for="respondentName" class="label">Nama (opsional)</label>
                        <input id="respondentName" type="text" wire:model="respondentName" class="input" placeholder="Boleh dikosongkan" maxlength="100" autocomplete="name">
                        @error('respondentName') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                </div>
            </x-ui.card>

            <x-ui.card :icon="'tabler-list-check'" title="Pernyataan" description="Beri tanda pada skala 1 (Sangat Tidak Setuju) sampai 5 (Sangat Setuju)." :padded="false">
                <ol class="divide-y divide-slate-100">
                    @foreach ($items as $number => $statement)
                        <li wire:key="item-{{ $number }}" class="px-5 py-5 sm:px-6">
                            <fieldset>
                                <legend class="flex gap-3 text-sm text-slate-800">
                                    <span class="flex size-6 shrink-0 items-center justify-center rounded-full bg-slate-100 text-xs font-semibold text-slate-600 num">{{ $number }}</span>
                                    <span class="pt-0.5">{{ $statement }}</span>
                                </legend>

                                <div class="mt-3 sm:pl-9">
                                    <div @class([
                                        'grid grid-cols-5 overflow-hidden rounded-xl border bg-white',
                                        'border-rose-300' => $errors->has('answers.'.$number),
                                        'border-slate-200' => ! $errors->has('answers.'.$number),
                                    ])>
                                        @foreach ($scale as $value => $option)
                                            <label class="relative cursor-pointer border-slate-200 not-first:border-l">
                                                <input type="radio"
                                                       name="answers-{{ $number }}"
                                                       value="{{ $value }}"
                                                       wire:model="answers.{{ $number }}"
                                                       class="peer sr-only"
                                                       aria-label="{{ $value }} — {{ $option['label'] }}">
                                                <span class="flex h-11 flex-col items-center justify-center text-sm font-semibold text-slate-600 transition hover:bg-slate-50 peer-checked:bg-accent-600 peer-checked:text-white peer-focus-visible:ring-2 peer-focus-visible:ring-inset peer-focus-visible:ring-accent-500 num">
                                                    {{ $value }}
                                                </span>
                                            </label>
                                        @endforeach
                                    </div>
                                    <div class="mt-1.5 flex justify-between text-[11px] text-slate-400" aria-hidden="true">
                                        <span>Sangat Tidak Setuju</span>
                                        <span>Sangat Setuju</span>
                                    </div>
                                    @error('answers.'.$number) <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                                </div>
                            </fieldset>
                        </li>
                    @endforeach
                </ol>
            </x-ui.card>

            <x-ui.card :icon="'tabler-message-circle'" title="Saran atau komentar" description="Opsional. Ceritakan hal yang menurut Anda perlu diperbaiki.">
                <label for="comment" class="sr-only">Komentar</label>
                <textarea id="comment" wire:model="comment" rows="4" maxlength="1000" class="input" placeholder="Tulis saran Anda di sini..."></textarea>
                @error('comment') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
            </x-ui.card>

            @if ($errors->any() && ! $errors->has('rateLimit'))
                <div class="alert alert-error" role="alert">
                    <x-tabler-alert-triangle class="size-5 shrink-0" />
                    <span>Masih ada pernyataan yang belum dijawab. Silakan periksa kembali isian yang ditandai.</span>
                </div>
            @endif

            <div class="flex flex-col-reverse gap-3 sm:flex-row sm:items-center sm:justify-between">
                <p class="inline-flex items-center gap-1.5 text-xs text-slate-500"><x-tabler-lock class="size-3.5 shrink-0 text-slate-400" /> Data hanya digunakan untuk keperluan penelitian.</p>
                <button type="submit" class="btn btn-accent" wire:loading.attr="disabled" wire:target="submit">
                    <x-tabler-send class="size-4" />
                    <span wire:loading.remove wire:target="submit">Kirim Jawaban</span>
                    <span wire:loading wire:target="submit">Mengirim...</span>
                </button>
            </div>
        </form>
    @endif
</div>
