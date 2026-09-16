<?php

namespace Database\Seeders;

use App\Models\StyleCombinationAdvice;
use App\Support\QuizStructure;
use Illuminate\Database\Seeder;

/**
 * Zet alle 21 stijlcombinatie-adviesrecords klaar (6 stijlen -> 15 paren + 6 zelfde-stijl-
 * combinaties, zie het implementatieplan "Partnerfunctie", sectie 7) als concept, met een
 * neutrale placeholdertekst — de daadwerkelijke redactionele tekst per paar vult een stylist
 * later in via de "Stijlcombinaties"-beheerpagina (zie StyleCombinationAdvicesPage). Een
 * concept-record wordt nooit aan een bezoeker getoond (zie PartnerComparisonService::
 * suggestionsFor(), die dan op de vaste fallbacktekst terugvalt) — deze seeder zorgt dus alleen
 * dat er voor elk paar iets klaarstaat om te redigeren, nooit voor ongeziene content in productie.
 */
class StyleCombinationAdviceSeeder extends Seeder
{
    public function run(): void
    {
        $styleKeys = array_keys(QuizStructure::styleOptions());
        $labels = QuizStructure::styleOptions();

        foreach ($this->allPairs($styleKeys) as [$a, $b]) {
            [$canonicalA, $canonicalB] = StyleCombinationAdvice::canonicalPair($a, $b);

            StyleCombinationAdvice::query()->firstOrCreate(
                ['style_key_a' => $canonicalA, 'style_key_b' => $canonicalB],
                [
                    'title' => "{$labels[$canonicalA]} & {$labels[$canonicalB]}",
                    'intro' => 'Redactionele tekst volgt nog.',
                    'basis_tip' => 'Redactionele tekst volgt nog.',
                    'materials_tip' => 'Redactionele tekst volgt nog.',
                    'accent_tip' => 'Redactionele tekst volgt nog.',
                    'status' => 'concept',
                    'version' => 1,
                ],
            );
        }
    }

    /** @param  array<int, string>  $styleKeys @return array<int, array{0: string, 1: string}> alle paren, inclusief elke stijl met zichzelf */
    private function allPairs(array $styleKeys): array
    {
        $pairs = [];

        foreach ($styleKeys as $i => $a) {
            foreach ($styleKeys as $j => $b) {
                if ($j < $i) {
                    continue;
                }
                $pairs[] = [$a, $b];
            }
        }

        return $pairs;
    }
}
