<?php

namespace Tests\Feature;

use App\Livewire\RecommendationPage;
use App\Models\Node;
use App\Models\Room;
use App\Models\SensorLog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class RecommendationPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_page_shows_all_four_rules_and_disclaimer(): void
    {
        $this->get(route('recommendations'))
            ->assertOk()
            ->assertSee('Rekomendasi Tindakan')
            ->assertSee('Saran untuk setiap kategori')
            ->assertSeeInOrder(['Normal', 'Waspada', 'Perlu Tindakan', 'Segera Bertindak'])
            ->assertSee('Kondisi udara relatif baik. Pertahankan ventilasi ruangan.')
            ->assertSee('Tingkatkan sirkulasi udara, misalnya dengan membuka jendela atau pintu.')
            ->assertSee('Tingkatkan ventilasi, periksa dan kurangi sumber polutan seperti asap atau gas, serta kurangi aktivitas di dalam ruangan.')
            ->assertSee('Segera tingkatkan ventilasi, hentikan sumber polutan, dan pertimbangkan meninggalkan ruangan sementara sampai kondisi udara membaik.')
            ->assertSee('Rekomendasi bersifat informatif dan bukan diagnosis medis.');
    }

    public function test_basis_of_recommendations_is_linked_to_guide(): void
    {
        Livewire::test(RecommendationPage::class)
            ->assertSee('Dasar penyusunan saran')
            ->assertSee(route('guide'), false)
            ->assertDontSee('Strategi EPA')
            ->assertDontSee(config('recommendations.basis')[0]);
    }

    public function test_current_condition_shows_latest_category_and_recommendation_per_room(): void
    {
        $room = Room::create(['name' => 'Laboratorium Jaringan', 'code' => 'LAB']);
        $node = Node::create(['room_id' => $room->id, 'name' => 'Sensor Lab', 'code' => 'AIR-001']);

        SensorLog::factory()->withGases(30, 20, 10, 5)->create(['node_id' => $node->id, 'created_at' => now()->subMinute()]);
        SensorLog::factory()->withGases(30, 20, 55, 5)->create(['node_id' => $node->id, 'created_at' => now()]);

        Livewire::test(RecommendationPage::class)
            ->assertSee('Laboratorium Jaringan')
            ->assertViewHas('conditions', fn ($conditions) => $conditions->count() === 1
                && $conditions->first()['log']->overall_category === 'Tidak Sehat'
                && $conditions->first()['recommendation']['level'] === 'Perlu Tindakan');
    }

    public function test_current_condition_is_empty_without_data(): void
    {
        $room = Room::create(['name' => 'Laboratorium Jaringan', 'code' => 'LAB']);
        Node::create(['room_id' => $room->id, 'name' => 'Sensor Lab', 'code' => 'AIR-001']);

        Livewire::test(RecommendationPage::class)
            ->assertSee('Laboratorium Jaringan')
            ->assertSee('Belum ada data')
            ->assertViewHas('conditions', fn ($conditions) => $conditions->first()['log'] === null);
    }
}
