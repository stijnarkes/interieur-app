<?php

namespace App\Console\Commands;

use App\Models\QuizMaterial;
use App\Models\QuizOption;
use App\Support\QuizImageManifest;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

/**
 * Kopieert alle bekende quiz-afbeeldingen (antwoordopties, materialen, sfeer-/start-/
 * overgangsschermfoto's) van de ene disk naar de andere — bedoeld om te draaien vóórdat
 * QUIZ_IMAGES_DISK op "s3" gezet wordt, zodat alles wat al lokaal staat mee overgaat i.p.v.
 * verloren te gaan (zie de config-uitleg bij `quiz_images_disk`). Werkt met expliciete
 * --from/--to-disks, dus onafhankelijk van de op dat moment actieve QUIZ_IMAGES_DISK-waarde —
 * de volgorde "Object Storage koppelen → dit commando draaien → pas dan QUIZ_IMAGES_DISK
 * omzetten" is daardoor veilig, in willekeurige combinatie met wanneer de nieuwe code live gaat.
 */
class MigrateQuizImages extends Command
{
    protected $signature = 'quiz:migrate-images
        {--from=quiz_images : Naam van de brondisk (zie config/filesystems.php)}
        {--to=s3 : Naam van de doeldisk}
        {--dry-run : Alleen tonen wat er zou gebeuren, niets kopiëren}';

    protected $description = "Kopieert bestaande antwoordoptie-/materiaal-/sfeer-/schermfoto's van de ene disk naar de andere (bv. lokaal naar S3)";

    public function handle(): int
    {
        $from = Storage::disk($this->option('from'));
        $to = Storage::disk($this->option('to'));
        $dryRun = (bool) $this->option('dry-run');

        $keys = $this->collectKnownKeys();
        $this->info(sprintf('%d bekende afbeeldingsplekken gevonden.', count($keys)));

        $copied = 0;
        $alreadyPresent = 0;
        $missingOnSource = 0;

        $bar = $this->output->createProgressBar(count($keys));
        $bar->start();

        foreach ($keys as $key) {
            $bar->advance();

            if (! $from->exists($key)) {
                $missingOnSource++;

                continue;
            }

            if ($to->exists($key)) {
                $alreadyPresent++;

                continue;
            }

            if (! $dryRun) {
                $to->put($key, $from->get($key), 'public');
            }

            $copied++;
        }

        $bar->finish();
        $this->newLine(2);

        $this->info(sprintf(
            '%s%d gekopieerd, %d al aanwezig op doel, %d niet gevonden op bron (waarschijnlijk nog geen foto geüpload — geen probleem).',
            $dryRun ? '[dry-run] ' : '',
            $copied,
            $alreadyPresent,
            $missingOnSource,
        ));

        return self::SUCCESS;
    }

    /** @return array<int, string> alle bekende storage-keys, ongeacht of het bestand al bestaat. */
    protected function collectKnownKeys(): array
    {
        $keys = [];

        foreach (QuizOption::all() as $option) {
            if ($path = $option->resolvedImagePath()) {
                $keys[] = ltrim($path, '/');
            }
        }

        foreach (QuizMaterial::all() as $material) {
            $keys[] = $material->relativePath();
        }

        $slotSections = [...QuizImageManifest::pageSections(), ...QuizImageManifest::atmosphereSections()];
        foreach ($slotSections as $section) {
            foreach ($section['slots'] as $slot) {
                $keys[] = "images/interior/{$section['folder']}/{$slot['filename']}";
            }
        }

        return array_values(array_unique($keys));
    }
}
