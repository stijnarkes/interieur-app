<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * Centrale kleurencatalogus voor de accentkleurstap ná de stijlberekening (zie
 * App\Services\AccentColorSelector). Een kleur mag bij meerdere woonstijlen horen — zelfde
 * `style_keys`-patroon als QuizOption::linkedStyleKeys().
 */
class AccentColor extends Model
{
    protected $fillable = [
        'name',
        'hex',
        'style_keys',
        'sort_order',
        'is_active',
    ];

    protected $casts = [
        'style_keys' => 'array',
        'is_active' => 'boolean',
    ];

    protected static function booted(): void
    {
        static::addGlobalScope('sorted', fn (Builder $query) => $query->orderBy('sort_order')->orderBy('id'));
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /** @return array<int, string> */
    public function linkedStyleKeys(): array
    {
        return array_values(array_unique($this->style_keys ?? []));
    }

    /** @return array{id: int, name: string, hex: string} — vorm die de klant-quiz en PDF gebruiken. */
    public function toOptionArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'hex' => $this->hex,
        ];
    }
}
