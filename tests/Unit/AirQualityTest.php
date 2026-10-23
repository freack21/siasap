<?php

namespace Tests\Unit;

use App\Support\AirQuality;
use InvalidArgumentException;
use Tests\TestCase;

class AirQualityTest extends TestCase
{
    public function test_threshold_label_describes_each_category_range_in_ppm(): void
    {
        $this->assertSame('0 – < 50 ppm', AirQuality::thresholdLabel('co2', 'Baik'));
        $this->assertSame('50 – < 100 ppm', AirQuality::thresholdLabel('co2', 'Sedang'));
        $this->assertSame('100 – < 150 ppm', AirQuality::thresholdLabel('co2', 'Tidak Sehat'));
        $this->assertSame('≥ 150 ppm', AirQuality::thresholdLabel('co2', 'Berbahaya'));
        $this->assertSame('20 – < 50 ppm', AirQuality::thresholdLabel('co', 'Sedang'));
        $this->assertSame('≥ 60 ppm', AirQuality::thresholdLabel('h2', 'Berbahaya'));
    }

    public function test_threshold_label_rejects_unknown_gas_or_category(): void
    {
        $this->expectException(InvalidArgumentException::class);

        AirQuality::thresholdLabel('o3', 'Baik');
    }

    public function test_category_keys_follow_configured_order(): void
    {
        $this->assertSame(config('air_quality.category_order'), array_keys(AirQuality::categories()));
    }

    public function test_categories_have_no_index_range(): void
    {
        foreach (AirQuality::categories() as $category) {
            $this->assertArrayNotHasKey('range', $category);
        }
        $this->assertArrayNotHasKey('range', AirQuality::category(null));
    }
}
