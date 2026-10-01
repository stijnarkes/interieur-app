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

        $this->registerClarendonFont($pdf);

        $pdf->setPaper('A4', 'portrait');

        $path = "submissions/{$submission->id}/quiz-result.pdf";
        Storage::disk(config('filesystems.quiz_pdfs_disk'))->put($path, $pdf->output());

        return $path;
    }

    /**
     * Registreert het huisstijllettertype rechtstreeks bij dompdf i.p.v. via een CSS @font-face
     * met een url() naar een lokaal pad — dat laatste bleek dompdf's eigen URL-parsing te raken
     * (een "C:\..."-achtig pad wordt daar niet altijd betrouwbaar herkend als lokaal bestand).
     * registerFont() werkt rechtstreeks met het bestandspad en omzeilt die laag volledig. Moet vóór
     * $pdf->output() gebeuren, maar de volgorde t.o.v. loadView() maakt verder niet uit — dit vult
     * alleen dompdf's eigen fontlettertabel, dat gebeurt los van het al geladen HTML/CSS.
     */
    private function registerClarendonFont(\Barryvdh\DomPDF\PDF $pdf): void
    {
        $fontMetrics = $pdf->getDomPDF()->getFontMetrics();

        $fontMetrics->registerFont(
            ['family' => 'Clarendon LT Std', 'weight' => 300, 'style' => 'normal'],
            resource_path('fonts/clarendon/ClarendonLTStd-Light.otf'),
        );
        $fontMetrics->registerFont(
            ['family' => 'Clarendon LT Std', 'weight' => 'normal', 'style' => 'normal'],
            resource_path('fonts/clarendon/ClarendonLTStd.otf'),
        );
        $fontMetrics->registerFont(
            ['family' => 'Clarendon LT Std', 'weight' => 'bold', 'style' => 'normal'],
            resource_path('fonts/clarendon/ClarendonLTStd-Bold.otf'),
        );
    }
}
