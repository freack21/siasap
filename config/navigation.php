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
        'icon' => 'heroicon-o-squares-2x2',
        'permission' => 'telemetry.view',
        'section' => 'Pemantauan',
    ],
    [
        'title' => 'Data Sensor',
        'route' => 'admin.data',
        'icon' => 'heroicon-o-table-cells',
        'permission' => 'telemetry.view',
        'section' => 'Pemantauan',
    ],
    [
        'title' => 'Ruangan & Perangkat',
        'route' => 'admin.rooms',
        'icon' => 'heroicon-o-building-office-2',
        'permission' => 'rooms.manage',
        'section' => 'Pengelolaan',
    ],
    [
        'title' => 'Pengujian Jaringan',
        'route' => 'admin.qos',
        'icon' => 'heroicon-o-signal',
        'permission' => 'qos.view',
        'section' => 'Pengujian',
    ],
    [
        'title' => 'Hasil Kuesioner',
        'route' => 'admin.survey',
        'icon' => 'heroicon-o-clipboard-document-check',
        'permission' => 'survey.view',
        'section' => 'Pengujian',
    ],
];
