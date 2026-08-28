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

    private const STYLE_LABELS = [
        'japandi' => 'Japandi',
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
                : self::styleLabelFromOptionId((string) $optionId);
        }

        return $result;
    }

    /**
     * Toont zowel het huidige formaat (één sfeerpalet-id, string) als het formaat van vóór de
     * sfeerpaletten (meerdere losse kleur-id's, array) — oudere inzendingen bevatten nog dat
     * laatste formaat.
     */
    private static function colorPreferenceLabel(mixed $optionId): string
    {
        if (is_array($optionId)) {
            if ($optionId === []) {
                return '—';
            }

            return implode(', ', array_map(
                static fn ($id): string => self::LEGACY_COLOR_LABELS[$id] ?? (string) $id,
                $optionId
            ));
        }

        return QuizPalette::where('palette_key', $optionId)->value('name') ?? (string) $optionId;
    }

    private static function styleLabelFromOptionId(string $optionId): string
    {
        foreach (self::STYLE_LABELS as $slug => $label) {
            if (str_ends_with($optionId, "-{$slug}")) {
                return $label;
            }
        }

        return $optionId;
    }
}
