<?php

namespace App\Services;

use App\Models\QuizOption;
use App\Models\QuizQuestion;
use App\Models\QuizSetting;
use App\Support\QuizAnswerScoreMatrix;

/**
 * Servergestuurde, autoritatieve berekening van een quizresultaat — zie de opdracht "scoring
 * woonstijltest Boer Staphorst". Elke antwoordoptie heeft in QuizAnswerScoreMatrix een vaste,
 * onafhankelijke 0-1-matchscore per stijl (niet per se optellend tot 1). Per beantwoorde vraag telt
 * de score van de gekozen optie mee, met vraaggewicht (QuizQuestion::weight — 1,5 voor de keuken-/
 * badkamervraag, anders 1,0). Bij meubelstof en verlichting mag een bezoeker twee opties kiezen
 * (niet verplicht, zie QuizQuestion::max_selections); in dat geval is de "gekozen score" per stijl
 * het gemiddelde van de scores van die twee opties binnen dezelfde vraag, en wordt pas dáárna het
 * vraaggewicht toegepast — nooit de twee scores los meetellen (dat zou een dubbele keuze
 * onbedoeld zwaarder laten wegen dan één keuze).
 *
 * Een rechtstreekse vergelijking van de opgetelde ruwe scores bevoordeelt structureel de stijl
 * waarvan de meeste losse afbeeldingen toch al wat "mee scoren" (zie Modern in de brontabel).
 * Daarom wordt per stijl ook berekend hoe een score zich verhoudt tot wat je "toevallig" zou
 * verwachten (het gemiddelde van alle opties binnen dezelfde beantwoorde vragen) en hoe veel die
 * vragen normaal gesproken uiteenlopen (de spreiding daarvan) — zie uitslagScore() hieronder. De
 * hoofdstijl is de stijl met de hoogste uitslagScore, niet de hoogste ruwe som.
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
     * Zoals compute(), maar geeft ook de tussenstappen terug (per stijl: de genormaliseerde
     * uitslagScore, en of de op-één-na-hoogste stijl aan de "invloed"-eisen voldeed) — gebruikt
     * door het admin-debugscherm om te laten zien wáárom een resultaat zo uitpakte, zonder dat
     * daarvoor iets extra's op QuizResult opgeslagen hoeft te worden (alles is hier deterministisch
     * te herleiden uit de al opgeslagen ruwe antwoorden).
     *
     * @param  array<string, array<int, string>>  $answers  questionId => geselecteerde option_slugs
     * @return array{
     *     style_scores: array<string, float>,
     *     uitslag_scores: array<string, float>,
     *     secondary_influence_max_gap: float,
     *     primary_style: ?string,
     *     secondary_style: ?string,
     * }
     */
    public function explain(array $answers): array
    {
        $questions = QuizQuestion::query()->get()->keyBy('question_key');

        $optionIdsInAnswers = collect($answers)->flatten()->filter()->unique()->values()->all();
        $chosenOptions = QuizOption::query()
            ->whereIn('option_slug', $optionIdsInAnswers)
            ->get()
            ->keyBy('option_slug');

        // Alle actieve opties per vraag — dit zijn precies de opties waar een bezoeker uit kon
        // kiezen, en dus de juiste populatie voor "gemiddelde/spreiding binnen deze vraag". Eén
        // aparte query per vraag i.p.v. alles in één keer op te halen: er zijn hoogstens 9 vragen,
        // en dit blijft zo het makkelijkst te volgen.
        $activeOptionsByQuestion = [];

        $styleKeys = QuizAnswerScoreMatrix::styleKeys();
        $ruwTotaal = array_fill_keys($styleKeys, 0.0);
        $normaalTotaal = array_fill_keys($styleKeys, 0.0);
        $spreidingSom = array_fill_keys($styleKeys, 0.0); // som van gewicht² × variantie
        // Per stijl: op hoeveel afzonderlijke beantwoorde vragen scoorde de gekozen optie voor die
        // stijl boven het vraaggemiddelde van die stijl — zie de "invloed"-eis hieronder.
        $abovePerQuestionAverageCount = array_fill_keys($styleKeys, 0);
        $answeredQuestionCount = 0;

        foreach ($answers as $questionId => $optionIds) {
            $question = $questions->get($questionId);
            if (! $question) {
                continue;
            }

            // Eén of twee gekozen opties (zie klassedocblok) — dubbele/onbekende option_slugs eruit
            // gefilterd i.p.v. te crashen op onverwachte/verouderde invoer.
            $chosenOptionModels = collect($optionIds)
                ->filter()
                ->unique()
                ->map(fn (string $optionId) => $chosenOptions->get($optionId))
                ->filter()
                ->values();

            if ($chosenOptionModels->isEmpty()) {
                continue;
            }

            if (! array_key_exists($questionId, $activeOptionsByQuestion)) {
                $activeOptionsByQuestion[$questionId] = QuizOption::query()
                    ->where('question_id', $questionId)
                    ->where('is_active', true)
                    ->get();
            }

            $optionsInQuestion = $activeOptionsByQuestion[$questionId];
            if ($optionsInQuestion->isEmpty()) {
                continue;
            }

            $weight = (float) $question->weight;
            $answeredQuestionCount++;

            foreach ($styleKeys as $styleKey) {
                $scoresForStyle = $optionsInQuestion
                    ->map(fn (QuizOption $option): float => QuizAnswerScoreMatrix::scoresFor($option->option_slug)[$styleKey] ?? 0.0)
                    ->all();

                $optionCount = count($scoresForStyle);
                $mean = array_sum($scoresForStyle) / $optionCount;
                $variance = array_sum(array_map(fn (float $v): float => ($v - $mean) ** 2, $scoresForStyle)) / $optionCount;

                // Bij twee gekozen opties (meubelstof/verlichting): het gemiddelde van hun beider
                // scores voor deze stijl — bij één gekozen optie is dat gewoon die ene score.
                $chosenScoresForStyle = $chosenOptionModels
                    ->map(fn (QuizOption $option): float => QuizAnswerScoreMatrix::scoresFor($option->option_slug)[$styleKey] ?? 0.0);
                $chosenScore = $chosenScoresForStyle->sum() / $chosenScoresForStyle->count();

                $ruwTotaal[$styleKey] += $weight * $chosenScore;
                $normaalTotaal[$styleKey] += $weight * $mean;
                $spreidingSom[$styleKey] += ($weight ** 2) * $variance;

                if ($chosenScore > $mean) {
                    $abovePerQuestionAverageCount[$styleKey]++;
                }
            }
        }

        $uitslagScores = [];
        foreach ($styleKeys as $styleKey) {
            $spreiding = sqrt($spreidingSom[$styleKey]);
            // Spreiding kan alleen 0 zijn als elke optie in elke beantwoorde vraag exact dezelfde
            // score voor deze stijl had — dan is de gekozen score per definitie ook gelijk aan het
            // gemiddelde (ruwTotaal - normaalTotaal is dan ook 0), dus 0 is hier geen gokwaarde maar
            // het enige consistente antwoord: geen enkele afwijking van "verwacht" mogelijk.
            $uitslagScores[$styleKey] = $spreiding > 0.0
                ? ($ruwTotaal[$styleKey] - $normaalTotaal[$styleKey]) / $spreiding
                : 0.0;
        }

        $secondaryInfluenceMaxGap = (float) QuizSetting::current()->secondary_influence_max_gap;
        $result = $this->determineResult($uitslagScores, $abovePerQuestionAverageCount, $answeredQuestionCount, $secondaryInfluenceMaxGap);

        return [
            'style_scores' => $ruwTotaal,
            'uitslag_scores' => $uitslagScores,
            'secondary_influence_max_gap' => $secondaryInfluenceMaxGap,
            'primary_style' => $result['primary'],
            'secondary_style' => $result['secondary'],
        ];
    }

    /**
     * Pure functie (geen DB-calls) zodat dit met handmatige score-arrays unit-getest kan worden.
     *
     * @param  array<string, float>  $uitslagScores  style_key => genormaliseerde uitslagScore
     * @param  array<string, int>  $abovePerQuestionAverageCount  style_key => aantal beantwoorde
     *   vragen waarin de gekozen optie boven het vraaggemiddelde van die stijl scoorde
     * @return array{primary: ?string, secondary: ?string}
     */
    public function determineResult(
        array $uitslagScores,
        array $abovePerQuestionAverageCount,
        int $answeredQuestionCount,
        float $secondaryInfluenceMaxGap,
    ): array {
        if ($answeredQuestionCount === 0) {
            return ['primary' => null, 'secondary' => null];
        }

        // Volgorde uit QuizAnswerScoreMatrix::styleKeys() blijft behouden bij een exact gelijke
        // stand (arsort() sorteert in PHP 8+ stabiel) — vast, voorspelbaar gedrag bij een tie die
        // niet aan de top zit.
        $ordered = $uitslagScores;
        arsort($ordered);

        $styleKeysByScore = array_keys($ordered);
        $primary = $styleKeysByScore[0];
        $primaryScore = $ordered[$primary];

        // Exact gelijke hoogste uitslagScore: mag als twee stijlen getoond worden (zie de
        // opdracht) — dus geen van de drie "invloed"-eisen hieronder geldt voor deze tweede stijl,
        // die staat op precies gelijke voet met de hoofdstijl.
        $tiedWithPrimary = array_slice($styleKeysByScore, 1, null, true);
        foreach ($tiedWithPrimary as $candidate) {
            if (abs($ordered[$candidate] - $primaryScore) < 1e-9) {
                return ['primary' => $primary, 'secondary' => $candidate];
            }

            break; // $ordered is aflopend gesorteerd: geen tie aan de top als de eerstvolgende al lager is.
        }

        $candidate = $styleKeysByScore[1] ?? null;
        if ($candidate === null) {
            return ['primary' => $primary, 'secondary' => null];
        }

        $candidateScore = $ordered[$candidate];

        $meetsPositive = $candidateScore > 0.0;
        $meetsGap = abs($primaryScore - $candidateScore) <= $secondaryInfluenceMaxGap;
        $meetsSpread = ($abovePerQuestionAverageCount[$candidate] ?? 0) >= 2;

        $secondary = ($meetsPositive && $meetsGap && $meetsSpread) ? $candidate : null;

        return ['primary' => $primary, 'secondary' => $secondary];
    }
}
