<?php

namespace App\Models;

use Illuminate\Support\Facades\Cache;

class Setting extends BaseModel
{
    protected static function booted(): void
    {
        static::saved(function () {
            Cache::forget('bogis.settings');
        });

        static::deleted(function () {
            Cache::forget('bogis.settings');
        });
    }

    public static function get(string $key, $default = null)
    {
        $settings = Cache::rememberForever('bogis.settings', function () {
            return static::pluck('value', 'key')->toArray();
        });

        return $settings[$key] ?? $default;
    }

    public static function set(string $key, $value): void
    {
        static::updateOrCreate(['key' => $key], ['value' => $value]);
    }
}
