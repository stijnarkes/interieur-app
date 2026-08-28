<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Submission extends Model
{
    protected $fillable = [
        'style',
        'quiz_answers',
        'quiz_result',
        'name',
        'email',
        'email_opt_in',
        'result_id',
        'result_generated',
        'pdf_path',
        'email_status',
        'email_sent_at',
        'email_error',
    ];

    protected function casts(): array
    {
        return [
            'quiz_answers' => 'array',
            'quiz_result' => 'array',
            'email_opt_in' => 'boolean',
            'result_generated' => 'boolean',
            'email_sent_at' => 'datetime',
        ];
    }
}
