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
    public function store(array $answers, array $computed, ?string $colorPreference = null): QuizResult
    {
        $ranking = $computed['ranking'];

        return QuizResult::create([
            'uuid' => (string) Str::uuid(),
            'answers' => $answers,
            'style_scores' => $computed['style_scores'],
            'style_percentages' => $computed['style_percentages'],
            'primary_style' => $ranking['primary_style'],
            'secondary_style' => $ranking['secondary_style'],
            'tertiary_style' => $ranking['tertiary_style'],
            'primary_strength' => $ranking['primary_strength'],
            'secondary_strength' => $ranking['secondary_strength'],
            'tertiary_strength' => $ranking['tertiary_strength'],
            'case' => $ranking['case'],
            'dominant_traits' => $computed['dominant_traits'],
            'room_profiles' => $computed['room_profiles'],
            'color_preference' => $colorPreference,
        ]);
    }
}
