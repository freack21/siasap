<?php

namespace Tests\Feature;

use App\Livewire\TopologyTest;
use App\Models\TopologyTestDevice;
use App\Models\TopologyTestLog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Livewire\Livewire;
use Tests\TestCase;

class TopologyTestPageTest extends TestCase
{
    use RefreshDatabase;

    private function log(string $device, string $topology, int $seq, string $receivedAt, int $rssi = -60): void
    {
        TopologyTestLog::factory()->create([
            'device_id' => $device,
            'topology' => $topology,
            'seq_number' => $seq,
            'rssi' => $rssi,
            'recorded_at' => Carbon::parse($receivedAt),
            'created_at' => Carbon::parse($receivedAt),
            'updated_at' => Carbon::parse($receivedAt),
        ]);
    }

    public function test_hidden_page_shows_three_devices_and_is_not_indexed(): void
    {
        $this->get('/uji-topologi')
            ->assertOk()
            ->assertSee('Uji Topologi Star &amp; Mesh', false)
            ->assertSee('noindex, nofollow', false)
            ->assertSee(['AIR-001', 'AIR-002', 'AIR-003'])
            ->assertSee(url('api/uji-topologi'));
    }

    public function test_page_is_not_linked_from_simku_pages(): void
    {
        foreach (['/', '/riwayat', '/perangkat', '/panduan'] as $page) {
            $this->get($page)->assertOk()->assertDontSee('uji-topologi');
        }
    }

    public function test_device_connection_follows_last_received_data(): void
    {
        Carbon::setTestNow('2026-11-07 10:01:00');
        $this->log('AIR-001', 'star', 1, '2026-11-07 10:00:45');
        $this->log('AIR-002', 'star', 1, '2026-11-07 09:59:00');

        $devices = Livewire::test(TopologyTest::class)->viewData('devices')->keyBy('id');

        $this->assertTrue($devices['AIR-001']['connected']);
        $this->assertFalse($devices['AIR-002']['connected']);
        $this->assertNull($devices['AIR-003']['latest']);
    }

    public function test_qos_is_summarized_per_device_and_topology_within_filter(): void
    {
        foreach ([1, 2, 4] as $i => $seq) {
            $this->log('AIR-002', 'mesh', $seq, '2026-11-07 10:00:0'.($i * 5 % 10), -70);
        }
        $this->log('AIR-002', 'star', 1, '2026-11-07 10:00:00');
        $this->log('AIR-002', 'mesh', 9, '2026-11-07 11:30:00');

        $qos = Livewire::test(TopologyTest::class)
            ->set('topology', 'mesh')
            ->set('from', '2026-11-07T10:00')
            ->set('to', '2026-11-07T10:30')
            ->viewData('qos');

        $this->assertCount(1, $qos);
        $this->assertSame('AIR-002', $qos[0]['device_id']);
        $this->assertSame('mesh', $qos[0]['topology']);
        $this->assertSame(3, $qos[0]['unique']);
        $this->assertSame(4, $qos[0]['expected']);
        $this->assertSame(75.0, $qos[0]['pdr']);
    }

    public function test_csv_contains_filtered_packets(): void
    {
        $this->log('AIR-001', 'star', 7, '2026-11-07 10:00:00', -55);
        $this->log('AIR-003', 'mesh', 3, '2026-11-07 10:00:05', -80);

        $response = Livewire::test(TopologyTest::class)->set('topology', 'star')->call('downloadCsv');

        $csv = $response->effects['download']['content'] ?? '';
        $csv = base64_decode($csv);
        $lines = array_values(array_filter(explode("\n", trim(ltrim($csv, "\xEF\xBB\xBF")))));

        $this->assertStringStartsWith('waktu_terima,waktu_perangkat,device_id,topologi,seq_number,hop_count,rssi', $lines[0]);
        $this->assertCount(2, $lines);
        $this->assertStringContainsString('AIR-001,star,7,1,-55', $lines[1]);
    }

    public function test_device_card_shows_handshake_ip(): void
    {
        TopologyTestDevice::create([
            'device_id' => 'AIR-001',
            'ip' => '192.168.137.23',
            'remote_ip' => '192.168.137.23',
            'topology' => 'mesh',
            'handshake_count' => 3,
            'last_handshake_at' => now(),
        ]);

        $this->get('/uji-topologi')
            ->assertOk()
            ->assertSee('ip.addr == 192.168.137.23')
            ->assertSee('dari handshake')
            ->assertSee('Belum melakukan handshake.')
            ->assertSee(url('api/uji-topologi').'/handshake');
    }

    public function test_ip_falls_back_to_data_sender_and_gateway_for_mesh_nodes(): void
    {
        TopologyTestLog::factory()->create(['device_id' => 'AIR-001', 'topology' => 'mesh', 'hop_count' => 1, 'source_ip' => '192.168.137.50']);
        TopologyTestLog::factory()->create(['device_id' => 'AIR-003', 'topology' => 'mesh', 'hop_count' => 3, 'source_ip' => '192.168.137.50']);

        $devices = Livewire::test(TopologyTest::class)->viewData('devices')->keyBy('id');

        $this->assertSame(['ip' => '192.168.137.50', 'source' => 'data', 'gateway_ip' => null], $devices['AIR-001']['network']);
        $this->assertSame(['ip' => null, 'source' => 'gateway', 'gateway_ip' => '192.168.137.50'], $devices['AIR-003']['network']);
        $this->assertSame(['ip' => null, 'source' => null, 'gateway_ip' => null], $devices['AIR-002']['network']);

        $this->get('/uji-topologi')
            ->assertSee('ip.addr == 192.168.137.50')
            ->assertSee('IP gateway (node tanpa IP)')
            ->assertSee('IP belum diketahui.');
    }
}
