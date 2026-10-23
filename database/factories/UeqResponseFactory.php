<?php

namespace Database\Factories;

use App\Models\UeqResponse;
use App\Support\Ueq;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<UeqResponse>
 */
class UeqResponseFactory extends Factory
{
    protected $model = UeqResponse::class;

    public function definition(): array
    {
        $answers = [];
        for ($i = 1; $i <= Ueq::ITEM_COUNT; $i++) {
            $answers[$i] = fake()->numberBetween(1, 7);
        }

        return [
            'respondent_name' => fake()->optional(0.6)->name(),
            'answers' => $answers,
            'ip_hash' => null,
        ];
    }

    /**
     * @param  array<int, int>  $answers  26 nilai mentah 1..7
     */
    public function answers(array $answers): static
    {
        return $this->state(fn () => ['answers' => array_combine(range(1, Ueq::ITEM_COUNT), array_values($answers))]);
    }

    public function uniform(int $value): static
    {
        return $this->answers(array_fill(0, Ueq::ITEM_COUNT, $value));
    }
}
