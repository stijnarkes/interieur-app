<?php

namespace App\Jobs;

use App\Mail\QuizResultMail;
use App\Models\PartnerParticipant;
use App\Models\QuizResult;
use App\Models\QuizSetting;
use App\Models\Submission;
use App\Services\PartnerLinkService;
use App\Services\QuizResultPdfService;
use App\Support\PartnerAccessGuard;
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

    /**
     * @param  ?string  $partnerClaimToken  alleen gezet tijdens de geïsoleerde partnertest (zie
     *   QuizLeadController) — voorkomt dat resolvePartnerInvite() hieronder voor de partner zelf
     *   nog een (zinloze, want al deelnemer) nieuwe uitnodiging aanmaakt.
     */
    public function __construct(public int $submissionId, public ?string $partnerClaimToken = null) {}

    public function handle(QuizResultPdfService $pdfService, PartnerLinkService $partnerLinkService): void
    {
        $submission = Submission::findOrFail($this->submissionId);

        $pdfPath = $pdfService->generate($submission);
        $submission->update(['pdf_path' => $pdfPath]);

        $partnerInvite = $this->resolvePartnerInvite($submission, $partnerLinkService);

        Mail::to($submission->email)->send(new QuizResultMail($submission, $pdfPath, $partnerInvite));

        $submission->update(['email_status' => 'sent', 'email_sent_at' => now(), 'email_error' => null]);
    }

    /**
     * Maakt (of hergebruikt idempotent) een partneruitnodiging voor dít resultaat, zodat de
     * bevestigingsmail hierboven de uitnodigingslink meteen kan tonen — geen aparte knop op de
     * resultatenpagina meer nodig, zie het implementatieplan-vervolg "Partnerfunctie in de mail".
     * Geeft bewust `null` (en faalt dus nooit de hoofd-e-mail) zolang: de partnerfunctie uitstaat,
     * dit resultaat geen (bekende) QuizResult heeft, of dit de partnertest zelf is.
     */
    private function resolvePartnerInvite(Submission $submission, PartnerLinkService $partnerLinkService): ?array
    {
        try {
            if (! QuizSetting::current()->partner_feature_enabled) {
                return null;
            }

            if ($this->partnerClaimToken) {
                $participant = PartnerAccessGuard::resolve($this->partnerClaimToken);
                if ($participant?->role === PartnerParticipant::ROLE_PARTNER) {
                    // Dit IS de partnertest zelf — nooit een eigen uitnodiging aanmaken (max. 2
                    // deelnemers, zie het implementatieplan).
                    return null;
                }
            }

            $quizResult = $submission->quiz_result_id ? QuizResult::find($submission->quiz_result_id) : null;
            if (! $quizResult) {
                return null;
            }

            return $partnerLinkService->createOrGetInvite($quizResult, $submission->name, $submission->email);
        } catch (\Throwable) {
            // Nooit de eigen, individuele PDF-mail laten mislukken op een kapotte partnerkoppeling.
            return null;
        }
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
