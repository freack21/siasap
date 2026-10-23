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

    protected function makeRoom(string $name, string $code, bool $active = true): array
    {
        $room = Room::create(['name' => $name, 'code' => $code, 'description' => 'Ruang uji '.$name]);
        $node = Node::create(['room_id' => $room->id, 'name' => 'Node '.$code, 'code' => 'N-'.$code, 'is_active' => $active]);

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

    public function test_home_renders_for_guests_with_rooms_and_overall_status(): void
    {
        [, $kelas] = $this->makeRoom('Ruang Kelas A', 'RKA');
        [, $dapur] = $this->makeRoom('Dapur Umum', 'DPR');

        $this->log($kelas, ['co2' => 30, 'nh3' => 20, 'co' => 10, 'h2' => 5]);   // Baik
        $this->log($dapur, ['co2' => 70, 'nh3' => 20, 'co' => 10, 'h2' => 5]);  // Sedang (CO2)

        $this->assertGuest();

        $this->get(route('home'))
            ->assertOk()
            ->assertSee('Kualitas udara ruangan saat ini')
            ->assertSee('Ruang Kelas A')
            ->assertSee('Dapur Umum')
            ->assertSee('Sedang')
            ->assertSee('Penyebab utama')
            ->assertSee('CO₂')
            ->assertSee('Tingkatkan sirkulasi udara, misalnya dengan membuka jendela atau pintu.')
            ->assertDontSee('Indeks KU')
            ->assertSee(route('rooms.show', $kelas->room_id));
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

    public function test_room_without_data_shows_no_data_state(): void
    {
        $this->makeRoom('Ruang Baru', 'RBR');

        Livewire::test(UserDashboard::class)
            ->assertSee('Ruang Baru')
            ->assertSee('Belum ada data')
            ->assertSee('Sensor terputus');
    }

    public function test_home_marks_stale_devices_offline(): void
    {
        [, $online] = $this->makeRoom('Ruang Aktif', 'AKT');
        [, $stale] = $this->makeRoom('Ruang Lama', 'LMA');

        $this->log($online, ['co2' => 30, 'nh3' => 20, 'co' => 10, 'h2' => 5]);
        $this->log($stale, ['co2' => 30, 'nh3' => 20, 'co' => 10, 'h2' => 5], now()->subMinutes(10));

        Livewire::test(UserDashboard::class)
            ->assertSeeInOrder(['Ruang Aktif', 'Sensor terhubung'])
            ->assertSeeInOrder(['Ruang Lama', 'Sensor terputus'])
            ->assertViewHas('devicesOnline', 1)
            ->assertViewHas('devicesTotal', 2);
    }

    public function test_room_detail_shows_current_values_and_chart_data(): void
    {
        [$room, $node] = $this->makeRoom('Laboratorium', 'LAB');
        $this->log($node, ['co2' => 30.5, 'nh3' => 20, 'co' => 10, 'h2' => 5], now()->subHours(2));
        $this->log($node, ['co2' => 70, 'nh3' => 20, 'co' => 10, 'h2' => 5]);

        $this->get(route('rooms.show', $room))
            ->assertOk()
            ->assertSee('Laboratorium')
            ->assertSee('Ruang uji Laboratorium')
            ->assertSee('Karbon Dioksida')
            ->assertSee('70,00')
            ->assertSee('50 – < 100 ppm')
            ->assertSee('Rekomendasi')
            ->assertSee('Waspada')
            ->assertDontSee('Sub-indeks')
            ->assertSee('data-chart', false)
            ->assertSee('10 pembacaan terakhir');

        Livewire::test(RoomDetail::class, ['room' => $room])
            ->assertSet('range', '24h')
            ->assertViewHas('gasChart', function (array $chart) {
                $points = array_values(array_filter($chart['datasets'][0]['data'], fn ($v) => $v !== null));

                return count($chart['labels']) <= 120 && count($points) === 2;
            })
            ->assertViewHas('gasChart', fn (array $chart) => count($chart['datasets']) === 4)
            ->assertViewHas('stats', fn (array $stats) => $stats['count'] === 2 && $stats['distribution']['Sedang'] == 50.0);
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
            ->assertViewHas('stats', fn ($stats) => $stats['count'] === 1)
            ->assertViewHas('gasChart', fn ($chart) => count($chart['labels']) <= 120);

        $component->call('setRange', '7d')
            ->assertSet('range', '7d')
            ->assertViewHas('stats', fn ($stats) => $stats['count'] === 3)
            ->assertViewHas('gasChart', fn ($chart) => count($chart['labels']) <= 120);

        $component->call('setRange', 'bogus')->assertSet('range', '24h');
    }

    public function test_room_detail_without_data_shows_empty_state(): void
    {
        [$room] = $this->makeRoom('Ruang Kosong', 'KSG');

        $this->get(route('rooms.show', $room))
            ->assertOk()
            ->assertSee('Belum ada data dari ruangan ini');
    }

    public function test_unknown_room_returns_404(): void
    {
        $this->get(route('rooms.show', 9999))->assertNotFound();
    }
}
