<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** Eén-rij-tabel voor quiz-brede instellingen die niet bij een specifieke vraag/optie horen — zie de migratie voor uitleg. */
class QuizSetting extends Model
{
    protected $fillable = [
        'color_preference_max_selections',
    ];

    protected $casts = [
        'color_preference_max_selections' => 'integer',
    ];

    public static function current(): self
    {
        return static::query()->firstOrCreate([], ['color_preference_max_selections' => 1]);
    }
}
