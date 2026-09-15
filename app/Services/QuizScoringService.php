<?php

namespace App\Services;

use App\Models\QuizOption;
use App\Models\QuizQuestion;
use App\Models\QuizSetting;
use App\Support\QuizStructure;

/**
 * Servergestuurde, autoritatieve berekening van een quizresultaat. Transparante, simpele regels
 * (zie de opdracht "vereenvoudiging woonstijltest"):
 *
 * 1. Elke vraag heeft een totaal te verdelen gewicht (QuizQuestion::weight).
 * 2. Bij één gekozen optie krijgt die optie het volledige vraaggewicht; bij meerdere gekozen
 *    opties (max_selections > 1) deelt elke gekozen optie dat gewicht gelijk — twee keuzes maken
 *    een vraag dus nooit zwaarder dan één keuze.
 * 3. Elke aan een optie gekoppelde stijl (een optie mag bij meerdere stijlen passen, zie
 *    QuizOption::linkedStyleKeys()) krijgt vervolgens de VOLLEDIGE punten van die optie — geen
 *    verdere deling over de gekoppelde stijlen.
 * 4. De stijl met de hoogste totaalscore is de basisstijl. Een tweede stijl wordt alleen als
 *    "invloed" getoond als ze minstens een instelbaar percentage van de basisscore haalt (zie
 *    QuizSetting::secondary_influence_ratio) én in minstens 2 verschillende vragen punten kreeg.
 *    Nooit een derde stijl.
 *
 * Een optie zonder gekoppelde stijl (onvolledig, zie QuizOption::linkedStyleKeys()) draagt bewust
 * 0 punten bij aan geen enkele stijl — er wordt nooit een stijl verzonnen.
 */
class QuizScoringService
{
    /**
     * @param  array<string, array<int, string>>  $answers  questionId => geselecteerde option_slugs
     * @return array{
     *     style_scores: array<string, float>,
     *     primary_style: ?string,
     *     secondary_style: ?string,
     * }
     */
    public function compute(array $answers): array
    {
        $explanation = $this->explain($answers);

        return [
            'style_scores' => $explanation['style_scores'],
            'primary_style' => $explanation['primary_style'],
            'secondary_style' => $explanation['secondary_style'],
        ];
    }

    /**
     * Zoals compute(), maar geeft ook de tussenstappen terug (per stijl: uit hoeveel vragen ze
     * punten kreeg, en of ze aan de invloed-eis voldeed) — gebruikt door het admin-debugscherm om
     * te laten zien wáárom een resultaat zo uitpakte, zonder dat daarvoor iets extra's op
     * QuizResult opgeslagen hoeft te worden (alles is hier deterministisch te herleiden uit de
     * al opgeslagen ruwe antwoorden).
     *
     * @param  array<string, array<int, string>>  $answers  questionId => geselecteerde option_slugs
     * @return array{
     *     style_scores: array<string, float>,
     *     style_question_counts: array<string, int>,
     *     secondary_influence_ratio: int,
     *     primary_style: ?string,
     *     secondary_style: ?string,
     * }
     */
    public function explain(array $answers): array
    {
        $options = QuizOption::query()
            ->whereIn('option_slug', collect($answers)->flatten()->filter()->unique()->values()->all())
            ->get()
            ->keyBy('option_slug');

        $questions = QuizQuestion::query()->get()->keyBy('question_key');

        $styleScores = array_fill_keys(QuizStructure::styleKeys(), 0.0);
        $styleQuestionCounts = array_fill_keys(QuizStructure::styleKeys(), 0);

        foreach ($answers as $questionId => $optionIds) {
            $question = $questions->get($questionId);
            if (! $question) {
                continue;
            }

            $chosenOptions = collect($optionIds)
                ->map(fn (string $optionId) => $options->get($optionId))
                ->filter();

            if ($chosenOptions->isEmpty()) {
                continue;
            }

            // Regel 2: het vraaggewicht wordt gelijk verdeeld over de gekozen opties binnen déze
            // vraag — dus nooit hoger totaal dan het vraaggewicht, ongeacht hoeveel er gekozen zijn.
            $pointsPerOption = $question->weight / $chosenOptions->count();

            $stylesToppedUpThisQuestion = [];

            foreach ($chosenOptions as $option) {
                // Regel 3: elke gekoppelde stijl krijgt de volledige punten van de optie, niet
                // verder verdeeld over de gekoppelde stijlen. Een stijl-key die niet (meer) in
                // QuizStructure::STYLES staat (bv. een vervallen stijl waarvoor een optie nog niet
                // herkoppeld is) telt bewust nergens voor mee — nooit een verwijderde stijl als
                // quizuitslag.
                foreach ($option->linkedStyleKeys() as $styleKey) {
                    if (! array_key_exists($styleKey, $styleScores)) {
                        continue;
                    }

                    $styleScores[$styleKey] += $pointsPerOption;
                    $stylesToppedUpThisQuestion[$styleKey] = true;
                }
            }

            foreach (array_keys($stylesToppedUpThisQuestion) as $styleKey) {
                $styleQuestionCounts[$styleKey]++;
            }
        }

        $secondaryInfluenceRatio = QuizSetting::current()->secondary_influence_ratio;
        $result = $this->determineResult($styleScores, $styleQuestionCounts, $secondaryInfluenceRatio);

        return [
            'style_scores' => $styleScores,
            'style_question_counts' => $styleQuestionCounts,
            'secondary_influence_ratio' => $secondaryInfluenceRatio,
            'primary_style' => $result['primary'],
            'secondary_style' => $result['secondary'],
        ];
    }

    /**
     * Pure functie (geen DB-calls) zodat dit met handmatige score-arrays unit-getest kan worden.
     *
     * @param  array<string, float>  $styleScores  style_key => opgeteld aantal punten
     * @param  array<string, int>  $styleQuestionCounts  style_key => aantal verschillende vragen dat punten gaf
     * @return array{primary: ?string, secondary: ?string}
     */
    public function determineResult(array $styleScores, array $styleQuestionCounts, int $secondaryInfluenceRatio): array
    {
        // Gelijke stand: het eerst-gedeclareerde style-key in QuizStructure::STYLES wint — vast,
        // voorspelbaar gedrag (zie test "gelijke scores / deterministische uitslag"). arsort()
        // sorteert in PHP 8+ stabiel, en $styleScores staat al in QuizStructure-volgorde
        // (opgebouwd via array_fill_keys(QuizStructure::styleKeys(), ...)), dus die volgorde
        // blijft bij gelijke scores behouden.
        $ordered = $styleScores;
        arsort($ordered);

        $styleKeysByScore = array_keys($ordered);
        $primary = $styleKeysByScore[0] ?? null;

        if ($primary === null || $ordered[$primary] <= 0) {
            return ['primary' => null, 'secondary' => null];
        }

        $primaryScore = $ordered[$primary];
        $secondary = null;

        foreach (array_slice($styleKeysByScore, 1) as $candidate) {
            $candidateScore = $ordered[$candidate];

            if ($candidateScore <= 0) {
                break;
            }

            $meetsRatio = $candidateScore >= ($primaryScore * $secondaryInfluenceRatio / 100);
            $meetsSpread = ($styleQuestionCounts[$candidate] ?? 0) >= 2;

            if ($meetsRatio && $meetsSpread) {
                $secondary = $candidate;
            }

            break; // nooit verder dan de op-één-na-hoogste stijl bekijken — nooit een derde stijl.
        }

        return ['primary' => $primary, 'secondary' => $secondary];
    }
}
