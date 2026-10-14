<?php

namespace Tests\Feature;

use App\Models\Node;
use App\Models\TestingSession;
use App\Support\QosCalculator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class QosCalculatorTest extends TestCase
{
    use RefreshDatabase;

    private Carbon $t0;

    protected function setUp(): void
    {
        parent::setUp();

        config(['air_quality.send_interval_seconds' => 5]);
        $this->t0 = Carbon::parse('2026-10-01 08:00:00');

        // Nodes registered before any session.
        foreach ([1, 2, 3] as $id) {
            $node = new Node(['name' => "Node Sensor {$id}", 'code' => "NODE-0{$id}", 'is_active' => true]);
            $node->id = $id;
            $node->created_at = $this->t0->copy()->subDay();
            $node->updated_at = $this->t0->copy()->subDay();
            $node->save();
        }
    }

    private function makeSession(string $topology, int $minutes = 10, ?Carbon $start = null): TestingSession
    {
        $start ??= $this->t0;

        return TestingSession::create([
            'name' => "Sesi {$topology}",
            'topology_type' => $topology,
            'status' => 'completed',
            'started_at' => $start,
            'ended_at' => $start->copy()->addMinutes($minutes),
        ]);
    }

    /**
     * Insert a log; $at is seconds after t0 (defaults to seq * 5 s).
     */
    private function log(?int $sessionId, int $nodeId, int $seq, ?int $rssi = -60, int $hop = 1, string $topology = 'star', ?int $at = null): void
    {
        DB::table('sensor_logs')->insert([
            'testing_session_id' => $sessionId,
            'node_id' => $nodeId,
            'co2' => 400, 'nh3' => 1, 'co' => 1, 'h2' => 1,
            'ku_value' => 10, 'ku_status' => 'Baik',
            'rssi' => $rssi,
            'seq_number' => $seq,
            'hop_count' => $hop,
            'topology_type' => $topology,
            'created_at' => $this->t0->copy()->addSeconds($at ?? $seq * 5),
            'updated_at' => $this->t0->copy()->addSeconds($at ?? $seq * 5),
        ]);
    }

    /** Fill the other nodes so they don't get estimated expected values. */
    private function fullNode(int $sessionId, int $nodeId, int $count = 10, string $topology = 'star'): void
    {
        for ($i = 1; $i <= $count; $i++) {
            $this->log($sessionId, $nodeId, $i, -55, 1, $topology);
        }
    }

    public function test_normal_packet_loss(): void
    {
        $s = $this->makeSession('star');
        foreach ([1, 2, 3, 5, 6, 8, 9, 10] as $seq) { // 4 and 7 lost
            $this->log($s->id, 1, $seq);
        }
        $this->fullNode($s->id, 2);
        $this->fullNode($s->id, 3);

        $q = QosCalculator::forTopology('star', $s->id);

        $this->assertSame(8, $q['per_node'][1]['received']);
        $this->assertSame(10, $q['per_node'][1]['expected']);
        $this->assertEquals(80.0, $q['per_node'][1]['pdr']);
        $this->assertEquals(20.0, $q['per_node'][1]['loss_rate']);
        $this->assertFalse($q['per_node'][1]['expected_estimated']);
        $this->assertSame(28, $q['total_received']);
        $this->assertSame(30, $q['expected_packets']);
        $this->assertEquals(93.3, $q['pdr']);
        $this->assertSame(1, $q['sessions_count']);
    }

    public function test_zero_packet_node_gets_estimated_expected_and_is_included_in_overall(): void
    {
        $s = $this->makeSession('star', 10); // 600 s / 5 s = 120 expected
        $this->fullNode($s->id, 1, 120);
        $this->fullNode($s->id, 2, 120);
        // Node 3 sends nothing (blind spot).

        $q = QosCalculator::forTopology('star', $s->id);

        $this->assertArrayHasKey(3, $q['per_node']);
        $this->assertSame(0, $q['per_node'][3]['received']);
        $this->assertSame(120, $q['per_node'][3]['expected']);
        $this->assertTrue($q['per_node'][3]['expected_estimated']);
        $this->assertEquals(0.0, $q['per_node'][3]['pdr']);
        $this->assertTrue($q['has_estimated']);

        $this->assertSame(240, $q['total_received']);
        $this->assertSame(360, $q['expected_packets']);
        $this->assertEquals(66.7, $q['pdr']);
    }

    public function test_duplicate_packets_are_counted_once(): void
    {
        $s = $this->makeSession('mesh');
        for ($seq = 1; $seq <= 10; $seq++) {
            $this->log($s->id, 3, $seq, -70, 1, 'mesh');
            if (in_array($seq, [4, 7])) {
                // Same packet received again via relay, 1 s later.
                $this->log($s->id, 3, $seq, -65, 2, 'mesh', $seq * 5 + 1);
            }
        }
        // Late duplicate arriving after the next packet (out of order).
        $this->log($s->id, 3, 9, -66, 2, 'mesh', 9 * 5 + 3);
        $this->fullNode($s->id, 1, 10, 'mesh');
        $this->fullNode($s->id, 2, 10, 'mesh');

        $q = QosCalculator::forTopology('mesh', $s->id);

        $this->assertSame(10, $q['per_node'][3]['received']);
        $this->assertSame(10, $q['per_node'][3]['expected']);
        $this->assertEquals(100.0, $q['per_node'][3]['pdr']);
        // RSSI/hop from first reception only.
        $this->assertEquals(-70.0, $q['per_node'][3]['avg_rssi']);
        $this->assertEquals(1.0, $q['per_node'][3]['avg_hop']);
    }

    public function test_seq_reset_after_reboot_starts_new_segment(): void
    {
        $s = $this->makeSession('star');
        // Segment 1: seq 1..20 with 18 lost → 19 received / 20 expected.
        for ($seq = 1; $seq <= 20; $seq++) {
            if ($seq !== 18) {
                $this->log($s->id, 1, $seq);
            }
        }
        // Reboot: seq restarts at 1; segment 2: 1..5 with 3 lost → 4 / 5.
        foreach ([1, 2, 4, 5] as $seq) {
            $this->log($s->id, 1, $seq, -60, 1, 'star', 200 + $seq * 5);
        }
        $this->fullNode($s->id, 2);
        $this->fullNode($s->id, 3);

        $q = QosCalculator::forTopology('star', $s->id);

        $this->assertSame(23, $q['per_node'][1]['received']);
        $this->assertSame(25, $q['per_node'][1]['expected']);
        $this->assertEquals(92.0, $q['per_node'][1]['pdr']);
    }

    public function test_sessions_are_not_mixed_when_no_session_filter(): void
    {
        $a = $this->makeSession('star', 10);
        $b = $this->makeSession('star', 10, $this->t0->copy()->addHour());

        // Session A: node 1 seq 1..10 all received.
        // Session B: node 1 seq 100..109 with 2 lost. Mixed min/max would give 109 expected.
        $this->fullNode($a->id, 1, 10);
        foreach (range(100, 109) as $seq) {
            if (! in_array($seq, [103, 106])) {
                $this->log($b->id, 1, $seq, -60, 1, 'star', 3600 + ($seq - 99) * 5);
            }
        }
        foreach ([$a, $b] as $s) {
            $this->fullNode($s->id, 2);
            $this->fullNode($s->id, 3);
        }

        $q = QosCalculator::forTopology('star');

        $this->assertSame(2, $q['sessions_count']);
        $this->assertSame(18, $q['per_node'][1]['received']);
        $this->assertSame(20, $q['per_node'][1]['expected']);
        $this->assertEquals(90.0, $q['per_node'][1]['pdr']);
        $this->assertSame(58, $q['total_received']);
        $this->assertSame(60, $q['expected_packets']);
    }

    public function test_logs_without_session_are_excluded(): void
    {
        $s = $this->makeSession('star');
        $this->fullNode($s->id, 1);
        $this->fullNode($s->id, 2);
        $this->fullNode($s->id, 3);
        // Out-of-experiment telemetry: should not affect anything.
        foreach ([1, 50, 500] as $seq) {
            $this->log(null, 1, $seq, -99);
        }

        $q = QosCalculator::forTopology('star');

        $this->assertSame(1, $q['sessions_count']);
        $this->assertSame(30, $q['total_received']);
        $this->assertSame(30, $q['expected_packets']);
        $this->assertEquals(-55.0, $q['avg_rssi']);
    }

    public function test_no_sessions_returns_empty_result(): void
    {
        $this->log(null, 1, 1);

        $q = QosCalculator::forTopology('mesh');

        $this->assertSame(0, $q['sessions_count']);
        $this->assertSame([], $q['per_node']);
        $this->assertNull($q['pdr']);
    }

    public function test_null_rssi_is_excluded_from_average_and_std_dev(): void
    {
        // Requires sensor_logs.rssi to be NULLABLE (migration
        // 2026_10_14_000001_update_sensor_logs_for_ku_dominant_and_nullable_rssi).
        $s = $this->makeSession('star');
        $this->log($s->id, 1, 1, -50);
        $this->log($s->id, 1, 2, null);
        $this->log($s->id, 1, 3, -60);
        $this->log($s->id, 1, 4, null);
        $this->log($s->id, 1, 5, -70);
        $this->fullNode($s->id, 2, 5);
        $this->fullNode($s->id, 3, 5);

        $q = QosCalculator::forTopology('star', $s->id);
        $n1 = $q['per_node'][1];

        $this->assertSame(5, $n1['received']);
        $this->assertSame(3, $n1['rssi_samples']);
        $this->assertEquals(-60.0, $n1['avg_rssi']);
        $this->assertEquals(10.0, $n1['std_rssi']); // sample SD of {-50,-60,-70}
    }

    public function test_sample_std_dev(): void
    {
        $this->assertNull(QosCalculator::sampleStdDev([-60.0]));
        $this->assertEqualsWithDelta(2.138, QosCalculator::sampleStdDev([2, 4, 4, 4, 5, 5, 7, 9]), 0.001);
    }

    public function test_node_registered_after_session_is_not_estimated_for_that_session(): void
    {
        $s = $this->makeSession('star');
        $this->fullNode($s->id, 1);
        $this->fullNode($s->id, 2);
        $this->fullNode($s->id, 3);

        $late = new Node(['name' => 'Node Baru', 'code' => 'NODE-04', 'is_active' => true]);
        $late->id = 4;
        $late->created_at = $this->t0->copy()->addDays(5);
        $late->updated_at = $this->t0->copy()->addDays(5);
        $late->save();

        $q = QosCalculator::forTopology('star', $s->id);

        $this->assertArrayNotHasKey(4, $q['per_node']);
        $this->assertEquals(100.0, $q['pdr']);
    }
}
