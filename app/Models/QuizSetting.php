<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Eén-rij-tabel voor quiz-brede instellingen — zie QuizScoringService::determineResult().
 * `primary_dominant_margin`/`close_pair_margin`/`close_triple_margin`/`secondary_influence_ratio`
 * bestaan nog in de database (non-destructief, hoorden bij eerdere scoringversies) maar worden
 * niet meer gebruikt — sinds de uitslagScore-gebaseerde berekening (zie QuizAnswerScoreMatrix)
 * bepaalt `secondary_influence_max_gap` of een tweede stijl als "invloed" getoond wordt.
 */
class QuizSetting extends Model
{
    protected $fillable = [
        'secondary_influence_ratio',
        'secondary_influence_max_gap',
        'partner_feature_enabled',
    ];

    protected $casts = [
        'secondary_influence_ratio' => 'integer',
        'secondary_influence_max_gap' => 'float',
        'partner_feature_enabled' => 'boolean',
    ];

    public static function current(): self
    {
        return static::query()->firstOrCreate([]);
    }
}
