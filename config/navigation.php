<?php

/*
|--------------------------------------------------------------------------
| Menu Sidebar Panel Admin
|--------------------------------------------------------------------------
|
| Item hanya ditampilkan bila pengguna memiliki permission terkait
| (null = semua pengguna yang login). 'active' adalah pola nama route untuk
| menandai menu aktif; default sama dengan 'route'.
|
*/

return [
    [
        'title' => 'Ringkasan',
        'route' => 'admin.overview',
        'icon' => 'tabler-layout-dashboard',
        'permission' => 'telemetry.view',
        'section' => 'Pemantauan',
    ],
    [
        'title' => 'Data Sensor',
        'route' => 'admin.data',
        'icon' => 'tabler-table',
        'permission' => 'telemetry.view',
        'section' => 'Pemantauan',
    ],
    [
        'title' => 'Ruangan & Perangkat',
        'route' => 'admin.rooms',
        'icon' => 'tabler-building-community',
        'permission' => 'rooms.manage',
        'section' => 'Pengelolaan',
    ],
    [
        'title' => 'Pengujian Jaringan',
        'route' => 'admin.qos',
        'icon' => 'tabler-topology-star-3',
        'permission' => 'qos.view',
        'section' => 'Pengujian',
    ],
];
