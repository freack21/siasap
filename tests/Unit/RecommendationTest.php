<?php

namespace Tests\Unit;

use App\Support\Recommendation;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class RecommendationTest extends TestCase
{
    /**
     * Tabel 3.9 naskah: [kategori, tingkat, teks rekomendasi].
     *
     * @return array<string, array{string, string, string}>
     */
    public static function tabel39Provider(): array
    {
        return [
            'Baik' => ['Baik', 'Normal', 'Kondisi udara relatif baik. Pertahankan ventilasi ruangan.'],
            'Sedang' => ['Sedang', 'Waspada', 'Tingkatkan sirkulasi udara, misalnya dengan membuka jendela atau pintu.'],
            'Tidak Sehat' => ['Tidak Sehat', 'Perlu Tindakan', 'Tingkatkan ventilasi, periksa dan kurangi sumber polutan seperti asap atau gas, serta kurangi aktivitas di dalam ruangan.'],
            'Berbahaya' => ['Berbahaya', 'Segera Bertindak', 'Segera tingkatkan ventilasi, hentikan sumber polutan, dan pertimbangkan meninggalkan ruangan sementara sampai kondisi udara membaik.'],
        ];
    }

    #[DataProvider('tabel39Provider')]
    public function test_for_category_matches_table_3_9(string $category, string $level, string $text): void
    {
        $rule = Recommendation::forCategory($category);

        $this->assertSame($category, $rule['category']);
        $this->assertSame($level, $rule['level']);
        $this->assertSame($text, $rule['text']);
    }

    public function test_all_returns_four_rules_in_order(): void
    {
        $this->assertSame(
            ['Baik', 'Sedang', 'Tidak Sehat', 'Berbahaya'],
            array_column(Recommendation::all(), 'category'),
        );
    }

    public function test_every_rule_cites_epa_strategy(): void
    {
        foreach (Recommendation::all() as $rule) {
            $this->assertNotEmpty($rule['strategies']);
            $this->assertEmpty(array_diff($rule['strategies'], ['kendali_sumber', 'ventilasi', 'pembersihan_udara']));
        }
    }

    public function test_sources_expose_label_and_url(): void
    {
        $sources = Recommendation::sources();

        $this->assertCount(2, $sources);
        foreach ($sources as $source) {
            $this->assertNotEmpty($source['label']);
            $this->assertStringStartsWith('https://', $source['url']);
        }
    }

    public function test_unknown_category_throws(): void
    {
        $this->expectException(InvalidArgumentException::class);

        Recommendation::forCategory('Kritis');
    }
}
