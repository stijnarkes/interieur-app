<?php

namespace App\Services;

use App\Mail\PartnerReportMail;
use App\Models\PartnerComparison;
use App\Models\PartnerEvent;
use App\Models\PartnerLink;
use App\Models\PartnerParticipant;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;

/**
 * Genereert (indien nodig) de gezamenlijke PDF en verstuurt 'm naar het e-mailadres van één
 * deelnemer — gedeeld tussen PartnerComparisonController::mail() (de deelnemer vraagt het zelf
 * aan op de resultaatpagina) en QuizResultController::linkPartnerParticipant() (de initiator kreeg
 * al bij het aanmaken van de uitnodiging de kans om een adres op te geven, zie
 * PartnerLinkController::create() — zodra de vergelijking klaar is, versturen we dan automatisch,
 * zonder dat diegene terug hoeft te komen om het zelf aan te vragen). Zelfde synchrone patroon en
 * idempotentiebescherming (via partner_participants.mail_status/mail_requested_at) als
 * QuizLeadController::isInFlight() — nooit een tweede mail bij een dubbele aanroep.
 */
class PartnerReportMailer
{
    private const STALE_QUEUE_AFTER_MINUTES = 2;

    public function send(PartnerParticipant $participant, PartnerLink $link, PartnerComparison $comparison, string $email): void
    {
        if ($this->isInFlight($participant)) {
            return;
        }

        $participant->update([
            'email' => $email,
            'mail_status' => 'queued',
            'mail_requested_at' => now(),
            'mail_error' => null,
        ]);

        try {
            $pdfPath = $comparison->pdf_path;
            if (! $pdfPath || ! Storage::disk(config('filesystems.quiz_pdfs_disk'))->exists($pdfPath)) {
                $pdfPath = app(PartnerReportPdfService::class)->generate($link, $comparison);
                $comparison->update(['pdf_path' => $pdfPath]);
            }

            $recipientName = $participant->role === PartnerParticipant::ROLE_INITIATOR
                ? $link->initiator_name
                : $link->partner_name;

            Mail::to($email)->send(new PartnerReportMail($link, $pdfPath, $recipientName));

            $participant->update(['mail_status' => 'sent']);
            PartnerEvent::record('report_requested', $link->id);
        } catch (\Throwable $e) {
            $participant->update(['mail_status' => 'failed', 'mail_error' => $e->getMessage()]);
        }
    }

    /** @see class-docblock voor de "vastgelopen"-uitzondering. */
    public function isInFlight(PartnerParticipant $participant): bool
    {
        if ($participant->mail_status === 'sent') {
            return true;
        }

        if ($participant->mail_status === 'queued') {
            return $participant->mail_requested_at?->gt(now()->subMinutes(self::STALE_QUEUE_AFTER_MINUTES)) ?? false;
        }

        return false;
    }
}
