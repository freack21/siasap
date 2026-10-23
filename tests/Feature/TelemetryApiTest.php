<?php

namespace Tests\Feature;

use App\Models\Node;
use App\Models\Room;
use App\Models\SensorLog;
use App\Models\TelemetryRejection;
use App\Support\AirQualityClassifier;
use App\Support\Recommendation;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;
use Tests\Unit\AirQualityClassifierTest;

class TelemetryApiTest extends TestCase
{
    use RefreshDatabase;

    protected Node $node;

    protected function setUp(): void
    {
        parent::setUp();

        // Pastikan API key kosong secara default (tanpa autentikasi)
        config()->set('services.telemetry.api_key', null);

        $room = Room::create(['name' => 'Ruang Uji', 'code' => 'ROOM-01']);
        $this->node = Node::create([
            'room_id' => $room->id,
            'name' => 'Perangkat IoT 1',
            'code' => 'AIR-001',
            'is_active' => true,
        ]);
    }

    /**
     * Payload format naskah 3.9.1 dengan pembacaan udara bersih.
     *
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    protected function payload(array $overrides = []): array
    {
        return array_merge([
            'device_id' => 'AIR-001',
            'timestamp' => '2026-05-01 10:00:00',
            'co2_ppm' => 30,
            'nh3_ppm' => 20,
            'co_ppm' => 10,
            'h2_ppm' => 5,
        ], $overrides);
    }

    public function test_complete_payload_is_stored_and_returns_classification(): void
    {
        $response = $this->postJson('/api/telemetry', $this->payload());

        $response->assertStatus(201)
            ->assertJsonPath('status', 'success')
            ->assertJsonPath('data.device_id', 'AIR-001')
            ->assertJsonPath('data.recorded_at', '2026-05-01 10:00:00')
            ->assertJsonPath('data.categories', ['co2' => 'Baik', 'nh3' => 'Baik', 'co' => 'Baik', 'h2' => 'Baik'])
            ->assertJsonPath('data.overall_category', 'Baik')
            ->assertJsonPath('data.dominant_gases', [])
            ->assertJsonPath('data.recommendation.level', 'Normal')
            ->assertJsonPath('data.recommendation.text', 'Kondisi udara relatif baik. Pertahankan ventilasi ruangan.');

        $this->assertDatabaseHas('sensor_logs', [
            'node_id' => $this->node->id,
            'co2' => 30,
            'nh3' => 20,
            'co' => 10,
            'h2' => 5,
            'overall_category' => 'Baik',
        ]);
        $this->assertDatabaseCount('telemetry_rejections', 0);
    }

    /**
     * Tabel 3.7 naskah: [override payload, field yang ditolak].
     *
     * @return array<string, array{array<string, mixed>, list<string>, string}>
     */
    public static function tabel37Provider(): array
    {
        return [
            'co_ppm tidak dikirim' => [['co_ppm' => null], ['co_ppm'], 'AIR-001'],
            'co2_ppm bukan angka' => [['co2_ppm' => 'abc'], ['co2_ppm'], 'AIR-001'],
            'device_id kosong' => [['device_id' => ''], ['device_id'], ''],
            'format timestamp salah' => [['timestamp' => '01-05-2026 10:00'], ['timestamp'], 'AIR-001'],
            'device_id tidak terdaftar' => [['device_id' => 'AIR-999'], ['device_id'], 'AIR-999'],
        ];
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @param  list<string>  $failedFields
     */
    #[DataProvider('tabel37Provider')]
    public function test_invalid_payload_is_rejected_and_logged(array $overrides, array $failedFields, string $expectedDeviceId): void
    {
        $payload = $this->payload($overrides);
        foreach ($overrides as $key => $value) {
            if ($value === null) {
                unset($payload[$key]);
            }
        }

        $this->postJson('/api/telemetry', $payload)
            ->assertStatus(422)
            ->assertJsonPath('status', 'error')
            ->assertJsonValidationErrors($failedFields, 'errors');

        $this->assertDatabaseCount('sensor_logs', 0);
        $this->assertDatabaseCount('telemetry_rejections', 1);

        $rejection = TelemetryRejection::first();
        if ($expectedDeviceId === '') {
            $this->assertNull($rejection->device_id);
        } else {
            $this->assertSame($expectedDeviceId, $rejection->device_id);
        }
        $this->assertSame($payload['timestamp'], $rejection->payload['timestamp']);
        $this->assertArrayHasKey($failedFields[0], $rejection->errors);
    }

    public function test_negative_ppm_is_rejected(): void
    {
        Log::shouldReceive('channel')->once()->with('telemetry')->andReturnSelf();
        Log::shouldReceive('warning')->once();

        $this->postJson('/api/telemetry', $this->payload(['nh3_ppm' => -1]))
            ->assertStatus(422)
            ->assertJsonValidationErrors('nh3_ppm', 'errors');

        $this->assertDatabaseCount('telemetry_rejections', 1);
    }

    /**
     * @return array<string, array{float, float, float, float, string}>
     */
    public static function tabel38Provider(): array
    {
        return AirQualityClassifierTest::tabel38Provider();
    }

    #[DataProvider('tabel38Provider')]
    public function test_table_3_8_classification_via_api(float $co2, float $nh3, float $co, float $h2, string $expected): void
    {
        $response = $this->postJson('/api/telemetry', $this->payload([
            'co2_ppm' => $co2,
            'nh3_ppm' => $nh3,
            'co_ppm' => $co,
            'h2_ppm' => $h2,
        ]));

        $response->assertStatus(201)->assertJsonPath('data.overall_category', $expected);

        $log = SensorLog::firstOrFail();
        $this->assertSame($expected, $log->overall_category);
        $this->assertSame(Recommendation::forCategory($expected)['level'], $log->recommendation_level);
        $this->assertSame(
            AirQualityClassifier::classify($co2, $nh3, $co, $h2)['categories'],
            $log->categories(),
        );
    }

    public function test_dominant_gases_are_stored_and_returned(): void
    {
        $response = $this->postJson('/api/telemetry', $this->payload([
            'co2_ppm' => 70,
            'nh3_ppm' => 30,
            'co_ppm' => 55,
            'h2_ppm' => 45,
        ]));

        $response->assertStatus(201)
            ->assertJsonPath('data.overall_category', 'Tidak Sehat')
            ->assertJsonPath('data.dominant_gases', ['co', 'h2']);

        $this->assertSame(['co', 'h2'], SensorLog::first()->dominantGases());
        $this->assertDatabaseHas('sensor_logs', ['dominant_gases' => 'co,h2']);
    }

    public function test_legacy_format_is_accepted_and_qos_fields_are_ignored(): void
    {
        $response = $this->postJson('/api/telemetry', [
            'node_id' => $this->node->id,
            'co2' => 70,
            'nh3' => 30,
            'co' => 10,
            'h2' => 5,
            'rssi' => -62,
            'seq_number' => 101,
            'hop_count' => 2,
            'topology_type' => 'mesh',
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.device_id', 'AIR-001')
            ->assertJsonPath('data.overall_category', 'Sedang');

        $log = SensorLog::firstOrFail();
        $this->assertSame($this->node->id, $log->node_id);
        $this->assertEqualsWithDelta(now()->timestamp, $log->recorded_at->timestamp, 5);
        $this->assertArrayNotHasKey('rssi', $log->getAttributes());
        $this->assertArrayNotHasKey('seq_number', $log->getAttributes());
    }

    public function test_legacy_format_with_unknown_node_is_rejected_and_logged(): void
    {
        $this->postJson('/api/telemetry', ['node_id' => 999, 'co2' => 30, 'nh3' => 20, 'co' => 10, 'h2' => 5])
            ->assertStatus(422)
            ->assertJsonValidationErrors('device_id', 'errors');

        $this->assertDatabaseCount('telemetry_rejections', 1);
    }

    public function test_device_timestamp_is_kept_while_created_at_is_server_time(): void
    {
        $this->travelTo(now()->setDate(2026, 10, 23)->setTime(12, 0, 0));

        $this->postJson('/api/telemetry', $this->payload(['timestamp' => '2026-05-01 10:00:00']))
            ->assertStatus(201);

        $log = SensorLog::firstOrFail();
        $this->assertSame('2026-05-01 10:00:00', $log->recorded_at->format('Y-m-d H:i:s'));
        $this->assertSame('2026-10-23 12:00:00', $log->created_at->format('Y-m-d H:i:s'));
    }

    public function test_api_key_is_enforced_when_configured_without_logging_rejection(): void
    {
        config()->set('services.telemetry.api_key', 'rahasia-node');

        $this->postJson('/api/telemetry', $this->payload())
            ->assertStatus(401)
            ->assertJson(['status' => 'error']);

        $this->postJson('/api/telemetry', $this->payload(), ['X-Api-Key' => 'salah'])
            ->assertStatus(401);

        $this->getJson('/api/telemetry')->assertStatus(401);

        $this->assertDatabaseCount('telemetry_rejections', 0);

        $this->postJson('/api/telemetry', $this->payload(), ['X-Api-Key' => 'rahasia-node'])
            ->assertStatus(201);

        $this->getJson('/api/telemetry', ['X-Api-Key' => 'rahasia-node'])
            ->assertStatus(200);
    }

    public function test_api_key_is_not_required_when_empty(): void
    {
        config()->set('services.telemetry.api_key', '');

        $this->postJson('/api/telemetry', $this->payload())->assertStatus(201);
        $this->getJson('/api/telemetry')->assertStatus(200);
    }
}
