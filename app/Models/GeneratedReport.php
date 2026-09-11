<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GeneratedReport extends Model
{
    protected $fillable = [
        'quiz_result_id',
        'variant',
        'prompt_version',
        'style_content_version',
        'input_summary',
        'output',
        'status',
        'error',
        'generated_at',
    ];

    protected $casts = [
        'input_summary' => 'array',
        'output' => 'array',
        'generated_at' => 'datetime',
    ];

    public function quizResult()
    {
        return $this->belongsTo(QuizResult::class);
    }
}
