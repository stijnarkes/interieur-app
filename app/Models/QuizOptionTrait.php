<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** Eén koppeling tussen een antwoordoptie en een trait, met een gewicht — zie QuizOptionStyle. */
class QuizOptionTrait extends Model
{
    protected $fillable = [
        'option_id',
        'trait_id',
        'weight',
    ];

    protected $casts = [
        'weight' => 'integer',
    ];

    public function traitRecord()
    {
        return $this->belongsTo(QuizTrait::class, 'trait_id');
    }

    public function option()
    {
        return $this->belongsTo(QuizOption::class, 'option_id');
    }
}
