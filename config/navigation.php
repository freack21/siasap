<?php

/*
|--------------------------------------------------------------------------
| Menu Navigasi Sidebar
|--------------------------------------------------------------------------
|
| Daftar menu statis untuk sidebar. Setiap item hanya ditampilkan jika
| pengguna memiliki permission yang disebutkan (null = semua pengguna).
| Urutan array menentukan urutan tampilan; item dikelompokkan per section.
|
*/

return [
    [
        'title' => 'Kualitas Udara',
        'route' => 'user.dashboard',
        'icon' => 'heroicon-o-sparkles',
        'permission' => 'user.view',
        'section' => 'PEMANTAUAN UDARA',
    ],
    [
        'title' => 'Riwayat Kualitas Udara',
        'route' => 'user.history',
        'icon' => 'heroicon-o-clock',
        'permission' => 'user.history',
        'section' => 'PEMANTAUAN UDARA',
    ],
    [
        'title' => 'Telemetri Teknis',
        'route' => 'telemetry',
        'icon' => 'heroicon-o-cpu-chip',
        'permission' => 'telemetry.view',
        'section' => 'PANEL PENGELOLA',
    ],
    [
        'title' => 'Benchmark QoS Topologi',
        'route' => 'qos-analytics',
        'icon' => 'heroicon-o-chart-bar-square',
        'permission' => 'qos.view',
        'section' => 'PANEL PENGELOLA',
    ],
    [
        'title' => 'Master Ruangan & Node',
        'route' => 'room-management',
        'icon' => 'heroicon-o-building-office-2',
        'permission' => 'rooms.manage',
        'section' => 'PANEL PENGELOLA',
    ],
];
