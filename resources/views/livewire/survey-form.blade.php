<div class="mx-auto max-w-3xl space-y-6">
    <x-ui.page-header
        :icon="'tabler-clipboard-check'"
        title="Kuesioner Pengalaman Pengguna"
        description="Bantu kami menilai pengalaman Anda menggunakan website SIASAP dengan User Experience Questionnaire (UEQ). Penilaian ini merupakan bagian dari penelitian skripsi.">
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
                <p class="text-slate-500">Setiap baris berisi dua istilah yang berlawanan makna. Pilih lingkaran yang paling dekat dengan kesan Anda, dan putuskan secara spontan tanpa berpikir terlalu lama. Tidak ada jawaban benar atau salah, dan mohon setiap baris diisi walaupun Anda kurang yakin.</p>
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

            <x-ui.card :icon="'tabler-list-check'" title="Penilaian" description="Pilih satu lingkaran pada tiap baris, dari 1 (dekat istilah kiri) sampai 7 (dekat istilah kanan)." :padded="false">
                <ol class="divide-y divide-slate-100">
                    @foreach ($items as $number => [$left, $right])
                        <li wire:key="item-{{ $number }}" class="px-5 py-4 sm:px-6">
                            <fieldset class="flex items-center gap-3">
                                <legend class="sr-only">Butir {{ $number }}: {{ $left }} atau {{ $right }}</legend>
                                <span class="hidden size-6 shrink-0 items-center justify-center rounded-full bg-slate-100 text-xs font-semibold text-slate-600 num sm:flex" aria-hidden="true">{{ $number }}</span>
                                <span class="hidden w-36 shrink-0 text-right text-sm font-medium text-slate-800 sm:block" aria-hidden="true">{{ $left }}</span>
                                <div class="min-w-0 flex-1">
                                    <div class="mb-1.5 flex justify-between gap-3 text-sm font-medium text-slate-800 sm:hidden" aria-hidden="true">
                                        <span><span class="num mr-1.5 text-xs text-slate-400">{{ $number }}.</span>{{ $left }}</span>
                                        <span class="text-right">{{ $right }}</span>
                                    </div>
                                    <div @class([
                                        'grid grid-cols-7 overflow-hidden rounded-xl border bg-white',
                                        'border-rose-300' => $errors->has('answers.'.$number),
                                        'border-slate-200' => ! $errors->has('answers.'.$number),
                                    ])>
                                        @foreach ($points as $value)
                                            <label class="relative cursor-pointer border-slate-200 not-first:border-l">
                                                <input type="radio"
                                                       name="answers-{{ $number }}"
                                                       value="{{ $value }}"
                                                       wire:model="answers.{{ $number }}"
                                                       class="peer sr-only"
                                                       aria-label="{{ $value }} dari 7 ({{ $value < 4 ? 'lebih dekat ke '.$left : ($value > 4 ? 'lebih dekat ke '.$right : 'netral') }})">
                                                <span class="flex h-11 items-center justify-center text-sm font-semibold text-slate-600 transition hover:bg-slate-50 peer-checked:bg-accent-600 peer-checked:text-white peer-focus-visible:ring-2 peer-focus-visible:ring-inset peer-focus-visible:ring-accent-500 num">
                                                    {{ $value }}
                                                </span>
                                            </label>
                                        @endforeach
                                    </div>
                                    @error('answers.'.$number) <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                                </div>
                                <span class="hidden w-36 shrink-0 text-sm font-medium text-slate-800 sm:block" aria-hidden="true">{{ $right }}</span>
                            </fieldset>
                        </li>
                    @endforeach
                </ol>
            </x-ui.card>

            @if ($errors->any() && ! $errors->has('rateLimit'))
                <div class="alert alert-error" role="alert">
                    <x-tabler-alert-triangle class="size-5 shrink-0" />
                    <span>Masih ada baris yang belum dijawab. Silakan periksa kembali isian yang ditandai.</span>
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
