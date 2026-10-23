<?php

namespace Tests\Unit;

use App\Support\PreventiveActions;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class PreventiveActionsTest extends TestCase
{
    /**
     * @return array<string, array{float, string}>
     */
    public static function coAqiLevelProvider(): array
    {
        return [
            'nol' => [0.0, 'good'],
            'batas good' => [50.0, 'good'],
            'awal moderate' => [50.1, 'moderate'],
            'batas moderate' => [100.0, 'moderate'],
            'awal usg' => [100.1, 'usg'],
            'batas usg' => [150.0, 'usg'],
            'awal unhealthy' => [150.1, 'unhealthy'],
            'batas unhealthy' => [200.0, 'unhealthy'],
            'awal very unhealthy' => [200.1, 'very_unhealthy'],
            'batas very unhealthy' => [300.0, 'very_unhealthy'],
            'awal hazardous' => [300.1, 'hazardous'],
            'maksimum' => [500.0, 'hazardous'],
        ];
    }

    #[DataProvider('coAqiLevelProvider')]
    public function test_co_aqi_level_follows_epa_index_ranges(float $subIndex, string $expectedLevel): void
    {
        $this->assertSame($expectedLevel, PreventiveActions::coAqiLevel($subIndex)['key']);
    }

    public function test_all_good_reading_returns_only_maintenance_advice(): void
    {
        $result = PreventiveActions::forReading(co2: 420, nh3: 1, co: 0.5, h2: 5);

        $this->assertTrue($result['all_good']);
        $this->assertSame([], $result['items']);
        $this->assertNotEmpty($result['maintenance']);
    }

    public function test_elevated_co_uses_epa_aqi_level_and_cautionary_statement(): void
    {
        // CO 10 ppm → sub-indeks ±110 → EPA "Unhealthy for Sensitive Groups"; kategori KU tetap "Sedang".
        $result = PreventiveActions::forReading(co2: 420, nh3: 1, co: 10, h2: 5);

        $this->assertFalse($result['all_good']);
        $this->assertCount(1, $result['items']);

        $item = $result['items'][0];
        $this->assertSame('co', $item['gas']);
        $this->assertSame('usg', $item['level']);
        $this->assertSame('Sedang', $item['category']);
        $this->assertStringContainsString('jantung', $item['cautionary']);
        $this->assertNotEmpty($item['actions']);
        $this->assertContains(config('preventive_actions.sources.epa_aqi.url'), array_column($item['sources'], 'url'));
    }

    public function test_items_are_sorted_from_highest_sub_index(): void
    {
        $result = PreventiveActions::forReading(co2: 1500, nh3: 1, co: 35, h2: 5);

        $this->assertSame(['co', 'co2'], array_column($result['items'], 'gas'));
        $this->assertSame('hazardous', $result['items'][0]['level']);
    }

    public function test_non_aqi_gases_use_ku_category_actions_from_indoor_air_quality_guidance(): void
    {
        $result = PreventiveActions::forReading(co2: 420, nh3: 30, co: 0.5, h2: 9000);

        $byGas = array_column($result['items'], null, 'gas');

        $this->assertSame('Sedang', $byGas['nh3']['category']);
        $this->assertSame('Sedang', $byGas['nh3']['level']);
        $this->assertNull($byGas['nh3']['cautionary']);
        $this->assertSame(config('preventive_actions.actions.nh3.Sedang'), $byGas['nh3']['actions']);

        $this->assertSame('Berbahaya', $byGas['h2']['category']);
        $this->assertSame(config('preventive_actions.actions.h2.Berbahaya'), $byGas['h2']['actions']);
    }

    public function test_every_item_cites_at_least_one_epa_source(): void
    {
        $result = PreventiveActions::forReading(co2: 2500, nh3: 40, co: 20, h2: 5000);

        $this->assertCount(4, $result['items']);

        foreach ($result['items'] as $item) {
            foreach ($item['sources'] as $source) {
                $this->assertStringStartsWith('https://', $source['url']);
            }

            $epaSources = array_filter($item['sources'], fn (array $source): bool => str_contains($source['label'], 'EPA'));
            $this->assertNotEmpty($epaSources, "Gas {$item['gas']} tidak punya sumber EPA.");
        }
    }
}
