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

    /**
     * Slaat het door de bezoeker gekozen basispalet gedenormaliseerd op ({id,name,description,
     * colors}) — zie BasePalette::toOptionArray(). Zelfde denormalisatieredenering als
     * saveAccentColors(): een later gewijzigde/verwijderde catalogusrij mag een al opgeslagen
     * resultaat/PDF nooit met terugwerkende kracht veranderen.
     *
     * @param  array{id: int, name: string, description: ?string, colors: array}  $palette
     */
    public function saveBasePalette(QuizResult $result, array $palette): QuizResult
    {
        $result->update(['chosen_base_palette' => $palette]);

        return $result;
    }
}
