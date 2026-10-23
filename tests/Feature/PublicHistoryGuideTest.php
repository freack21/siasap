<?php

namespace Tests\Feature;

use App\Livewire\AirQualityGuide;
use App\Livewire\UserHistory;
use App\Models\Node;
use App\Models\Room;
use App\Models\SensorLog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Livewire\Livewire;
use Tests\TestCase;

class PublicHistoryGuideTest extends TestCase
{
    use RefreshDatabase;

    protected Room $kitchen;

    protected Room $bedroom;

    protected function setUp(): void
    {
        parent::setUp();

        $this->kitchen = Room::create(['name' => 'Dapur Utama', 'code' => 'DPR']);
        $this->bedroom = Room::create(['name' => 'Kamar Tidur', 'code' => 'KMR']);

        $kitchenNode = Node::create(['room_id' => $this->kitchen->id, 'name' => 'Node Dapur', 'code' => 'N-DPR']);
        $bedroomNode = Node::create(['room_id' => $this->bedroom->id, 'name' => 'Node Kamar', 'code' => 'N-KMR']);

        $this->log($kitchenNode, 42.5, 'Baik', 'co2', '2026-10-01 08:00:00', co2: 850);
        $this->log($kitchenNode, 220.0, 'Tidak Sehat', 'co', '2026-10-02 09:30:00', co: 21.4);
        $this->log($bedroomNode, 120.0, 'Sedang', null, '2026-10-03 10:15:00', co2: 1700);
    }

    private function log(Node $node, float $ku, string $status, ?string $dominant, string $at, float $co2 = 500, float $co = 1): SensorLog
    {
        $log = SensorLog::create([
            'node_id' => $node->id,
            'co2' => $co2,
            'nh3' => 2,
            'co' => $co,
            'h2' => 100,
            'ku_value' => $ku,
            'ku_status' => $status,
            'ku_dominant' => $dominant,
            'seq_number' => 1,
            'hop_count' => 1,
            'topology_type' => 'star',
        ]);
        $log->created_at = Carbon::parse($at);
        $log->save();

        return $log;
    }

    public function test_history_page_renders_for_guests(): void
    {
        $this->get(route('history'))
            ->assertOk()
            ->assertSee('Riwayat Kualitas Udara')
            ->assertSee('Unduh CSV')
            ->assertSee('Dapur Utama')
            ->assertSee('Kamar Tidur')
            ->assertSee('01 Okt 2026 08:00:00');
    }

    public function test_history_summary_reflects_filtered_set(): void
    {
        Livewire::test(UserHistory::class)
            ->assertViewHas('summary', function (array $summary) {
                return $summary['count'] === 3
                    && round($summary['average'], 1) === 127.5
                    && $summary['worst'] === 'Tidak Sehat'
                    && $summary['distribution']['Baik']['count'] === 1
                    && $summary['distribution']['Berbahaya']['count'] === 0;
            });
    }

    public function test_history_filters_by_room(): void
    {
        Livewire::test(UserHistory::class)
            ->set('room', (string) $this->bedroom->id)
            ->assertViewHas('logs', fn ($logs) => $logs->total() === 1 && $logs->first()->ku_status === 'Sedang');
    }

    public function test_history_filters_by_category(): void
    {
        Livewire::test(UserHistory::class)
            ->set('category', 'Tidak Sehat')
            ->assertViewHas('logs', fn ($logs) => $logs->total() === 1 && (float) $logs->first()->ku_value === 220.0)
            ->assertViewHas('summary', fn ($s) => $s['count'] === 1 && $s['worst'] === 'Tidak Sehat');
    }

    public function test_history_filters_by_date_range(): void
    {
        Livewire::test(UserHistory::class)
            ->set('from', '2026-10-02')
            ->set('to', '2026-10-02')
            ->assertViewHas('logs', fn ($logs) => $logs->total() === 1 && $logs->first()->ku_status === 'Tidak Sehat');

        Livewire::withQueryParams(['dari' => '2026-10-02'])
            ->test(UserHistory::class)
            ->assertSet('from', '2026-10-02')
            ->assertViewHas('logs', fn ($logs) => $logs->total() === 2);
    }

    public function test_reset_filters_clears_all_filters(): void
    {
        Livewire::test(UserHistory::class)
            ->set('room', (string) $this->kitchen->id)
            ->set('category', 'Baik')
            ->call('resetFilters')
            ->assertSet('room', '')
            ->assertSet('category', '')
            ->assertViewHas('logs', fn ($logs) => $logs->total() === 3);
    }

