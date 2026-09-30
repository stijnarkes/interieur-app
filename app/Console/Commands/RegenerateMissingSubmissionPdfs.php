<?php

namespace App\Console\Commands;

use App\Models\Submission;
use App\Services\QuizResultPdfService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use Throwable;

/**
 * Herstelt inzendingen waarvan het PDF-bestand niet meer op de geconfigureerde disk staat (zie
 * QUIZ_PDFS_DISK) — bijvoorbeeld omdat die PDF nog op de lokale, niet-persistente "public"-disk
 * stond vóórdat QUIZ_PDFS_DISK op "s3" werd gezet, en een deploy die lokale schijf heeft gewist.
 * Genereert de PDF gewoon opnieuw uit de al opgeslagen quiz_result-data (QuizResultPdfService
 * leunt uitsluitend op dat veld, zie daar) — geen dataverlies, en verstuurt bewust geen nieuwe
 * bevestigingsmail, dit repareert alleen het bestand.
 */
class RegenerateMissingSubmissionPdfs extends Command
{
    protected $signature = 'quiz:regenerate-missing-pdfs {--dry-run : Alleen tonen welke inzendingen het zou raken, niets genereren}';

    protected $description = 'Genereert de PDF opnieuw voor inzendingen waarvan het PDF-bestand niet meer op de geconfigureerde disk staat';

    public function handle(QuizResultPdfService $pdfService): int
    {
        $disk = Storage::disk(config('filesystems.quiz_pdfs_disk'));
        $dryRun = (bool) $this->option('dry-run');

        $submissions = Submission::query()
            ->whereNotNull('quiz_result')
            ->get()
            ->filter(fn (Submission $submission): bool => ! $submission->pdf_path || ! $disk->exists($submission->pdf_path));

        if ($submissions->isEmpty()) {
            $this->info('Geen inzendingen gevonden met een ontbrekende PDF.');

            return self::SUCCESS;
        }

        $this->info(sprintf('%d inzending(en) met een ontbrekende PDF gevonden.', $submissions->count()));

        $regenerated = 0;
        $failed = 0;

        foreach ($submissions as $submission) {
            if ($dryRun) {
                $this->line("[dry-run] Zou PDF regenereren voor inzending #{$submission->id} ({$submission->email}).");

                continue;
            }

            try {
                $path = $pdfService->generate($submission);
                $submission->update(['pdf_path' => $path]);
                $this->line("Inzending #{$submission->id} ({$submission->email}): PDF opnieuw gegenereerd.");
                $regenerated++;
            } catch (Throwable $e) {
                $this->error("Inzending #{$submission->id} ({$submission->email}): mislukt — {$e->getMessage()}");
                $failed++;
            }
        }

        if (! $dryRun) {
            $this->newLine();
            $this->info("{$regenerated} PDF('s) opnieuw gegenereerd, {$failed} mislukt.");
        }

        return self::SUCCESS;
    }
}
