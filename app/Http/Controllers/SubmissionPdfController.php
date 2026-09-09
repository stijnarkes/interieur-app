<?php

namespace App\Http\Controllers;

use App\Models\Submission;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class SubmissionPdfController extends Controller
{
    public function show(Submission $submission): Response|StreamedResponse
    {
        abort_unless($submission->pdf_path && Storage::disk(config('filesystems.quiz_pdfs_disk'))->exists($submission->pdf_path), 404);

        return Storage::disk(config('filesystems.quiz_pdfs_disk'))->response($submission->pdf_path, 'quiz-resultaat.pdf', [
            'Content-Disposition' => 'inline; filename="quiz-resultaat.pdf"',
        ]);
    }

    public function download(Submission $submission): Response|StreamedResponse
    {
        abort_unless($submission->pdf_path && Storage::disk(config('filesystems.quiz_pdfs_disk'))->exists($submission->pdf_path), 404);

        $filename = 'quiz-resultaat-'.($submission->name ? \Illuminate\Support\Str::slug($submission->name) : $submission->id).'.pdf';

        return Storage::disk(config('filesystems.quiz_pdfs_disk'))->download($submission->pdf_path, $filename);
    }
}
