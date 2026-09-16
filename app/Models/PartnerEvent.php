<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Minimale, PII-vrije telling voor de partnerfunctie (zie het implementatieplan, sectie 11) — geen
 * tracking-script, geen consent nodig: puur server-side eigen telling van event-namen.
 */
class PartnerEvent extends Model
{
    protected $fillable = [
        'partner_link_id',
        'name',
    ];

    public static function record(string $name, ?int $partnerLinkId = null): self
    {
        return static::create(['name' => $name, 'partner_link_id' => $partnerLinkId]);
    }
}
