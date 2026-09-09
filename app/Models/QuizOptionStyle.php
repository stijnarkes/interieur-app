<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** Eén koppeling tussen een antwoordoptie en een woonstijl waar die punten aan geeft — zie QuizOption::styleKeys()/syncStyles(). */
class QuizOptionStyle extends Model
{
    protected $fillable = [
        'option_id',
        'style_key',
    ];

    public function option()
    {
        return $this->belongsTo(QuizOption::class, 'option_id');
    }
}
