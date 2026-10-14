<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Tabler Icons
    |--------------------------------------------------------------------------
    |
    | Komponen dipanggil dengan prefix "tabler", contoh: <x-tabler-wind />.
    | Fallback dikosongkan agar nama ikon yang salah langsung memicu error
    | (tertangkap oleh test), bukan diam-diam menampilkan ikon lain.
    | Garis 1.75 agar tampilan lebih ringan dan selaras dengan tipografi.
    |
    */

    'prefix' => 'tabler',

    'fallback' => '',

    'class' => '',

    'attributes' => [
        'stroke-width' => 1.75,
    ],

];
