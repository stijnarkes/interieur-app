<?php

namespace App\Support;

/**
 * Zet de ruwe quiz_answers ({vraag-id: optie-id}) om naar leesbare labels voor het admin,
 * zonder de volledige stijltest-inhoud (die in resources/js/quiz/ leeft) in PHP te dupliceren.
 * Optie-id's volgen altijd het patroon "{vraag-slug}-{stijl-slug}", dus per antwoord tonen we
 * welke van de 6 stijlen die keuze vertegenwoordigt.
 */
class QuizAnswerFormatter
{
    private const QUESTION_LABELS = [
        'colorPreference' => 'Kleurvoorkeur',
        'floor' => 'Vloer',
        'wallColor' => 'Wandkleur',
        'wallFinish' => 'Wandafwerking',
        'sofaMaterial' => 'Bankkleur & stof',
        'sofaModel' => 'Bankmodel',
        'coffeeTable' => 'Salontafel',
        'diningTable' => 'Eettafel',
        'diningChair' => 'Eetkamerstoel',
        'lighting' => 'Verlichting',
        'rug' => 'Vloerkleed',
        'cabinet' => 'Kast / dressoir',
    ];

    // Bevat zowel de huidige 8 stijlen als de 6 die daarvóór golden — dat laatste blijft nodig
    // om oudere inzendingen (met option-id's die op de oude stijl-slugs eindigen) leesbaar te
    // houden. Zie QuizStructure::STYLES voor de actieve set.
    private const STYLE_LABELS = [
        'hotel-luxe' => 'Hotel luxe',
        'japandi' => 'Japandi',
        'kleur-explosie' => 'Kleur explosie',
        'landelijk' => 'Landelijk',
        'modern' => 'Modern',
        'modern-luxe' => 'Modern luxe',
        'natuurlijk' => 'Natuurlijk',
        'scandinavisch' => 'Scandinavisch',
        // Oude stijlen (vóór de omzetting naar de 8 hierboven) — alleen voor leesbaarheid van
        // bestaande inzendingen.
        'hotel-chique' => 'Hotel Chique',
        'industrial' => 'Industrieel',
        'biophilic' => 'Biophilic / Botanisch',
        'modern-country' => 'Landelijk modern',
        'retro-vintage' => 'Retro / Vintage',
    ];

    // Alleen nog nodig om oudere inzendingen leesbaar te tonen: de kleurvoorkeur-vraag (en de
    // bijbehorende quiz_palettes-tabel) is verwijderd (bezoekers vonden zelf een kleurensfeer
    // kiezen lastig; het resultaat toont nu gewoon het vaste palet van de winnende woonstijl).
    // Eerst de 8 sfeerpaletten die tot dan toe bestonden (palette_key => naam, ongewijzigd sinds
    // hun aanmaak), dan de nog oudere losse-kleuren-vorm van vóór de sfeerpaletten.
    private const LEGACY_PALETTE_LABELS = [
        'warm-earthy' => 'Warm & aards',
        'soft-light' => 'Zacht & licht',
        'dark-dramatic' => 'Donker & dramatisch',
        'fresh-cool' => 'Fris & koel',
        'green-natural' => 'Groen & natuurlijk',
        'rich-refined' => 'Rijk & verfijnd',
        'monochrome-sharp' => 'Monochroom & strak',
        'bold-colorful' => 'Kleurrijk & gedurfd',
    ];

    private const LEGACY_COLOR_LABELS = [
        'warm-white' => 'Warm wit',
        'sand' => 'Zand',
        'beige' => 'Beige',
        'greige' => 'Greige',
        'taupe' => 'Taupe',
        'dark-brown' => 'Donkerbruin',
        'terracotta' => 'Terracotta',
        'ochre' => 'Oker',
        'olive-green' => 'Olijfgroen',
        'moss-green' => 'Mosgroen',
        'deep-blue' => 'Diepblauw',
        'bordeaux' => 'Bordeaux',
        'light-gray' => 'Lichtgrijs',
        'anthracite' => 'Antraciet',
    ];

    /** @return array<string, string> vraaglabel => gekozen stijl (of sfeerpalet, voor de kleurvoorkeur-vraag) */
    public static function format(?array $answers): array
    {
        if (! $answers) {
            return [];
        }

        $result = [];
        foreach ($answers as $questionId => $optionId) {
            // Vaste, korte labels voor de oorspronkelijke vragen; een later via de admin
            // toegevoegde vraag heeft geen tegenhanger hier, dan tonen we de volledige vraagtekst.
            $questionLabel = self::QUESTION_LABELS[$questionId]
                ?? QuizStructure::question((string) $questionId)['title']
                ?? $questionId;
            $result[$questionLabel] = $questionId === 'colorPreference'
                ? self::colorPreferenceLabel($optionId)
                : self::styleLabelFromOptionId($optionId);
        }

        return $result;
    }

    /**
     * De kleurvoorkeur-vraag bestaat niet meer, maar oudere inzendingen bevatten 'm nog in drie
     * mogelijke vormen: een array van sfeerpalet-id's (meerdere keuzes), één sfeerpalet-id
     * (string, daarvóór), of meerdere losse kleur-id's (nog ouder, van vóór de sfeerpaletten).
     */
    private static function colorPreferenceLabel(mixed $optionId): string
    {
        $ids = is_array($optionId) ? $optionId : [$optionId];

        if ($ids === []) {
            return '—';
        }

        return implode(', ', array_map(
            static fn ($id): string => self::LEGACY_PALETTE_LABELS[$id]
                ?? self::LEGACY_COLOR_LABELS[$id]
                ?? (string) $id,
            $ids
        ));
    }

    /**
     * Toont zowel het huidige formaat (array van option-id's, sinds meerdere keuzes per vraag
     * mogelijk zijn) als het oudere formaat (één option-id, string).
     */
    private static function styleLabelFromOptionId(mixed $optionId): string
    {
        $ids = is_array($optionId) ? $optionId : [$optionId];

        if ($ids === []) {
            return '—';
        }

        return implode(', ', array_map(static function ($id): string {
            foreach (self::STYLE_LABELS as $slug => $label) {
                if (str_ends_with((string) $id, "-{$slug}")) {
                    return $label;
                }
            }

            return (string) $id;
        }, $ids));
    }
}
