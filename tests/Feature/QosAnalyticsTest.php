<?php

namespace Tests\Feature;

use App\Livewire\QosAnalytics;
use App\Models\Permission;
use App\Models\Role;
use App\Models\TestingSession;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use Tests\TestCase;

class QosAnalyticsTest extends TestCase
{
    use RefreshDatabase;

    private function userWithPermissions(array $permissions): User
    {
        $role = Role::create(['name' => 'role-'.implode('-', $permissions) ?: 'none', 'display_name' => 'Uji']);
        foreach ($permissions as $name) {
            $perm = Permission::firstOrCreate(['name' => $name], ['display_name' => $name, 'group' => 'Uji']);
            $role->permissions()->attach($perm->id);
        }

        $user = User::factory()->create();
        $user->roles()->attach($role->id);

        return $user->fresh();
    }

    public function test_user_without_qos_view_is_forbidden(): void
    {
        Livewire::actingAs($this->userWithPermissions(['user.view']))
            ->test(QosAnalytics::class)
            ->assertForbidden();
    }

    public function test_user_without_session_manage_cannot_start_or_stop_session(): void
    {
        $user = $this->userWithPermissions(['qos.view']);

        Livewire::actingAs($user)
            ->test(QosAnalytics::class)
            ->assertDontSee('Mulai Sesi Pengujian Sekarang')
            ->set('sessionName', 'Sesi Ilegal')
            ->call('startSession')
            ->assertForbidden();

        $this->assertDatabaseMissing('testing_sessions', ['name' => 'Sesi Ilegal']);

        $active = TestingSession::create([
            'name' => 'Aktif', 'topology_type' => 'star', 'status' => 'active', 'started_at' => now(),
        ]);

        Livewire::actingAs($user)
            ->test(QosAnalytics::class)
            ->call('stopActiveSession')
            ->assertForbidden();

        $this->assertSame('active', $active->fresh()->status);
    }

    public function test_user_with_session_manage_can_start_session(): void
    {
        Livewire::actingAs($this->userWithPermissions(['qos.view', 'session.manage']))
            ->test(QosAnalytics::class)
            ->set('sessionName', 'Sesi Mesh #1')
            ->set('sessionTopology', 'mesh')
            ->call('startSession')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('testing_sessions', ['name' => 'Sesi Mesh #1', 'topology_type' => 'mesh', 'status' => 'active']);
    }

    public function test_renders_node_names_and_estimated_footnote(): void
    {
        \App\Models\Node::create(['name' => 'Node Sensor Dapur', 'code' => 'NODE-X1', 'is_active' => true]);
        $session = TestingSession::create([
            'name' => 'Sesi Star B', 'topology_type' => 'star', 'status' => 'completed',
            'started_at' => now()->subMinutes(10), 'ended_at' => now(),
        ]);
        DB::table('sensor_logs')->insert([
            'testing_session_id' => $session->id, 'node_id' => 77, 'seq_number' => 1, 'rssi' => -60,
            'co2' => 400, 'nh3' => 1, 'co' => 2, 'h2' => 3, 'ku_value' => 10, 'ku_status' => 'Baik',
            'hop_count' => 1, 'topology_type' => 'star', 'created_at' => now(), 'updated_at' => now(),
        ]);

        Livewire::actingAs($this->userWithPermissions(['qos.view']))
            ->test(QosAnalytics::class)
            ->assertSee('Node Sensor Dapur')
            ->assertSee('Node #77')
            ->assertSee('0/120')
            ->assertSee('diestimasi')
            ->assertDontSee('Generate Dataset Uji Skenario');
    }

    public function test_synthetic_dataset_action_is_removed(): void
    {
        $this->assertFalse(method_exists(QosAnalytics::class, 'populateResearchBenchmarkData'));
    }

    public function test_export_csv_streams_session_logs_only(): void
    {
        Carbon::setTestNow('2026-10-14 10:00:00');

        $session = TestingSession::create([
            'name' => 'Sesi Star A', 'topology_type' => 'star', 'status' => 'completed',
            'started_at' => now()->subHour(), 'ended_at' => now(),
        ]);

        $base = [
            'co2' => 400, 'nh3' => 1, 'co' => 2, 'h2' => 3, 'ku_value' => 10, 'ku_status' => 'Baik',
            'hop_count' => 1, 'topology_type' => 'star', 'created_at' => now(), 'updated_at' => now(),
        ];
        DB::table('sensor_logs')->insert($base + ['testing_session_id' => $session->id, 'node_id' => 1, 'seq_number' => 7, 'rssi' => -61]);
        DB::table('sensor_logs')->insert($base + ['testing_session_id' => null, 'node_id' => 2, 'seq_number' => 99, 'rssi' => -62]);

        $component = Livewire::actingAs($this->userWithPermissions(['qos.view']))
            ->test(QosAnalytics::class)
            ->set('filterSessionId', $session->id)
            ->call('exportCsv')
            ->assertFileDownloaded('siasap-qos-sensor-logs-sesi-'.$session->id.'-20261014_100000.csv');

        $csv = base64_decode(data_get($component->effects, 'download.content'));
        $lines = array_values(array_filter(explode("\n", $csv)));

        $this->assertSame(implode(',', QosAnalytics::CSV_COLUMNS), $lines[0]);
        $this->assertCount(2, $lines); // header + 1 session row; null-session log excluded
        $this->assertStringStartsWith($session->id.',"Sesi Star A",star,1,7,-61,1,', $lines[1]);

        Carbon::setTestNow();
    }
}
