<?php

namespace App\Support;

use App\Models\QuizOption;

/**
 * Zet ruwe quiz_answers (question_key => [option_slug, ...]) om naar een leesbare, geordende
 * doorloop van alle negen vragen met de daadwerkelijk gekozen optie(s) — titel, afbeelding,
 * productnaam en interne notitie. Gebruikt door het stylisten-overzicht (SubmissionResource,
 * onderdeel "Gekozen antwoorden"). Een overgeslagen vraag (geen enkele optie gekozen) blijft in de
 * lijst staan met `skipped: true` i.p.v. stilzwijgend te verdwijnen — zo blijft in één oogopslag
 * zichtbaar dát een vraag is overgeslagen, niet alleen dat 'ie ontbreekt. Een optie die niet meer
 * bestaat (verwijderd na inzending) wordt gewoon overgeslagen — geen foutmelding, veilig gedrag
 * voor historische resultaten.
 */
class QuizAnswerBreakdown
{
    /**
     * @param  array<string, array<int, string>>  $answers
     * @return array<int, array{question: string, skipped: bool, options: array<int, array{title: string, image: string, productName: ?string, internalNote: ?string}>}>
     */
    public static function build(?array $answers): array
    {
        if (! $answers) {
            return [];
        }

        $optionSlugs = collect($answers)->flatten()->filter()->unique()->values()->all();
        $options = QuizOption::query()->whereIn('option_slug', $optionSlugs)->get()->keyBy('option_slug');

        $breakdown = [];

        foreach (QuizStructure::questions() as $questionKey => $question) {
            $chosenSlugs = $answers[$questionKey] ?? [];

            $chosenOptions = collect($chosenSlugs)
                ->map(fn (string $slug) => $options->get($slug))
                ->filter()
                ->map(fn (QuizOption $option): array => [
                    'title' => $option->title,
                    'image' => $option->publicImageUrl(),
                    'productName' => $option->product_name,
                    'internalNote' => $option->internal_note,
                ])
                ->values()
                ->all();

            $breakdown[] = [
                'question' => $question['title'],
                'skipped' => $chosenOptions === [],
                'options' => $chosenOptions,
            ];
        }

        return $breakdown;
    }
}
