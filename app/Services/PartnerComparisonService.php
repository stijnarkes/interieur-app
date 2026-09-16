<?php

namespace App\Services;

use App\Models\PartnerComparison;
use App\Models\PartnerLink;
use App\Models\QuizOption;
use App\Models\StyleCombinationAdvice;
use Illuminate\Support\Collection;

/**
 * Pure, deterministische vergelijking tussen twee bevroren QuizResult-snapshots (zie
 * App\Support\PartnerSnapshotBuilder) — zelfde ontwerp-rol als QuizScoringService, maar raakt die
 * klasse zelf nooit aan (zie het implementatieplan, sectie 6: geen nieuwe score-normalisatie, geen
 * matchpercentage, geen gedwongen gemiddelde stijl). Voedt zowel de webpagina als de PDF met
 * identieke output.
 *
 * Regel voor elke claim: alleen tonen wat uit de daadwerkelijk opgeslagen data volgt. Geen
 * kleurfamilie-claims (die vereisen beheerde metadata die nog niet bestaat) en geen
 * materiaalclaims zonder QuizOption::tags — liever een leeg blok dan een verzonnen overeenkomst.
 */
class PartnerComparisonService
{
    public const ALGORITHM_VERSION = '1.0';

    private const MAX_FACTS = 3;

    /** @return array{facts: array{similarities: array, differences: array}, suggestions: array, contentVersion: string} */
    public function compare(array $initiatorSnapshot, array $partnerSnapshot): array
    {
        $similarities = collect();
        $differences = collect();

        $initiatorPrimary = $initiatorSnapshot['primary_style'] ?? null;
        $partnerPrimary = $partnerSnapshot['primary_style'] ?? null;
        $initiatorSecondary = $initiatorSnapshot['secondary_style'] ?? null;
        $partnerSecondary = $partnerSnapshot['secondary_style'] ?? null;

        if ($initiatorPrimary && $partnerPrimary) {
            if ($initiatorPrimary === $partnerPrimary) {
                $similarities->push(['type' => 'primary_style_match', 'styleKey' => $initiatorPrimary]);
            } else {
                $differences->push([
                    'type' => 'primary_style_difference',
                    'initiatorStyleKey' => $initiatorPrimary,
                    'partnerStyleKey' => $partnerPrimary,
                ]);
            }
        }

        if ($initiatorSecondary && $initiatorSecondary === $partnerSecondary) {
            $similarities->push(['type' => 'secondary_style_match', 'styleKey' => $initiatorSecondary]);
        }

        $sharedPaletteHexes = $this->sharedHexes(
            $initiatorSnapshot['chosen_base_palette']['colors'] ?? [],
            $partnerSnapshot['chosen_base_palette']['colors'] ?? [],
        );
        if ($sharedPaletteHexes->isNotEmpty()) {
            $similarities->push(['type' => 'base_palette_color_match', 'hexes' => $sharedPaletteHexes->all()]);
        }

        $sharedAccentHexes = $this->sharedHexes(
            $initiatorSnapshot['chosen_accent_colors'] ?? [],
            $partnerSnapshot['chosen_accent_colors'] ?? [],
        );
        if ($sharedAccentHexes->isNotEmpty()) {
            $similarities->push(['type' => 'accent_color_match', 'hexes' => $sharedAccentHexes->all()]);
        }

        $sharedOptionSlugs = $this->sharedOptionSlugs(
            $initiatorSnapshot['answers'] ?? [],
            $partnerSnapshot['answers'] ?? [],
        );
        if ($sharedOptionSlugs->isNotEmpty()) {
            $similarities->push(['type' => 'shared_option_selection', 'optionSlugs' => $sharedOptionSlugs->all()]);
        }

        $sharedTags = $this->sharedMaterialTags(
            $initiatorSnapshot['answers'] ?? [],
            $partnerSnapshot['answers'] ?? [],
        );
        if ($sharedTags->isNotEmpty()) {
            $similarities->push(['type' => 'shared_material_tags', 'tags' => $sharedTags->all()]);
        }

        [$suggestions, $contentVersion] = $this->suggestionsFor($initiatorPrimary, $partnerPrimary);

        return [
            'facts' => [
                'similarities' => $similarities->take(self::MAX_FACTS)->values()->all(),
                'differences' => $differences->take(self::MAX_FACTS)->values()->all(),
            ],
            'suggestions' => $suggestions,
            'contentVersion' => $contentVersion,
        ];
    }

