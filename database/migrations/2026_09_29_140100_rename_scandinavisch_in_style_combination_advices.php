<?php

use App\Models\StyleCombinationAdvice;
use Illuminate\Database\Migrations\Migration;

/**
 * Vervangt "Scandinavisch" door "Modern Scandinavisch" in de redactionele tekst van de 6
 * stijlcombinatie-adviezen die deze stijl noemen (zie 2026_09_17_090000_seed_style_combination_advices).
 * Conditionele `where`-update op de exacte, nog oude tekst per veld (i.p.v. de hele rij
 * overschrijven) zodat een admin die een van deze combinaties intussen zelf herschreven heeft via
 * de "Stijlcombinaties"-beheerpagina, nooit overschreven wordt — zelfde voorzichtige patroon als
 * 2026_09_17_110000_fix_moderns_possessive_typo_in_style_combination_advices. style_key_a/_b zelf
 * blijven ongewijzigd ("scandinavisch"), dus canonicalPair()'s alfabetische volgorde verandert niet.
 */
return new class extends Migration
{
    public function up(): void
    {
        foreach ($this->renames() as [$styleKeyA, $styleKeyB, $field, $old, $new]) {
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
        foreach ($this->renames() as [$styleKeyA, $styleKeyB, $field, $old, $new]) {
            [$canonicalA, $canonicalB] = StyleCombinationAdvice::canonicalPair($styleKeyA, $styleKeyB);

            StyleCombinationAdvice::query()
                ->where('style_key_a', $canonicalA)
                ->where('style_key_b', $canonicalB)
                ->where($field, $new)
                ->update([$field => $old]);
        }
    }

    /** @return array<int, array{0: string, 1: string, 2: string, 3: string, 4: string}> */
    private function renames(): array
    {
        return [
            ['hotelLuxe', 'scandinavisch', 'title',
                'Hotel luxe & Scandinavisch',
                'Hotel luxe & Modern Scandinavisch'],
            ['hotelLuxe', 'scandinavisch', 'basis_tip',
                'Houd de basis licht, zoals Scandinavisch dat wil, met wit en lichte houttinten — en voeg de luxe toe via één donkerder, rijker accentmeubel of -muur in plaats van de hele ruimte te verzwaren.',
                'Houd de basis licht, zoals Modern Scandinavisch dat wil, met wit en lichte houttinten — en voeg de luxe toe via één donkerder, rijker accentmeubel of -muur in plaats van de hele ruimte te verzwaren.'],
            ['hotelLuxe', 'scandinavisch', 'materials_tip',
                'Licht hout en zachte stoffen uit Scandinavisch design combineren goed met één luxueus materiaal, zoals fluweel of marmer, als bewust hoogtepunt in een verder rustige ruimte.',
                'Licht hout en zachte stoffen uit Modern Scandinavisch design combineren goed met één luxueus materiaal, zoals fluweel of marmer, als bewust hoogtepunt in een verder rustige ruimte.'],
            ['hotelLuxe', 'scandinavisch', 'accent_tip',
                'Een zachte, warme tint zoals oudroze of taupe voelt bij allebei op zijn plek — licht genoeg voor Scandinavisch, verfijnd genoeg voor hotel luxe.',
                'Een zachte, warme tint zoals oudroze of taupe voelt bij allebei op zijn plek — licht genoeg voor Modern Scandinavisch, verfijnd genoeg voor hotel luxe.'],

            ['japandi', 'scandinavisch', 'title',
                'Japandi & Scandinavisch',
                'Japandi & Modern Scandinavisch'],
            ['japandi', 'scandinavisch', 'basis_tip',
                'Licht hout en zachte wittinten werken voor jullie allebei — kies samen of het geheel iets warmer (Japandi) of iets frisser (Scandinavisch) mag aanvoelen, en laat die keuze de basis kleuren.',
                'Licht hout en zachte wittinten werken voor jullie allebei — kies samen of het geheel iets warmer (Japandi) of iets frisser (Modern Scandinavisch) mag aanvoelen, en laat die keuze de basis kleuren.'],
            ['japandi', 'scandinavisch', 'materials_tip',
                "Eiken, wol en linnen zijn bij beide stijlen vertrouwd terrein — een mix van Japandi's iets rustiekere keramiek en Scandinavisch' speelsere accessoires geeft genoeg afwisseling.",
                "Eiken, wol en linnen zijn bij beide stijlen vertrouwd terrein — een mix van Japandi's iets rustiekere keramiek en Modern Scandinavisch' speelsere accessoires geeft genoeg afwisseling."],
            ['japandi', 'scandinavisch', 'accent_tip',
                'Een zachte pasteltint of taupe past bij beide — licht genoeg voor Scandinavisch, warm genoeg voor Japandi.',
                'Een zachte pasteltint of taupe past bij beide — licht genoeg voor Modern Scandinavisch, warm genoeg voor Japandi.'],

            ['kleurExplosie', 'scandinavisch', 'title',
                'Kleur explosie & Scandinavisch',
                'Kleur explosie & Modern Scandinavisch'],
            ['kleurExplosie', 'scandinavisch', 'basis_tip',
                "Scandinavisch' lichte, neutrale basis (wit en licht hout) geeft precies de rust die nodig is om felle accenten te laten stralen zonder dat het te druk wordt.",
                "Modern Scandinavisch' lichte, neutrale basis (wit en licht hout) geeft precies de rust die nodig is om felle accenten te laten stralen zonder dat het te druk wordt."],
            ['kleurExplosie', 'scandinavisch', 'materials_tip',
                'Licht hout en eenvoudige keramiek uit Scandinavisch design combineren verrassend goed met één of twee gelakte of glanzende kleuraccenten.',
                'Licht hout en eenvoudige keramiek uit Modern Scandinavisch design combineren verrassend goed met één of twee gelakte of glanzende kleuraccenten.'],
            ['kleurExplosie', 'scandinavisch', 'accent_tip',
                'Eén vrolijke kleur, zoals felroze of geel, mag hier gerust de show stelen — zolang de basis licht en rustig blijft, verdraagt Scandinavisch dat goed.',
                'Eén vrolijke kleur, zoals felroze of geel, mag hier gerust de show stelen — zolang de basis licht en rustig blijft, verdraagt Modern Scandinavisch dat goed.'],

            ['landelijk', 'scandinavisch', 'title',
                'Landelijk & Scandinavisch',
                'Landelijk & Modern Scandinavisch'],
            ['landelijk', 'scandinavisch', 'basis_tip',
                'Kies een basis die tussen de twee in zit: iets lichter dan puur landelijk, iets warmer dan puur Scandinavisch — denk aan zand, gebroken wit en licht tot middel hout.',
                'Kies een basis die tussen de twee in zit: iets lichter dan puur landelijk, iets warmer dan puur Modern Scandinavisch — denk aan zand, gebroken wit en licht tot middel hout.'],
            ['landelijk', 'scandinavisch', 'materials_tip',
                "Landelijk aardewerk en natuursteen combineren goed met Scandinavisch' lichtere hout en eenvoudige keramiek — beide stijlen houden van materiaal dat je kunt voelen.",
                "Landelijk aardewerk en natuursteen combineren goed met Modern Scandinavisch' lichtere hout en eenvoudige keramiek — beide stijlen houden van materiaal dat je kunt voelen."],
            ['landelijk', 'scandinavisch', 'accent_tip',
                'Een zachte, aardse tint zoals olijfgroen past bij allebei — natuurlijk genoeg voor landelijk, rustig genoeg voor Scandinavisch.',
                'Een zachte, aardse tint zoals olijfgroen past bij allebei — natuurlijk genoeg voor landelijk, rustig genoeg voor Modern Scandinavisch.'],

            ['modern', 'scandinavisch', 'title',
                'Modern & Scandinavisch',
                'Modern & Modern Scandinavisch'],
            ['modern', 'scandinavisch', 'basis_tip',
                "Beide stijlen beginnen met een lichte, neutrale basis — voeg Scandinavisch hout en zachte stoffen toe aan modern's strakkere vormen zodat het geheel niet te koud wordt.",
                "Beide stijlen beginnen met een lichte, neutrale basis — voeg Modern Scandinavisch hout en zachte stoffen toe aan modern's strakkere vormen zodat het geheel niet te koud wordt."],
            ['modern', 'scandinavisch', 'materials_tip',
                'Gladde oppervlakken uit modern (glas, metaal) in combinatie met licht hout en wol uit Scandinavisch design zorgen voor precies genoeg warmte in een verder strakke ruimte.',
                'Gladde oppervlakken uit modern (glas, metaal) in combinatie met licht hout en wol uit Modern Scandinavisch design zorgen voor precies genoeg warmte in een verder strakke ruimte.'],
            ['modern', 'scandinavisch', 'accent_tip',
                'Een zachte pasteltint naast antraciet geeft het beste van beide: de rust van modern, de gezelligheid van Scandinavisch.',
                'Een zachte pasteltint naast antraciet geeft het beste van beide: de rust van modern, de gezelligheid van Modern Scandinavisch.'],

            ['scandinavisch', 'scandinavisch', 'title',
                'Scandinavisch & Scandinavisch',
                'Modern Scandinavisch & Modern Scandinavisch'],
        ];
    }
};
