<?php

namespace Tests\Feature;

use App\Livewire\DeviceStatus;
use App\Models\Node;
use App\Models\Room;
use App\Models\SensorLog;
use App\Models\TelemetryRejection;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class DeviceStatusTest extends TestCase
{
    use RefreshDatabase;

    protected Node $node;

    protected function setUp(): void
    {
        parent::setUp();

        $this->freezeSecond();

        $room = Room::create(['name' => 'Laboratorium Jaringan', 'code' => 'LAB']);
        $this->node = Node::create(['room_id' => $room->id, 'name' => 'Sensor Lab', 'code' => 'AIR-001']);
    }

    private function logAt(int $secondsAgo): SensorLog
    {
        return SensorLog::factory()->create([
            'node_id' => $this->node->id,
            'created_at' => now()->subSeconds($secondsAgo),
        ]);
    }

    public function test_page_renders_device_with_room_and_gases(): void
    {
        $this->logAt(10);

        $this->get(route('devices'))
            ->assertOk()
            ->assertSee('Status Perangkat')
            ->assertSee('wire:poll.5s', false)
            ->assertSee('AIR-001')
            ->assertSee('Laboratorium Jaringan')
            ->assertSee(['CO₂', 'NH₃', 'CO', 'H₂']);
    }

    public function test_device_is_connected_when_last_data_is_recent(): void
    {
        $this->logAt(10);

        Livewire::test(DeviceStatus::class)
            ->assertSee('Terhubung')
            ->assertDontSee('Terputus')
            ->assertViewHas('devices', fn ($devices) => $devices->first()['connected'] === true);
    }

    public function test_device_is_connected_at_exactly_thirty_seconds(): void
    {
        $this->logAt(30);

        Livewire::test(DeviceStatus::class)
            ->assertViewHas('devices', fn ($devices) => $devices->first()['connected'] === true);
    }

    public function test_device_is_disconnected_when_last_data_is_old(): void
    {
        $this->logAt(120);

        Livewire::test(DeviceStatus::class)
            ->assertSee('Terputus')
            ->assertDontSee('Terhubung')
            ->assertViewHas('devices', fn ($devices) => $devices->first()['connected'] === false);
    }

    public function test_device_without_data_is_disconnected(): void
    {
        Livewire::test(DeviceStatus::class)
            ->assertSee('Terputus')
            ->assertSee('Belum pernah mengirim data')
            ->assertViewHas('devices', fn ($devices) => $devices->first()['connected'] === false
                && $devices->first()['interval'] === null);
    }

    public function test_measured_interval_is_average_gap_of_last_ten_logs(): void
    {
        // Data lama dengan selisih besar tidak ikut dihitung (di luar 10 data terakhir).
        $this->logAt(1000);
        $this->logAt(900);

        foreach (range(9, 0) as $i) {
            $this->logAt($i * 6);
        }

        Livewire::test(DeviceStatus::class)
            ->assertSee('6,0 detik')
            ->assertViewHas('devices', fn ($devices) => abs($devices->first()['interval'] - 6.0) < 0.001);
    }

    public function test_interval_is_unknown_with_single_log(): void
    {
        $this->logAt(5);

        Livewire::test(DeviceStatus::class)
            ->assertViewHas('devices', fn ($devices) => $devices->first()['interval'] === null);
    }

    public function test_shows_data_flow_steps(): void
    {
        Livewire::test(DeviceStatus::class)
            ->assertSeeInOrder(['Alur data', 'Sensor membaca gas', 'Dikirim ke server', 'Validasi format', 'Klasifikasi', 'Disimpan', 'Ditampilkan']);
    }

    public function test_shows_ten_latest_rejections_with_short_errors(): void
    {
        TelemetryRejection::create([
            'device_id' => 'AIR-OLD',
            'payload' => ['device_id' => 'AIR-OLD'],
            'errors' => ['timestamp' => ['Format timestamp lama.']],
            'created_at' => now()->subHour(),
        ]);

        foreach (range(1, 9) as $i) {
            TelemetryRejection::create([
                'device_id' => 'AIR-001',
                'payload' => [],
                'errors' => ['co2_ppm' => ['co2_ppm (CO₂) harus berupa angka.']],
                'created_at' => now()->subMinutes($i),
            ]);
        }

        TelemetryRejection::create([
            'device_id' => 'AIR-999',
            'payload' => ['device_id' => 'AIR-999'],
            'errors' => [
                'device_id' => ['device_id tidak terdaftar atau tidak aktif.'],
                'timestamp' => ['timestamp wajib diisi.'],
            ],
            'created_at' => now(),
        ]);

        Livewire::test(DeviceStatus::class)
            ->assertSee('Penolakan data terakhir')
            ->assertSee('AIR-999')
            ->assertSee('device_id tidak terdaftar atau tidak aktif.')
            ->assertSee('timestamp wajib diisi.')
            ->assertDontSee('AIR-OLD')
            ->assertViewHas('rejections', fn ($rejections) => $rejections->count() === 10
                && $rejections->first()->device_id === 'AIR-999');
    }

    public function test_rejection_without_device_id_is_shown_with_placeholder(): void
    {
        TelemetryRejection::create([
            'device_id' => null,
            'payload' => [],
            'errors' => ['device_id' => ['device_id wajib diisi.']],
        ]);

        Livewire::test(DeviceStatus::class)
            ->assertSee('device_id wajib diisi.')
            ->assertSee('Tidak ada');
    }

    public function test_shows_empty_state_without_rejections(): void
    {
        Livewire::test(DeviceStatus::class)
            ->assertSee('Belum ada data yang ditolak');
    }
}
