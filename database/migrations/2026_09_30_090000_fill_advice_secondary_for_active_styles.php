<?php

use App\Models\StyleProfile;
use Illuminate\Database\Migrations\Migration;

/**
 * Vult het tot nu toe overal lege "Als secundaire invloed"-veld (StyleProfile::advice_secondary)
 * voor de zes actief gebruikte stijlen — zie QuizResultTextComposer::secondaryInfluenceSentence(),
 * dat dit veld nu leest i.p.v. altijd dezelfde generieke zin te tonen. Modern luxe en Natuurlijk
 * blijven bewust ongemoeid (nog geen enkele andere redactionele inhoud voor die twee, zie
 * 2026_09_29_180000_improve_style_profile_copy).
 *
 * whereNull() i.p.v. altijd overschrijven: dit veld is voortaan een gewoon, door een admin
 * bewerkbaar tekstveld (zie StyleProfilesPage) — een handmatige aanpassing na deze migratie mag
 * een latere her-deploy nooit ongedaan maken.
 */
return new class extends Migration
{
    public function up(): void
    {
        foreach ($this->texts() as $styleKey => $text) {
            StyleProfile::query()
                ->where('style_key', $styleKey)
                ->whereNull('advice_secondary')
                ->update(['advice_secondary' => $text]);
        }
    }

    public function down(): void
    {
        // Bewust geen terugdraai-logica: dit vult een leeg contentveld, geen technische fix — en
        // whereNull() hierboven betekent toch al dat een inmiddels handmatig aangepaste tekst nooit
        // door deze migratie zelf is weggeschreven.
    }

    /** @return array<string, string> style_key => tekst */
    private function texts(): array
    {
        return [
            'hotelLuxe' => 'Ook Hotel luxe komt in je keuzes naar voren. Met een rijke stof, sfeervolle verlichting of een verfijnd detail kun je de ruimte extra luxe geven.',
            'landelijk' => 'Ook Landelijk komt in je keuzes naar voren. Natuurlijk hout en comfortabele stoffen kunnen de ruimte warm en huiselijk maken.',
            'japandi' => 'Ook Japandi komt in je keuzes naar voren. Natuurlijke materialen en eenvoudige vormen kunnen voor meer rust en warmte zorgen.',
            'kleurExplosie' => 'Ook Kleur explosie komt in je keuzes naar voren. Een uitgesproken kleur of opvallend object kan de ruimte een speels accent geven.',
            'modern' => 'Ook Modern komt in je keuzes naar voren. Strakke lijnen en rustige vlakken kunnen voor een heldere, verzorgde uitstraling zorgen.',
            'scandinavisch' => 'Ook Modern Scandinavisch komt in je keuzes naar voren. Licht hout, zachte stoffen en eenvoudige meubels kunnen het geheel fris en warm maken.',
        ];
    }
};
