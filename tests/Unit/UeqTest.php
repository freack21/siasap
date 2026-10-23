<?php

namespace Tests\Unit;

use App\Support\Ueq;
use InvalidArgumentException;
use Tests\TestCase;

class UeqTest extends TestCase
{
    /**
     * @return array<int, int>
     */
    private function bestAnswers(): array
    {
        $answers = [];
        for ($i = 1; $i <= Ueq::ITEM_COUNT; $i++) {
            $answers[$i] = in_array($i, config('ueq.reversed'), true) ? 1 : 7;
        }

        return $answers;
    }

    public function test_all_neutral_answers_give_zero_on_every_scale(): void
    {
        $means = Ueq::scaleMeans(array_fill(1, 26, 4));

        $this->assertCount(6, $means);
        foreach ($means as $mean) {
            $this->assertSame(0.0, (float) $mean);
        }
    }

    public function test_best_answers_give_plus_three_on_every_scale(): void
    {
        foreach (Ueq::scaleMeans($this->bestAnswers()) as $mean) {
            $this->assertSame(3.0, (float) $mean);
        }
    }

    public function test_reversed_item_3_answer_1_becomes_plus_3(): void
    {
        $answers = array_fill(1, 26, 4);
        $answers[3] = 1;
        $answers[1] = 1;

        $transformed = Ueq::transform($answers);

        $this->assertSame(3, $transformed[3]);
        $this->assertSame(-3, $transformed[1]);
    }

    public function test_scale_items_cover_all_26_items_once(): void
    {
        $all = collect(config('ueq.scales'))->flatten()->sort()->values()->all();

        $this->assertSame(range(1, 26), $all);
        $this->assertCount(26, config('ueq.items'));
    }

    public function test_benchmark_categories_follow_lower_bounds(): void
    {
        $this->assertSame('Excellent', Ueq::benchmarkCategory('Perspicuity', 1.90));
        $this->assertSame('Good', Ueq::benchmarkCategory('Perspicuity', 1.89));
        $this->assertSame('Above Average', Ueq::benchmarkCategory('Perspicuity', 1.08));
        $this->assertSame('Below Average', Ueq::benchmarkCategory('Perspicuity', 0.64));
        $this->assertSame('Bad', Ueq::benchmarkCategory('Perspicuity', 0.63));
    }

    public function test_alpha_null_when_fewer_than_two_responses(): void
    {
        $summary = Ueq::summary([$this->bestAnswers()]);

        $this->assertSame(1, $summary['count']);
        $this->assertSame(3.0, (float) $summary['scales']['Efficiency']['mean']);
        $this->assertNull($summary['scales']['Efficiency']['alpha']);
        $this->assertNull($summary['scales']['Efficiency']['sd']);
        $this->assertSame('Excellent', $summary['scales']['Efficiency']['benchmark']);
    }

    public function test_summary_computes_mean_sd_and_alpha_for_multiple_responses(): void
    {
        $varied = $this->bestAnswers();
        $varied[9] = 7; // butir terbalik dijawab 7 => -3

        $summary = Ueq::summary([$this->bestAnswers(), $varied, array_fill(1, 26, 4)]);

        $this->assertSame(3, $summary['count']);
        $this->assertNotNull($summary['scales']['Efficiency']['alpha']);
        $this->assertGreaterThan(0.0, $summary['scales']['Efficiency']['sd']);
        $this->assertEqualsWithDelta(2.0, $summary['scales']['Perspicuity']['mean'], 1e-9);
    }

    public function test_empty_summary_has_null_values(): void
    {
        $summary = Ueq::summary([]);

        $this->assertSame(0, $summary['count']);
        $this->assertNull($summary['scales']['Novelty']['mean']);
    }

    public function test_incomplete_or_out_of_range_answers_throw(): void
    {
        try {
            Ueq::transform(array_fill(1, 25, 4));
            $this->fail('Jawaban tidak lengkap harus ditolak.');
        } catch (InvalidArgumentException) {
            $this->addToAssertionCount(1);
        }

        $this->expectException(InvalidArgumentException::class);
        Ueq::transform(array_replace(array_fill(1, 26, 4), [5 => 8]));
    }
}
