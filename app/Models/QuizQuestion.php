<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class QuizQuestion extends Model
{
    protected $fillable = [
        'question_key',
        'section',
        'room',
        'title',
        'folder',
        'sort_order',
        'max_selections',
        'weight',
        'image_display_mode',
    ];

    protected $casts = [
        'sort_order' => 'integer',
        'max_selections' => 'integer',
        'weight' => 'integer',
    ];

    public function options()
    {
        return $this->hasMany(QuizOption::class, 'question_id', 'question_key');
    }
}
