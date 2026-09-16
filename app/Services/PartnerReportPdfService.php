<?php

namespace App\Services;

use App\Models\PartnerComparison;
use App\Models\PartnerLink;
use App\Models\SiteContent;
use App\Models\StyleProfile;
use App\Support\PartnerFactPresenter;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Storage;

/**
 * Bouwt de gezamenlijke PDF (zie resources/views/pdf/partner-result.blade.php) — zelfde vorm als
 * QuizResultPdfService::generate(), zelfde opslagconventie (geconfigureerde PDF-disk, storage-key
 * teruggeven i.p.v. een absoluut pad).
 */
class PartnerReportPdfService
{
    public function generate(PartnerLink $link, PartnerComparison $comparison): string
    {
        $styleLabel = fn (?string $key) => $key ? (StyleProfile::forStyle($key)?->label ?? $key) : $key;
        $siteContent = SiteContent::current();

        $resolvedFacts = PartnerFactPresenter::resolveStyleKeys($comparison->facts ?? [], $styleLabel);

        $pdf = Pdf::loadView('pdf.partner-result', [
            'initiatorName' => $link->initiator_name ?: 'Deelnemer 1',
            'partnerName' => $link->partner_name ?: 'Deelnemer 2',
            'initiatorStyleLabel' => $styleLabel($link->initiator_snapshot['primary_style'] ?? null),
            'partnerStyleLabel' => $styleLabel($link->partner_snapshot['primary_style'] ?? null),
            'initiatorPalette' => $link->initiator_snapshot['chosen_base_palette']['colors'] ?? [],
            'partnerPalette' => $link->partner_snapshot['chosen_base_palette']['colors'] ?? [],
            'initiatorAccentColors' => $link->initiator_snapshot['chosen_accent_colors'] ?? [],
            'partnerAccentColors' => $link->partner_snapshot['chosen_accent_colors'] ?? [],
            'similarities' => PartnerFactPresenter::describeAll($resolvedFacts['similarities']),
            'differences' => PartnerFactPresenter::describeAll($resolvedFacts['differences']),
            'suggestions' => $comparison->suggestions ?? [],
            'ctaLabel' => $siteContent->email_cta_label,
            'ctaUrl' => $siteContent->email_cta_url,
        ]);

        $pdf->setPaper('A4', 'portrait');

        $path = "partner-links/{$link->id}/gezamenlijke-woonstijl.pdf";
        Storage::disk(config('filesystems.quiz_pdfs_disk'))->put($path, $pdf->output());

        return $path;
    }
}
