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
            ->assertSee('Sensor Lab')
            ->assertSee('Kode AIR-001')
            ->assertSee('Laboratorium Jaringan')
            ->assertSee(['CO₂', 'NH₃', 'CO', 'H₂'])
            ->assertSee(['Karbon Dioksida', 'Amonia', 'Karbon Monoksida', 'Hidrogen']);
    }

    public function test_device_is_connected_when_last_data_is_recent(): void
    {
        $this->logAt(10);

        Livewire::test(DeviceStatus::class)
            ->assertSee('Terhubung')
            ->assertSee('Perangkat sedang mengirim data.')
            ->assertSee('10 detik yang lalu')
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
            ->assertSee('Tidak ada data baru lebih dari 30 detik.')
            ->assertDontSee('Perangkat sedang mengirim data.')
            ->assertViewHas('devices', fn ($devices) => $devices->first()['connected'] === false);
    }

    public function test_device_without_data_is_disconnected(): void
    {
        Livewire::test(DeviceStatus::class)
            ->assertSee('Perangkat belum pernah mengirim data.')
            ->assertViewHas('devices', fn ($devices) => $devices->first()['connected'] === false
                && $devices->first()['last_seen'] === null);
    }

    public function test_page_links_to_guide_instead_of_showing_technical_details(): void
    {
        TelemetryRejection::create([
            'device_id' => 'AIR-999',
            'payload' => [],
            'errors' => ['device_id' => ['device_id tidak terdaftar atau tidak aktif.']],
        ]);

        Livewire::test(DeviceStatus::class)
            ->assertSee('Baca cara kerja SIMKU')
            ->assertSee(route('guide').'#cara-kerja', false)
            ->assertDontSee('Alur data')
            ->assertDontSee('/api/telemetry')
            ->assertDontSee('device_id tidak terdaftar atau tidak aktif.');
    }

    public function test_shows_empty_state_without_devices(): void
    {
        $this->node->delete();

        Livewire::test(DeviceStatus::class)
            ->assertSee('Belum ada perangkat');
    }
}
