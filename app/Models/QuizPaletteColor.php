<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class QuizPaletteColor extends Model
{
    protected $fillable = [
        'quiz_palette_id',
        'name',
        'hex',
        'sort_order',
    ];

    protected $casts = [
        'sort_order' => 'integer',
    ];

    public function palette(): BelongsTo
    {
        return $this->belongsTo(QuizPalette::class);
    }
}
