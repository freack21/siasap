<?php

namespace Tests\Feature;

use App\Livewire\RoomDetail;
use App\Livewire\UserDashboard;
use App\Models\Node;
use App\Models\Room;
use App\Models\SensorLog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class PublicDashboardTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Teks rekomendasi Tabel 3.3 naskah per kategori keseluruhan.
     */
    private const RECOMMENDATION_TEXTS = [
        'Baik' => 'Kondisi udara relatif baik. Pertahankan ventilasi ruangan.',
        'Sedang' => 'Tingkatkan sirkulasi udara, misalnya dengan membuka jendela atau pintu.',
        'Tidak Sehat' => 'Tingkatkan ventilasi, periksa dan kurangi sumber polutan seperti asap atau gas, serta kurangi aktivitas di dalam ruangan.',
        'Berbahaya' => 'Segera tingkatkan ventilasi, hentikan sumber polutan, dan pertimbangkan meninggalkan ruangan sementara sampai kondisi udara membaik.',
    ];

    protected function makeRoom(string $name, string $code, bool $active = true): array
    {
        $room = Room::create(['name' => $name, 'code' => $code, 'description' => 'Ruang uji '.$name]);
        $node = Node::create(['room_id' => $room->id, 'name' => 'Perangkat '.$code, 'code' => 'AIR-'.$code, 'is_active' => $active]);

        return [$room, $node];
    }

    protected function log(Node $node, array $gases, $at = null): SensorLog
    {
        $log = SensorLog::factory()
            ->withGases($gases['co2'], $gases['nh3'], $gases['co'], $gases['h2'])
            ->create(['node_id' => $node->id]);

        if ($at) {
            $log->forceFill(['created_at' => $at, 'updated_at' => $at, 'recorded_at' => $at])->save();
        }

        return $log;
    }

    public function test_home_with_single_room_focuses_on_that_room(): void
    {
        [$room, $node] = $this->makeRoom('Ruang Uji', 'R01');

        // CO₂ Sedang, NH₃ Baik, CO Tidak Sehat, H₂ Baik → keseluruhan Tidak Sehat (penyebab CO)
        $this->log($node, ['co2' => 70, 'nh3' => 20, 'co' => 55.5, 'h2' => 5]);

        $this->assertGuest();

        $this->get(route('home'))
            ->assertOk()
            ->assertSee('Ruang Uji')
            ->assertSee('wire:poll.5s', false)
            ->assertSeeInOrder(['Kategori keseluruhan', 'Tidak Sehat', 'Penyebab utama', 'CO'])
            ->assertSeeInOrder([
                'CO₂', '70,00', 'Sedang',
                'NH₃', '20,00', 'Baik',
                'CO', '55,50', 'Tidak Sehat',
                'H₂', '5,00', 'Baik',
            ])
            ->assertSee('Perlu Tindakan')
            ->assertSee(self::RECOMMENDATION_TEXTS['Tidak Sehat'])
            ->assertDontSee(self::RECOMMENDATION_TEXTS['Sedang'])
            ->assertSee(route('recommendations'))
            ->assertSee('Perangkat terhubung')
            ->assertSee(route('rooms.show', $room))
            ->assertDontSee('Kondisi per ruangan')
            ->assertDontSee('Indeks KU');
    }

    public function test_home_hides_main_cause_when_overall_is_good(): void
    {
        [, $node] = $this->makeRoom('Ruang Uji', 'R01');
        $this->log($node, ['co2' => 30, 'nh3' => 20, 'co' => 10, 'h2' => 5]);

        Livewire::test(UserDashboard::class)
            ->assertSee('Normal')
            ->assertSee(self::RECOMMENDATION_TEXTS['Baik'])
            ->assertDontSee('Penyebab utama');
    }

    public function test_home_without_data_shows_waiting_state_without_recommendation(): void
    {
        $this->makeRoom('Ruang Uji', 'R01');

        $response = $this->get(route('home'))
            ->assertOk()
            ->assertSee('Ruang Uji')
            ->assertSee('Menunggu data dari perangkat IoT')
            ->assertDontSee('Rekomendasi tindakan');

        foreach (self::RECOMMENDATION_TEXTS as $text) {
            $response->assertDontSee($text);
        }
    }

    public function test_home_with_multiple_rooms_shows_a_card_per_room(): void
    {
        [$kelas, $kelasNode] = $this->makeRoom('Ruang Kelas A', 'RKA');
        [$dapur, $dapurNode] = $this->makeRoom('Dapur Umum', 'DPR');

        $this->log($kelasNode, ['co2' => 30, 'nh3' => 20, 'co' => 10, 'h2' => 5]);   // Baik
        $this->log($dapurNode, ['co2' => 70, 'nh3' => 20, 'co' => 10, 'h2' => 65]); // Berbahaya (H₂)

        $this->get(route('home'))
            ->assertOk()
            ->assertSee('Kondisi per ruangan')
            ->assertSeeInOrder(['Dapur Umum', 'Berbahaya', 'H₂', 'Segera Bertindak', self::RECOMMENDATION_TEXTS['Berbahaya']])
            ->assertSeeInOrder(['Ruang Kelas A', 'Baik', 'Normal', self::RECOMMENDATION_TEXTS['Baik']])
            ->assertSee('65,00')
            ->assertSee(route('rooms.show', $kelas))
            ->assertSee(route('rooms.show', $dapur));
    }

    public function test_home_hides_rooms_without_active_nodes(): void
    {
        [, $node] = $this->makeRoom('Gudang Tertutup', 'GDG', active: false);
        $this->log($node, ['co2' => 30, 'nh3' => 20, 'co' => 10, 'h2' => 5]);

        $this->get(route('home'))
            ->assertOk()
            ->assertDontSee('Gudang Tertutup')
            ->assertSee('Belum ada ruangan yang dipantau');
    }

    public function test_home_shows_empty_state_without_rooms(): void
    {
        Livewire::test(UserDashboard::class)
            ->assertOk()
            ->assertSee('Belum ada ruangan yang dipantau');
    }

    public function test_home_marks_stale_devices_disconnected(): void
    {
        [, $online] = $this->makeRoom('Ruang Aktif', 'AKT');
        [, $stale] = $this->makeRoom('Ruang Lama', 'LMA');

        $this->log($online, ['co2' => 30, 'nh3' => 20, 'co' => 10, 'h2' => 5]);
        $this->log($stale, ['co2' => 30, 'nh3' => 20, 'co' => 10, 'h2' => 5], now()->subSeconds(31));

        Livewire::test(UserDashboard::class)
            ->assertSeeInOrder(['Ruang Aktif', 'Perangkat terhubung'])
            ->assertSeeInOrder(['Ruang Lama', 'Perangkat terputus']);
    }

    public function test_single_room_home_warns_when_data_is_stale(): void
    {
        [, $node] = $this->makeRoom('Ruang Uji', 'R01');
        $this->log($node, ['co2' => 30, 'nh3' => 20, 'co' => 10, 'h2' => 5], now()->subMinutes(5));

        Livewire::test(UserDashboard::class)
            ->assertSee('Data terakhir diterima')
            ->assertSee('5 menit yang lalu')
            ->assertSee('bukan kondisi terkini. Periksa perangkat IoT.');
    }

    public function test_single_room_home_has_no_stale_warning_when_online(): void
    {
        [, $node] = $this->makeRoom('Ruang Uji', 'R01');
        $this->log($node, ['co2' => 30, 'nh3' => 20, 'co' => 10, 'h2' => 5]);

        Livewire::test(UserDashboard::class)
            ->assertDontSee('bukan kondisi terkini');
    }

    public function test_room_detail_warns_when_data_is_stale(): void
    {
        [$room, $node] = $this->makeRoom('Laboratorium', 'LAB');
        $this->log($node, ['co2' => 30, 'nh3' => 20, 'co' => 10, 'h2' => 5], now()->subMinutes(5));

        Livewire::test(RoomDetail::class, ['room' => $room])
            ->assertSee('Data terakhir diterima')
            ->assertSee('bukan kondisi terkini. Periksa perangkat IoT.');

        $this->log($node, ['co2' => 30, 'nh3' => 20, 'co' => 10, 'h2' => 5]);

        Livewire::test(RoomDetail::class, ['room' => $room])
            ->assertDontSee('bukan kondisi terkini');
    }

    public function test_room_detail_shows_server_time_with_device_time_secondary(): void
    {
        [$room, $node] = $this->makeRoom('Laboratorium', 'LAB');
        $log = $this->log($node, ['co2' => 30, 'nh3' => 20, 'co' => 10, 'h2' => 5]);
        $log->forceFill(['created_at' => '2026-10-05 07:45:00', 'recorded_at' => '2026-01-01 00:00:00'])->save();

        Livewire::test(RoomDetail::class, ['room' => $room])
            ->assertSeeInOrder(['05 Okt 07:45:00', 'waktu perangkat 01 Jan 00:00:00']);
    }

    public function test_room_detail_defaults_to_co2_chart_with_threshold_lines(): void
    {
        [$room, $node] = $this->makeRoom('Laboratorium', 'LAB');
        $this->log($node, ['co2' => 30.5, 'nh3' => 20, 'co' => 10, 'h2' => 5], now()->subHours(2));
        $this->log($node, ['co2' => 70, 'nh3' => 20, 'co' => 10, 'h2' => 5]);

        $this->get(route('rooms.show', $room))
            ->assertOk()
            ->assertSee('Laboratorium')
            ->assertSee('wire:poll.5s', false)
            ->assertSee('data-chart', false)
            ->assertSee('10 pembacaan terakhir');

        Livewire::test(RoomDetail::class, ['room' => $room])
            ->assertSet('gas', 'co2')
            ->assertSet('range', '24h')
            ->assertViewHas('gasChart', function (array $chart) {
                [$series, $baik, $sedang, $tidakSehat] = $chart['datasets'];
                $points = array_values(array_filter($series['data'], fn ($value) => $value !== null));

                return count($chart['datasets']) === 4
                    && $chart['yTitle'] === 'ppm (indikator relatif)'
                    && $series['label'] === 'CO₂'
                    && empty($series['dashed'])
                    && $points === [30.5, 70.0]
                    && count($chart['labels']) <= 120
                    && $baik['dashed'] && array_unique($baik['data']) === [50]
                    && $sedang['dashed'] && array_unique($sedang['data']) === [100]
                    && $tidakSehat['dashed'] && array_unique($tidakSehat['data']) === [150]
                    && count($baik['data']) === count($chart['labels']);
            })
            ->assertViewHas('stats', fn (array $stats) => $stats['count'] === 2
                && $stats['avg'] === 50.25
                && $stats['min'] === 30.5
                && $stats['max'] === 70.0
                && $stats['distribution']['Sedang'] == 50.0
                && $stats['distribution']['Baik'] == 50.0);
    }

    public function test_room_detail_gas_query_selects_gas_and_its_thresholds(): void
    {
        [$room, $node] = $this->makeRoom('Laboratorium', 'LAB');
        $this->log($node, ['co2' => 30, 'nh3' => 20, 'co' => 55.5, 'h2' => 5]);

        $this->get(route('rooms.show', ['room' => $room, 'gas' => 'co']))
            ->assertOk()
            ->assertSee('&quot;label&quot;:&quot;CO&quot;', false);

        Livewire::withQueryParams(['gas' => 'co'])
            ->test(RoomDetail::class, ['room' => $room])
            ->assertSet('gas', 'co')
            ->assertViewHas('gasChart', function (array $chart) {
                $thresholds = array_values(array_filter($chart['datasets'], fn (array $set) => ! empty($set['dashed'])));

                return $chart['datasets'][0]['label'] === 'CO'
                    && count($thresholds) === 3
                    && array_map(fn (array $set) => $set['data'][0], $thresholds) === [20, 50, 80];
            })
            ->assertViewHas('stats', fn (array $stats) => $stats['max'] === 55.5);
    }

    public function test_room_detail_unknown_gas_falls_back_to_co2(): void
    {
        [$room] = $this->makeRoom('Laboratorium', 'LAB');

        Livewire::withQueryParams(['gas' => 'xyz'])
            ->test(RoomDetail::class, ['room' => $room])
            ->assertSet('gas', 'co2')
            ->assertViewHas('gasChart', fn (array $chart) => $chart['datasets'][0]['label'] === 'CO₂');

        Livewire::test(RoomDetail::class, ['room' => $room])
            ->call('setGas', 'h2')
            ->assertSet('gas', 'h2')
            ->assertViewHas('gasChart', fn (array $chart) => $chart['datasets'][0]['label'] === 'H₂')
            ->call('setGas', 'bogus')
            ->assertSet('gas', 'co2');
    }

    public function test_room_detail_range_switching(): void
    {
        [$room, $node] = $this->makeRoom('Laboratorium', 'LAB');
        $this->log($node, ['co2' => 30, 'nh3' => 20, 'co' => 10, 'h2' => 5], now()->subDays(3));
        $this->log($node, ['co2' => 31, 'nh3' => 20, 'co' => 10, 'h2' => 5], now()->subHours(3));
        $this->log($node, ['co2' => 32, 'nh3' => 20, 'co' => 10, 'h2' => 5]);

        $component = Livewire::test(RoomDetail::class, ['room' => $room]);

        $component->call('setRange', '1h')
            ->assertSet('range', '1h')
            ->assertViewHas('stats', fn ($stats) => $stats['count'] === 1 && $stats['avg'] === 32.0)
            ->assertViewHas('gasChart', fn ($chart) => count($chart['labels']) <= 120);

        $component->call('setRange', '7d')
            ->assertSet('range', '7d')
            ->assertViewHas('stats', fn ($stats) => $stats['count'] === 3 && $stats['min'] === 30.0 && $stats['max'] === 32.0)
            ->assertViewHas('gasChart', fn ($chart) => count($chart['labels']) <= 120);

        $component->call('setRange', 'bogus')->assertSet('range', '24h');
    }

    public function test_room_detail_lists_ten_latest_readings_by_created_at(): void
    {
        [$room, $node] = $this->makeRoom('Laboratorium', 'LAB');

        foreach (range(1, 12) as $minutesAgo) {
            $this->log($node, ['co2' => $minutesAgo, 'nh3' => 20, 'co' => 10, 'h2' => 5], now()->subMinutes($minutesAgo));
        }

        Livewire::test(RoomDetail::class, ['room' => $room])
            ->assertViewHas('recent', fn ($recent) => $recent->pluck('co2')->all() === [1.0, 2.0, 3.0, 4.0, 5.0, 6.0, 7.0, 8.0, 9.0, 10.0]);
    }

    public function test_room_detail_without_data_shows_empty_state(): void
    {
        [$room] = $this->makeRoom('Ruang Kosong', 'KSG');

        $this->get(route('rooms.show', $room))
            ->assertOk()
            ->assertSee('Menunggu data dari perangkat IoT');
    }

    public function test_unknown_room_returns_404(): void
    {
        $this->get(route('rooms.show', 9999))->assertNotFound();
    }
}
