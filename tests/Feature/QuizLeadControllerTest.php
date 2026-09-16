<?php

namespace Tests\Feature;

use App\Mail\QuizResultMail;
use App\Models\QuizOption;
use App\Models\QuizQuestion;
use App\Models\QuizResult;
use App\Models\StyleProfile;
use App\Models\Submission;
use App\Services\QuizResultPdfService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Dekt de opdracht-vereisten rond de verzendflow: nooit dubbel mailen, exact dezelfde uitslag als
 * op het scherm, en een moodboard met echt gekozen producten. PDF-generatie/mailverzending lopen
 * synchroon binnen de aanvraag (zie QuizLeadController/GenerateAndSendQuizResultPdfJob — er draait
 * geen queue-worker, de eigenlijke snelheidswinst zit in PdfImageResolver). Verzendt nooit een
 * echte mail — altijd Mail::fake().
 */
class QuizLeadControllerTest extends TestCase
{
    use RefreshDatabase;

    private function makeQuizResult(): QuizResult
    {
        StyleProfile::create([
            'style_key' => 'japandi', 'label' => 'Japandi', 'slug' => 'japandi',
            'long_description' => 'Rust en warmte passen bij jou.',
        ]);

        QuizQuestion::create([
            'question_key' => 'vloer', 'section' => 'materials-colors', 'title' => 'Welke vloer?',
            'folder' => null, 'sort_order' => 10, 'max_selections' => 1, 'weight' => 1, 'image_display_mode' => 'contain',
        ]);

        $option = QuizOption::create([
            'question_id' => 'vloer', 'style_key' => 'japandi', 'option_slug' => 'eiken',
            'primary_style' => 'japandi', 'title' => 'Eiken vloer', 'is_active' => true, 'has_image' => false,
        ]);

        return QuizResult::create([
            'uuid' => (string) Str::uuid(),
            'answers' => ['vloer' => [$option->option_slug]],
            'style_scores' => ['japandi' => 1],
            'primary_style' => 'japandi',
            'secondary_style' => null,
        ]);
    }

    private function postLead(string $resultUuid, array $overrides = []): \Illuminate\Testing\TestResponse
    {
        return $this->postJson('/api/quiz-lead', array_merge([
            'resultUuid' => $resultUuid,
            'name' => 'Test',
            'email' => 'test@example.com',
        ], $overrides));
    }

    #[Test]
    public function succesvolle_inzending_slaat_op_en_verstuurt_precies_een_mail(): void
    {
        Mail::fake();
        $quizResult = $this->makeQuizResult();

        $response = $this->postLead($quizResult->uuid);

        $response->assertOk();
        $response->assertJsonFragment(['status' => 'sent']);
        Mail::assertSent(QuizResultMail::class, 1);
        $this->assertSame(1, Submission::where('quiz_result_id', $quizResult->id)->count());
        $this->assertSame('sent', Submission::first()->email_status);
    }

    #[Test]
    public function marketingoptin_is_optioneel_en_blokkeert_de_pdf_niet(): void
    {
        Mail::fake();
        $quizResult = $this->makeQuizResult();

        // Bewust geen marketingOptIn meegegeven.
        $response = $this->postJson('/api/quiz-lead', [
            'resultUuid' => $quizResult->uuid,
            'name' => 'Test',
            'email' => 'test@example.com',
        ]);

        $response->assertOk();
        Mail::assertSent(QuizResultMail::class, 1);
        $this->assertFalse(Submission::first()->email_opt_in);
    }

    #[Test]
    public function een_dubbele_inzending_met_dezelfde_resultuuid_verstuurt_nooit_een_tweede_mail(): void
    {
        Mail::fake();
        $quizResult = $this->makeQuizResult();

        $first = $this->postLead($quizResult->uuid);
        $second = $this->postLead($quizResult->uuid);

        $first->assertOk();
        $second->assertOk();
        Mail::assertSent(QuizResultMail::class, 1);
        $this->assertSame(1, Submission::where('quiz_result_id', $quizResult->id)->count());
    }

    #[Test]
    public function een_mislukte_pdf_of_mailaanroep_geeft_een_eerlijke_foutmelding_en_verstuurt_niets(): void
    {
        Mail::fake();
        $this->mock(QuizResultPdfService::class, function ($mock) {
            $mock->shouldReceive('generate')->andThrow(new \RuntimeException('PDF-generatie mislukt in de test.'));
        });

        $quizResult = $this->makeQuizResult();
        $response = $this->postLead($quizResult->uuid);

        $response->assertOk();
        $response->assertJsonFragment([
            'status' => 'failed',
            'message' => 'Je gegevens zijn opgeslagen, maar het versturen van de e-mail is niet gelukt.',
        ]);
        Mail::assertNotSent(QuizResultMail::class);
        $this->assertSame('failed', Submission::first()->email_status);
        $this->assertSame('PDF-generatie mislukt in de test.', Submission::first()->email_error);
    }

