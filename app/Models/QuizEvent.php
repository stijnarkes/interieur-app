<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Minimale, PII-vrije trechtertelling voor de hoofdquiz (zie App\Models\PartnerEvent voor
 * hetzelfde patroon bij de partnerfunctie) — gebruikt door StatsPage/QuizFunnelChartWidget om te
 * laten zien hoeveel bezoekers starten, per vraag verdergaan, de test afronden, en uiteindelijk
 * hun gegevens achterlaten.
 */
class QuizEvent extends Model
{
    public const STARTED = 'quiz_started';

    public const QUESTION_REACHED = 'question_reached';

    public const COMPLETED = 'quiz_completed';

    public const LEAD_SUBMITTED = 'lead_submitted';

    protected $fillable = [
        'name',
        'question_key',
    ];

    public static function record(string $name, ?string $questionKey = null): self
    {
        return static::create(['name' => $name, 'question_key' => $questionKey]);
    }
}
