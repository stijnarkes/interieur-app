<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** Eén-rij-tabel voor quiz-brede instellingen — zie QuizScoringService::determineRanking(). */
class QuizSetting extends Model
{
    protected $fillable = [
        'primary_dominant_margin',
        'close_pair_margin',
        'close_triple_margin',
    ];

    protected $casts = [
        'primary_dominant_margin' => 'integer',
        'close_pair_margin' => 'integer',
        'close_triple_margin' => 'integer',
    ];

    public static function current(): self
    {
        return static::query()->firstOrCreate([]);
    }
}
