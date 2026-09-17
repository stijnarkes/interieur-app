<?php

use App\Models\StyleCombinationAdvice;
use Illuminate\Database\Migrations\Migration;

/**
 * Herstelt een bezittelijke-vorm-typefout ("moderns" i.p.v. "modern's") in vijf van de 21
 * stijlcombinatie-adviezen (zie 2026_09_17_090000_seed_style_combination_advices) — die migratie
 * is op productie al eerder uitgevoerd, dus het aanpassen van dat bestand zelf landt daar niet
 * opnieuw. Conditionele `where`-update op de exacte, nog foute tekst (i.p.v. een vast veld
 * overschrijven) zodat een admin die dit paar intussen zelf herschreven heeft, nooit overschreven
 * wordt — zelfde voorzichtige patroon als eerdere content-correcties dit traject.
 */
return new class extends Migration
{
    public function up(): void
    {
        foreach ($this->fixes() as [$styleKeyA, $styleKeyB, $field, $old, $new]) {
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
        // Bewust geen terugdraai-logica: dit herstelt alleen een typefout, geen inhoudelijke wijziging.
    }

    /** @return array<int, array{0: string, 1: string, 2: string, 3: string, 4: string}> */
    private function fixes(): array
    {
        return [
            ['hotelLuxe', 'modern', 'basis_tip',
                "Ga uit van moderns lichte, neutrale basis (wit, greige, lichtgrijs) en voeg de warmte van hotel luxe toe via hout en textiel — zo blijft het rustig, maar niet steriel.",
                "Ga uit van modern's lichte, neutrale basis (wit, greige, lichtgrijs) en voeg de warmte van hotel luxe toe via hout en textiel — zo blijft het rustig, maar niet steriel."],
            ['japandi', 'modern', 'basis_tip',
                "Neem moderns lichte, neutrale basis en voeg Japandi's warme houttinten toe — dat voorkomt dat het geheel te kil aanvoelt, zonder de rust van modern te verliezen.",
                "Neem modern's lichte, neutrale basis en voeg Japandi's warme houttinten toe — dat voorkomt dat het geheel te kil aanvoelt, zonder de rust van modern te verliezen."],
            ['kleurExplosie', 'modern', 'accent_tip',
                "Eén heldere kleur, zoals kobaltblauw of felroze, op een verder rustige achtergrond geeft precies de impact die kleur explosie zoekt, zonder moderns rust te verstoren.",
                "Eén heldere kleur, zoals kobaltblauw of felroze, op een verder rustige achtergrond geeft precies de impact die kleur explosie zoekt, zonder modern's rust te verstoren."],
            ['landelijk', 'modern', 'basis_tip',
                "Neem moderns lichte, rustige basis (wit, greige, lichtgrijs) en voeg landelijke warmte toe via hout en textiel — dat voorkomt dat het geheel kil aanvoelt.",
                "Neem modern's lichte, rustige basis (wit, greige, lichtgrijs) en voeg landelijke warmte toe via hout en textiel — dat voorkomt dat het geheel kil aanvoelt."],
            ['landelijk', 'modern', 'materials_tip',
                "Combineer moderns strakke oppervlakken met landelijk hout en linnen — het ruwere, natuurlijke materiaal zorgt voor precies de balans die beide stijlen nodig hebben.",
                "Combineer modern's strakke oppervlakken met landelijk hout en linnen — het ruwere, natuurlijke materiaal zorgt voor precies de balans die beide stijlen nodig hebben."],
            ['modern', 'scandinavisch', 'basis_tip',
                "Beide stijlen beginnen met een lichte, neutrale basis — voeg Scandinavisch hout en zachte stoffen toe aan moderns strakkere vormen zodat het geheel niet te koud wordt.",
                "Beide stijlen beginnen met een lichte, neutrale basis — voeg Scandinavisch hout en zachte stoffen toe aan modern's strakkere vormen zodat het geheel niet te koud wordt."],
        ];
    }
};
