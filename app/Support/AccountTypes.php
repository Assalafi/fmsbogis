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

    /**
     * Detect the economic code type from the code's starting digits.
     * Standard Nigerian government classification:
     *   1...  → Revenue
     *   21... → Expense / Personnel
     *   22... → Expense / Overhead
     *   23... → Expense / Capital
     *
     * @return array{type: string, account_type: string|null}|null
     */
    public static function detectFromCode(string $code): ?array
    {
        $code = trim($code);

        if ($code === '') {
            return null;
        }

        if (str_starts_with($code, '21')) {
            return ['type' => 'expense', 'account_type' => 'personnel'];
        }

        if (str_starts_with($code, '22')) {
            return ['type' => 'expense', 'account_type' => 'overhead'];
        }

        if (str_starts_with($code, '23')) {
            return ['type' => 'expense', 'account_type' => 'capital'];
        }

        if (str_starts_with($code, '1')) {
            return ['type' => 'revenue', 'account_type' => null];
        }

        return null;
    }

    /**
     * Client-side detection hint: prefix → [type, account_type].
     */
    public static function detectRules(): array
    {
        return [
            '21' => ['expense', 'personnel'],
            '22' => ['expense', 'overhead'],
            '23' => ['expense', 'capital'],
            '1' => ['revenue', ''],
        ];
    }
}
