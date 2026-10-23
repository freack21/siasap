<?php

namespace Tests\Feature;

use App\Livewire\SurveyForm;
use App\Models\SurveyResponse;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class SurveyTest extends TestCase
{
    use RefreshDatabase;

    private function fillAnswers($component, array $answers)
    {
        foreach (array_values($answers) as $index => $value) {
            $component->set('answers.'.($index + 1), $value);
        }

        return $component;
    }

    public function test_guest_can_open_survey_page(): void
    {
        $this->get(route('survey'))
            ->assertOk()
            ->assertSee('Kuesioner Kegunaan Website')
            ->assertSee('Saya merasa website ini mudah digunakan.')
            ->assertSee('Sangat Tidak Setuju')
            ->assertDontSee('Kelompok responden');
    }

    public function test_guest_can_submit_and_score_is_stored(): void
    {
        $component = Livewire::test(SurveyForm::class)
            ->set('respondentName', 'Budi')
            ->set('comment', 'Tampilannya jelas.');

        $this->fillAnswers($component, [4, 2, 5, 1, 4, 2, 3, 2, 4, 3])
            ->call('submit')
            ->assertHasNoErrors()
            ->assertSet('submitted', true)
            ->assertSet('resultScore', 75.0)
            ->assertSee('Terima kasih')
            ->assertSee('B/C');

        $response = SurveyResponse::sole();
        $this->assertSame('Budi', $response->respondent_name);
        $this->assertSame('pengguna', $response->respondent_group);
        $this->assertSame(4, $response->q1);
        $this->assertSame(3, $response->q10);
        $this->assertEquals(75.0, $response->sus_score);
        $this->assertSame(64, strlen($response->ip_hash));
    }

    public function test_name_and_comment_are_optional(): void
    {
        $this->fillAnswers(Livewire::test(SurveyForm::class), array_fill(0, 10, 3))
            ->call('submit')
            ->assertHasNoErrors();

        $response = SurveyResponse::sole();
        $this->assertNull($response->respondent_name);
        $this->assertNull($response->comment);
        $this->assertEquals(50.0, $response->sus_score);
    }

    public function test_all_items_are_required(): void
    {
        $component = Livewire::test(SurveyForm::class);
        $this->fillAnswers($component, [4, 2, 5, 1, 4, 2, 3, 2, 4, 3])
            ->set('answers.7', null)
            ->call('submit')
            ->assertHasErrors(['answers.7' => 'required'])
            ->assertHasNoErrors(['answers.1'])
            ->assertSet('submitted', false);

        Livewire::test(SurveyForm::class)
            ->call('submit')
            ->assertHasErrors(['answers.1', 'answers.10']);

        $this->assertDatabaseCount('survey_responses', 0);
    }

    public function test_answers_must_be_between_one_and_five(): void
    {
        $component = Livewire::test(SurveyForm::class);
        $this->fillAnswers($component, [6, 2, 5, 1, 4, 2, 3, 0, 4, 3])
            ->call('submit')
            ->assertHasErrors(['answers.1' => 'between', 'answers.8' => 'between']);

        $this->assertDatabaseCount('survey_responses', 0);
    }

    public function test_submissions_are_rate_limited_per_ip(): void
    {
        for ($i = 0; $i < SurveyForm::MAX_PER_HOUR; $i++) {
            $this->fillAnswers(Livewire::test(SurveyForm::class), array_fill(0, 10, 4))
                ->call('submit')
                ->assertHasNoErrors();
        }

        $this->fillAnswers(Livewire::test(SurveyForm::class), array_fill(0, 10, 4))
            ->call('submit')
            ->assertHasErrors('rateLimit')
            ->assertSet('submitted', false)
            ->assertSee('Silakan coba lagi');

        $this->assertDatabaseCount('survey_responses', SurveyForm::MAX_PER_HOUR);
    }

    public function test_model_recomputes_score_from_answers(): void
    {
        $response = SurveyResponse::factory()->uniform(3)->create(['sus_score' => 99]);

        $this->assertEquals(50.0, $response->fresh()->sus_score);
    }
}
