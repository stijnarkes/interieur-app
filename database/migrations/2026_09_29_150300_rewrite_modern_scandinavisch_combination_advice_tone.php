<?php

use App\Models\StyleCombinationAdvice;
use Illuminate\Database\Migrations\Migration;

/**
 * Vervolg op 2026_09_29_140100_rename_scandinavisch_in_style_combination_advices (die alleen de
 * naam verving): past nu ook de woordkeuze aan waar de tekst nog uitgaat van het oude, lichte/
 * pastel/speelse "Scandinavisch" i.p.v. de nieuwe, ingetogener "Modern Scandinavisch"-richting
 * (zie 2026_09_29_150000_rewrite_modern_scandinavisch_style_profile). Conditionele where-update
 * op de exacte, nog oude tekst per veld — zelfde voorzichtige patroon als eerdere
 * combinatietekst-correcties in dit traject, zodat een intussen zelf herschreven combinatie nooit
 * overschreven wordt.
 */
return new class extends Migration
{
    public function up(): void
    {
        foreach ($this->changes() as [$styleKeyA, $styleKeyB, $field, $old, $new]) {
            [$canonicalA, $canonicalB] = StyleCombinationAdvice::canonicalPair($styleKeyA, $styleKeyB);

            StyleCombinationAdvice::query()
                ->where('style_key_a', $canonicalA)
                ->where('style_key_b', $canonicalB)
                ->where($field, $old)
                ->update([$field => $new]);
        }
    }

    public function down(): void
    {
        foreach ($this->changes() as [$styleKeyA, $styleKeyB, $field, $old, $new]) {
            [$canonicalA, $canonicalB] = StyleCombinationAdvice::canonicalPair($styleKeyA, $styleKeyB);

            StyleCombinationAdvice::query()
                ->where('style_key_a', $canonicalA)
                ->where('style_key_b', $canonicalB)
                ->where($field, $new)
                ->update([$field => $old]);
        }
    }

    /** @return array<int, array{0: string, 1: string, 2: string, 3: string, 4: string}> */
    private function changes(): array
    {
        return [
            ['hotelLuxe', 'scandinavisch', 'accent_tip',
                'Een zachte, warme tint zoals oudroze of taupe voelt bij allebei op zijn plek — licht genoeg voor Modern Scandinavisch, verfijnd genoeg voor hotel luxe.',
                'Een zachte, aardse tint zoals taupe of roestbruin voelt bij allebei op zijn plek — verfijnd genoeg voor Modern Scandinavisch, warm genoeg voor hotel luxe.'],
            ['japandi', 'scandinavisch', 'materials_tip',
                "Eiken, wol en linnen zijn bij beide stijlen vertrouwd terrein — een mix van Japandi's iets rustiekere keramiek en Modern Scandinavisch' speelsere accessoires geeft genoeg afwisseling.",
                "Eiken, wol en linnen zijn bij beide stijlen vertrouwd terrein — een mix van Japandi's iets rustiekere keramiek en Modern Scandinavisch' strakkere accessoires geeft genoeg afwisseling."],
            ['modern', 'scandinavisch', 'intro',
                'Strak en koel tegenover licht en gezellig — samen vinden jullie een huis dat er verzorgd uitziet, maar wel warm blijft aanvoelen.',
                'Allebei houden jullie van rust en een strakke basis — het verschil zit vooral in de warmte: koel en strak tegenover licht en natuurlijk.'],
            ['modern', 'scandinavisch', 'accent_tip',
                'Een zachte pasteltint naast antraciet geeft het beste van beide: de rust van modern, de gezelligheid van Modern Scandinavisch.',
                'Antraciet als gedeeld vertrekpunt geeft het beste van beide: de rust van modern, de natuurlijke warmte van Modern Scandinavisch.'],
            ['scandinavisch', 'scandinavisch', 'intro',
                'Allebei houden jullie van een licht, fris en gezellig huis — een van de makkelijkste combinaties om samen invulling aan te geven.',
                'Allebei houden jullie van een licht, fris en verfijnd huis — een van de makkelijkste combinaties om samen invulling aan te geven.'],
            ['scandinavisch', 'scandinavisch', 'basis_tip',
                'Blijf bij wit en lichte neutrale tinten als basis — met dezelfde smaak wordt de keuze vooral welke zachte pasteltint jullie er samen aan toevoegen.',
                'Blijf bij wit en lichte neutrale tinten als basis — met dezelfde smaak wordt de keuze vooral welke ingetogen accentkleur jullie er samen aan toevoegen.'],
            ['scandinavisch', 'scandinavisch', 'materials_tip',
                'Licht hout, zachte stoffen en eenvoudige keramische accessoires blijven de kern — voeg gerust wat meer variatie in structuur toe dan je alleen zou doen, dat houdt het interessant zonder de frisheid te verliezen.',
                'Licht hout, zachte stoffen en eenvoudige keramische accessoires blijven de kern — voeg gerust wat meer variatie in structuur toe dan je alleen zou doen, dat houdt het interessant zonder de rust te verliezen.'],
            ['scandinavisch', 'scandinavisch', 'accent_tip',
                'Een zachte pasteltint, zoals lichtroze of mintgroen, past bij jullie allebei en houdt het geheel licht en gezellig.',
                'Een ingetogen accentkleur, zoals antraciet of dennengroen, past bij jullie allebei en houdt het geheel licht en verfijnd.'],
        ];
    }
};
