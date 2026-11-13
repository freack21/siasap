<?php

/*
|--------------------------------------------------------------------------
| Hasil Pengujian Kondisi Ruangan
|--------------------------------------------------------------------------
|
| Halaman tersembunyi untuk mengunduh hasil perintah telemetry:ekspor-uji-ruangan
| (ringkasan dan data mentah per tanggal pengujian). Tidak ditautkan dari menu SIMKU
| dan tidak diindeks mesin pencari. Ganti UJI_RUANGAN_PATH di .env agar alamatnya
| hanya diketahui peneliti.
|
*/

return [

    'path' => env('UJI_RUANGAN_PATH', 'uji-ruangan-88e38d91'),

];
