<?php

namespace App\Console\Commands;

use App\Models\QuizMaterial;
use App\Models\QuizOption;
use Illuminate\Console\Command;

/**
 * Verwijdert alle bestaande antwoordopties en materialen-per-stijl-rijen — bedoeld om eenmalig
 * te draaien bij het omzetten naar een nieuwe set woonstijlen (zie QuizStructure::STYLES), zodat
 * er geen rijen blijven hangen die nog naar een inmiddels verwijderde stijl verwijzen. De vragen
 * zelf (quiz_questions) blijven bestaan — die staan los van welke woonstijlen er zijn, en
 * krijgen straks gewoon nieuwe antwoordopties via de "Antwoordopties"-pagina in de admin.
 *
 * Verwijdert bewust GEEN bestanden op de opslag: voor de oorspronkelijke, geseede opties leidt
 * QuizOption::deleteImage() het bestandspad af via de vaste map/stijl-slug-conventie
 * (imageFolder()/imageFilename()) — met de nieuwe stijlset zou dat voor een stijl-key die in
 * beide sets voorkomt (bv. "japandi") per ongeluk het echte, herbruikbare bestand van die stijl
 * wegvegen, terwijl dat bestand na deze reset nog steeds geldige content is voor diezelfde
 * (nieuwe) stijl. Eventuele nu ongebruikte bestanden van écht verdwenen stijlen blijven gewoon
 * op de disk staan — onschuldig, en veiliger dan per ongeluk iets bruikbaars wissen.
 */
class ResetQuizStyles extends Command
{
    protected $signature = 'quiz:reset-styles {--dry-run : Alleen tonen wat er zou gebeuren, niets verwijderen}';

    protected $description = 'Verwijdert alle antwoordoptie- en materiaal-rijen — voor het overschakelen naar een nieuwe set woonstijlen (laat bestanden op de opslag ongemoeid)';

    public function handle(): int
    {
        $dryRun = (bool) $this->option('dry-run');

        $optionCount = QuizOption::count();
        $materialCount = QuizMaterial::count();

        $this->info(sprintf(
            '%s%d antwoordopties en %d materialen worden verwijderd (bestanden op de opslag blijven staan).',
            $dryRun ? '[dry-run] ' : '',
            $optionCount,
            $materialCount,
        ));

        if (! $dryRun) {
            QuizOption::query()->delete();
            QuizMaterial::query()->delete();
        }

        $this->info($dryRun ? 'Niets verwijderd (dry-run).' : 'Klaar. De vragen zelf zijn blijven staan, hun antwoordopties zijn nu leeg.');

        return self::SUCCESS;
    }
}
