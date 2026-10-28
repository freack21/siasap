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
            ->assertSee('Panduan Sistem')
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
            ->assertSee('dibandingkan dengan kondisi udara saat perangkat dinyalakan (indikator relatif)')
            ->assertSee('kategori gas yang paling buruk')
            ->assertSee(['Karbon Dioksida', 'Amonia', 'Karbon Monoksida', 'Hidrogen'])
            ->assertSee(['MQ-135', 'MQ-2']);
    }

    public function test_guide_explains_how_simku_works_and_each_page(): void
    {
        Livewire::test(AirQualityGuide::class)
            ->assertSee('id="cara-kerja"', false)
            ->assertSeeInOrder(['Cara kerja SIMKU', 'Perangkat membaca udara', 'Data dikirim ke SIMKU', 'Data diperiksa',
                'Kategori ditentukan', 'Saran disiapkan dan disimpan', 'Hasil ditampilkan'])
            ->assertSeeInOrder(['Isi setiap halaman', 'Beranda', 'Detail Ruangan', 'Riwayat', 'Rekomendasi', 'Perangkat', 'Panduan', 'Kuesioner']);
    }

    public function test_guide_shows_basis_and_sources_of_recommendations(): void
    {
        Livewire::test(AirQualityGuide::class)
            ->assertSee('Dasar saran tindakan')
            ->assertSee(config('recommendations.basis'))
            ->assertSee('https://www.epa.gov/indoor-air-quality-iaq/improving-indoor-air-quality', false)
            ->assertSee('https://document.airnow.gov/technical-assistance-document-for-the-reporting-of-daily-air-quailty.pdf', false);
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
