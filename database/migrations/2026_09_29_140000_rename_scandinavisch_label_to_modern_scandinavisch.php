<?php

use App\Models\StyleProfile;
use Illuminate\Database\Migrations\Migration;

/**
 * Hernoemt alleen het zichtbare label van de "scandinavisch"-stijl naar "Modern Scandinavisch" —
 * style_key/slug (en dus alle bestandsnamen/foto's, zie QuizOption::storeImage()) blijven bewust
 * "scandinavisch", dat raakt geen opgeslagen bestand of andere databaserij. Conditionele
 * `where`-update op het huidige label (i.p.v. altijd overschrijven) zodat een admin die dit label
 * intussen zelf al aangepast heeft via StyleProfilesPage nooit overschreven wordt — zelfde
 * voorzichtige patroon als 2026_09_17_110000_fix_moderns_possessive_typo_in_style_combination_advices.
 */
return new class extends Migration
{
    public function up(): void
    {
        StyleProfile::query()
            ->where('style_key', 'scandinavisch')
            ->where('label', 'Scandinavisch')
            ->update(['label' => 'Modern Scandinavisch']);
    }

    public function down(): void
    {
        StyleProfile::query()
            ->where('style_key', 'scandinavisch')
            ->where('label', 'Modern Scandinavisch')
            ->update(['label' => 'Scandinavisch']);
    }
};
