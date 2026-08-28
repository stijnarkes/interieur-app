<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class QuizPalette extends Model
{
    protected $fillable = [
        'palette_key',
        'name',
        'sort_order',
    ];

    protected $casts = [
        'sort_order' => 'integer',
    ];

    public function colors(): HasMany
    {
        return $this->hasMany(QuizPaletteColor::class);
    }
}
