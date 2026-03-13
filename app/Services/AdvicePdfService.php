<?php

namespace App\Services;

use App\Models\Submission;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Storage;

class AdvicePdfService
{
    public function generate(Submission $submission, string $moodboardDataUrl, string $inspirationDataUrl): string
    {
        $pdf = Pdf::loadView('pdf.advice', [
            'submission'       => $submission,
            'moodboardBase64'  => $moodboardDataUrl,
            'inspirationBase64' => $inspirationDataUrl,
        ]);

        $pdf->setPaper('A4', 'portrait');

        $path = "submissions/{$submission->id}/advice.pdf";
        Storage::disk('public')->put($path, $pdf->output());

        return Storage::disk('public')->path($path);
    }
}
