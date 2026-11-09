<?php

namespace Tests\Unit;

use App\Models\TopologyTestLog;
use App\Support\TopologyQos;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Tests\TestCase;

class TopologyQosTest extends TestCase
{
    /**
     * Data berurutan: setiap entri [seq_number, detik terima sejak 10:00:00, rssi, hop].
     *
     * @param  list<array{0: ?int, 1: int, 2?: int, 3?: int}>  $rows
     * @return Collection<int, TopologyTestLog>
     */
    private function logs(array $rows): Collection
    {
        $start = Carbon::parse('2026-11-07 10:00:00');

        return collect($rows)->map(fn (array $row) => new TopologyTestLog([
            'seq_number' => $row[0],
            'rssi' => $row[2] ?? -60,
            'hop_count' => $row[3] ?? 1,
            'recorded_at' => $start->copy()->addSeconds($row[1]),
        ]))->each(fn (TopologyTestLog $log, int $i) => $log->created_at = $start->copy()->addSeconds($rows[$i][1] + 1));
    }

    public function test_pdr_counts_missing_sequence_numbers_as_lost(): void
    {
        $summary = TopologyQos::summarize($this->logs([[1, 0], [2, 5], [4, 15], [5, 20]]));

        $this->assertSame(4, $summary['unique']);
        $this->assertSame(5, $summary['expected']);
        $this->assertSame(80.0, $summary['pdr']);
        $this->assertSame(20.0, $summary['loss']);
        $this->assertSame(6.7, $summary['interval_avg']);
    }

    public function test_delay_jitter_and_throughput(): void
    {
        $logs = $this->logs([[1, 0], [2, 5], [3, 10], [4, 20]]);
        foreach ([[100, 200], [140, 200], [120, 200], [180, 200]] as $i => [$delay, $bytes]) {
            $logs[$i]->delay_ms = $delay;
            $logs[$i]->payload_bytes = $bytes;
        }

        $summary = TopologyQos::summarize($logs);

        $this->assertSame(135.0, $summary['delay_avg']);
        $this->assertSame(40.0, $summary['jitter']);
        $this->assertSame(320.0, $summary['throughput_bps']);
    }

    public function test_single_packet_has_no_jitter_or_throughput(): void
    {
        $logs = $this->logs([[1, 0]]);
        $logs[0]->delay_ms = 90;
        $logs[0]->payload_bytes = 200;

        $summary = TopologyQos::summarize($logs);

        $this->assertSame(90.0, $summary['delay_avg']);
        $this->assertNull($summary['jitter']);
        $this->assertNull($summary['throughput_bps']);
    }

    public function test_duplicates_are_counted_once(): void
    {
        $summary = TopologyQos::summarize($this->logs([[1, 0, -70, 3], [1, 1, -55, 2], [2, 5, -60, 2]]));

        $this->assertSame(3, $summary['received']);
        $this->assertSame(2, $summary['unique']);
        $this->assertSame(1, $summary['duplicates']);
        $this->assertSame(100.0, $summary['pdr']);
        $this->assertSame(-70, $summary['rssi_min']);
        $this->assertSame(2.33, $summary['hop_avg']);
    }

    public function test_device_restart_starts_a_new_session(): void
    {
        $summary = TopologyQos::summarize($this->logs([[9, 0], [10, 5], [1, 10], [3, 20]]));

        $this->assertSame(2, $summary['sessions']);
        $this->assertSame(5, $summary['expected']);
        $this->assertSame(4, $summary['unique']);
        $this->assertSame(80.0, $summary['pdr']);
    }

    public function test_small_reordering_stays_in_the_same_session(): void
    {
        $summary = TopologyQos::summarize($this->logs([[10, 0], [12, 5], [11, 6], [13, 10]]));

        $this->assertSame(1, $summary['sessions']);
        $this->assertSame(100.0, $summary['pdr']);
    }

    public function test_without_sequence_numbers_pdr_is_unknown(): void
    {
        $summary = TopologyQos::summarize($this->logs([[null, 0], [null, 5]]));

        $this->assertNull($summary['expected']);
        $this->assertNull($summary['pdr']);
        $this->assertSame(2, $summary['received']);
    }
}
