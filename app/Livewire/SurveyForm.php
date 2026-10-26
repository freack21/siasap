<?php

namespace App\Livewire;

use App\Models\UeqResponse;
use App\Support\Ueq;
use Illuminate\Support\Facades\RateLimiter;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts::public')]
#[Title('Kuesioner — SIMKU')]
class SurveyForm extends Component
{
    /** Batas pengiriman per alamat IP per jam. */
    public const MAX_PER_HOUR = 3;

    public string $respondentName = '';

    /** @var array<int|string, int|string|null> Jawaban butir 1..26 (1 = istilah kiri, 7 = istilah kanan) */
    public array $answers = [];

    public bool $submitted = false;

    protected function rules(): array
    {
        $rules = [
            'respondentName' => ['nullable', 'string', 'max:100'],
        ];

        foreach (array_keys(config('ueq.items')) as $i) {
            $rules["answers.{$i}"] = ['required', 'integer', 'between:'.Ueq::MIN.','.Ueq::MAX];
        }

        return $rules;
    }

    protected function messages(): array
    {
        return [
            'answers.*.required' => 'Pilih salah satu lingkaran pada baris ini.',
            'answers.*.integer' => 'Jawaban tidak valid.',
            'answers.*.between' => 'Jawaban harus bernilai 1 sampai 7.',
            'respondentName.max' => 'Nama maksimal 100 karakter.',
        ];
    }

    public function submit(): void
    {
        $validated = $this->validate();

        $ipHash = UeqResponse::hashIp(request()->ip());
        $key = 'survey-submit:'.($ipHash ?? 'unknown');

        if (RateLimiter::tooManyAttempts($key, self::MAX_PER_HOUR)) {
            $minutes = (int) ceil(RateLimiter::availableIn($key) / 60);
            $this->addError('rateLimit', "Terima kasih, kuesioner dari perangkat ini sudah cukup banyak. Silakan coba lagi dalam {$minutes} menit.");

            return;
        }

        RateLimiter::hit($key, 3600);

        $answers = [];
        foreach (array_keys(config('ueq.items')) as $i) {
            $answers[$i] = (int) $validated['answers'][$i];
        }

        UeqResponse::create([
            'respondent_name' => filled($validated['respondentName'] ?? null) ? trim($validated['respondentName']) : null,
            'answers' => $answers,
            'ip_hash' => $ipHash,
        ]);

        $this->submitted = true;
        $this->reset('respondentName', 'answers');
    }

    public function render()
    {
        return view('livewire.survey-form', [
            'items' => config('ueq.items'),
            'points' => range(Ueq::MIN, Ueq::MAX),
        ]);
    }
}
