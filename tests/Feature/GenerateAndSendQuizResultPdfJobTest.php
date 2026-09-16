<?php

namespace Tests\Feature;

use App\Jobs\GenerateAndSendQuizResultPdfJob;
use App\Mail\QuizResultMail;
use App\Models\Submission;
use App\Services\QuizResultPdfService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Dekt de daadwerkelijke PDF-generatie/mailverzending — sinds QuizLeadController alleen nog een
 * taak inplant (zie QuizLeadControllerTest), gebeurt dat werk hier. Verzendt nooit een echte mail
 * — altijd Mail::fake().
 */
class GenerateAndSendQuizResultPdfJobTest extends TestCase
{
    use RefreshDatabase;

    private function makeQueuedSubmission(): Submission
    {
        return Submission::create([
            'style' => 'Japandi',
            'name' => 'Test',
            'email' => 'test@example.com',
            'quiz_result' => ['resultName' => 'Jouw woonstijl: Japandi', 'primaryStyle' => ['label' => 'Japandi']],
            'email_status' => 'queued',
        ]);
    }

    #[Test]
    public function de_taak_genereert_de_pdf_en_verstuurt_de_mail(): void
    {
        Mail::fake();
        $submission = $this->makeQueuedSubmission();

        (new GenerateAndSendQuizResultPdfJob($submission->id))->handle(app(QuizResultPdfService::class));

        Mail::assertSent(QuizResultMail::class, 1);
        $submission->refresh();
        $this->assertSame('sent', $submission->email_status);
        $this->assertNotNull($submission->pdf_path);
        $this->assertNotNull($submission->email_sent_at);
    }

    #[Test]
    public function een_mislukte_pdf_generatie_registreert_de_fout_pas_na_de_laatste_poging(): void
    {
        Mail::fake();
        $this->mock(QuizResultPdfService::class, function ($mock) {
            $mock->shouldReceive('generate')->andThrow(new \RuntimeException('PDF-generatie mislukt in de test.'));
        });
        $submission = $this->makeQueuedSubmission();

        $job = new GenerateAndSendQuizResultPdfJob($submission->id);

        try {
            $job->handle(app(QuizResultPdfService::class));
            $this->fail('handle() had een uitzondering moeten gooien.');
        } catch (\RuntimeException $e) {
            // Verwacht: Laravel's wachtrij-worker vangt dit normaal op en probeert het (afhankelijk
            // van $tries) nog een paar keer opnieuw vóórdat failed() wordt aangeroepen.
        }

        // Nog niet als mislukt geregistreerd na één enkele mislukte poging — dat gebeurt pas als
        // Laravel alle $tries pogingen heeft uitgeput en failed() aanroept (hieronder gesimuleerd).
        $this->assertSame('queued', $submission->fresh()->email_status);

        $job->failed(new \RuntimeException('PDF-generatie mislukt in de test.'));

        $submission->refresh();
        $this->assertSame('failed', $submission->email_status);
        $this->assertSame('PDF-generatie mislukt in de test.', $submission->email_error);
        Mail::assertNotSent(QuizResultMail::class);
    }
}
