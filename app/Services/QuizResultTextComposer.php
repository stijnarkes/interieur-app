<?php

namespace App\Services;

use App\Models\QuizResult;
use App\Models\StyleProfile;

/**
 * Stelt de titel en introtekst van een quizresultaat samen — puur uit de al berekende
 * primaire/secundaire stijl en de bestaande stijlprofielteksten. Geen AI: dat garandeert dat
 * scherm, PDF en e-mail altijd exact dezelfde tekst tonen voor hetzelfde resultaat, en dat er
 * nooit een feit/kenmerk bij verzonnen wordt (zie de opdracht "vereenvoudiging woonstijltest").
 */
class QuizResultTextComposer
{
    /** @return array{comboName: string, intro: string} */
    public function build(QuizResult $result): array
    {
        $primary = $result->primary_style ? StyleProfile::forStyle($result->primary_style) : null;
        $secondary = $result->secondary_style ? StyleProfile::forStyle($result->secondary_style) : null;

        if (! $primary) {
            return [
                'comboName' => 'Jouw persoonlijke woonstijl',
                'intro' => 'We hebben nog niet genoeg keuzes van je om een duidelijk profiel te schetsen.',
            ];
        }

        return [
            'comboName' => $secondary
                ? "Jouw woonstijl: {$primary->label} met {$secondary->label}-invloeden"
                : "Jouw woonstijl: {$primary->label}",
            'intro' => $this->intro($primary, $secondary),
        ];
    }

    private function intro(StyleProfile $primary, ?StyleProfile $secondary): string
    {
        $base = $primary->long_description ?? '';

        if (! $secondary) {
            return $base;
        }

        return trim($base.' '.$this->secondaryInfluenceSentence($secondary));
    }

    /**
     * Eigen, per-stijl tekst (StyleProfile::advice_secondary, beheerd via StijlprofielenPage) i.p.v.
     * altijd dezelfde generieke zin — sluit zo aan op de omschrijving van de hoofdstijl in plaats
     * van los ervan te staan. Nog geen tekst ingevuld voor deze stijl (bv. Modern luxe/Natuurlijk,
     * of het veld is later weer leeggemaakt) -> nette generieke terugvalzin, nooit een lege regel.
     */
    private function secondaryInfluenceSentence(StyleProfile $secondary): string
    {
        return $secondary->advice_secondary
            ?? "Daarnaast zien we bij jou ook duidelijk iets van {$secondary->label} terug.";
    }
}
