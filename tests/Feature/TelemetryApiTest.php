<?php

namespace Tests\Feature;

use App\Models\Node;
use App\Models\Room;
use App\Models\SensorLog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TelemetryApiTest extends TestCase
{
    use RefreshDatabase;

    protected Node $node;

    protected function setUp(): void
    {
        parent::setUp();

        // Pastikan API key kosong secara default (tanpa autentikasi)
        config()->set('services.telemetry.api_key', null);

        $room = Room::create(['name' => 'Ruang Uji', 'code' => 'R-UJI']);
        $this->node = Node::create([
            'room_id' => $room->id,
            'name' => 'Node 1',
            'code' => 'NODE_1',
            'is_active' => true,
        ]);
    }

    /**
     * Payload telemetri valid dengan pembacaan udara ambien normal
     */
    protected function payload(array $overrides = []): array
    {
        return array_merge([
            'node_id' => $this->node->id,
            'co2' => 420,
            'nh3' => 1,
            'co' => 0.5,
            'h2' => 5,
            'rssi' => -62,
            'seq_number' => 101,
            'hop_count' => 1,
        ], $overrides);
    }

    public function test_can_store_telemetry_via_api()
    {
        $response = $this->postJson('/api/telemetry', $this->payload());

        $response->assertStatus(201)
                 ->assertJson([
                     'status' => 'success',
                 ]);

        $this->assertDatabaseHas('sensor_logs', [
            'node_id' => $this->node->id,
            'ku_status' => 'Baik',
            'seq_number' => 101,
            'rssi' => -62,
        ]);
    }

    public function test_node_fallback_parameter_is_supported()
    {
        $payload = $this->payload(['node' => 'NODE_'.$this->node->id]);
        unset($payload['node_id']);

        $this->postJson('/api/telemetry', $payload)->assertStatus(201);

        $this->assertDatabaseHas('sensor_logs', ['node_id' => $this->node->id]);
    }

    public function test_missing_seq_number_is_rejected()
    {
        $payload = $this->payload();
        unset($payload['seq_number']);

        $this->postJson('/api/telemetry', $payload)
             ->assertStatus(422)
             ->assertJsonValidationErrors('seq_number');

        $this->assertDatabaseCount('sensor_logs', 0);
    }

    public function test_missing_hop_count_is_rejected()
    {
        $payload = $this->payload();
        unset($payload['hop_count']);

        $this->postJson('/api/telemetry', $payload)
             ->assertStatus(422)
             ->assertJsonValidationErrors('hop_count');
    }

    public function test_missing_rssi_is_stored_as_null()
    {
        $payload = $this->payload();
        unset($payload['rssi']);

        $this->postJson('/api/telemetry', $payload)->assertStatus(201);

        $this->assertNull(SensorLog::first()->rssi);
    }

    public function test_out_of_range_rssi_is_rejected()
    {
        $this->postJson('/api/telemetry', $this->payload(['rssi' => 10]))
             ->assertStatus(422)
             ->assertJsonValidationErrors('rssi');
    }

    public function test_unknown_node_is_rejected()
    {
        $this->postJson('/api/telemetry', $this->payload(['node_id' => 999]))
             ->assertStatus(422)
             ->assertJsonValidationErrors('node_id');
    }

    public function test_api_key_is_enforced_when_configured()
    {
        config()->set('services.telemetry.api_key', 'rahasia-node');

        $this->postJson('/api/telemetry', $this->payload())
             ->assertStatus(401)
             ->assertJson(['status' => 'error']);

        $this->postJson('/api/telemetry', $this->payload(), ['X-Api-Key' => 'salah'])
             ->assertStatus(401);

        $this->getJson('/api/telemetry')->assertStatus(401);

        $this->postJson('/api/telemetry', $this->payload(), ['X-Api-Key' => 'rahasia-node'])
             ->assertStatus(201);

        $this->getJson('/api/telemetry', ['X-Api-Key' => 'rahasia-node'])
             ->assertStatus(200);
    }

    public function test_api_key_is_not_required_when_empty()
    {
        config()->set('services.telemetry.api_key', '');

        $this->postJson('/api/telemetry', $this->payload())->assertStatus(201);
        $this->getJson('/api/telemetry')->assertStatus(200);
    }

    public function test_ambient_reading_is_classified_as_good()
    {
        $response = $this->postJson('/api/telemetry', $this->payload([
            'co2' => 420,
            'nh3' => 1,
            'co' => 0.5,
            'h2' => 5,
        ]));

        $response->assertStatus(201)
                 ->assertJsonPath('data.ku_status', 'Baik');

        $this->assertLessThanOrEqual(50, $response->json('data.ku_value'));
    }

    public function test_high_co_is_classified_as_dangerous_with_co_dominant()
    {
        $response = $this->postJson('/api/telemetry', $this->payload(['co' => 35]));

        $response->assertStatus(201)
                 ->assertJsonPath('data.ku_status', 'Berbahaya')
                 ->assertJsonPath('data.ku_dominant', 'co');

        $this->assertDatabaseHas('sensor_logs', [
            'ku_status' => 'Berbahaya',
            'ku_dominant' => 'co',
        ]);
    }
}
