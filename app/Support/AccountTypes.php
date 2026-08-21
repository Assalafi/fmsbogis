<?php

namespace App\Support;

class AccountTypes
{
    public const TYPES = ['capital', 'overhead', 'personnel'];

    public const LABELS = [
        'capital' => 'Capital',
        'overhead' => 'Overhead',
        'personnel' => 'Personnel',
    ];

    public static function badgeColor(?string $type): string
    {
        return match ($type) {
            'capital' => 'dark',
            'personnel' => 'warning',
            default => 'info',
        };
    }

    public static function options(): array
    {
        return self::TYPES;
    }

    public static function label(?string $type): string
    {
        return self::LABELS[$type] ?? ucfirst((string) $type);
    }
}
