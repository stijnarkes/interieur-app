<?php

namespace App\Support;

use App\Models\QuizPalette;

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

    // Alleen nog nodig om oudere inzendingen leesbaar te tonen: vóór de sfeerpaletten koos de
    // kleurvoorkeur-vraag meerdere losse kleuren (mirror van het inmiddels verwijderde
    // admin-scherm voor losse kleuren).
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
     * Toont zowel het huidige formaat (array van sfeerpalet-id's, sinds meerdere keuzes per
     * vraag mogelijk zijn), het formaat daarvóór (één sfeerpalet-id, string), als het formaat van
     * vóór de sfeerpaletten (meerdere losse kleur-id's, array) — oudere inzendingen kunnen elk van
     * deze drie bevatten.
     */
    private static function colorPreferenceLabel(mixed $optionId): string
    {
        $ids = is_array($optionId) ? $optionId : [$optionId];

        if ($ids === []) {
            return '—';
        }

        return implode(', ', array_map(
            static fn ($id): string => QuizPalette::where('palette_key', $id)->value('name')
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
