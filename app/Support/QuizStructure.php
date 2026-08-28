<?php

namespace App\Support;

use App\Models\QuizQuestion;

/**
 * Kleine PHP-mirror van de sectie-/stijlstructuur uit resources/js/quiz/data.js en
 * styleProfiles.js — zelfde aanpak als de al bestaande mirrors QuizAnswerFormatter en
 * QuizImageManifest. De twee secties (Kleur & materiaal / Meubels & accessoires) liggen vast;
 * de vragen zelf staan sinds de invoering van vraagbeheer (zie QuizOptionsPage) in de
 * `quiz_questions`-tabel, niet meer hier.
 */
class QuizStructure
{
    /** @var array<string, array{id: string, title: string}> sectie-id => weergavenaam, in vaste volgorde */
    public const SECTIONS = [
        'materials-colors' => ['id' => 'materials-colors', 'title' => 'Kleur & materiaal'],
        'objects' => ['id' => 'objects', 'title' => 'Meubels & accessoires'],
    ];

    /** camelCase key => {label, slug} — zelfde 6 stijlen als STYLE_PROFILES in styleProfiles.js. */
    private const STYLES = [
        'japandi' => ['label' => 'Japandi', 'slug' => 'japandi'],
        'hotelChique' => ['label' => 'Hotel Chique', 'slug' => 'hotel-chique'],
        'industrial' => ['label' => 'Industrieel', 'slug' => 'industrial'],
        'biophilic' => ['label' => 'Biophilic / Botanisch', 'slug' => 'biophilic'],
        'modernCountry' => ['label' => 'Landelijk modern', 'slug' => 'modern-country'],
        'retroVintage' => ['label' => 'Retro / Vintage', 'slug' => 'retro-vintage'],
    ];

    /**
     * Alle vragen, gesorteerd op sectie (in de vaste SECTIONS-volgorde) en dan op sort_order
     * binnen die sectie. `sort_order` is bewust alleen lokaal (per sectie) betekenisvol —
     * verplaatsen van een vraag raakt daardoor nooit de andere sectie.
     *
     * @return array<string, array{section: string, sectionTitle: string, title: string, folder: ?string, order: int}>
     */
    public static function questions(): array
    {
        $sectionOrder = array_flip(array_keys(self::SECTIONS));

        return QuizQuestion::query()
            ->get()
            ->sortBy(fn (QuizQuestion $question): string => sprintf('%d-%08d', $sectionOrder[$question->section] ?? 99, $question->sort_order))
            ->mapWithKeys(fn (QuizQuestion $question): array => [
                $question->question_key => [
                    'section' => $question->section,
                    'sectionTitle' => self::SECTIONS[$question->section]['title'] ?? $question->section,
                    'title' => $question->title,
                    'folder' => $question->folder,
                    'order' => $question->sort_order,
                ],
            ])
            ->all();
    }

    public static function question(string $questionId): ?array
    {
        return self::questions()[$questionId] ?? null;
    }

    public static function questionLabel(string $questionId): string
    {
        $question = self::question($questionId);

        return $question ? "{$question['sectionTitle']} — {$question['title']}" : $questionId;
    }

    public static function folderFor(string $questionId): ?string
    {
        return self::question($questionId)['folder'] ?? null;
    }

    /** @return array<string, string> sectie-id => label, voor Filament Select-opties */
    public static function sectionOptions(): array
    {
        return array_map(fn (array $section): string => $section['title'], self::SECTIONS);
    }

    /** @return array<string, string> stijl-key => label, voor Filament Select-opties */
    public static function styleOptions(): array
    {
        return array_map(fn (array $style): string => $style['label'], self::STYLES);
    }

    public static function styleLabel(string $styleKey): string
    {
        return self::STYLES[$styleKey]['label'] ?? $styleKey;
    }

    public static function styleSlug(string $styleKey): string
    {
        return self::STYLES[$styleKey]['slug'] ?? $styleKey;
    }
}
