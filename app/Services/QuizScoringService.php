<?php

namespace App\Services;

use App\Models\QuizOption;
use App\Models\QuizQuestion;
use App\Models\QuizSetting;
use App\Support\QuizStructure;

/**
 * Servergestuurde, autoritatieve berekening van een quizresultaat — vervangt resources/js/quiz/
 * scoring.js's client-side, ongewogen berekening. Telt gewogen stijlpunten (QuizOptionStyle::points)
 * en traits (QuizOptionTrait::weight) op over de gekozen opties, per ruimte én in totaal, en bepaalt
 * daarna primaire/secundaire/tertiaire stijl inclusief "sterkte" via configureerbare drempels
 * (QuizSetting). Bevat zelf geen AI-tekst — dat is een aparte laag die alleen deze uitkomst als
 * grondstof krijgt (zie het implementatieplan "persoonlijke digitale interieuradviseur").
 */
class QuizScoringService
{
    /**
     * @param  array<string, array<int, string>>  $answers  questionId => geselecteerde option_slugs
     * @return array{
     *     style_scores: array<string, int>,
     *     style_percentages: array<string, int>,
     *     room_profiles: array<string, array<string, int>>,
     *     dominant_traits: array<int, array{key: string, label: string, weight: int}>,
     *     ranking: array{
     *         primary_style: ?string, secondary_style: ?string, tertiary_style: ?string,
     *         primary_strength: ?string, secondary_strength: ?string, tertiary_strength: ?string,
     *         case: string,
     *     },
     * }
     */
    public function compute(array $answers): array
    {
        $optionSlugs = collect($answers)->flatten()->filter()->unique()->values()->all();

        $options = QuizOption::query()
            ->whereIn('option_slug', $optionSlugs)
            ->with(['styleLinks', 'traitLinks.traitRecord'])
            ->get()
            ->keyBy('option_slug');

        $questions = QuizQuestion::query()->get()->keyBy('question_key');

        $styleScores = array_fill_keys(QuizStructure::styleKeys(), 0);
        $roomScores = [];
        $traitWeights = [];

        foreach ($answers as $questionId => $optionIds) {
            $room = $questions->get($questionId)?->room;

            foreach ((array) $optionIds as $optionId) {
                $option = $options->get($optionId);
                if (! $option) {
                    continue;
                }

                foreach ($option->styleLinks as $link) {
                    $styleScores[$link->style_key] = ($styleScores[$link->style_key] ?? 0) + $link->points;

                    if ($room) {
                        $roomScores[$room][$link->style_key] = ($roomScores[$room][$link->style_key] ?? 0) + $link->points;
                    }
                }

                foreach ($option->traitLinks as $traitLink) {
                    $trait = $traitLink->traitRecord;
                    if (! $trait) {
                        continue;
                    }

                    $traitWeights[$trait->key] ??= ['label' => $trait->label, 'weight' => 0];
                    $traitWeights[$trait->key]['weight'] += $traitLink->weight;
                }
            }
        }

        $percentages = $this->percentagesFor($styleScores);

        $roomProfiles = [];
        foreach ($roomScores as $room => $scores) {
            $roomProfiles[$room] = $this->percentagesFor($scores);
        }

        $dominantTraits = collect($traitWeights)
            ->map(fn (array $data, string $key): array => ['key' => $key, 'label' => $data['label'], 'weight' => $data['weight']])
            ->values()
            ->sortByDesc('weight')
            ->take(6)
            ->values()
            ->all();

        $settings = QuizSetting::current();
        $ranking = $this->determineRanking(
            $percentages,
            $settings->primary_dominant_margin,
            $settings->close_pair_margin,
            $settings->close_triple_margin,
        );

        return [
            'style_scores' => $styleScores,
            'style_percentages' => $percentages,
            'room_profiles' => $roomProfiles,
            'dominant_traits' => $dominantTraits,
            'ranking' => $ranking,
        ];
    }

    /**
     * @param  array<string, int>  $styleScores
     * @return array<string, int>
     */
    private function percentagesFor(array $styleScores): array
    {
        $sum = array_sum($styleScores);

        if ($sum <= 0) {
            return array_fill_keys(array_keys($styleScores), 0);
        }

        return array_map(fn (int $score): int => (int) round($score / $sum * 100), $styleScores);
    }

    /**
     * Bepaalt primair/secundair/tertiair + "sterkte" uit een percentageverdeling. Pure functie
     * (geen DB-calls, marges als parameters) zodat dit met handmatige percentage-arrays
     * unit-getest kan worden voor elk van de 4 gevallen.
     *
     * @param  array<string, int>  $percentages  style_key => percentage, hoeft niet gesorteerd te zijn
     */
    public function determineRanking(
        array $percentages,
        int $primaryDominantMargin,
        int $closePairMargin,
        int $closeTripleMargin,
    ): array {
        arsort($percentages);
        $ranked = array_keys($percentages);
        $values = array_values($percentages);

        $top1 = $values[0] ?? 0;

        if ($top1 <= 0) {
            return [
                'primary_style' => null,
                'secondary_style' => null,
                'tertiary_style' => null,
                'primary_strength' => null,
                'secondary_strength' => null,
                'tertiary_strength' => null,
                'case' => 'clear_winner',
            ];
        }

        $top2 = $values[1] ?? 0;
        $top3 = $values[2] ?? 0;
        $gap1 = $top1 - $top2;
        $gap2 = $top2 - $top3;

        if ($gap1 >= $primaryDominantMargin) {
            $case = 'clear_winner';
            $strengths = ['strong', 'moderate', 'subtle'];
        } elseif ($gap1 < $closePairMargin && $gap2 >= $closePairMargin) {
            $case = 'close_pair';
            $strengths = ['strong', 'strong', 'subtle'];
        } elseif ($gap1 < $closeTripleMargin && $gap2 < $closeTripleMargin) {
            $case = 'close_triple';
            $strengths = ['moderate', 'moderate', 'moderate'];
        } else {
            $case = 'contradictory';
            $strengths = ['strong', 'moderate', 'subtle'];
        }

        return [
            'primary_style' => $ranked[0] ?? null,
            'secondary_style' => $top2 > 0 ? ($ranked[1] ?? null) : null,
            'tertiary_style' => $top3 > 0 ? ($ranked[2] ?? null) : null,
            'primary_strength' => $strengths[0],
            'secondary_strength' => $top2 > 0 ? $strengths[1] : null,
            'tertiary_strength' => $top3 > 0 ? $strengths[2] : null,
            'case' => $case,
        ];
    }
}
