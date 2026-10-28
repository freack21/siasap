{{--
    Peringatan data usang: tampil bila perangkat tidak terhubung (data terakhir
    lebih lama dari ambang daring) sehingga kondisi di bawahnya bukan kondisi terkini.
    $updatedAt = waktu server data terakhir diterima (created_at).
--}}
<div class="alert alert-warning" role="alert">
    <x-tabler-alert-triangle class="mt-0.5 size-5 shrink-0" />
    <p>
        Data terakhir diterima <span class="font-semibold">{{ $updatedAt->locale('id')->diffForHumans() }}</span>
        — bukan kondisi terkini. Periksa perangkat pemantau.
    </p>
</div>
