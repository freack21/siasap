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
            'display_name' => 'Super Administrator',
            'description' => 'Akses penuh ke seluruh sistem telemetri, QoS, dan manajemen pengguna.',
        ]);

        $researcherRole = Role::firstOrCreate(['name' => 'researcher'], [
            'display_name' => 'Laboran / Peneliti QoS',
            'description' => 'Akses monitoring telemetri teknis dan manajemen sesi eksperimen QoS topologi.',
        ]);

        $userRole = Role::firstOrCreate(['name' => 'user'], [
            'display_name' => 'Pengguna Umum / Publik',
            'description' => 'Akses tampilan kualitas udara awam yang ramah pengguna & riwayat data.',
        ]);

        // 2. Create Permissions
        $permissions = [
            ['name' => 'user.view', 'display_name' => 'Lihat Dashboard Udara Publik', 'group' => 'Tampilan Publik'],
            ['name' => 'user.history', 'display_name' => 'Lihat Riwayat Udara Publik', 'group' => 'Tampilan Publik'],
            ['name' => 'telemetry.view', 'display_name' => 'Lihat Telemetri Teknis (Node/Sinyal)', 'group' => 'Panel Admin & Teknis'],
            ['name' => 'telemetry.simulate', 'display_name' => 'Jalankan Simulasi Transmisi ESP32', 'group' => 'Panel Admin & Teknis'],
            ['name' => 'qos.view', 'display_name' => 'Lihat Analytics & Benchmark QoS', 'group' => 'Panel Admin & Teknis'],
            ['name' => 'session.manage', 'display_name' => 'Mulai & Kelola Sesi Pengujian Topologi', 'group' => 'Panel Admin & Teknis'],
            ['name' => 'users.manage', 'display_name' => 'Kelola Pengguna & Hak Akses (RBAC)', 'group' => 'Administrator'],
            ['name' => 'navigation.manage', 'display_name' => 'Kelola Navigasi Sistem', 'group' => 'Administrator'],
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
        ]);
        $userRole->permissions()->sync([
            $permModels['user.view']->id,
            $permModels['user.history']->id,
        ]);

        // 4. Create Default Admin & User Accounts
        $adminUser = User::firstOrCreate(
            ['email' => 'admin@siasap.test'],
            [
                'name' => 'Administrator SIASAP',
                'password' => Hash::make('password'),
            ]
        );
        $adminUser->roles()->sync([$adminRole->id]);

        $publicUser = User::firstOrCreate(
            ['email' => 'user@siasap.test'],
            [
                'name' => 'Pengguna Umum SIASAP',
                'password' => Hash::make('password'),
            ]
        );
        $publicUser->roles()->sync([$userRole->id]);

        // 5. Create System Navigations
        $navigations = [
            // Section: Tampilan Publik / User
            [
                'title' => 'Kualitas Udara (User)',
                'route_name' => 'user.dashboard',
                'icon' => 'heroicon-o-sparkles',
                'permission_name' => 'user.view',
                'section' => 'TAMPILAN PUBLIK (USER)',
                'order' => 1,
            ],
            [
                'title' => 'Riwayat Kualitas Udara',
                'route_name' => 'user.history',
                'icon' => 'heroicon-o-clock',
                'permission_name' => 'user.history',
                'section' => 'TAMPILAN PUBLIK (USER)',
                'order' => 2,
            ],

            // Section: Panel Admin & Teknis
            [
                'title' => 'Telemetri Teknis (Admin)',
                'route_name' => 'telemetry',
                'icon' => 'heroicon-o-cpu-chip',
                'permission_name' => 'telemetry.view',
                'section' => 'PANEL ADMIN & LABORATORIUM',
                'order' => 3,
            ],
            [
                'title' => 'Benchmark QoS Topologi',
                'route_name' => 'qos-analytics',
                'icon' => 'heroicon-o-chart-bar-square',
                'permission_name' => 'qos.view',
                'section' => 'PANEL ADMIN & LABORATORIUM',
                'order' => 4,
            ],

            // Section: Administrator & RBAC
            [
                'title' => 'Pengguna & RBAC',
                'route_name' => 'rbac-management',
                'icon' => 'heroicon-o-shield-check',
                'permission_name' => 'users.manage',
                'section' => 'PENGATURAN HAK AKSES',
                'order' => 5,
            ],
            [
                'title' => 'Navigasi Sistem',
                'route_name' => 'navigation-management',
                'icon' => 'heroicon-o-bars-3-center-left',
                'permission_name' => 'navigation.manage',
                'section' => 'PENGATURAN HAK AKSES',
                'order' => 6,
            ],
        ];

        SystemNavigation::truncate();

        foreach ($navigations as $nav) {
            SystemNavigation::create($nav);
        }
    }
}
