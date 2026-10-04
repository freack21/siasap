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
            'description' => 'Akses monitoring telemetri dan manajemen sesi eksperimen QoS topologi.',
        ]);

        $operatorRole = Role::firstOrCreate(['name' => 'operator'], [
            'display_name' => 'Operator Sensor',
            'description' => 'Akses baca monitoring status node sensor real-time.',
        ]);

        // 2. Create Permissions
        $permissions = [
            ['name' => 'telemetry.view', 'display_name' => 'Lihat Dashboard Telemetri Sensor', 'group' => 'Telemetry'],
            ['name' => 'telemetry.simulate', 'display_name' => 'Jalankan Simulasi Transmisi ESP32', 'group' => 'Telemetry'],
            ['name' => 'qos.view', 'display_name' => 'Lihat Analytics & Benchmark QoS', 'group' => 'QoS Benchmarking'],
            ['name' => 'session.manage', 'display_name' => 'Mulai & Kelola Sesi Pengujian Topologi', 'group' => 'QoS Benchmarking'],
            ['name' => 'users.manage', 'display_name' => 'Kelola Pengguna & Hak Akses (RBAC)', 'group' => 'Administration'],
            ['name' => 'navigation.manage', 'display_name' => 'Kelola Navigasi Sistem', 'group' => 'Administration'],
        ];

        $permModels = [];
        foreach ($permissions as $perm) {
            $permModels[$perm['name']] = Permission::firstOrCreate(['name' => $perm['name']], $perm);
        }

        // 3. Assign Permissions to Roles
        $adminRole->permissions()->sync(collect($permModels)->pluck('id'));
        $researcherRole->permissions()->sync([
            $permModels['telemetry.view']->id,
            $permModels['telemetry.simulate']->id,
            $permModels['qos.view']->id,
            $permModels['session.manage']->id,
        ]);
        $operatorRole->permissions()->sync([
            $permModels['telemetry.view']->id,
        ]);

        // 4. Create Default Admin User
        $adminUser = User::firstOrCreate(
            ['email' => 'admin@siasap.test'],
            [
                'name' => 'Administrator SIASAP',
                'password' => Hash::make('password'),
            ]
        );
        $adminUser->roles()->sync([$adminRole->id]);

        // 5. Create System Navigations
        $navigations = [
            // Section: Main Monitoring
            [
                'title' => 'Dashboard Telemetri',
                'route_name' => 'telemetry',
                'icon' => 'heroicon-o-cpu-chip',
                'permission_name' => 'telemetry.view',
                'section' => 'MONITORING UTAMA',
                'order' => 1,
            ],
            [
                'title' => 'Benchmark QoS Topologi',
                'route_name' => 'qos-analytics',
                'icon' => 'heroicon-o-chart-bar-square',
                'permission_name' => 'qos.view',
                'section' => 'MONITORING UTAMA',
                'order' => 2,
            ],
            // Section: Lab & Sesi
            [
                'title' => 'Sesi Uji Topologi',
                'route_name' => 'sessions-management',
                'icon' => 'heroicon-o-beaker',
                'permission_name' => 'session.manage',
                'section' => 'LABORATORIUM',
                'order' => 3,
            ],
            // Section: Administrator & Access
            [
                'title' => 'Pengguna & RBAC',
                'route_name' => 'rbac-management',
                'icon' => 'heroicon-o-shield-check',
                'permission_name' => 'users.manage',
                'section' => 'PENGATURAN AKSES',
                'order' => 4,
            ],
            [
                'title' => 'Navigasi Sistem',
                'route_name' => 'navigation-management',
                'icon' => 'heroicon-o-bars-3-center-left',
                'permission_name' => 'navigation.manage',
                'section' => 'PENGATURAN AKSES',
                'order' => 5,
            ],
        ];

        foreach ($navigations as $nav) {
            SystemNavigation::updateOrCreate(
                ['title' => $nav['title']],
                $nav
            );
        }
    }
}
