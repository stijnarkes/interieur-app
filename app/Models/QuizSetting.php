<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Eén-rij-tabel voor quiz-brede instellingen — zie QuizScoringService::determineResult().
 * `primary_dominant_margin`/`close_pair_margin`/`close_triple_margin` bestaan nog in de database
 * (non-destructief, hoorden bij de vorige, complexere scoring) maar worden niet meer gebruikt.
 */
class QuizSetting extends Model
{
    protected $fillable = [
        'secondary_influence_ratio',
        'partner_feature_enabled',
    ];

    protected $casts = [
        'secondary_influence_ratio' => 'integer',
        'partner_feature_enabled' => 'boolean',
    ];

    public static function current(): self
    {
        return static::query()->firstOrCreate([]);
    }
}
