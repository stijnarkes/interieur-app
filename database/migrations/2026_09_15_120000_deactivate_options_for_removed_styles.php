<?php

use App\Models\QuizOption;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Log;

/**
 * De woonstijllijst gaat van 8 naar 6 vaste stijlen: "Modern luxe" en "Natuurlijk" vervallen (zie
 * QuizStructure::STYLES). Zonder ingrijpen zou een antwoordoptie die alleen aan een van deze twee
 * hing onopgemerkt een "dode" keuze in de klant-quiz worden — QuizScoringService negeert een
 * vervallen stijl-key al bij het scoren, dus zo'n optie zou nog gewoon getoond worden maar nergens
 * meer aan bijdragen. Op uitdrukkelijk verzoek van de klant worden zulke opties hier daarom
 * gedeactiveerd (nooit verwijderd) — ze zijn met de Actief-knop in de admin terug te zetten zodra
 * ze aan een van de 6 overgebleven stijlen zijn gekoppeld. Opties die daarnaast ook nog aan
 * minstens 1 geldige stijl hingen, blijven actief; alleen de vervallen stijl-key(s) worden uit hun
 * koppeling gehaald zodat de admin geen verwarrende "onbekende stijl"-badge blijft zien.
 */
return new class extends Migration
{
    private const REMOVED_STYLES = ['modernLuxe', 'natuurlijk'];

    public function up(): void
    {
        QuizOption::query()->each(function (QuizOption $option): void {
            $linked = $option->linkedStyleKeys();
            $remaining = array_values(array_diff($linked, self::REMOVED_STYLES));

            if ($remaining === $linked) {
                return;
            }

            $updates = ['style_keys' => $remaining];

            if ($remaining === []) {
                $updates['is_active'] = false;

                Log::warning('QuizOption gedeactiveerd: uitsluitend gekoppeld aan een vervallen woonstijl (Modern luxe/Natuurlijk).', [
                    'option_id' => $option->id,
                    'option_slug' => $option->option_slug,
                    'title' => $option->title,
                    'was_linked_to' => $linked,
                ]);
            }

            $option->forceFill($updates)->saveQuietly();
        });
    }

    public function down(): void
    {
        // Bewust geen down(): wie een gedeactiveerde optie terug wil, gebruikt de Actief-knop in
        // de admin nadat hij/zij 'm aan een geldige stijl heeft gekoppeld.
    }
};
