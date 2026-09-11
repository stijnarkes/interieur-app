<?php

namespace App\Services\AI;

use App\Models\QuizResult;
use App\Models\StyleProfile;
use App\Support\QuizStructure;

/**
 * Puur PHP/DB-gebaseerde adviestekst, zonder AI — gebruikt zowel als de AI-laag (fase 3) faalt of
 * een ongeldige output geeft, als (voorlopig, tot fase 3 gebouwd is) als enige bron. Mag nooit
 * blokkeren: leest alleen style_profiles-content en sjablonen per `case`, verzint niets.
 */
class QuizAdviceFallback
{
    /** @return array{comboName: string, intro: string} */
    public function build(QuizResult $result): array
    {
        $primary = $result->primary_style ? StyleProfile::forStyle($result->primary_style) : null;
        $secondary = $result->secondary_style ? StyleProfile::forStyle($result->secondary_style) : null;
        $tertiary = $result->tertiary_style ? StyleProfile::forStyle($result->tertiary_style) : null;

        if (! $primary) {
            return [
                'comboName' => 'Jouw persoonlijke woonstijl',
                'intro' => 'We hebben nog niet genoeg keuzes van je om een duidelijk profiel te schetsen.',
            ];
        }

        return [
            'comboName' => $this->comboName($result->case, $primary, $secondary, $tertiary),
            'intro' => $this->intro($result->case, $primary, $secondary),
        ];
    }

    private function comboName(string $case, StyleProfile $primary, ?StyleProfile $secondary, ?StyleProfile $tertiary): string
    {
        return match (true) {
            $case === 'close_pair' && $secondary => "{$primary->label} met een {$secondary->label}-invloed",
            $case === 'close_triple' && $secondary && $tertiary => "{$primary->label}, {$secondary->label} en {$tertiary->label} door elkaar",
            $case === 'contradictory' && $secondary => "{$primary->label} met verrassende accenten van {$secondary->label}",
            default => $primary->label,
        };
    }

    private function intro(string $case, StyleProfile $primary, ?StyleProfile $secondary): string
    {
        $base = $primary->long_description ?? '';

        if (! $secondary || ! in_array($case, ['close_pair', 'close_triple', 'contradictory'], true)) {
            return $base;
        }

        return trim($base.' Daarnaast zien we bij jou ook duidelijk iets van '.$secondary->label.' terug — dat maakt jouw stijl net even persoonlijker dan één stijl alleen.');
    }

    /** @return array<int, string> top 4-6 labels voor de "kernwoorden"-chips op de resultatenpagina */
    public function keywordChips(QuizResult $result): array
    {
        return collect($result->dominant_traits ?? [])
            ->pluck('label')
            ->take(6)
            ->values()
            ->all();
    }

    /**
     * Eén adviesparagraaf per ruimte, gebaseerd op de dominante stijl(en) binnen die ruimte —
     * gebruikt voor de pdf_full-variant als de AI-laag faalt. `null` voor een ruimte zonder
     * beantwoorde vragen (geen room_profiles-data), zodat de PDF die ruimte gewoon weglaat.
     *
     * @return array<string, ?string> ruimte-key => adviestekst
     */
    public function roomAdvice(QuizResult $result): array
    {
        $advice = [];

        foreach (QuizStructure::ROOMS as $roomKey => $roomLabel) {
            $percentages = $result->room_profiles[$roomKey] ?? null;

            if (! $percentages || array_sum($percentages) <= 0) {
                $advice[$roomKey] = null;

                continue;
            }

            arsort($percentages);
            $topStyleKey = array_key_first($percentages);
            $profile = StyleProfile::forStyle($topStyleKey);

            $advice[$roomKey] = $profile
                ? "Voor je {$roomLabel} past de {$profile->label}-stijl goed bij je: {$profile->advice_primary}"
                : null;
        }

        return $advice;
    }
}
