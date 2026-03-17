<?php

namespace App\Jobs;

use App\Mail\AdviceMail;
use App\Models\Generation;
use App\Models\Submission;
use App\Services\AdvicePdfService;
use App\Services\InteriorGenerationService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;

class GenerateInteriorAdviceJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 300;
    public int $tries   = 1;

    public function __construct(private readonly int $generationId) {}

    public function handle(InteriorGenerationService $service): void
    {
        $generation = Generation::findOrFail($this->generationId);
        $generation->update(['status' => 'processing']);

        try {
            $submissionId = $generation->submission_id;
            $result       = $service->generate($generation->input, $submissionId);

            $generation->update([
                'status' => 'completed',
                'result' => $result,
            ]);

            // Submission bijwerken voor admin
            if ($submissionId) {
                $submission = Submission::find($submissionId);
                if ($submission) {
                    $submission->update([
                        'result_id'              => $result['resultId'],
                        'result_generated'       => true,
                        'advice_bullets'         => $result['adviceBullets'] ?? null,
                        'palette'                => $result['palette'] ?? null,
                        'materials'              => $result['materials'] ?? null,
                        'layout_tips'            => $result['layoutTips'] ?? null,
                        'product_ideas'          => $result['productIdeas'] ?? null,
                        'moodboard_generated'    => isset($result['moodboardImageDataUrl']),
                        'room_preview_generated' => isset($result['roomPreviewImageDataUrl']),
                        'moodboard_path'         => $result['moodboardPath'] ?? null,
                        'inspiration_path'       => $result['inspirationPath'] ?? null,
                    ]);

                    // PDF + e-mail versturen
                    if ($submission->email) {
                        $this->sendEmail($submission, $result);
                    }
                }
            }

        } catch (\Throwable $e) {
            $generation->update([
                'status' => 'failed',
                'error'  => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    private function sendEmail(Submission $submission, array $result): void
    {
        try {
            $pdfService = new AdvicePdfService();
            $pdfPath    = $pdfService->generate(
                $submission,
                $result['moodboardImageDataUrl'] ?? '',
                $result['roomPreviewImageDataUrl'] ?? ''
            );

            $submission->update(['pdf_path' => "submissions/{$submission->id}/advice.pdf"]);

            Mail::to($submission->email)->send(new AdviceMail($submission, $pdfPath));

            $submission->update(['email_status' => 'sent', 'email_sent_at' => now()]);
        } catch (\Throwable $e) {
            $submission->update(['email_status' => 'failed', 'email_error' => $e->getMessage()]);
        }
    }
}