    public function compareAndStore(PartnerLink $link): PartnerComparison
    {
        $result = $this->compare($link->initiator_snapshot ?? [], $link->partner_snapshot ?? []);

        return PartnerComparison::updateOrCreate(
            ['partner_link_id' => $link->id],
            [
                'algorithm_version' => self::ALGORITHM_VERSION,
                'content_version' => $result['contentVersion'],
                'facts' => $result['facts'],
                'suggestions' => $result['suggestions'],
                'status' => PartnerComparison::STATUS_READY,
                'error_message' => null,
            ]
        );
    }

    /** @return array{0: array, 1: string} */
    private function suggestionsFor(?string $initiatorPrimary, ?string $partnerPrimary): array
    {
        if ($initiatorPrimary && $partnerPrimary) {
            $advice = StyleCombinationAdvice::forPair($initiatorPrimary, $partnerPrimary);

            if ($advice && $advice->status === 'published') {
                return [[
                    'source' => 'editorial',
                    'title' => $advice->title,
                    'intro' => $advice->intro,
                    'basisTip' => $advice->basis_tip,
                    'materialsTip' => $advice->materials_tip,
                    'accentTip' => $advice->accent_tip,
                    'basePaletteStyleKey' => $advice->base_palette_style_key,
                ], (string) $advice->version];
            }
        }

        return [$this->fallbackSuggestion(), 'fallback'];
    }

    /** @return array<string, mixed> */
    private function fallbackSuggestion(): array
    {
        return [
            'source' => 'fallback',
            'title' => 'Jullie combinatietip volgt nog',
            'intro' => 'Voor deze combinatie van woonstijlen werken we nog aan een speciaal advies. '
                .'Gebruik intussen de gedeelde kleuren en beelden hierboven als vertrekpunt voor '
                .'jullie gezamenlijke inrichting.',
            'basisTip' => null,
            'materialsTip' => null,
            'accentTip' => null,
            'basePaletteStyleKey' => null,
        ];
    }

    /**
     * @param  array<int, array{hex?: string}>  $a
     * @param  array<int, array{hex?: string}>  $b
     */
    private function sharedHexes(array $a, array $b): Collection
    {
        $hexesA = collect($a)->pluck('hex')->filter()->map(fn (string $hex): string => strtolower($hex));
        $hexesB = collect($b)->pluck('hex')->filter()->map(fn (string $hex): string => strtolower($hex));

        return $hexesA->intersect($hexesB)->unique()->values();
    }

    /**
     * @param  array<string, array<int, string>>  $a
     * @param  array<string, array<int, string>>  $b
     */
    private function sharedOptionSlugs(array $a, array $b): Collection
    {
        $shared = collect();

        foreach ($a as $questionKey => $slugs) {
            if (! isset($b[$questionKey])) {
                continue;
            }

            foreach (array_intersect((array) $slugs, (array) $b[$questionKey]) as $slug) {
                $shared->push($slug);
            }
        }

        return $shared->unique()->values();
    }

    /**
     * @param  array<string, array<int, string>>  $a
     * @param  array<string, array<int, string>>  $b
     */
    private function sharedMaterialTags(array $a, array $b): Collection
    {
        return $this->tagsForAnswers($a)->intersect($this->tagsForAnswers($b))->unique()->values();
    }

    /** @param  array<string, array<int, string>>  $answers */
    private function tagsForAnswers(array $answers): Collection
    {
        $slugs = collect($answers)->flatten()->unique()->values()->all();

        if ($slugs === []) {
            return collect();
        }

        return QuizOption::query()
            ->whereIn('option_slug', $slugs)
            ->pluck('tags')
            ->filter()
            ->flatten()
            ->filter()
            ->unique()
            ->values();
    }
}
