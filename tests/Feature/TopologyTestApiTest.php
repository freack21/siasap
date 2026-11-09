<?php

namespace Tests\Feature;

use App\Models\SensorLog;
use App\Models\TelemetryRejection;
use App\Models\TopologyTestDevice;
use App\Models\TopologyTestLog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class TopologyTestApiTest extends TestCase
{
    use RefreshDatabase;

    private const ENDPOINT = '/api/uji-topologi';

    protected function setUp(): void
    {
        parent::setUp();

        config()->set('services.telemetry.api_key', null);
    }

    /**
     * Payload yang sama dengan POST /api/telemetry ditambah metrik jaringan.
     *
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function payload(array $overrides = []): array
    {
        return array_merge([
            'device_id' => 'AIR-003',
            'timestamp' => '2026-11-07 10:00:00',
            'co2_ppm' => 70,
            'nh3_ppm' => 30,
            'co_ppm' => 55,
            'h2_ppm' => 10,
            'rssi' => -78,
            'seq_number' => 12,
            'hop_count' => 3,
            'topology_type' => 'mesh',
        ], $overrides);
    }

    public function test_payload_is_stored_separately_from_simku_data(): void
    {
        $this->postJson(self::ENDPOINT, $this->payload())
            ->assertCreated()
            ->assertJsonPath('data.device_id', 'AIR-003')
            ->assertJsonPath('data.overall_category', 'Tidak Sehat')
            ->assertJsonPath('data.dominant_gases', ['co'])
            ->assertJsonPath('data.recommendation.level', 'Perlu Tindakan');

        $log = TopologyTestLog::sole();
        $this->assertSame('mesh', $log->topology);
        $this->assertSame([-78, 12, 3], [$log->rssi, $log->seq_number, $log->hop_count]);
        $this->assertSame('Sedang', $log->co2_category);
        $this->assertSame(0, SensorLog::count());
    }

    public function test_delay_and_payload_size_are_recorded(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-11-07 10:00:01.250'));
        $payload = $this->payload(['timestamp' => '2026-11-07 10:00:01', 'timestamp_ms' => 100]);

        $this->postJson(self::ENDPOINT, $payload)->assertCreated();

        $log = TopologyTestLog::sole();
        $this->assertSame(150, $log->delay_ms);
        $this->assertSame(strlen(json_encode($payload)), $log->payload_bytes);
    }

    public function test_delay_without_milliseconds_uses_whole_seconds(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-11-07 10:00:02.400'));

        $this->postJson(self::ENDPOINT, $this->payload(['timestamp' => '2026-11-07 10:00:01']))->assertCreated();

        $this->assertSame(1400, TopologyTestLog::sole()->delay_ms);
    }

    public function test_simku_payload_without_network_metrics_is_accepted(): void
    {
        $payload = $this->payload(['device_id' => 'AIR-001']);
        unset($payload['rssi'], $payload['seq_number'], $payload['hop_count'], $payload['topology_type']);

        $this->postJson(self::ENDPOINT, $payload)->assertCreated();

        $this->assertNull(TopologyTestLog::sole()->topology);
    }

    public function test_invalid_payloads_are_rejected_without_being_stored(): void
    {
        $this->postJson(self::ENDPOINT, $this->payload(['device_id' => 'AIR-004']))
            ->assertUnprocessable()
            ->assertJsonPath('errors.device_id.0', 'device_id bukan perangkat uji topologi.');

        $this->postJson(self::ENDPOINT, $this->payload(['co2_ppm' => 'abc', 'timestamp' => '07-11-2026 10:00']))
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['co2_ppm', 'timestamp']);

        $this->postJson(self::ENDPOINT, $this->payload(['topology_type' => 'ring', 'hop_count' => 0, 'rssi' => 5, 'timestamp_ms' => 1000]))
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['topology_type', 'hop_count', 'rssi', 'timestamp_ms']);

        $this->assertSame(0, TopologyTestLog::count());
        $this->assertSame(0, TelemetryRejection::count());
    }

    public function test_api_key_is_enforced_when_configured(): void
    {
        config()->set('services.telemetry.api_key', 'rahasia');

        $this->postJson(self::ENDPOINT, $this->payload())->assertUnauthorized();
        $this->postJson(self::ENDPOINT, $this->payload(), ['X-Api-Key' => 'rahasia'])->assertCreated();
    }

    public function test_handshake_records_device_ip_and_counts_repeats(): void
    {
        Carbon::setTestNow('2026-11-10 09:00:00');
        $handshake = ['device_id' => 'AIR-002', 'ip' => '192.168.137.23', 'mac' => 'a4:cf:12:9b:3e:01', 'topology_type' => 'star', 'rssi' => -61];

        $this->postJson(self::ENDPOINT.'/handshake', $handshake, ['REMOTE_ADDR' => '192.168.137.23'])
            ->assertOk()
            ->assertJsonPath('data.ip', '192.168.137.23')
            ->assertJsonPath('data.handshake_count', 1)
            ->assertJsonPath('data.server_time', '2026-11-10 09:00:00');

        $this->postJson(self::ENDPOINT.'/handshake', array_merge($handshake, ['ip' => '192.168.137.40']))
            ->assertOk()
            ->assertJsonPath('data.handshake_count', 2);

        $device = TopologyTestDevice::sole();
        $this->assertSame('192.168.137.40', $device->ip);
        $this->assertSame('A4:CF:12:9B:3E:01', $device->mac);
        $this->assertSame('star', $device->topology);
        $this->assertSame(0, TopologyTestLog::count());
    }

    public function test_invalid_handshake_is_rejected(): void
    {
        $this->postJson(self::ENDPOINT.'/handshake', ['device_id' => 'AIR-009', 'ip' => '999.1.1.1', 'mac' => 'xx'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['device_id', 'ip', 'mac']);

        $this->assertSame(0, TopologyTestDevice::count());
    }
}
