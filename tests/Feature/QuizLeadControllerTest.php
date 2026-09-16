<?php

namespace Tests\Feature;

use App\Jobs\GenerateAndSendQuizResultPdfJob;
use App\Models\QuizOption;
use App\Models\QuizQuestion;
use App\Models\QuizResult;
use App\Models\StyleProfile;
use App\Models\Submission;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Str;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Dekt de opdracht-vereisten rond de verzendflow: nooit dubbel mailen, exact dezelfde uitslag als
 * op het scherm, en een moodboard met echt gekozen producten. PDF-generatie/mailverzending zelf
 * gebeurt op de achtergrond (zie GenerateAndSendQuizResultPdfJob, apart getest) — deze tests
 * gebruiken Bus::fake() en controleren alleen of de juiste taak wel/niet ingepland wordt.
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
    public function succesvolle_inzending_slaat_op_en_plant_precies_een_taak_in(): void
    {
        Bus::fake();
        $quizResult = $this->makeQuizResult();

        $response = $this->postLead($quizResult->uuid);

        $response->assertOk();
        $response->assertJsonFragment(['status' => 'queued']);
        $this->assertSame(1, Submission::where('quiz_result_id', $quizResult->id)->count());
        $this->assertSame('queued', Submission::first()->email_status);
        Bus::assertDispatched(GenerateAndSendQuizResultPdfJob::class, fn ($job) => $job->submissionId === Submission::first()->id);
    }

    #[Test]
    public function marketingoptin_is_optioneel_en_blokkeert_de_aanvraag_niet(): void
    {
        Bus::fake();
        $quizResult = $this->makeQuizResult();

        // Bewust geen marketingOptIn meegegeven.
        $response = $this->postJson('/api/quiz-lead', [
            'resultUuid' => $quizResult->uuid,
            'name' => 'Test',
            'email' => 'test@example.com',
        ]);

        $response->assertOk();
        Bus::assertDispatched(GenerateAndSendQuizResultPdfJob::class);
        $this->assertFalse(Submission::first()->email_opt_in);
    }

    #[Test]
    public function een_dubbele_inzending_met_dezelfde_resultuuid_plant_de_taak_niet_opnieuw_in(): void
    {
        Bus::fake();
        $quizResult = $this->makeQuizResult();

        $first = $this->postLead($quizResult->uuid);
        $second = $this->postLead($quizResult->uuid);

        $first->assertOk();
        $second->assertOk();
        $second->assertJsonFragment(['status' => 'queued']);
        Bus::assertDispatchedTimes(GenerateAndSendQuizResultPdfJob::class, 1);
        $this->assertSame(1, Submission::where('quiz_result_id', $quizResult->id)->count());
    }

    #[Test]
    public function een_al_verzonden_inzending_start_nooit_een_nieuwe_taak(): void
    {
        Bus::fake();
        $quizResult = $this->makeQuizResult();

        Submission::create([
            'quiz_result_id' => $quizResult->id,
            'style' => 'Japandi',
            'name' => 'Test',
            'email' => 'test@example.com',
            'email_status' => 'sent',
            'email_sent_at' => now(),
        ]);

        $response = $this->postLead($quizResult->uuid);

        $response->assertJsonFragment(['status' => 'sent']);
        Bus::assertNotDispatched(GenerateAndSendQuizResultPdfJob::class);
        $this->assertSame(1, Submission::where('quiz_result_id', $quizResult->id)->count());
    }

    #[Test]
    public function een_vastgelopen_wachtrij_status_mag_opnieuw_geprobeerd_worden(): void
    {
        Bus::fake();
        $quizResult = $this->makeQuizResult();

        $stale = Submission::create([
            'quiz_result_id' => $quizResult->id,
            'style' => 'Japandi',
            'name' => 'Test',
            'email' => 'test@example.com',
            'email_status' => 'queued',
        ]);
        // Simuleert een taak die (bv. door een ontbrekende worker) al lang op 'queued' staat.
        $stale->forceFill(['updated_at' => now()->subMinutes(5)])->saveQuietly();

        $response = $this->postLead($quizResult->uuid);

        $response->assertJsonFragment(['status' => 'queued']);
        Bus::assertDispatched(GenerateAndSendQuizResultPdfJob::class);
        $this->assertSame(1, Submission::where('quiz_result_id', $quizResult->id)->count());
    }

    #[Test]
    public function een_mislukte_inzending_kan_opnieuw_geprobeerd_worden(): void
    {
        Bus::fake();
        $quizResult = $this->makeQuizResult();

        Submission::create([
            'quiz_result_id' => $quizResult->id,
            'style' => 'Japandi',
            'name' => 'Test',
            'email' => 'test@example.com',
            'email_status' => 'failed',
            'email_error' => 'Eerdere test-mislukking.',
        ]);

        $response = $this->postLead($quizResult->uuid);

        $response->assertJsonFragment(['status' => 'queued']);
        Bus::assertDispatched(GenerateAndSendQuizResultPdfJob::class);
        $this->assertSame(1, Submission::where('quiz_result_id', $quizResult->id)->count());
        $this->assertNull(Submission::first()->email_error);
    }

    #[Test]
    public function het_moodboard_toont_alleen_daadwerkelijk_gekozen_producten(): void
    {
        Bus::fake();
        $quizResult = $this->makeQuizResult();

        $this->postLead($quizResult->uuid);

        $moodboard = Submission::first()->quiz_result['moodboard'];
        $this->assertCount(1, $moodboard);
        $this->assertSame('Eiken vloer', $moodboard[0]['title']);
    }

    #[Test]
    public function resultatenpagina_en_pdf_gebruiken_exact_dezelfde_basisstijl(): void
    {
        Bus::fake();
        $quizResult = $this->makeQuizResult();

        $resultResponse = $this->postJson('/api/quiz-result', ['answers' => ['vloer' => ['eiken']]]);
        $resultResponse->assertOk();
        $screenLabel = $resultResponse->json('primaryStyle.label');

        $this->postLead($resultResponse->json('resultUuid'));
        $pdfLabel = Submission::first()->quiz_result['primaryStyle']['label'];

        $this->assertSame($screenLabel, $pdfLabel);
    }
}
