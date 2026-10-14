<?php

namespace Tests\Unit;

use App\Support\Sus;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class SusTest extends TestCase
{
    public function test_all_neutral_answers_score_fifty(): void
    {
        $this->assertSame(50.0, Sus::score(array_fill(0, 10, 3)));
    }

    public function test_best_case_scores_one_hundred(): void
    {
        $this->assertSame(100.0, Sus::score([5, 1, 5, 1, 5, 1, 5, 1, 5, 1]));
    }

    public function test_worst_case_scores_zero(): void
    {
        $this->assertSame(0.0, Sus::score([1, 5, 1, 5, 1, 5, 1, 5, 1, 5]));
    }

    public function test_mixed_example(): void
    {
        // Ganjil: (4-1)+(5-1)+(4-1)+(3-1)+(4-1) = 15
        // Genap : (5-2)+(5-1)+(5-2)+(5-2)+(5-3) = 15
        // (15 + 15) × 2,5 = 75
        $this->assertSame(75.0, Sus::score([4, 2, 5, 1, 4, 2, 3, 2, 4, 3]));
    }

    public function test_accepts_one_indexed_keys(): void
    {
        $answers = [1 => 4, 2 => 2, 3 => 5, 4 => 1, 5 => 4, 6 => 2, 7 => 3, 8 => 2, 9 => 4, 10 => 3];

        $this->assertSame(75.0, Sus::score($answers));
    }

    public function test_rejects_invalid_input(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        Sus::score([3, 3, 3]);
    }

    public function test_rejects_out_of_range_answer(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        Sus::score([6, 3, 3, 3, 3, 3, 3, 3, 3, 3]);
    }

    public static function gradeProvider(): array
    {
        return [
            [100, 'A'],
            [80.3, 'A'],
            [80.29, 'B/C'],
            [68, 'B/C'],
            [67.99, 'D'],
            [51, 'D'],
            [50.99, 'F'],
            [0, 'F'],
        ];
    }

    #[DataProvider('gradeProvider')]
    public function test_grade_boundaries(float $score, string $grade): void
    {
        $this->assertSame($grade, Sus::grade($score)['grade']);
    }

    public static function acceptabilityProvider(): array
    {
        return [
            [100, 'acceptable'],
            [70, 'acceptable'],
            [69.99, 'marginal'],
            [50, 'marginal'],
            [49.99, 'not_acceptable'],
            [0, 'not_acceptable'],
        ];
    }

    #[DataProvider('acceptabilityProvider')]
    public function test_acceptability_boundaries(float $score, string $key): void
    {
        $this->assertSame($key, Sus::acceptability($score)['key']);
    }

    public function test_grade_adjectives(): void
    {
        $this->assertSame('Excellent', Sus::grade(85)['adjective']);
        $this->assertSame('Good', Sus::grade(70)['adjective']);
        $this->assertSame('OK', Sus::grade(60)['adjective']);
        $this->assertSame('Poor', Sus::grade(40)['adjective']);
    }

    public function test_mean_and_sample_standard_deviation(): void
    {
        $this->assertNull(Sus::mean([]));
        $this->assertNull(Sus::standardDeviation([70.0]));
        $this->assertEqualsWithDelta(75.0, Sus::mean([70, 80]), 1e-9);
        $this->assertEqualsWithDelta(7.0710678, Sus::standardDeviation([70, 80]), 1e-6);
    }
}
