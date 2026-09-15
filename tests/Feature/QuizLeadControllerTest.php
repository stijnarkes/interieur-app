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
 * op het scherm, en een moodboard met echt gekozen producten. Verzendt nooit een echte mail —
 * altijd Mail::fake().
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
        $response->assertJsonFragment(['message' => 'Je gegevens zijn opgeslagen, maar het versturen van de e-mail is niet gelukt.']);
        Mail::assertNotSent(QuizResultMail::class);
        $this->assertSame('failed', Submission::first()->email_status);
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
