<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class QuizResult extends Model
{
    protected $fillable = [
        'uuid',
        'answers',
        'style_scores',
        'style_percentages',
        'primary_style',
        'secondary_style',
        'chosen_accent_colors',
        'tertiary_style',
        'primary_strength',
        'secondary_strength',
        'tertiary_strength',
        'case',
        'dominant_traits',
        'room_profiles',
        'color_preference',
    ];

    protected $casts = [
        'answers' => 'array',
        'style_scores' => 'array',
        'style_percentages' => 'array',
        'chosen_accent_colors' => 'array',
        'dominant_traits' => 'array',
        'room_profiles' => 'array',
    ];

    public function generatedReports()
    {
        return $this->hasMany(GeneratedReport::class);
    }

    public function submissions()
    {
        return $this->hasMany(Submission::class);
    }
}
