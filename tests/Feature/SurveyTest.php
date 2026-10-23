<?php

namespace Tests\Feature;

use App\Livewire\SurveyForm;
use App\Models\UeqResponse;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
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

    public function test_guest_can_open_survey_page_with_26_items(): void
    {
        $response = $this->get(route('survey'))
            ->assertOk()
            ->assertSee('Kuesioner Pengalaman Pengguna')
            ->assertSee('menyusahkan')
            ->assertSee('konservatif')
            ->assertSee('Tidak ada jawaban benar atau salah');

        $this->assertSame(26 * 7, substr_count($response->getContent(), 'type="radio"'));
        $this->assertSame(26, substr_count($response->getContent(), '<legend'));
    }

    public function test_guest_can_submit_complete_answers(): void
    {
        $answers = range(1, 7);
        $answers = array_slice(array_merge($answers, $answers, $answers, $answers), 0, 26);

        $this->fillAnswers(Livewire::test(SurveyForm::class)->set('respondentName', 'Budi'), $answers)
            ->call('submit')
            ->assertHasNoErrors()
            ->assertSet('submitted', true)
            ->assertSee('Terima kasih');

        $response = UeqResponse::sole();
        $this->assertSame('Budi', $response->respondent_name);
        $this->assertCount(26, $response->answers);
        $this->assertSame(1, $response->answers[1]);
        $this->assertSame($answers[25], $response->answers[26]);
        $this->assertSame(64, strlen($response->ip_hash));
    }

    public function test_name_is_optional(): void
    {
        $this->fillAnswers(Livewire::test(SurveyForm::class), array_fill(0, 26, 4))
            ->call('submit')
            ->assertHasNoErrors();

        $this->assertNull(UeqResponse::sole()->respondent_name);
    }

    public function test_all_items_are_required(): void
    {
        $this->fillAnswers(Livewire::test(SurveyForm::class), array_fill(0, 26, 4))
            ->set('answers.7', null)
            ->call('submit')
            ->assertHasErrors(['answers.7' => 'required'])
            ->assertHasNoErrors(['answers.1'])
            ->assertSet('submitted', false);

        Livewire::test(SurveyForm::class)
            ->call('submit')
            ->assertHasErrors(['answers.1', 'answers.26']);

        $this->assertDatabaseCount('ueq_responses', 0);
    }

    public function test_answers_must_be_between_one_and_seven(): void
    {
        $answers = array_fill(0, 26, 4);
        $answers[0] = 8;
        $answers[7] = 0;

        $this->fillAnswers(Livewire::test(SurveyForm::class), $answers)
            ->call('submit')
            ->assertHasErrors(['answers.1' => 'between', 'answers.8' => 'between']);

        $this->assertDatabaseCount('ueq_responses', 0);
    }

    public function test_submissions_are_rate_limited_per_ip(): void
    {
        for ($i = 0; $i < SurveyForm::MAX_PER_HOUR; $i++) {
            $this->fillAnswers(Livewire::test(SurveyForm::class), array_fill(0, 26, 4))
                ->call('submit')
                ->assertHasNoErrors();
        }

        $this->fillAnswers(Livewire::test(SurveyForm::class), array_fill(0, 26, 4))
            ->call('submit')
            ->assertHasErrors('rateLimit')
            ->assertSet('submitted', false)
            ->assertSee('Silakan coba lagi');

        $this->assertDatabaseCount('ueq_responses', SurveyForm::MAX_PER_HOUR);
    }

    public function test_report_command_prints_summary_and_writes_csv(): void
    {
        UeqResponse::factory()->uniform(4)->create(['respondent_name' => 'Siti']);
        UeqResponse::factory()->uniform(5)->create(['respondent_name' => null]);
        $path = storage_path('framework/testing/ueq-export.csv');

        $this->artisan('ueq:report', ['--csv' => $path])
            ->expectsOutputToContain('Jumlah responden: 2')
            ->expectsOutputToContain('Attractiveness')
            ->expectsOutputToContain('Novelty')
            ->assertSuccessful();

        $lines = file($path, FILE_IGNORE_NEW_LINES);
        $this->assertSame('id,waktu,nama,'.implode(',', array_map(fn ($i) => "i{$i}", range(1, 26))), $lines[0]);
        $this->assertCount(3, $lines);
        $this->assertStringContainsString(',Siti,'.implode(',', array_fill(0, 26, 4)), $lines[1]);

        File::delete($path);
    }

    public function test_report_command_neutralizes_formula_in_respondent_name(): void
    {
        $names = ['=HYPERLINK("http://x")', '+1', '-1', '@SUM(A1)', "\tTab", 'Budi'];
        foreach ($names as $name) {
            UeqResponse::factory()->uniform(4)->create(['respondent_name' => $name]);
        }
        $path = storage_path('framework/testing/ueq-formula.csv');

        $this->artisan('ueq:report', ['--csv' => $path])->assertSuccessful();

        $handle = fopen($path, 'r');
        fgetcsv($handle, escape: '');
        $exported = [];
        while (($row = fgetcsv($handle, escape: '')) !== false) {
            $exported[] = $row[2];
        }
        fclose($handle);
        File::delete($path);

        $this->assertSame(["'=HYPERLINK(\"http://x\")", "'+1", "'-1", "'@SUM(A1)", "'\tTab", 'Budi'], $exported);
    }

    public function test_report_command_handles_no_responses(): void
    {
        $path = storage_path('framework/testing/ueq-empty.csv');

        $this->artisan('ueq:report', ['--csv' => $path])
            ->expectsOutputToContain('Jumlah responden: 0')
            ->assertSuccessful();

        File::delete($path);
    }
}
