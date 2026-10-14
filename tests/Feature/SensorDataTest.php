<?php

namespace Tests\Feature;

use App\Livewire\TelemetryDashboard;
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

class SensorDataTest extends TestCase
{
    use RefreshDatabase;

    private Node $kitchen;

    private Node $bedroom;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(SetupRbacSeeder::class);

        $kitchenRoom = Room::create(['name' => 'Dapur Uji', 'code' => 'T-DPR']);
        $bedroomRoom = Room::create(['name' => 'Kamar Uji', 'code' => 'T-KMR']);
        $this->kitchen = Node::create(['room_id' => $kitchenRoom->id, 'name' => 'Node Dapur Uji', 'code' => 'T-N1', 'is_active' => true]);
        $this->bedroom = Node::create(['room_id' => $bedroomRoom->id, 'name' => 'Node Kamar Uji', 'code' => 'T-N2', 'is_active' => true]);
    }

    private function admin(): User
    {
        return User::where('email', 'admin@siasap.test')->firstOrFail();
    }

    private function log(Node $node, array $attributes = []): SensorLog
    {
        return SensorLog::create(array_merge([
            'node_id' => $node->id,
            'co2' => 600, 'nh3' => 2, 'co' => 1, 'h2' => 10,
            'ku_value' => 30, 'ku_status' => 'Baik', 'ku_dominant' => 'co2',
            'rssi' => -70, 'seq_number' => 1, 'hop_count' => 1, 'topology_type' => 'star',
        ], $attributes));
    }

    public function test_user_without_permission_gets_403(): void
    {
        $role = Role::create(['name' => 'viewer', 'display_name' => 'Tanpa Akses']);
        $user = User::factory()->create();
        $user->roles()->attach($role);

        $this->actingAs($user)->get(route('admin.data'))->assertForbidden();
    }

    public function test_page_renders_table_and_null_rssi_as_dash(): void
    {
        $this->log($this->kitchen, ['rssi' => null, 'seq_number' => 777, 'co2' => 812.5]);

        $this->actingAs($this->admin())
            ->get(route('admin.data'))
            ->assertOk()
            ->assertSee('Data Sensor')
            ->assertSee('Node Dapur Uji')
            ->assertSee('Dapur Uji')
            ->assertSee('812.50')
            ->assertSee('777')
            ->assertSeeInOrder(['<td class="num text-right whitespace-nowrap">', '—'], false)
            ->assertDontSee('dBm');
    }

    public function test_filters_narrow_the_results(): void
    {
        $session = TestingSession::create(['name' => 'Sesi Mesh A', 'topology_type' => 'mesh', 'status' => 'completed']);

        $this->log($this->kitchen, ['seq_number' => 1001, 'ku_value' => 200, 'ku_status' => 'Tidak Sehat', 'testing_session_id' => $session->id, 'topology_type' => 'mesh']);
        $this->log($this->bedroom, ['seq_number' => 2002]);
        $old = $this->log($this->bedroom, ['seq_number' => 3003]);
        $old->forceFill(['created_at' => now()->subDays(10)])->save();

        $admin = $this->admin();

        Livewire::actingAs($admin)->test(TelemetryDashboard::class)
            ->set('node', (string) $this->kitchen->id)
            ->assertSee('1001')->assertDontSee('2002')->assertDontSee('3003');

        Livewire::actingAs($admin)->test(TelemetryDashboard::class)
            ->set('category', 'Tidak Sehat')
            ->assertSee('1001')->assertDontSee('2002');

        Livewire::actingAs($admin)->test(TelemetryDashboard::class)
            ->set('session', (string) $session->id)
            ->assertSee('1001')->assertDontSee('2002');

        Livewire::actingAs($admin)->test(TelemetryDashboard::class)
            ->set('session', TelemetryDashboard::OUTSIDE_SESSION)
            ->assertDontSee('1001')->assertSee('2002')->assertSee('3003');

        Livewire::actingAs($admin)->test(TelemetryDashboard::class)
            ->set('dateFrom', now()->subDay()->toDateString())
            ->set('dateTo', now()->toDateString())
            ->assertSee('2002')->assertDontSee('3003')
            ->call('resetFilters')
            ->assertSet('dateFrom', '')
            ->assertSee('3003');
    }

    public function test_filters_are_read_from_query_string(): void
    {
        $this->log($this->kitchen, ['seq_number' => 1001]);
        $this->log($this->bedroom, ['seq_number' => 2002]);

        $this->actingAs($this->admin())
            ->get(route('admin.data', ['node' => $this->bedroom->id]))
            ->assertOk()
            ->assertSee('2002')
            ->assertDontSee('1001');
    }

    public function test_csv_export_contains_header_and_filtered_rows(): void
    {
        $this->log($this->kitchen, ['seq_number' => 1001, 'rssi' => null, 'ku_dominant' => 'nh3']);
        $this->log($this->bedroom, ['seq_number' => 2002]);

        $component = Livewire::actingAs($this->admin())
            ->test(TelemetryDashboard::class)
            ->set('node', (string) $this->kitchen->id)
            ->call('export')
            ->assertFileDownloaded();

        $csv = base64_decode($component->effects['download']['content']);
        $lines = array_values(array_filter(explode("\n", trim($csv))));

        $this->assertCount(2, $lines);
        $this->assertStringStartsWith('id,waktu,node_id,node,ruangan,co2_ppm,nh3_ppm,co_ppm,h2_ppm,ku_value,ku_status,ku_dominant,rssi_dbm,hop_count,seq_number,topology_type,testing_session_id,sesi', $lines[0]);
        $this->assertStringContainsString('"Node Dapur Uji","Dapur Uji"', $lines[1]);
        $this->assertStringContainsString(',nh3,,1,1001,star,,', $lines[1]);
    }
}
