<?php

namespace App\Console\Commands;

use App\Models\QuizMaterial;
use App\Models\QuizOption;
use App\Support\QuizStructure;
use Illuminate\Console\Command;

/**
 * Verwijdert alleen de antwoordopties en materialen die uitsluitend naar inmiddels verwijderde
 * woonstijlen verwijzen (zie QuizStructure::STYLES) — niet alles. Een optie blijft dus gewoon
 * staan zodra er minstens één geldige, huidige stijl aan gekoppeld is; alleen opties die
 * helemaal geen enkele geldige stijl (meer) hebben, worden verwijderd. Zo kun je dit veilig
 * draaien terwijl je al bezig bent met het opnieuw opbouwen van antwoordopties voor de nieuwe
 * stijlen — dat werk raakt hiermee nooit kwijt.
 *
 * Verwijdert bewust GEEN bestanden op de opslag: voor de oorspronkelijke, geseede opties leidt
 * QuizOption::deleteImage() het bestandspad af via de vaste map/stijl-slug-conventie
 * (imageFolder()/imageFilename()) — voor een stijl-key die in beide sets voorkomt (bv.
 * "japandi") zou dat per ongeluk het echte, herbruikbare bestand van die stijl wegvegen.
 * Eventuele nu ongebruikte bestanden van écht verdwenen stijlen blijven gewoon op de disk staan
 * — onschuldig, en veiliger dan per ongeluk iets bruikbaars wissen.
 */
class ResetQuizStyles extends Command
{
    protected $signature = 'quiz:reset-styles {--dry-run : Alleen tonen wat er zou gebeuren, niets verwijderen}';

    protected $description = 'Verwijdert antwoordopties/materialen die alleen nog naar verwijderde woonstijlen verwijzen (laat geldige content en bestanden op de opslag ongemoeid)';

    public function handle(): int
    {
        $dryRun = (bool) $this->option('dry-run');
        $validStyleKeys = array_keys(QuizStructure::styleOptions());

        $orphanedOptions = QuizOption::with('styleLinks')
            ->get()
            ->filter(fn (QuizOption $option): bool => empty(array_intersect($option->styleKeys(), $validStyleKeys)));

        $orphanedMaterials = QuizMaterial::whereNotIn('style_key', $validStyleKeys)->get();

        $this->info(sprintf(
            '%s%d antwoordopties en %d materialen verwijzen alleen nog naar verwijderde stijlen en worden verwijderd (bestanden op de opslag blijven staan). Opties/materialen met minstens één geldige stijl blijven staan.',
            $dryRun ? '[dry-run] ' : '',
            $orphanedOptions->count(),
            $orphanedMaterials->count(),
        ));

        if (! $dryRun) {
            $orphanedOptions->each(fn (QuizOption $option) => $option->delete());
            $orphanedMaterials->each(fn (QuizMaterial $material) => $material->delete());
        }

        $this->info($dryRun ? 'Niets verwijderd (dry-run).' : 'Klaar.');

        return self::SUCCESS;
    }
}
