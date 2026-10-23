<?php

namespace Tests\Feature;

use App\Livewire\UserHistory;
use App\Models\Node;
use App\Models\Room;
use App\Models\SensorLog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Livewire\Livewire;
use Tests\TestCase;

class PublicHistoryTest extends TestCase
{
    use RefreshDatabase;

    private const CSV_HEADER = 'waktu,ruangan,co2_ppm,nh3_ppm,co_ppm,h2_ppm,kategori_co2,kategori_nh3,kategori_co,kategori_h2,kategori_keseluruhan,penyebab_utama,rekomendasi';

    protected Room $kitchen;

    protected Room $bedroom;

    protected function setUp(): void
    {
        parent::setUp();

        $this->kitchen = Room::create(['name' => 'Dapur Utama', 'code' => 'DPR']);
        $this->bedroom = Room::create(['name' => 'Kamar Tidur', 'code' => 'KMR']);

        $kitchenNode = Node::create(['room_id' => $this->kitchen->id, 'name' => 'Node Dapur', 'code' => 'N-DPR']);
        $bedroomNode = Node::create(['room_id' => $this->bedroom->id, 'name' => 'Node Kamar', 'code' => 'N-KMR']);

        $this->log($kitchenNode, '2026-10-01 08:00:00', co2: 30);                  // Baik
        $this->log($kitchenNode, '2026-10-02 09:30:00', co2: 30, co: 55.5);        // Tidak Sehat (CO)
        $this->log($bedroomNode, '2026-10-03 10:15:00', co2: 70);                  // Sedang (CO2)
    }

    private function log(Node $node, string $at, float $co2 = 30, float $co = 10): SensorLog
    {
        return SensorLog::factory()
            ->withGases($co2, 20, $co, 5)
            ->create([
                'node_id' => $node->id,
                'recorded_at' => Carbon::parse($at),
                'created_at' => Carbon::parse($at),
                'updated_at' => Carbon::parse($at),
            ]);
    }

    public function test_history_page_renders_for_guests(): void
    {
        $this->get(route('history'))
            ->assertOk()
            ->assertSee('Riwayat Kualitas Udara')
            ->assertSee('Unduh CSV')
            ->assertSee('wire:poll.5s', false)
            ->assertSee('Dapur Utama')
            ->assertSee('Kamar Tidur')
            ->assertSee('01 Okt 2026 08:00:00')
            ->assertSee('55,50');
    }

    public function test_history_summary_reflects_filtered_set(): void
    {
        Livewire::test(UserHistory::class)
            ->assertViewHas('summary', function (array $summary) {
                return $summary['count'] === 3
                    && $summary['worst'] === 'Tidak Sehat'
                    && $summary['distribution']['Baik']['count'] === 1
                    && $summary['distribution']['Berbahaya']['count'] === 0;
            });
    }

    public function test_history_filters_by_room(): void
    {
        Livewire::test(UserHistory::class)
            ->set('room', (string) $this->bedroom->id)
            ->assertViewHas('logs', fn ($logs) => $logs->total() === 1 && $logs->first()->overall_category === 'Sedang');
    }

    public function test_history_filters_by_category(): void
    {
        Livewire::test(UserHistory::class)
            ->set('category', 'Tidak Sehat')
            ->assertViewHas('logs', fn ($logs) => $logs->total() === 1 && $logs->first()->overall_category === 'Tidak Sehat')
            ->assertViewHas('summary', fn ($s) => $s['count'] === 1 && $s['worst'] === 'Tidak Sehat');
    }

    public function test_history_ignores_unknown_category(): void
    {
        Livewire::withQueryParams(['kategori' => 'Sangat Baik'])
            ->test(UserHistory::class)
            ->assertViewHas('logs', fn ($logs) => $logs->total() === 3);
    }

    public function test_history_filters_by_date_range(): void
    {
        Livewire::test(UserHistory::class)
            ->set('from', '2026-10-02')
            ->set('to', '2026-10-02')
            ->assertViewHas('logs', fn ($logs) => $logs->total() === 1 && $logs->first()->overall_category === 'Tidak Sehat');

        Livewire::withQueryParams(['dari' => '2026-10-02'])
            ->test(UserHistory::class)
            ->assertSet('from', '2026-10-02')
            ->assertViewHas('logs', fn ($logs) => $logs->total() === 2);
    }

    public function test_history_paginates_twenty_rows_per_page(): void
    {
        $node = Node::where('code', 'N-KMR')->firstOrFail();

        foreach (range(1, 20) as $i) {
            $this->log($node, '2026-10-04 08:'.str_pad((string) $i, 2, '0', STR_PAD_LEFT).':00');
        }

        Livewire::test(UserHistory::class)
            ->assertViewHas('logs', fn ($logs) => $logs->total() === 23 && $logs->count() === 20 && $logs->lastPage() === 2);
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

    public function test_csv_download_has_exact_header_and_filtered_rows(): void
    {
        $component = Livewire::test(UserHistory::class)
            ->set('room', (string) $this->kitchen->id)
            ->call('downloadCsv');

        $download = $component->effects['download'];
        $this->assertMatchesRegularExpression('/^riwayat-kualitas-udara-\d{8}_\d{6}\.csv$/', $download['name']);

        $csv = base64_decode($download['content']);
        $lines = explode("\n", trim(preg_replace('/^\xEF\xBB\xBF/', '', $csv)));

        $this->assertSame(self::CSV_HEADER, $lines[0]);
        $this->assertCount(3, $lines);
        $this->assertStringStartsWith('"2026-10-02 09:30:00","Dapur Utama",30,20,55.5,5,Baik,Baik,"Tidak Sehat",Baik,"Tidak Sehat",co,', $lines[1]);
        $this->assertStringContainsString('Tingkatkan ventilasi, periksa dan kurangi sumber polutan', $lines[1]);
        $this->assertStringStartsWith('"2026-10-01 08:00:00","Dapur Utama",30,20,10,5,Baik,Baik,Baik,Baik,Baik,,', $lines[2]);
        $this->assertStringNotContainsString('Kamar Tidur', $csv);
    }

    public function test_csv_download_respects_category_and_date_filters(): void
    {
        $component = Livewire::test(UserHistory::class)
            ->set('category', 'Sedang')
            ->set('from', '2026-10-03')
            ->set('to', '2026-10-03')
            ->call('downloadCsv');

        $lines = explode("\n", trim(preg_replace('/^\xEF\xBB\xBF/', '', base64_decode($component->effects['download']['content']))));

        $this->assertCount(2, $lines);
        $this->assertStringStartsWith('"2026-10-03 10:15:00","Kamar Tidur",70,', $lines[1]);
    }
}
