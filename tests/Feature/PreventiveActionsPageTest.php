<?php

namespace Tests\Feature;

use App\Models\Node;
use App\Models\Room;
use App\Models\SensorLog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PreventiveActionsPageTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return array{0: Room, 1: Node}
     */
    protected function makeRoom(string $name, string $code): array
    {
        $room = Room::create(['name' => $name, 'code' => $code, 'description' => 'Ruang uji '.$name]);
        $node = Node::create(['room_id' => $room->id, 'name' => 'Node '.$code, 'code' => 'N-'.$code, 'is_active' => true]);

        return [$room, $node];
    }

    /**
     * @param  array{co2: float|int, nh3: float|int, co: float|int, h2: float|int}  $gases
     */
    protected function log(Node $node, array $gases): SensorLog
    {
        $ku = SensorLog::computeKu($gases['co2'], $gases['nh3'], $gases['co'], $gases['h2']);

        return SensorLog::create([
            'node_id' => $node->id,
            ...$gases,
            'ku_value' => $ku['value'],
            'ku_status' => $ku['status'],
            'ku_dominant' => $ku['dominant'],
            'seq_number' => 1,
            'hop_count' => 1,
            'topology_type' => 'star',
        ]);
    }

    public function test_room_detail_shows_epa_preventive_actions_for_elevated_co(): void
    {
        [$room, $node] = $this->makeRoom('Dapur Umum', 'DPR');
        $this->log($node, ['co2' => 420, 'nh3' => 1, 'co' => 10, 'h2' => 5]);

        $this->get(route('rooms.show', $room))
            ->assertOk()
            ->assertSee('id="tindakan-preventif"', false)
            ->assertSee('Tindakan Preventif')
            ->assertSee('Level AQI EPA:')
            ->assertSee('Unhealthy for Sensitive Groups')
            ->assertSee('Anjuran EPA')
            ->assertSee('Kelompok sensitif (penderita penyakit jantung): batasi aktivitas fisik berat dan hindari sumber CO.')
            ->assertSee('Penderita penyakit jantung sebaiknya menghindari aktivitas fisik berat di ruangan ini.')
            ->assertSee('https://document.airnow.gov/technical-assistance-document-for-the-reporting-of-daily-air-quailty.pdf')
            ->assertSee('Rekomendasi bersifat informatif dan bukan diagnosis medis.');
    }

    public function test_room_detail_shows_maintenance_when_all_gases_are_good(): void
    {
        [$room, $node] = $this->makeRoom('Ruang Kelas A', 'RKA');
        $this->log($node, ['co2' => 600, 'nh3' => 2, 'co' => 1, 'h2' => 10]);

        $this->get(route('rooms.show', $room))
            ->assertOk()
            ->assertSee('Tindakan Preventif')
            ->assertSee('Semua gas berada pada kategori Baik')
            ->assertSee(config('preventive_actions.maintenance.0'))
            ->assertDontSee('Anjuran EPA');
    }

    public function test_room_detail_without_data_has_no_preventive_card(): void
    {
        [$room] = $this->makeRoom('Gudang', 'GDG');

        $this->get(route('rooms.show', $room))
            ->assertOk()
            ->assertSee('Belum ada data dari ruangan ini')
            ->assertDontSee('Tindakan Preventif')
            ->assertDontSee('tindakan-preventif');
    }

    public function test_home_hero_shows_top_action_of_worst_room_with_link_to_detail(): void
    {
        [, $kelas] = $this->makeRoom('Ruang Kelas A', 'RKA');
        [$dapur, $dapurNode] = $this->makeRoom('Dapur Umum', 'DPR');

        $this->log($kelas, ['co2' => 600, 'nh3' => 2, 'co' => 1, 'h2' => 10]);
        $this->log($dapurNode, ['co2' => 420, 'nh3' => 1, 'co' => 10, 'h2' => 5]);

        $this->get(route('home'))
            ->assertOk()
            ->assertSee('Penderita penyakit jantung sebaiknya menghindari aktivitas fisik berat di ruangan ini.')
            ->assertSee('Lihat tindakan preventif')
            ->assertSee(route('rooms.show', $dapur).'#tindakan-preventif', false);
    }
}
