<?php

namespace Tests\Feature;

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
            ->get(route('room-management'))
            ->assertStatus(200)
            ->assertSee('Master Ruangan')
            ->assertSee('Ruang Tamu')
            ->assertSee('Kamar')
            ->assertSee('Dapur');
    }

    public function test_regular_user_cannot_access_room_management_page()
    {
        $user = User::where('email', 'user@siasap.test')->first();

        $this->actingAs($user)
            ->get(route('room-management'))
            ->assertStatus(403);
    }

    public function test_admin_can_create_new_room_via_livewire()
    {
        $admin = User::where('email', 'admin@siasap.test')->first();

        Livewire::actingAs($admin)
            ->test(\App\Livewire\RoomManagement::class)
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
            ->test(\App\Livewire\RoomManagement::class)
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
}
