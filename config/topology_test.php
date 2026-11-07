<?php

/*
|--------------------------------------------------------------------------
| Uji Topologi Star dan Mesh
|--------------------------------------------------------------------------
|
| Halaman dan endpoint tersembunyi untuk menguji tiga perangkat IoT pada topologi
| star dan mesh. Tidak ditautkan dari menu SIMKU dan tidak diindeks mesin pencari.
| Ganti TOPOLOGY_TEST_PATH di .env dengan kata yang sulit ditebak agar alamatnya
| hanya diketahui peneliti. Halaman: /{path}, endpoint: POST /api/{path}.
|
*/

return [

    'path' => env('TOPOLOGY_TEST_PATH', 'uji-topologi'),

    // Perangkat yang diterima endpoint uji (device_id => nama).
    'devices' => [
        'AIR-001' => 'Node 1',
        'AIR-002' => 'Node 2',
        'AIR-003' => 'Node 3',
    ],

    'topologies' => ['star', 'mesh'],

];
