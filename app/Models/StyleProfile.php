<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** Eén rij per vaste woonstijl (zie QuizStructure::STYLES) — vervangt styleProfiles.js als bron van waarheid. */
class StyleProfile extends Model
{
    protected $fillable = [
        'style_key',
        'label',
        'slug',
        'subtitle',
        'long_description',
        'traits_intro',
        'core_traits',
        'hero_image',
        'base_colors',
        'accent_colors',
        'color_tip',
        'materials',
        'materials_tip',
        'furniture_shapes',
        'lighting',
        'accessories',
        'advice_primary',
        'advice_secondary',
        'advice_tertiary',
        'wat_past_goed',
        'wat_past_minder_goed',
        'recipe',
        'product_tags',
    ];

    protected $casts = [
        'core_traits' => 'array',
        'base_colors' => 'array',
        'accent_colors' => 'array',
        'materials' => 'array',
        'furniture_shapes' => 'array',
        'accessories' => 'array',
        'wat_past_goed' => 'array',
        'wat_past_minder_goed' => 'array',
        'recipe' => 'array',
        'product_tags' => 'array',
    ];

    public static function forStyle(string $styleKey): ?self
    {
        return static::query()->where('style_key', $styleKey)->first();
    }
}