    #[Test]
    public function een_mislukte_inzending_kan_opnieuw_geprobeerd_worden_en_verstuurt_dan_alsnog_een_mail(): void
    {
        Mail::fake();
        $this->mock(QuizResultPdfService::class, function ($mock) {
            $mock->shouldReceive('generate')->once()->andThrow(new \RuntimeException('PDF-generatie mislukt in de test.'));
            $mock->shouldReceive('generate')->once()->andReturn('submissions/1/quiz-result.pdf');
        });

        $quizResult = $this->makeQuizResult();

        $first = $this->postLead($quizResult->uuid);
        $first->assertJsonFragment(['status' => 'failed']);

        $second = $this->postLead($quizResult->uuid);
        $second->assertJsonFragment(['status' => 'sent']);

        Mail::assertSent(QuizResultMail::class, 1);
        $this->assertSame(1, Submission::where('quiz_result_id', $quizResult->id)->count());
        $this->assertSame('sent', Submission::first()->email_status);
    }

    #[Test]
    public function een_vastgelopen_status_van_een_afgebroken_eerdere_aanvraag_mag_opnieuw_geprobeerd_worden(): void
    {
        Mail::fake();
        $quizResult = $this->makeQuizResult();

        // Simuleert een aanvraag die halverwege is afgebroken (bv. de server werd herstart)
        // vóórdat 'sent'/'failed' geregistreerd kon worden.
        $stuck = Submission::create([
            'quiz_result_id' => $quizResult->id,
            'style' => 'Japandi',
            'name' => 'Test',
            'email' => 'test@example.com',
            'email_status' => 'queued',
        ]);
        $stuck->forceFill(['updated_at' => now()->subMinutes(5)])->saveQuietly();

        $response = $this->postLead($quizResult->uuid);

        $response->assertJsonFragment(['status' => 'sent']);
        Mail::assertSent(QuizResultMail::class, 1);
        $this->assertSame(1, Submission::where('quiz_result_id', $quizResult->id)->count());
    }

    #[Test]
    public function de_statuscheck_geeft_de_actuele_uitkomst_van_een_inzending_terug(): void
    {
        Mail::fake();
        $quizResult = $this->makeQuizResult();
        $this->postLead($quizResult->uuid);

        $response = $this->getJson("/api/quiz-lead/{$quizResult->uuid}");

        $response->assertOk();
        $response->assertJsonFragment(['status' => 'sent']);
    }

    #[Test]
    public function de_statuscheck_op_een_resultaat_zonder_inzending_geeft_onbekend_terug(): void
    {
        $quizResult = $this->makeQuizResult();

        $response = $this->getJson("/api/quiz-lead/{$quizResult->uuid}");

        $response->assertStatus(404);
        $response->assertJsonFragment(['status' => 'unknown']);
    }

    #[Test]
    public function de_statuscheck_op_een_niet_bestaand_resultaat_geeft_onbekend_terug(): void
    {
        $response = $this->getJson('/api/quiz-lead/'.Str::uuid());

        $response->assertStatus(404);
        $response->assertJsonFragment(['status' => 'unknown']);
    }

    #[Test]
    public function het_moodboard_toont_alleen_daadwerkelijk_gekozen_producten(): void
    {
        Mail::fake();
        $quizResult = $this->makeQuizResult();

        $this->postLead($quizResult->uuid);

        $moodboard = Submission::first()->quiz_result['moodboard'];
        $this->assertCount(1, $moodboard);
        $this->assertSame('Eiken vloer', $moodboard[0]['title']);
    }

    #[Test]
    public function de_pdf_toont_exact_de_accentkleuren_die_de_bezoeker_zelf_koos(): void
    {
        Mail::fake();
        $quizResult = $this->makeQuizResult();
        $quizResult->update([
            'chosen_accent_colors' => [
                ['id' => 1, 'name' => 'Mosgroen', 'hex' => '#6b7a4f'],
            ],
        ]);

        $this->postLead($quizResult->uuid);

        $this->assertSame(
            [['id' => 1, 'name' => 'Mosgroen', 'hex' => '#6b7a4f']],
            Submission::first()->quiz_result['accentColors'],
        );
    }

    #[Test]
    public function een_oud_resultaat_zonder_gekozen_accentkleur_blijft_werken(): void
    {
        Mail::fake();
        $quizResult = $this->makeQuizResult();

        $response = $this->postLead($quizResult->uuid);

        $response->assertJsonFragment(['status' => 'sent']);
        $this->assertSame([], Submission::first()->quiz_result['accentColors']);
    }

    #[Test]
    public function resultatenpagina_en_pdf_gebruiken_exact_dezelfde_basisstijl(): void
    {
        Mail::fake();
        $quizResult = $this->makeQuizResult();

        $resultResponse = $this->postJson('/api/quiz-result', ['answers' => ['vloer' => ['eiken']]]);
        $resultResponse->assertOk();
        $screenLabel = $resultResponse->json('primaryStyle.label');

        $this->postLead($resultResponse->json('resultUuid'));
        $pdfLabel = Submission::first()->quiz_result['primaryStyle']['label'];

        $this->assertSame($screenLabel, $pdfLabel);
    }
}
