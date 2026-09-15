<?php

namespace App\Support;

use App\Models\QuizQuestion;
use App\Models\QuizTransitionSection;

/**
 * Kleine PHP-mirror van de sectie-/stijlstructuur uit resources/js/quiz/data.js en
 * styleProfiles.js — zelfde aanpak als de al bestaande mirror QuizImageManifest. De twee
 * secties (Kleur & materiaal / Wonen & inrichting) liggen vast;
 * de vragen zelf staan sinds de invoering van vraagbeheer (zie QuizOptionsPage) in de
 * `quiz_questions`-tabel, niet meer hier.
 */
class QuizStructure
{
    /** @var array<string, array{id: string, title: string}> sectie-id => weergavenaam, in vaste volgorde */
    public const SECTIONS = [
        'materials-colors' => ['id' => 'materials-colors', 'title' => 'Kleur & materiaal'],
        'objects' => ['id' => 'objects', 'title' => 'Wonen & inrichting'],
    ];

    /** @var array<string, string> ruimte-key => label, voor Filament Select-opties op een vraag */
    public const ROOMS = [
        'woonkamer' => 'Woonkamer',
        'eethoek' => 'Eethoek',
        'keuken' => 'Keuken',
    ];

    /**
     * camelCase key => {label, slug} — de 6 vaste woonstijlen. "Modern luxe" en "Natuurlijk" zijn
     * hier bewust uitgehaald (was 8); zie migration 2026_09_15_120000_deactivate_options_for_removed_styles
     * voor hoe bestaande koppelingen naar deze twee zijn opgeschoond. QuizScoringService negeert
     * een stijl-key die hier niet (meer) in staat altijd veilig, dus een enkele gemiste plek kan
     * nooit een verwijderde stijl als quizuitslag opleveren.
     */
    private const STYLES = [
        'hotelLuxe' => ['label' => 'Hotel luxe', 'slug' => 'hotel-luxe'],
        'japandi' => ['label' => 'Japandi', 'slug' => 'japandi'],
        'kleurExplosie' => ['label' => 'Kleur explosie', 'slug' => 'kleur-explosie'],
        'landelijk' => ['label' => 'Landelijk', 'slug' => 'landelijk'],
        'modern' => ['label' => 'Modern', 'slug' => 'modern'],
        'scandinavisch' => ['label' => 'Scandinavisch', 'slug' => 'scandinavisch'],
    ];

    /**
     * Alle vragen, gesorteerd op sectie (in de vaste SECTIONS-volgorde) en dan op sort_order
     * binnen die sectie. `sort_order` is bewust alleen lokaal (per sectie) betekenisvol —
     * verplaatsen van een vraag raakt daardoor nooit de andere sectie.
     *
     * @return array<string, array{section: string, sectionTitle: string, room: ?string, title: string, folder: ?string, order: int, maxSelections: int, imageDisplayMode: string}>
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
                    'sectionTitle' => self::sectionLabel($question->section),
                    'room' => $question->room,
                    'title' => $question->title,
                    'folder' => $question->folder,
                    'order' => $question->sort_order,
                    'maxSelections' => $question->max_selections,
                    'imageDisplayMode' => $question->image_display_mode,
                ],
            ])
            ->all();
    }

    /** @return array<string, string> mode-waarde => label, voor Filament Select-opties */
    public static function imageDisplayModeOptions(): array
    {
        return [
            'contain' => 'Passend (met rand, niets bijgesneden)',
            'cover' => 'Vullend (tegel vullen, randen bijsnijden)',
        ];
    }

    /** @return array<string, string> ruimte-key => label, voor Filament Select-opties */
    public static function roomOptions(): array
    {
        return self::ROOMS;
    }

    /** @return array<int, string> alle 6 vaste stijl-keys, o.a. voor het seeden van style_profiles */
    public static function styleKeys(): array
    {
        return array_keys(self::STYLES);
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
        return collect(self::SECTIONS)
            ->mapWithKeys(fn (array $section, string $id): array => [$id => self::sectionLabel($id)])
            ->all();
    }

    /**
     * Leest de sectietitel bij voorkeur uit quiz_transition_sections (admin-bewerkbaar via
     * TekstenPage) en valt terug op de hardcoded titel hierboven als er nog geen rij bestaat —
     * zelfde niet-destructieve fallback-patroon als QuizOption::linkedStyleKeys().
     */
    public static function sectionLabel(string $sectionId): string
    {
        return QuizTransitionSection::forSection($sectionId)?->title
            ?? self::SECTIONS[$sectionId]['title']
            ?? $sectionId;
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
