<?php

namespace Tests\Feature;

use App\Livewire\SurveyForm;
use App\Livewire\SurveyResults;
use App\Models\Permission;
use App\Models\Role;
use App\Models\SurveyResponse;
use App\Models\User;
use Database\Seeders\SetupRbacSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class SurveyTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        $this->seed(SetupRbacSeeder::class);

        return User::where('email', 'admin@siasap.test')->firstOrFail();
    }

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
            ->assertSee('Sangat Tidak Setuju');
    }

    public function test_guest_can_submit_and_score_is_stored(): void
    {
        $component = Livewire::test(SurveyForm::class)
            ->set('respondentName', 'Budi')
            ->set('respondentGroup', 'ahli_media')
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
        $this->assertSame('ahli_media', $response->respondent_group);
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
            ->set('respondentGroup', 'tidak-ada')
            ->call('submit')
            ->assertHasErrors(['answers.1' => 'between', 'answers.8' => 'between', 'respondentGroup' => 'in']);

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

    public function test_guest_cannot_view_results(): void
    {
        $this->get(route('admin.survey'))->assertRedirect(route('login'));
    }

    public function test_user_without_survey_permission_gets_403(): void
    {
        $this->seed(SetupRbacSeeder::class);

        $role = Role::create(['name' => 'tanpa-survei', 'display_name' => 'Tanpa Survei']);
        $role->permissions()->sync(Permission::where('name', 'telemetry.view')->pluck('id'));
        $user = User::factory()->create();
        $user->roles()->sync([$role->id]);

        $this->actingAs($user)->get(route('admin.survey'))->assertForbidden();

        $response = SurveyResponse::factory()->create();
        Livewire::actingAs($user)->test(SurveyResults::class)->assertForbidden();
        $this->assertDatabaseHas('survey_responses', ['id' => $response->id]);
    }

    public function test_admin_sees_averages_and_grade(): void
    {
        SurveyResponse::factory()->answers([5, 1, 5, 1, 5, 1, 5, 1, 5, 1])->create(['respondent_group' => 'pengguna']); // 100
        SurveyResponse::factory()->uniform(3)->create(['respondent_group' => 'pengguna', 'respondent_name' => null]); // 50
        SurveyResponse::factory()->answers([4, 2, 5, 1, 4, 2, 3, 2, 4, 3])->create(['respondent_group' => 'ahli_media', 'respondent_name' => 'Pakar']); // 75

        $this->actingAs($this->admin())
            ->get(route('admin.survey'))
            ->assertOk()
            ->assertSee('Hasil Kuesioner SUS')
            ->assertSee('75,00')   // rata-rata (100 + 50 + 75) / 3
            ->assertSee('25,00')   // standar deviasi sampel
            ->assertSee('Good')
            ->assertSee('Acceptable')
            ->assertSee('Anonim')
            ->assertSee('Pakar')
            ->assertSee('Unduh CSV');

        Livewire::actingAs($this->admin())
            ->test(SurveyResults::class)
            ->set('group', 'pengguna')
            ->assertSee('75,00')
            ->assertSee('35,36')
            ->assertDontSee('Pakar');
    }

    public function test_admin_sees_empty_state(): void
    {
        $this->actingAs($this->admin())
            ->get(route('admin.survey'))
            ->assertOk()
            ->assertSee('Belum ada respons');
    }

    public function test_admin_can_export_csv(): void
    {
        SurveyResponse::factory()->answers([4, 2, 5, 1, 4, 2, 3, 2, 4, 3])->create([
            'respondent_name' => 'Siti',
            'respondent_group' => 'pengguna',
            'comment' => 'Mantap',
        ]);

        $component = Livewire::actingAs($this->admin())
            ->test(SurveyResults::class)
            ->call('export')
            ->assertFileDownloaded();

        $csv = base64_decode($component->effects['download']['content']);

        $this->assertStringContainsString('no,waktu,nama,kelompok,q1,q2,q3,q4,q5,q6,q7,q8,q9,q10,skor_sus,grade', $csv);
        $this->assertStringContainsString('Siti,"Penghuni / Pengguna Ruangan",4,2,5,1,4,2,3,2,4,3,75.00,B/C,Good,Acceptable,Mantap', $csv);
    }

    public function test_admin_can_delete_response(): void
    {
        $response = SurveyResponse::factory()->create();

        Livewire::actingAs($this->admin())
            ->test(SurveyResults::class)
            ->call('delete', $response->id)
            ->assertHasNoErrors();

        $this->assertDatabaseMissing('survey_responses', ['id' => $response->id]);
    }

    public function test_model_recomputes_score_from_answers(): void
    {
        $response = SurveyResponse::factory()->uniform(3)->create(['sus_score' => 99]);

        $this->assertEquals(50.0, $response->fresh()->sus_score);
    }
}
