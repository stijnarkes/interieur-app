<?php

namespace App\Jobs;

use App\Mail\QuizResultMail;
use App\Models\Submission;
use App\Services\QuizResultPdfService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;

/**
 * Genereert de PDF en verstuurt de bevestigingsmail. Bewust als job-class opgezet (i.p.v. gewoon
 * een methode op de controller) zodat dit later zonder codewijziging alsnog op een echte
 * wachtrij-worker kan draaien (`implements ShouldQueue` staat er al) — voorlopig roept
 * QuizLeadController handle()/failed() rechtstreeks aan binnen de aanvraag zelf, omdat er geen
 * queue-worker actief is. De eigenlijke snelheidswinst die dit synchroon houdbaar maakt zit in
 * PdfImageResolver (server-side caching van bewerkte foto's), niet in deze klasse.
 *
 * $tries/$backoff zijn alleen relevant zodra dit ooit wél via ::dispatch() op een wachtrij loopt —
 * bij een rechtstreekse handle()-aanroep (huidige situatie) heeft de aanroeper zelf een try/catch
 * en roept bij een fout zelf failed() aan, zie QuizLeadController::handle().
 */
class GenerateAndSendQuizResultPdfJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $backoff = 15;

    public function __construct(public int $submissionId) {}

    public function handle(QuizResultPdfService $pdfService): void
    {
        $submission = Submission::findOrFail($this->submissionId);

        $pdfPath = $pdfService->generate($submission);
        $submission->update(['pdf_path' => $pdfPath]);

        Mail::to($submission->email)->send(new QuizResultMail($submission, $pdfPath));

        $submission->update(['email_status' => 'sent', 'email_sent_at' => now(), 'email_error' => null]);
    }

    /** Wordt door Laravel pas aangeroepen nadat alle $tries pogingen zijn mislukt. */
    public function failed(\Throwable $exception): void
    {
        Submission::find($this->submissionId)?->update([
            'email_status' => 'failed',
            'email_error' => $exception->getMessage(),
        ]);
    }
}
