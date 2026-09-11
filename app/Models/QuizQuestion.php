<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class QuizQuestion extends Model
{
    protected $fillable = [
        'question_key',
        'section',
        'title',
        'folder',
        'sort_order',
        'max_selections',
        'image_display_mode',
    ];

    protected $casts = [
        'sort_order' => 'integer',
        'max_selections' => 'integer',
    ];

    public function options()
    {
        return $this->hasMany(QuizOption::class, 'question_id', 'question_key');
    }
}
