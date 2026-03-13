<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PromptSetting extends Model
{
    protected $fillable = [
        'key',
        'label',
        'value',
    ];

    public static function getByKey(string $key, string $default = ''): string
    {
        $setting = static::where('key', $key)->first();

        return $setting ? $setting->value : $default;
    }
}
