<?php

namespace Tests\Feature;

use App\Filament\Resources\SubmissionResource;
use App\Models\QuizSetting;
use App\Models\Submission;
use App\Models\User;
use App\Services\QuizScoringService;
use App\Support\QuizStructure;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\Test;
use Tests\Concerns\SeedsRealStyleQuizData;
use Tests\TestCase;

/**
 * Dekt het vernieuwde admin-inzendingscherm (zie SubmissionResource::infolist()) — de opdracht
 * "beheerscherm Inzending bekijken" vroeg expliciet om minstens deze scenario's te controleren:
 * een uitslag met één stijl, met een tweede stijlinvloed, twee keuzes bij meubelstof/verlichting,
 * een overgeslagen vraag, de zes uitslagScores en de PDF-knoppen — plus dat afbeeldingen niet meer
 * als kapotte iconen renderen (zie het gerepareerde moodboard-/afbeeldingsprobleem).
 */
class ViewSubmissionTest extends TestCase
{
    use RefreshDatabase;
    use SeedsRealStyleQuizData;

    private function admin(): User
    {
        return User::factory()->create(['is_admin' => true]);
    }

    /** @param  array<string, array<int, string>>  $answers */
    private function makeSubmission(array $answers, array $quizResultOverrides = [], array $attributes = []): Submission
    {
        $computed = app(QuizScoringService::class)->compute($answers);
        $primaryLabel = $computed['primary_style'] ? QuizStructure::styleLabel($computed['primary_style']) : null;
        $secondaryLabel = $computed['secondary_style'] ? QuizStructure::styleLabel($computed['secondary_style']) : null;

        $quizResult = array_merge([
            'resultName' => $primaryLabel,
            'description' => 'Een korte klantomschrijving van de uitslag.',
            'primaryStyle' => [
                'label' => $primaryLabel,
                // Zelfde vorm als StyleProfile::furniture_shapes (een array, geen platte string) —
                // dit exacte verschil veroorzaakte een htmlspecialchars()-fout in productie omdat de
                // eerdere testfixture hier per ongeluk een string gebruikte.
                'furnitureAdvice' => [
                    'intro' => 'Kies voor rustige, functionele meubels.',
                    'items' => ['Een eenvoudige bank', 'Een houten tafel'],
                ],
                'recipe' => [
                    ['label' => 'Basis', 'value' => 'Zand, Wit'],
                    ['label' => 'Accentkleur', 'value' => 'Terracotta'],
                ],
            ],
            'secondaryStyleLabel' => $secondaryLabel,
            'personalPalette' => [
                ['name' => 'Zand', 'hex' => '#D8CBB8'],
                ['name' => 'Wit', 'hex' => '#FFFFFF'],
            ],
            'basePaletteName' => 'Warme basis',
            'accentColors' => [
                ['id' => 1, 'name' => 'Terracotta', 'hex' => '#C1663B'],
            ],
            'colorExplanation' => 'Dit zijn de kleuren die de klant zelf koos.',
        ], $quizResultOverrides);

        return Submission::create(array_merge([
            'style' => $primaryLabel ?? 'Onbekend',
            'quiz_answers' => $answers,
            'quiz_result' => $quizResult,
            'name' => 'Anna Voorbeeld',
            'email' => 'anna@example.com',
            'email_opt_in' => true,
            'result_id' => (string) Str::uuid(),
            'result_generated' => true,
            'email_status' => 'sent',
        ], $attributes));
    }

    private function viewSubmission(Submission $submission)
    {
        return Livewire::actingAs($this->admin())
            ->test(SubmissionResource\Pages\ViewSubmission::class, ['record' => $submission->getKey()]);
    }

    #[Test]
    public function een_uitslag_met_een_stijl_toont_de_hoofdstijl_zonder_invloedtekst(): void
    {
        $this->seedRealQuizQuestionsAndOptions();
        $answers = $this->answersFavoring(self::JAPANDI);

        $submission = $this->makeSubmission($answers);

        $this->viewSubmission($submission)
            ->assertSee('Japandi')
            ->assertDontSee('-invloeden');
    }

