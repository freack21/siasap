<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\Role;
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
            ['name' => 'qos.view', 'display_name' => 'Lihat Analisis Topologi', 'group' => 'Panel Pengelola'],
            ['name' => 'session.manage', 'display_name' => 'Mulai & Kelola Sesi Pengujian', 'group' => 'Panel Pengelola'],
            ['name' => 'rooms.manage', 'display_name' => 'Kelola Master Ruangan & Node', 'group' => 'Panel Pengelola'],
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
            $permModels['qos.view']->id,
            $permModels['session.manage']->id,
            $permModels['rooms.manage']->id,
        ]);
        $userRole->permissions()->sync([
            $permModels['user.view']->id,
            $permModels['user.history']->id,
        ]);

        // Bersihkan permission lama yang fiturnya sudah dihapus (simulasi data, RBAC & navigasi dinamis)
        Permission::whereIn('name', ['telemetry.simulate', 'users.manage', 'navigation.manage'])->each(function (Permission $permission) {
            $permission->roles()->detach();
            $permission->delete();
        });

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
    }
}
