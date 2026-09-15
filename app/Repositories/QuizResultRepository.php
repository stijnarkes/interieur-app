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
}
