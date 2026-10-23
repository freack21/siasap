<?php

namespace App\Livewire;

use App\Models\SurveyResponse;
use App\Support\Sus;
use Illuminate\Support\Facades\RateLimiter;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts::public')]
#[Title('Kuesioner — SIASAP')]
class SurveyForm extends Component
{
    /** Batas pengiriman per alamat IP per jam. */
    public const MAX_PER_HOUR = 3;

    public string $respondentName = '';

    /** @var array<int|string, int|string|null> Jawaban butir 1..10 */
    public array $answers = [];

    public string $comment = '';

    public bool $submitted = false;

    public ?float $resultScore = null;

    protected function rules(): array
    {
        $rules = [
            'respondentName' => ['nullable', 'string', 'max:100'],
            'comment' => ['nullable', 'string', 'max:1000'],
        ];

        foreach (array_keys(Sus::ITEMS) as $i) {
            $rules["answers.{$i}"] = ['required', 'integer', 'between:'.Sus::MIN.','.Sus::MAX];
        }

        return $rules;
    }

    protected function messages(): array
    {
        return [
            'answers.*.required' => 'Pilih salah satu jawaban untuk pernyataan ini.',
            'answers.*.integer' => 'Jawaban tidak valid.',
            'answers.*.between' => 'Jawaban harus bernilai 1 sampai 5.',
            'respondentName.max' => 'Nama maksimal 100 karakter.',
            'comment.max' => 'Komentar maksimal 1000 karakter.',
        ];
    }

    public function submit(): void
    {
        $validated = $this->validate();

        $key = 'survey-submit:'.(SurveyResponse::hashIp(request()->ip()) ?? 'unknown');

        if (RateLimiter::tooManyAttempts($key, self::MAX_PER_HOUR)) {
            $minutes = (int) ceil(RateLimiter::availableIn($key) / 60);
            $this->addError('rateLimit', "Terima kasih, kuesioner dari perangkat ini sudah cukup banyak. Silakan coba lagi dalam {$minutes} menit.");

            return;
        }

        RateLimiter::hit($key, 3600);

        $data = [
            'respondent_name' => filled($validated['respondentName'] ?? null) ? trim($validated['respondentName']) : null,
            'respondent_group' => Sus::DEFAULT_GROUP,
            'comment' => filled($validated['comment'] ?? null) ? trim($validated['comment']) : null,
            'ip_hash' => SurveyResponse::hashIp(request()->ip()),
        ];

        foreach (array_keys(Sus::ITEMS) as $i) {
            $data["q{$i}"] = (int) $validated['answers'][$i];
        }

        $response = SurveyResponse::create($data);

        $this->resultScore = $response->sus_score;
        $this->submitted = true;
        $this->reset('respondentName', 'answers', 'comment');
    }

    public function render()
    {
        return view('livewire.survey-form', [
            'items' => Sus::ITEMS,
            'scale' => Sus::SCALE,
            'grade' => $this->resultScore !== null ? Sus::grade($this->resultScore) : null,
            'acceptability' => $this->resultScore !== null ? Sus::acceptability($this->resultScore) : null,
        ]);
    }
}
