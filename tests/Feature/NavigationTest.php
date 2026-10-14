<?php

namespace Tests\Feature;

use App\Models\SensorLog;
use App\Models\User;
use Database\Seeders\SetupRbacSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NavigationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(SetupRbacSeeder::class);
    }

    public function test_admin_sees_admin_links_in_sidebar(): void
    {
        $admin = User::where('email', 'admin@siasap.test')->first();

        $this->actingAs($admin)
            ->get(route('user.dashboard'))
            ->assertOk()
            ->assertSee('Kualitas Udara')
            ->assertSee('Riwayat Kualitas Udara')
            ->assertSee('PANEL PENGELOLA')
            ->assertSee(route('telemetry'))
            ->assertSee(route('qos-analytics'))
            ->assertSee(route('room-management'));
    }

    public function test_basic_user_does_not_see_admin_links_in_sidebar(): void
    {
        $user = User::where('email', 'user@siasap.test')->first();

        $this->actingAs($user)
            ->get(route('user.dashboard'))
            ->assertOk()
            ->assertSee(route('user.history'))
            ->assertDontSee('PANEL PENGELOLA')
            ->assertDontSee(route('telemetry'))
            ->assertDontSee(route('qos-analytics'))
            ->assertDontSee(route('room-management'));
    }

    public function test_removed_admin_routes_return_404(): void
    {
        $admin = User::where('email', 'admin@siasap.test')->first();

        foreach (['/admin/sessions-management', '/admin/rbac-management', '/admin/navigation-management'] as $uri) {
            $this->actingAs($admin)->get($uri)->assertNotFound();
        }
    }

    public function test_telemetry_dashboard_has_no_simulation_button(): void
    {
        $admin = User::where('email', 'admin@siasap.test')->first();

        $this->actingAs($admin)
            ->get(route('telemetry'))
            ->assertOk()
            ->assertDontSee('generateSimulatedData')
            ->assertDontSee('Simulasi Transmisi');
    }

    public function test_dashboards_render_ku_index_and_null_rssi(): void
    {
        $admin = User::where('email', 'admin@siasap.test')->first();

        SensorLog::create([
            'node_id' => 1,
            'co2' => 400, 'nh3' => 5, 'co' => 3, 'h2' => 2,
            'ku_value' => 120, 'ku_status' => 'Sedang', 'ku_dominant' => 'co',
            'rssi' => null, 'seq_number' => 1, 'hop_count' => 1, 'topology_type' => 'star',
        ]);

        $this->actingAs($admin)->get(route('telemetry'))
            ->assertOk()
            ->assertSee('Parameter dominan: CO')
            ->assertSee('—')
            ->assertDontSee('null dBm');

        $this->actingAs($admin)->get(route('user.dashboard'))->assertOk()->assertSee('Indeks KU 120');
        $this->actingAs($admin)->get(route('user.history'))->assertOk()->assertSee('Indeks 120');
    }
}
