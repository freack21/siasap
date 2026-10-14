<?php

namespace Tests\Feature;

use App\Livewire\AdminOverview;
use App\Models\Node;
use App\Models\Role;
use App\Models\Room;
use App\Models\SensorLog;
use App\Models\TestingSession;
use App\Models\User;
use Database\Seeders\SetupRbacSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class AdminOverviewTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(SetupRbacSeeder::class);
        // Mulai dari kondisi bersih: perangkat dibuat sendiri di setiap tes.
        Node::query()->delete();
        Room::query()->delete();
    }

    private function admin(): User
    {
        return User::where('email', 'admin@siasap.test')->firstOrFail();
    }

    private function log(Node $node, array $attributes = []): SensorLog
    {
        $log = SensorLog::create(array_merge([
            'node_id' => $node->id,
            'co2' => 600, 'nh3' => 2, 'co' => 1, 'h2' => 10,
            'ku_value' => 30, 'ku_status' => 'Baik', 'ku_dominant' => 'co2',
            'rssi' => -70, 'seq_number' => 1, 'hop_count' => 1, 'topology_type' => 'star',
        ], array_diff_key($attributes, ['created_at' => true])));

        if (isset($attributes['created_at'])) {
            $log->forceFill(['created_at' => $attributes['created_at']])->save();
        }

        return $log;
    }

    public function test_user_without_permission_gets_403(): void
    {
        $role = Role::create(['name' => 'viewer', 'display_name' => 'Tanpa Akses']);
        $user = User::factory()->create();
        $user->roles()->attach($role);

        $this->actingAs($user)->get(route('admin.overview'))->assertForbidden();
    }

    public function test_guest_is_redirected_to_login(): void
    {
        $this->get(route('admin.overview'))->assertRedirect(route('login'));
    }

    public function test_overview_shows_device_status_and_counts(): void
    {
        $room = Room::create(['name' => 'Laboratorium Uji', 'code' => 'LAB']);
        $online = Node::create(['room_id' => $room->id, 'name' => 'Node Alfa', 'code' => 'N-A', 'is_active' => true]);
        $offline = Node::create(['room_id' => $room->id, 'name' => 'Node Beta', 'code' => 'N-B', 'is_active' => true]);

        $this->log($online, ['rssi' => -61, 'seq_number' => 4242, 'hop_count' => 2]);
        $this->log($offline, ['rssi' => null, 'created_at' => now()->subMinutes(10)]);
        TestingSession::create(['name' => 'Uji Mesh Pagi', 'topology_type' => 'mesh', 'status' => 'active']);

        $this->actingAs($this->admin())
            ->get(route('admin.overview'))
            ->assertOk()
            ->assertSee('Node Alfa')
            ->assertSee('Node Beta')
            ->assertSee('Laboratorium Uji')
            ->assertSee('Daring')
            ->assertSee('Luring')
            ->assertSee('-61 dBm')
            ->assertSee('4242')
            ->assertSee('1 / 2')
            ->assertSee('Uji Mesh Pagi');
    }

    public function test_overview_lists_recent_alerts(): void
    {
        $room = Room::create(['name' => 'Dapur Uji', 'code' => 'DPR']);
        $node = Node::create(['room_id' => $room->id, 'name' => 'Node Dapur', 'code' => 'N-D', 'is_active' => true]);

        $this->log($node, ['ku_value' => 350, 'ku_status' => 'Berbahaya', 'ku_dominant' => 'co']);

        Livewire::actingAs($this->admin())
            ->test(AdminOverview::class)
            ->assertSee('Peringatan Terbaru')
            ->assertSee('Berbahaya')
            ->assertSee('Dominan: CO')
            ->assertDontSee('Tidak ada peringatan');
    }

    public function test_overview_shows_empty_alert_state(): void
    {
        $room = Room::create(['name' => 'Kamar Uji', 'code' => 'KMR']);
        $node = Node::create(['room_id' => $room->id, 'name' => 'Node Kamar', 'code' => 'N-K', 'is_active' => true]);
        $this->log($node);

        Livewire::actingAs($this->admin())
            ->test(AdminOverview::class)
            ->assertSee('Tidak ada peringatan')
            ->assertSee('Tidak ada'); // tidak ada sesi aktif
    }
}
