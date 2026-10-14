<?php

namespace App\Models;

use App\Support\Sus;
use Database\Factories\SurveyResponseFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SurveyResponse extends Model
{
    /** @use HasFactory<SurveyResponseFactory> */
    use HasFactory;

    protected $fillable = [
        'respondent_name',
        'respondent_group',
        'q1', 'q2', 'q3', 'q4', 'q5', 'q6', 'q7', 'q8', 'q9', 'q10',
        'sus_score',
        'comment',
        'ip_hash',
    ];

    protected function casts(): array
    {
        $casts = ['sus_score' => 'float'];
        for ($i = 1; $i <= Sus::ITEM_COUNT; $i++) {
            $casts["q{$i}"] = 'integer';
        }

        return $casts;
    }

    /**
     * Skor SUS selalu dihitung ulang dari jawaban sehingga konsisten dengan data mentah.
     */
    protected static function booted(): void
    {
        static::saving(function (SurveyResponse $response) {
            $response->sus_score = Sus::score($response->answers());
        });
    }

    /**
     * @return array<int, int>
     */
    public function answers(): array
    {
        $answers = [];
        for ($i = 1; $i <= Sus::ITEM_COUNT; $i++) {
            $answers[$i] = (int) $this->getAttribute("q{$i}");
        }

        return $answers;
    }

    public function grade(): array
    {
        return Sus::grade((float) $this->sus_score);
    }

    public function groupLabel(): string
    {
        return Sus::groupLabel($this->respondent_group);
    }

    public static function hashIp(?string $ip): ?string
    {
        return $ip ? hash('sha256', $ip.config('app.key')) : null;
    }
}
