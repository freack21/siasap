<?php

namespace Tests\Feature;

use App\Livewire\AirQualityGuide;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class PublicHistoryGuideTest extends TestCase
{
    use RefreshDatabase;

    public function test_guide_page_renders_for_guests(): void
    {
        $this->get(route('guide'))
            ->assertOk()
            ->assertSee('Panduan Kualitas Udara')
            ->assertSee('Cara membaca kategori');
    }

    public function test_guide_shows_categories_gases_thresholds_and_recommendations(): void
    {
        Livewire::test(AirQualityGuide::class)
            ->assertSee(['Baik', 'Sedang', 'Tidak Sehat', 'Berbahaya'])
            ->assertSee(['Karbon Dioksida', 'Amonia', 'Karbon Monoksida', 'Hidrogen'])
            ->assertSee(['MQ-135', 'MQ-2'])
            ->assertSee(['0 – < 50 ppm', '50 – < 100 ppm', '100 – < 150 ppm', '≥ 150 ppm'])
            ->assertSee(['20 – < 50 ppm', '≥ 80 ppm'])
            ->assertSee('Kondisi udara relatif baik. Pertahankan ventilasi ruangan.')
            ->assertSee('Pertanyaan umum')
            ->assertSee('Rekomendasi bersifat informatif dan bukan diagnosis medis.')
            ->assertSee('10.23960/jitet.v14i1.5568');
    }
}