    public function test_history_shows_empty_state(): void
    {
        Livewire::test(UserHistory::class)
            ->set('category', 'Berbahaya')
            ->assertSee('Belum ada data')
            ->assertSee('Tidak ada pengukuran yang cocok dengan filter');
    }

    public function test_csv_download_contains_header_and_filtered_rows(): void
    {
        $component = Livewire::test(UserHistory::class)
            ->set('room', (string) $this->kitchen->id)
            ->call('downloadCsv');

        $download = $component->effects['download'];
        $this->assertMatchesRegularExpression('/^riwayat-kualitas-udara-\d{8}_\d{6}\.csv$/', $download['name']);

        $csv = base64_decode($download['content']);
        $this->assertStringContainsString('waktu,ruangan,indeks_ku,kategori,parameter_dominan,co2_ppm,nh3_ppm,co_ppm,h2_ppm', $csv);
        $this->assertStringContainsString('"2026-10-02 09:30:00","Dapur Utama",220,"Tidak Sehat",co,500,2,21.4,100', $csv);
        $this->assertStringContainsString('"2026-10-01 08:00:00","Dapur Utama",42.5,Baik,co2,850,2,1,100', $csv);
        $this->assertStringNotContainsString('Kamar Tidur', $csv);
    }

    public function test_guide_page_renders_for_guests(): void
    {
        $this->get(route('guide'))
            ->assertOk()
            ->assertSee('Panduan Kualitas Udara')
            ->assertSee('Cara membaca Indeks KU');
    }

    public function test_guide_shows_categories_gases_and_worked_example(): void
    {
        $expected = SensorLog::computeKu(850, 2, 6, 120);

        Livewire::test(AirQualityGuide::class)
            ->assertSee(['Baik', 'Sedang', 'Tidak Sehat', 'Berbahaya'])
            ->assertSee(['Karbon Dioksida', 'Amonia', 'Karbon Monoksida', 'Hidrogen'])
            ->assertSee(['MQ-135', 'MQ-2'])
            ->assertSee('850 ppm')
            ->assertSee('120 ppm')
            ->assertSee('Indeks KU =')
            ->assertSee(str_replace('.', ',', (string) $expected['value']))
            ->assertViewHas('result', fn ($r) => $r['dominant'] === 'co' && $r['status'] === 'Sedang')
            // Ambang CO hasil interpolasi balik: 4,4 / 12,4 / 30,4 ppm
            ->assertSee('0 – 4,4 ppm')
            ->assertSee('&gt; 12,4 – 30,4 ppm', false)
            ->assertSee('Pertanyaan umum')
            ->assertSee('bukan diagnosis medis');
    }

    public function test_guide_threshold_inverse_interpolation(): void
    {
        $this->assertSame(['Baik' => 1000.0, 'Sedang' => 2000.0, 'Tidak Sehat' => 5000.0], AirQualityGuide::thresholds('co2'));
        $this->assertEqualsWithDelta(4.4, AirQualityGuide::thresholds('co')['Baik'], 1e-9);
        $this->assertEqualsWithDelta(30.4, AirQualityGuide::thresholds('co')['Tidak Sehat'], 1e-9);
    }

    public function test_guide_explains_preventive_actions_with_epa_levels_and_sources(): void
    {
        Livewire::test(AirQualityGuide::class)
            ->assertSee('Tindakan preventif')
            ->assertSee(['Good', 'Moderate', 'Unhealthy for Sensitive Groups', 'Unhealthy', 'Very Unhealthy', 'Hazardous'])
            ->assertSee(['Tidak Sehat bagi Kelompok Sensitif', 'Sangat Tidak Sehat'])
            ->assertSee(config('preventive_actions.co_aqi_levels.2.cautionary'))
            ->assertSee(['0 – 50', '101 – 150', '≥ 301'])
            ->assertSee(config('preventive_actions.actions.h2.Berbahaya.0'))
            ->assertSee(config('preventive_actions.sources.epa_aqi.url'))
            ->assertSee('Rekomendasi bersifat informatif dan bukan diagnosis medis.');
    }

    public function test_guide_co_aqi_levels_derive_ranges_and_ku_category(): void
    {
        $levels = AirQualityGuide::coAqiLevels();

        $this->assertSame(['0 – 50', '51 – 100', '101 – 150', '151 – 200', '201 – 300', '≥ 301'], array_column($levels, 'range'));
        $this->assertSame(['Baik', 'Sedang', 'Sedang', 'Tidak Sehat', 'Tidak Sehat', 'Berbahaya'], array_column($levels, 'ku_status'));
    }
}
