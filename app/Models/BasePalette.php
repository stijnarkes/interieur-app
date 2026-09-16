<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * Catalogus van basispaletten waaruit de bezoeker er ná de stijlberekening één kiest (zie
 * App\Services\AccentColorSelector voor het vergelijkbare, maar wél over stijlen gedeelde
 * accentkleuren-patroon). Anders dan AccentColor hoort een basispalet altijd bij precies één
 * stijl — elke stijl heeft zijn eigen, bewust samengestelde paletten, dus een los `style_key`
 * in plaats van een gedeelde `style_keys`-array.
 */
class BasePalette extends Model
{
    protected $fillable = [
        'style_key',
        'name',
        'description',
        'colors',
        'sort_order',
        'is_active',
    ];

    protected $casts = [
        'colors' => 'array',
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

    public function scopeForStyle(Builder $query, string $styleKey): Builder
    {
        return $query->where('style_key', $styleKey);
    }

    /** @return array{id: int, name: string, description: ?string, colors: array} — vorm die de klant-quiz en PDF gebruiken. */
    public function toOptionArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'description' => $this->description,
            'colors' => $this->colors ?? [],
        ];
    }
}
