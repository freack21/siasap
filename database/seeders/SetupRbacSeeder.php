<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\Role;
use App\Models\SystemNavigation;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class SetupRbacSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 1. Create Roles
        $adminRole = Role::firstOrCreate(['name' => 'admin'], [
            'display_name' => 'Pengelola Sistem',
            'description' => 'Akses penuh ke seluruh sistem pemantauan, analisis teknis, dan manajemen pengguna.',
        ]);

        $researcherRole = Role::firstOrCreate(['name' => 'researcher'], [
            'display_name' => 'Peneliti / Laboran',
            'description' => 'Akses monitoring teknis dan manajemen sesi pengujian jaringan.',
        ]);

        $userRole = Role::firstOrCreate(['name' => 'user'], [
            'display_name' => 'Pengguna Umum',
            'description' => 'Akses tampilan kualitas udara yang ramah pengguna & riwayat data.',
        ]);

        // 2. Create Permissions
        $permissions = [
            ['name' => 'user.view', 'display_name' => 'Lihat Pemantauan Udara', 'group' => 'Pemantauan Utama'],
            ['name' => 'user.history', 'display_name' => 'Lihat Riwayat Udara', 'group' => 'Pemantauan Utama'],
            ['name' => 'telemetry.view', 'display_name' => 'Lihat Telemetri Teknis', 'group' => 'Panel Pengelola'],
            ['name' => 'telemetry.simulate', 'display_name' => 'Jalankan Simulasi Transmisi', 'group' => 'Panel Pengelola'],
            ['name' => 'qos.view', 'display_name' => 'Lihat Analisis Topologi', 'group' => 'Panel Pengelola'],
            ['name' => 'session.manage', 'display_name' => 'Mulai & Kelola Sesi Pengujian', 'group' => 'Panel Pengelola'],
            ['name' => 'rooms.manage', 'display_name' => 'Kelola Master Ruangan & Node', 'group' => 'Panel Pengelola'],
            ['name' => 'users.manage', 'display_name' => 'Kelola Pengguna & Hak Akses', 'group' => 'Pengaturan'],
            ['name' => 'navigation.manage', 'display_name' => 'Kelola Navigasi Sistem', 'group' => 'Pengaturan'],
        ];

        $permModels = [];
        foreach ($permissions as $perm) {
            $permModels[$perm['name']] = Permission::firstOrCreate(['name' => $perm['name']], $perm);
        }

        // 3. Assign Permissions to Roles
        $adminRole->permissions()->sync(collect($permModels)->pluck('id'));
        $researcherRole->permissions()->sync([
            $permModels['user.view']->id,
            $permModels['user.history']->id,
            $permModels['telemetry.view']->id,
            $permModels['telemetry.simulate']->id,
            $permModels['qos.view']->id,
            $permModels['session.manage']->id,
            $permModels['rooms.manage']->id,
        ]);
        $userRole->permissions()->sync([
            $permModels['user.view']->id,
            $permModels['user.history']->id,
        ]);

        // 4. Create Default Admin & User Accounts
        $adminUser = User::firstOrCreate(
            ['email' => 'admin@siasap.test'],
            [
                'name' => 'Pengelola SIASAP',
                'password' => Hash::make('password'),
            ]
        );
        $adminUser->roles()->sync([$adminRole->id]);

        $publicUser = User::firstOrCreate(
            ['email' => 'user@siasap.test'],
            [
                'name' => 'Pengguna Umum',
                'password' => Hash::make('password'),
            ]
        );
        $publicUser->roles()->sync([$userRole->id]);

        // 5. Seed 3 Default Rooms & 3 Nodes
        $roomTamu = \App\Models\Room::firstOrCreate(
            ['code' => 'ROOM-TAMU'],
            [
                'name' => 'Ruang Tamu',
                'description' => 'Area ruang tamu utama untuk menyambut pengunjung.',
            ]
        );

        $roomKamar = \App\Models\Room::firstOrCreate(
            ['code' => 'ROOM-KAMAR'],
            [
                'name' => 'Kamar',
                'description' => 'Area kamar tidur.',
            ]
        );

        $roomDapur = \App\Models\Room::firstOrCreate(
            ['code' => 'ROOM-DAPUR'],
            [
                'name' => 'Dapur',
                'description' => 'Area dapur tempat memasak dan pengolahan makanan.',
            ]
        );

        \App\Models\Node::firstOrCreate(
            ['id' => 1],
            [
                'room_id' => $roomTamu->id,
                'name' => 'Node Sensor 1 - Ruang Tamu',
                'code' => 'NODE-01',
                'description' => 'Node sensor utama di area Ruang Tamu.',
                'is_active' => true,
            ]
        );

        \App\Models\Node::firstOrCreate(
            ['id' => 2],
            [
                'room_id' => $roomKamar->id,
                'name' => 'Node Sensor 2 - Kamar',
                'code' => 'NODE-02',
                'description' => 'Node sensor di area Kamar.',
                'is_active' => true,
            ]
        );

        \App\Models\Node::firstOrCreate(
            ['id' => 3],
            [
                'room_id' => $roomDapur->id,
                'name' => 'Node Sensor 3 - Dapur',
                'code' => 'NODE-03',
                'description' => 'Node sensor di area Dapur.',
                'is_active' => true,
            ]
        );

        // 6. Create System Navigations
        $navigations = [
            // Section: Pemantauan Udara
            [
                'title' => 'Kualitas Udara',
                'route_name' => 'user.dashboard',
                'icon' => 'heroicon-o-sparkles',
                'permission_name' => 'user.view',
                'section' => 'PEMANTAUAN UDARA',
                'order' => 1,
            ],
            [
                'title' => 'Riwayat Kualitas Udara',
                'route_name' => 'user.history',
                'icon' => 'heroicon-o-clock',
                'permission_name' => 'user.history',
                'section' => 'PEMANTAUAN UDARA',
                'order' => 2,
            ],

            // Section: Panel Pengelola
            [
                'title' => 'Telemetri Teknis',
                'route_name' => 'telemetry',
                'icon' => 'heroicon-o-cpu-chip',
                'permission_name' => 'telemetry.view',
                'section' => 'PANEL PENGELOLA',
                'order' => 3,
            ],
            [
                'title' => 'Benchmark QoS Topologi',
                'route_name' => 'qos-analytics',
                'icon' => 'heroicon-o-chart-bar-square',
                'permission_name' => 'qos.view',
                'section' => 'PANEL PENGELOLA',
                'order' => 4,
            ],
            [
                'title' => 'Master Ruangan & Node',
                'route_name' => 'room-management',
                'icon' => 'heroicon-o-building-office-2',
                'permission_name' => 'rooms.manage',
                'section' => 'PANEL PENGELOLA',
                'order' => 5,
            ],

            // Section: Pengaturan Hak Akses
            [
                'title' => 'Pengguna & Hak Akses',
                'route_name' => 'rbac-management',
                'icon' => 'heroicon-o-shield-check',
                'permission_name' => 'users.manage',
                'section' => 'PENGATURAN HAK AKSES',
                'order' => 6,
            ],
            [
                'title' => 'Navigasi Sistem',
                'route_name' => 'navigation-management',
                'icon' => 'heroicon-o-bars-3-center-left',
                'permission_name' => 'navigation.manage',
                'section' => 'PENGATURAN HAK AKSES',
                'order' => 7,
            ],
        ];

        SystemNavigation::truncate();

        foreach ($navigations as $nav) {
            SystemNavigation::create($nav);
        }

        // 7. Generate Initial Telemetry Sensor Logs for 3 Nodes (CO2, NH3, CO, H2)
        if (\App\Models\SensorLog::count() === 0) {
            foreach ([1, 2, 3] as $nodeId) {
                $co2 = match ($nodeId) { 1 => 25.5, 2 => 18.0, 3 => 42.0 };
                $nh3 = match ($nodeId) { 1 => 12.0, 2 => 8.5, 3 => 24.0 };
                $co  = match ($nodeId) { 1 => 5.0,  2 => 3.2, 3 => 15.0 };
                $h2  = match ($nodeId) { 1 => 2.5,  2 => 1.8, 3 => 7.5 };

                $kuValue = \App\Models\SensorLog::computeKuValue($co2, $nh3, $co, $h2);
                $kuStatus = \App\Models\SensorLog::computeKuStatus($kuValue);

                \App\Models\SensorLog::create([
                    'node_id' => $nodeId,
                    'co2' => $co2,
                    'nh3' => $nh3,
                    'co' => $co,
                    'h2' => $h2,
                    'ku_value' => $kuValue,
                    'ku_status' => $kuStatus,
                    'rssi' => -55 - ($nodeId * 10),
                    'seq_number' => 1,
                    'hop_count' => ($nodeId === 3 ? 1 : 1),
                    'topology_type' => 'star',
                ]);
            }
        }
    }
}
