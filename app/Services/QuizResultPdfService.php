<?php

namespace App\Services;

use App\Models\Submission;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Storage;

class QuizResultPdfService
{
    public function generate(Submission $submission): string
    {
        $pdf = Pdf::loadView('pdf.quiz-result', [
            'submission' => $submission,
            'result' => $submission->quiz_result,
        ]);

        $pdf->setPaper('A4', 'portrait');

        $path = "submissions/{$submission->id}/quiz-result.pdf";
        Storage::disk('public')->put($path, $pdf->output());

        return Storage::disk('public')->path($path);
    }
}
