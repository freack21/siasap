<?php

namespace Tests\Feature;

use App\Models\SensorLog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TelemetryApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_store_telemetry_via_api()
    {
        $response = $this->postJson('/api/telemetry', [
            'node_id' => 1,
            'co2' => 45.5,
            'nh3' => 12.0,
            'co' => 8.2,
            'h2' => 3.1,
            'rssi' => -62,
            'seq_number' => 101,
            'hop_count' => 1,
        ]);

        $response->assertStatus(201)
                 ->assertJson([
                     'status' => 'success',
                 ]);

        $this->assertDatabaseHas('sensor_logs', [
            'node_id' => 1,
            'ku_status' => 'Baik',
            'seq_number' => 101,
        ]);
    }
}
