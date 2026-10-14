<?php

namespace Tests\Feature;

use App\Livewire\RoomManagement;
use App\Models\Node;
use App\Models\Room;
use App\Models\User;
use Database\Seeders\SetupRbacSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class RoomManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(SetupRbacSeeder::class);
    }

    public function test_admin_can_view_room_management_page()
    {
        $admin = User::where('email', 'admin@siasap.test')->first();

        $this->actingAs($admin)
            ->get(route('admin.rooms'))
            ->assertStatus(200)
            ->assertSee('Ruangan & Perangkat')
            ->assertSee('Ruang Tamu')
            ->assertSee('Kamar')
            ->assertSee('Dapur');
    }

    public function test_regular_user_cannot_access_room_management_page()
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('admin.rooms'))
            ->assertStatus(403);
    }

    public function test_admin_can_create_new_room_via_livewire()
    {
        $admin = User::where('email', 'admin@siasap.test')->first();

        Livewire::actingAs($admin)
            ->test(RoomManagement::class)
            ->set('roomName', 'Garasi Utama')
            ->set('roomCode', 'ROOM-GARASI')
            ->set('roomDescription', 'Area tempat parkir kendaraan.')
            ->call('saveRoom')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('rooms', [
            'name' => 'Garasi Utama',
            'code' => 'ROOM-GARASI',
        ]);
    }

    public function test_admin_can_create_new_node_and_assign_to_room()
    {
        $admin = User::where('email', 'admin@siasap.test')->first();
        $room = Room::where('code', 'ROOM-TAMU')->first();

        Livewire::actingAs($admin)
            ->test(RoomManagement::class)
            ->set('nodeRoomId', $room->id)
            ->set('nodeName', 'Node Testing Extra')
            ->set('nodeCode', 'NODE-99')
            ->set('nodeDescription', 'Node untuk pengujian otomatis')
            ->set('nodeIsActive', true)
            ->call('saveNode')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('nodes', [
            'name' => 'Node Testing Extra',
            'code' => 'NODE-99',
            'room_id' => $room->id,
        ]);
    }

    public function test_nodes_tab_lists_nodes_with_status()
    {
        $admin = User::where('email', 'admin@siasap.test')->first();
        Node::create(['name' => 'Node Cadangan', 'code' => 'NODE-OFF', 'is_active' => false]);

        Livewire::actingAs($admin)
            ->test(RoomManagement::class)
            ->call('setTab', 'nodes')
            ->assertSee('Node Cadangan')
            ->assertSee('NODE-OFF')
            ->assertSee('Nonaktif')
            ->assertSee('Belum ditempatkan')
            ->call('openCreateNodeModal')
            ->assertSet('showNodeModal', true)
            ->assertSee('Tambah Node Sensor')
            ->call('closeNodeModal')
            ->assertSet('showNodeModal', false);
    }

    public function test_admin_can_delete_room_and_node()
    {
        $admin = User::where('email', 'admin@siasap.test')->first();
        $room = Room::create(['name' => 'Gudang', 'code' => 'ROOM-GUDANG']);
        $node = Node::create(['name' => 'Node Gudang', 'code' => 'NODE-GDG', 'room_id' => $room->id, 'is_active' => true]);

        Livewire::actingAs($admin)
            ->test(RoomManagement::class)
            ->call('deleteNode', $node->id)
            ->call('deleteRoom', $room->id);

        $this->assertDatabaseMissing('nodes', ['id' => $node->id]);
        $this->assertDatabaseMissing('rooms', ['id' => $room->id]);
    }

    public function test_mutating_actions_require_rooms_manage_permission()
    {
        $admin = User::where('email', 'admin@siasap.test')->first();
        $room = Room::where('code', 'ROOM-TAMU')->first();

        $component = Livewire::actingAs($admin)->test(RoomManagement::class);

        // Hak akses dicabut setelah halaman terbuka: aksi tetap harus ditolak.
        $admin->roles()->detach();
        $this->app['auth']->forgetGuards();
        Livewire::actingAs($admin->fresh());

        $component->call('deleteRoom', $room->id)->assertForbidden();

        $this->assertDatabaseHas('rooms', ['id' => $room->id]);
    }
}
