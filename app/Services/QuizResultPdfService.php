<?php

namespace App\Services;

use App\Models\SiteContent;
use App\Models\Submission;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Storage;

class QuizResultPdfService
{
    /**
     * Slaat het PDF-bestand op via de geconfigureerde PDF-disk (zie
     * config('filesystems.quiz_pdfs_disk')) i.p.v. hardcoded op de lokale "public"-disk (al blijft
     * dat wel de standaard) — zo kan dit later ook naar S3 verhuizen zonder codewijziging, zonder
     * dat bestaande inzendingen hun PDF kwijtraken (zie de config-uitleg daar). Geeft de
     * storage-key terug, geen absoluut pad — zie QuizLeadController/QuizResultMail voor hoe die
     * key verder gebruikt wordt.
     */
    public function generate(Submission $submission): string
    {
        $siteContent = SiteContent::current();

        $pdf = Pdf::loadView('pdf.quiz-result', [
            'submission' => $submission,
            'result' => $submission->quiz_result,
            // Zelfde admin-bewerkbare interieuradvies-CTA als de mail/het bevestigingsscherm (zie
            // PartnerReportPdfService voor hetzelfde patroon in de gezamenlijke PDF).
            'ctaLabel' => $siteContent->email_cta_label,
            'ctaUrl' => $siteContent->email_cta_url,
        ]);

        $pdf->setPaper('A4', 'portrait');

        $path = "submissions/{$submission->id}/quiz-result.pdf";
        Storage::disk(config('filesystems.quiz_pdfs_disk'))->put($path, $pdf->output());

        return $path;
    }
}