    #[Test]
    public function een_uitslag_met_een_tweede_stijlinvloed_toont_de_invloedtekst(): void
    {
        $this->seedRealQuizQuestionsAndOptions();

        // Zelfde gemengde route als QuizScoringWoonstijlTest gebruikt om een realistische
        // "invloed"-uitslag te forceren: overwegend Japandi, met een paar Modern Scandinavisch-
        // antwoorden erdoorheen.
        $answers = $this->answersFavoring(self::JAPANDI);
        $answers['wallColor'] = ['wallcolor-behang-scandinavisch-nErLL'];
        $answers['sofaMaterial'] = ['sofamaterial-scandinavisch-m8Leo'];

        QuizSetting::current()->update(['secondary_influence_max_gap' => 50]);

        $computed = app(QuizScoringService::class)->compute($answers);
        $this->assertNotNull($computed['secondary_style'], 'Testopzet moet een secundaire stijl opleveren, anders test dit scenario niets.');

        $submission = $this->makeSubmission($answers);

        $this->viewSubmission($submission)
            ->assertSee(QuizStructure::styleLabel($computed['primary_style']))
            ->assertSee(QuizStructure::styleLabel($computed['secondary_style']).'-invloeden');
    }

    #[Test]
    public function twee_keuzes_bij_meubelstof_tonen_beide_gekozen_opties(): void
    {
        $slugsByQuestion = $this->seedRealQuizQuestionsAndOptions();
        $answers = $this->answersFavoring(self::JAPANDI);

        [$japandiOptie, , , , $modernOptie] = $slugsByQuestion['sofaMaterial'];
        $answers['sofaMaterial'] = [$japandiOptie, $modernOptie];

        $submission = $this->makeSubmission($answers);

        $this->viewSubmission($submission)
            ->assertSee($japandiOptie)
            ->assertSee($modernOptie);
    }

    #[Test]
    public function een_overgeslagen_vraag_wordt_duidelijk_aangegeven(): void
    {
        $this->seedRealQuizQuestionsAndOptions();
        $answers = $this->answersFavoring(self::JAPANDI);
        unset($answers['lighting']);

        $submission = $this->makeSubmission($answers);

        $this->viewSubmission($submission)->assertSee('Overgeslagen');
    }

    #[Test]
    public function de_zes_uitslagscores_staan_in_de_scorecontrole(): void
    {
        $this->seedRealQuizQuestionsAndOptions();
        $answers = $this->answersFavoring(self::JAPANDI);

        $submission = $this->makeSubmission($answers);

        $component = $this->viewSubmission($submission);

        foreach (QuizStructure::styleKeys() as $styleKey) {
            $component->assertSee(QuizStructure::styleLabel($styleKey));
        }
    }

    #[Test]
    public function de_pdf_knoppen_verschijnen_alleen_als_er_een_pdf_is(): void
    {
        $this->seedRealQuizQuestionsAndOptions();
        $answers = $this->answersFavoring(self::JAPANDI);

        $zonderPdf = $this->makeSubmission($answers, attributes: ['pdf_path' => null]);
        $this->viewSubmission($zonderPdf)->assertDontSee('Download PDF');

        $metPdf = $this->makeSubmission($answers, attributes: ['pdf_path' => 'submissions/test.pdf']);
        $this->viewSubmission($metPdf)
            ->assertSee('Bekijk PDF')
            ->assertSee('Download PDF');
    }

    #[Test]
    public function afbeeldingen_krijgen_een_nette_terugval_in_plaats_van_een_kapot_icoon(): void
    {
        $this->seedRealQuizQuestionsAndOptions();
        $answers = $this->answersFavoring(self::JAPANDI);

        $submission = $this->makeSubmission($answers);

        // Geen kapotte-afbeelding-detectie in een testomgeving zonder browser — wel controleren
        // dat elke afbeelding een nette "Geen afbeelding"-terugval heeft i.p.v. de oude, stille
        // realpath()-truc die op een absolute (S3/R2-)URL altijd faalde (zie moodboard-entry.blade.php,
        // nu verwijderd) en dat renderen zelf niet crasht op de echte publicImageUrl()-waarden.
        $this->viewSubmission($submission)->assertSee('Geen afbeelding');
    }

    #[Test]
    public function een_oude_inzending_zonder_quiz_result_of_quiz_answers_crasht_niet(): void
    {
        $submission = Submission::create([
            'style' => 'Landelijk',
            'quiz_answers' => null,
            'quiz_result' => null,
            'name' => 'Oude Klant',
            'email' => 'oud@example.com',
            'email_opt_in' => false,
            'result_id' => null,
            'result_generated' => false,
        ]);

        $this->viewSubmission($submission)->assertSuccessful();
    }
}
