<?php

namespace Tests\Feature;

use App\Models\Node;
use App\Models\SensorLog;
use Database\Seeders\RoomDeviceSeeder;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SensorLogNodeRelationTest extends TestCase
{
    use RefreshDatabase;

    public function test_sensor_log_requires_an_existing_node(): void
    {
        $this->expectException(QueryException::class);

        SensorLog::factory()->create(['node_id' => 9999]);
    }

    public function test_deleting_a_node_removes_its_sensor_logs(): void
    {
        $this->seed(RoomDeviceSeeder::class);
        $node = Node::firstOrFail();
        SensorLog::factory()->count(2)->create(['node_id' => $node->id]);

        $node->delete();

        $this->assertSame(0, SensorLog::count());
    }
}
