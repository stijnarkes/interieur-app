<?php

use App\Models\QuizOption;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Log;

/**
 * Vult de nieuwe quiz_options.secondary_style-kolom uit de bestaande quiz_option_styles-pivot
 * (die blijft ongewijzigd in de database staan — puur historisch archief, wordt door de nieuwe
 * scoring niet meer gelezen). Een optie met 2 gekoppelde stijlen krijgt de tweede als
 * secondary_style; primary_style stond via de vorige admin-pagina al gelijk aan de eerste
 * gekoppelde stijl. Opties met méér dan 2 gekoppelde stijlen (kon via de vorige, inmiddels
 * verwijderde punten-repeater ontstaan) nemen alleen de eerste 2 over — de rest wordt hier
 * gelogd, nooit stilzwijgend weggegooid uit de pivot zelf.
 */
return new class extends Migration
{
    public function up(): void
    {
        QuizOption::query()->with('styleLinks')->each(function (QuizOption $option): void {
            $styleKeys = $option->styleLinks->pluck('style_key')->values();

            if ($styleKeys->count() < 2) {
                return;
            }

            $option->forceFill(['secondary_style' => $styleKeys[1]])->saveQuietly();

            if ($styleKeys->count() > 2) {
                Log::warning('QuizOption had meer dan 2 gekoppelde stijlen; alleen de eerste 2 zijn overgenomen in primary_style/secondary_style.', [
                    'option_id' => $option->id,
                    'option_slug' => $option->option_slug,
                    'all_style_keys' => $styleKeys->all(),
                ]);
            }
        });
    }

    public function down(): void
    {
        // Bewust geen down(): dit vult alleen een nieuwe, lege kolom — er is niets om terug te draaien.
    }
};
