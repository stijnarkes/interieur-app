<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** Eén rij per vast overgangsscherm (zie QuizStructure::SECTIONS) — admin-beheerbaar via TekstenPage. */
class QuizTransitionSection extends Model
{
    protected $fillable = [
        'section_id',
        'title',
        'tagline',
        'wrap_up',
        'cta',
    ];

    public static function forSection(string $sectionId): ?self
    {
        return static::query()->where('section_id', $sectionId)->first();
    }
}
