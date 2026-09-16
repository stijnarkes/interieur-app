<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Redactioneel beheerde combinatietips voor de partnerfunctie (zie het implementatieplan
 * "Partnerfunctie: Ontdek jullie gezamenlijke woonstijl", sectie 7). 21 vaste records: 6 stijlen
 * geven 15 unieke paren + 6 zelfde-stijl-combinaties. `style_key_a`/`style_key_b` liggen altijd
 * alfabetisch gesorteerd opgeslagen zodat een paar maar op één manier bestaat — forPair() regelt
 * die sortering zodat de aanroeper zich nooit zorgen hoeft te maken over de volgorde waarin
 * initiator/partner hun stijl hebben (Japandi+Hotel luxe == Hotel luxe+Japandi).
 */
class StyleCombinationAdvice extends Model
{
    // "advice" is in het Engels ontelbaar — Laravels standaard meervoudsvorming voor de
    // tabelnaam zou hier ten onrechte op "style_combination_advice" (enkelvoud) uitkomen.
    protected $table = 'style_combination_advices';

    protected $fillable = [
        'style_key_a',
        'style_key_b',
        'title',
        'intro',
        'basis_tip',
        'materials_tip',
        'accent_tip',
        'base_palette_style_key',
        'status',
        'version',
    ];

    protected $casts = [
        'version' => 'integer',
    ];

    public function scopePublished($query)
    {
        return $query->where('status', 'published');
    }

    /** Sorteert de twee stijl-keys alfabetisch vóór opslag/lookup — garandeert symmetrie. */
    public static function forPair(string $styleKeyA, string $styleKeyB): ?self
    {
        [$a, $b] = self::canonicalPair($styleKeyA, $styleKeyB);

        return static::query()->where('style_key_a', $a)->where('style_key_b', $b)->first();
    }

    /** @return array{0: string, 1: string} */
    public static function canonicalPair(string $styleKeyA, string $styleKeyB): array
    {
        $pair = [$styleKeyA, $styleKeyB];
        sort($pair);

        return $pair;
    }
}
