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
 *
 * --limit begrenst het aantal PDF's per run: op het hostingplatform bleek een enkele run met
 * tientallen PDF's achteraf hard afgebroken te worden (geen foutmelding, gewoon een abrupt einde
 * halverwege) — vermoedelijk een tijd- of geheugenlimiet op dit soort opdrachten. Het commando is
 * door de filter hierboven vanzelf idempotent (een al herstelde inzending wordt de volgende run
 * overgeslagen), dus gewoon meerdere keren opnieuw draaien werkt de resterende lijst gewoon af.
 */
class RegenerateMissingSubmissionPdfs extends Command
{
    protected $signature = 'quiz:regenerate-missing-pdfs
        {--dry-run : Alleen tonen welke inzendingen het zou raken, niets genereren}
        {--limit=15 : Maximaal aantal PDF-bestanden in deze run (draai het commando gewoon opnieuw voor de rest)}
        {--skip= : Kommagescheiden inzending-ID'."'".'s die deze run overgeslagen moeten worden (bv. een inzending die blijft vastlopen)}';

    protected $description = 'Genereert de PDF opnieuw voor inzendingen waarvan het PDF-bestand niet meer op de geconfigureerde disk staat';

    public function handle(QuizResultPdfService $pdfService): int
    {
        $disk = Storage::disk(config('filesystems.quiz_pdfs_disk'));
        $dryRun = (bool) $this->option('dry-run');
        $limit = (int) $this->option('limit');
        $skipIds = array_filter(array_map('trim', explode(',', (string) $this->option('skip'))));

        $missing = Submission::query()
            ->whereNotNull('quiz_result')
            ->orderBy('id')
            ->get()
            ->reject(fn (Submission $submission): bool => in_array((string) $submission->id, $skipIds, true))
            ->filter(fn (Submission $submission): bool => ! $submission->pdf_path || ! $disk->exists($submission->pdf_path));

        if ($missing->isEmpty()) {
            $this->info('Geen inzendingen gevonden met een ontbrekende PDF.');

            return self::SUCCESS;
        }

        $submissions = $missing->take($limit);

        $this->info(sprintf(
            '%d inzending(en) met een ontbrekende PDF gevonden, deze run behandelt er %d.',
            $missing->count(),
            $submissions->count(),
        ));

        $regenerated = 0;
        $failed = 0;

        foreach ($submissions as $submission) {
            if ($dryRun) {
                $this->line("[dry-run] Zou PDF regenereren voor inzending #{$submission->id} ({$submission->email}).");

                continue;
            }

            // Vóór de poging gelogd (niet pas na succes) — zodat als een specifieke inzending
            // vastloopt (oneindig blijft hangen i.p.v. een nette exception te geven, bv. door een
            // kapotte/trage afbeeldingsverwijzing), we uit de laatst zichtbare regel precies kunnen
            // aflezen welke dat is, om 'm daarna gericht over te slaan met --skip.
            $this->line("Bezig met inzending #{$submission->id} ({$submission->email})...");

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
            $this->info("{$regenerated} PDF-bestand(en) opnieuw gegenereerd, {$failed} mislukt.");

            $remaining = $missing->count() - $submissions->count();
            if ($remaining > 0) {
                $this->info("Nog {$remaining} te gaan — draai het commando nogmaals om verder te gaan.");
            }
        }

        return self::SUCCESS;
    }
}
