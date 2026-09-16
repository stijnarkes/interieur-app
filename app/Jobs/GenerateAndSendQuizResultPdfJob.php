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
 * Genereert de PDF en verstuurt de bevestigingsmail op de achtergrond — dit gebeurde voorheen
 * synchroon binnen QuizLeadController::handle(), maar dat kon bij een trage aanvraag (bv. meerdere
 * moodboard-/materialenfoto's ophalen en verwerken) de verbinding tussen browser en server laten
 * verbreken vóórdat het antwoord terugkwam. De bezoeker zag dan "verzenden mislukt" terwijl de mail
 * server-side gewoon (iets later) alsnog verstuurd werd — dit werkt die aanname weg: de aanvraag
 * wordt meteen bevestigd (zie QuizLeadController::responseFor(), status 'queued'), en deze taak
 * mag zo lang duren als nodig.
 *
 * Bewuste automatische retries ($tries): een deel van de eerder gemelde mislukkingen was
 * waarschijnlijk voorbijgaande PDF-/mailinfrastructuur-hikjes, geen structurele fouten — Laravel
 * probeert het bij een mislukte poging zelf nog een paar keer opnieuw vóórdat failed() de
 * inzending definitief als mislukt registreert. Dat is precies het patroon dat eerder handmatig
 * zichtbaar was voor de bezoeker ("de eerste 1-2 pogingen mislukken, dan lukt het") — nu lost de
 * wachtrij dat zelf op, zonder dat de bezoeker het ooit merkt.
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
