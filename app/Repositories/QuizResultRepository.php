<?php

namespace App\Repositories;

use App\Models\QuizResult;
use Illuminate\Support\Str;

class QuizResultRepository
{
    /**
     * @param  array<string, array<int, string>>  $answers
     * @param  array<string, mixed>  $computed  output van QuizScoringService::compute()
     */
    public function store(array $answers, array $computed): QuizResult
    {
        return QuizResult::create([
            'uuid' => (string) Str::uuid(),
            'answers' => $answers,
            'style_scores' => $computed['style_scores'],
            'primary_style' => $computed['primary_style'],
            'secondary_style' => $computed['secondary_style'],
        ]);
    }

    /**
     * Slaat de door de bezoeker gekozen accentkleuren gedenormaliseerd op ({id,name,hex} per
     * kleur) — zie AccentColor::toOptionArray(). Zo blijft een eerder opgeslagen resultaat/PDF
     * correct, ook als de kleur later in de admin hernoemd, van hex gewijzigd of verwijderd wordt.
     *
     * @param  array<int, array{id: int, name: string, hex: string}>  $colors
     */
    public function saveAccentColors(QuizResult $result, array $colors): QuizResult
    {
        $result->update(['chosen_accent_colors' => $colors]);

        return $result;
    }
}
