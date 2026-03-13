<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Submission extends Model
{
    protected $fillable = [
        'style',
        'mood_words',
        'colors',
        'note',
        'has_room_photo',
        'room_photo_path',
        'name',
        'email',
        'email_opt_in',
        'result_id',
        'result_generated',
        'advice_bullets',
        'palette',
        'materials',
        'layout_tips',
        'product_ideas',
        'moodboard_generated',
        'room_preview_generated',
        'moodboard_path',
        'inspiration_path',
        'pdf_path',
        'email_status',
        'email_sent_at',
        'email_error',
    ];

    protected function casts(): array
    {
        return [
            'has_room_photo'        => 'boolean',
            'email_opt_in'          => 'boolean',
            'result_generated'      => 'boolean',
            'moodboard_generated'   => 'boolean',
            'room_preview_generated' => 'boolean',
            'advice_bullets'        => 'array',
            'palette'               => 'array',
            'materials'             => 'array',
            'layout_tips'           => 'array',
            'product_ideas'         => 'array',
            'email_sent_at'         => 'datetime',
        ];
    }
}
