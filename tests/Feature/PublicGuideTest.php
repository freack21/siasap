<?php

namespace Tests\Feature;

use App\Livewire\AirQualityGuide;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class PublicGuideTest extends TestCase
{
    use RefreshDatabase;

    public function test_guide_page_renders_for_guests(): void
    {
        $this->get(route('guide'))
            ->assertOk()
            ->assertSee('Panduan Kualitas Udara')
            ->assertSee('Cara membaca kategori');
    }

    public function test_guide_shows_threshold_table_with_solihat_citation(): void
    {
        Livewire::test(AirQualityGuide::class)
            ->assertSee('Tabel ambang batas')
            ->assertSeeInOrder(['0 – < 50 ppm', '50 – < 100 ppm', '100 – < 150 ppm', '≥ 150 ppm'])
            ->assertSee(['0 – < 40 ppm', '≥ 120 ppm', '20 – < 50 ppm', '≥ 80 ppm', '0 – < 15 ppm', '≥ 60 ppm'])
            ->assertSee('Solihat, S. M., Salsabila, S., & Susilawati. (2026)')
            ->assertSee('10.23960/jitet.v14i1.5568');
    }

    public function test_guide_explains_relative_ppm_and_worst_category(): void
    {
        Livewire::test(AirQualityGuide::class)
            ->assertSee('indikator relatif terhadap kondisi udara saat perangkat dinyalakan')
            ->assertSee('kategori gas yang paling buruk')
            ->assertSee(['Karbon Dioksida', 'Amonia', 'Karbon Monoksida', 'Hidrogen'])
            ->assertSee(['MQ-135', 'MQ-2']);
    }

    public function test_guide_faq_covers_interval_disconnection_and_disclaimer(): void
    {
        Livewire::test(AirQualityGuide::class)
            ->assertSee('Pertanyaan umum')
            ->assertSee('setiap 5 detik')
            ->assertSee('Terputus')
            ->assertSee('bukan diagnosis medis');
    }

    public function test_guide_has_no_sub_index_ku_index_or_co_aqi(): void
    {
        $html = $this->get(route('guide'))->assertOk()->getContent();

        $this->assertDoesNotMatchRegularExpression('/sub-?indeks|indeks\s+ku|aqi\s+co/i', $html);
    }
}
