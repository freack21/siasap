<?php

namespace Database\Factories;

use App\Models\TopologyTestLog;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * Data uji topologi: kategori diturunkan dari ppm lewat SensorLogFactory::attributesFor,
 * sama seperti endpoint.
 *
 * @extends Factory<TopologyTestLog>
 */
class TopologyTestLogFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'device_id' => 'AIR-001',
            'topology' => 'star',
            ...$this->gasAttributes(30, 20, 10, 5),
            'rssi' => -60,
            'seq_number' => 1,
            'hop_count' => 1,
        ];
    }

    /**
     * Data dengan kadar gas tertentu (ppm); kategori diturunkan otomatis.
     */
    public function withGases(float $co2, float $nh3, float $co, float $h2): static
    {
        return $this->state(fn () => $this->gasAttributes($co2, $nh3, $co, $h2));
    }

    /**
     * @return array<string, mixed>
     */
    private function gasAttributes(float $co2, float $nh3, float $co, float $h2): array
    {
        return SensorLogFactory::attributesFor($co2, $nh3, $co, $h2);
    }
}
