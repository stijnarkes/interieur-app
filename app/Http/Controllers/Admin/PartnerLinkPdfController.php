<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PartnerLink;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Admin-weergave van de gezamenlijke PDF — zelfde patroon als SubmissionPdfController, maar dan
 * voor een PartnerLink. Nooit via een klanttoken (zie PartnerComparisonController::report()): de
 * admin is al ingelogd (route staat achter 'auth', zie routes/web.php), dus dit werkt ook als het
 * bestand nog nooit door een deelnemer is opgevraagd (dan wordt het hier alsnog gegenereerd).
 */
class PartnerLinkPdfController extends Controller
{
    public function show(PartnerLink $partnerLink): Response|StreamedResponse
    {
        $comparison = $this->readyComparisonFor($partnerLink);

        return Storage::disk(config('filesystems.quiz_pdfs_disk'))->response($comparison->pdf_path, 'gezamenlijke-woonstijl.pdf', [
            'Content-Disposition' => 'inline; filename="gezamenlijke-woonstijl.pdf"',
        ]);
    }

    public function download(PartnerLink $partnerLink): Response|StreamedResponse
    {
        $comparison = $this->readyComparisonFor($partnerLink);

        return Storage::disk(config('filesystems.quiz_pdfs_disk'))->download($comparison->pdf_path, 'gezamenlijke-woonstijl.pdf');
    }

    private function readyComparisonFor(PartnerLink $partnerLink): \App\Models\PartnerComparison
    {
        $comparison = $partnerLink->comparison;
        abort_unless($comparison && $comparison->status === \App\Models\PartnerComparison::STATUS_READY, 404);

        if (! $comparison->pdf_path || ! Storage::disk(config('filesystems.quiz_pdfs_disk'))->exists($comparison->pdf_path)) {
            $pdfPath = app(\App\Services\PartnerReportPdfService::class)->generate($partnerLink, $comparison);
            $comparison->update(['pdf_path' => $pdfPath]);
        }

        return $comparison;
    }
}
