<?php

namespace Database\Factories;

use App\Models\SurveyResponse;
use App\Support\Sus;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SurveyResponse>
 */
class SurveyResponseFactory extends Factory
{
    protected $model = SurveyResponse::class;

    public function definition(): array
    {
        $data = [
            'respondent_name' => fake()->optional(0.6)->name(),
            'respondent_group' => fake()->randomElement(array_keys(Sus::GROUPS)),
            'comment' => fake()->optional(0.4)->sentence(),
            'ip_hash' => null,
        ];

        // Jawaban cenderung positif: butir ganjil 3–5, butir genap 1–3.
        $answers = [];
        for ($i = 1; $i <= Sus::ITEM_COUNT; $i++) {
            $answers[$i] = Sus::isPositive($i) ? fake()->numberBetween(3, 5) : fake()->numberBetween(1, 3);
            $data["q{$i}"] = $answers[$i];
        }

        // Dihitung ulang juga oleh model saat disimpan.
        $data['sus_score'] = Sus::score($answers);

        return $data;
    }

    /**
     * Isi semua butir dengan jawaban tertentu (key 1..10 atau list 10 nilai).
     *
     * @param  array<int, int>  $answers
     */
    public function answers(array $answers): static
    {
        $answers = array_values($answers);

        return $this->state(function () use ($answers) {
            $state = [];
            foreach ($answers as $index => $value) {
                $state['q'.($index + 1)] = $value;
            }

            return $state;
        });
    }

    public function uniform(int $value): static
    {
        return $this->answers(array_fill(0, Sus::ITEM_COUNT, $value));
    }
}
