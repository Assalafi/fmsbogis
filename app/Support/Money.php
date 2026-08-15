<?php

namespace App\Support;

class Money
{
    public static function add(string|int|float|null ...$values): string
    {
        $total = '0.00';
        foreach ($values as $value) {
            $total = bcadd($total, self::normalize($value), 2);
        }

        return $total;
    }

    public static function sub(string|int|float|null $a, string|int|float|null $b): string
    {
        return bcsub(self::normalize($a), self::normalize($b), 2);
    }

    public static function mul(string|int|float|null $a, string|int|float|null $b): string
    {
        return bcmul(self::normalize($a), self::normalize($b), 2);
    }

    public static function div(string|int|float|null $a, string|int|float|null $b, int $scale = 4): string
    {
        if (self::isZero($b)) {
            return '0.00';
        }

        return bcdiv(self::normalize($a), self::normalize($b), $scale);
    }

    public static function normalize(string|int|float|null $value): string
    {
        if ($value === null || $value === '') {
            return '0.00';
        }

        $value = (string) $value;

        if (str_contains($value, '.')) {
            [$whole, $decimal] = explode('.', $value, 2);
            $decimal = str_pad(substr($decimal, 0, 2), 2, '0');

            return $whole.'.'.$decimal;
        }

        return $value.'.00';
    }

    public static function format(string|int|float|null $value): string
    {
        return number_format((float) self::normalize($value), 2);
    }

    public static function isZero(string|int|float|null $value): bool
    {
        return bccomp(self::normalize($value), '0.00', 2) === 0;
    }

    public static function isNegative(string|int|float|null $value): bool
    {
        return bccomp(self::normalize($value), '0.00', 2) === -1;
    }

    public static function compare(string|int|float|null $a, string|int|float|null $b): int
    {
        return bccomp(self::normalize($a), self::normalize($b), 2);
    }

    public static function inWords(string|int|float|null $amount): string
    {
        $amount = self::normalize($amount);
        [$naira, $kobo] = explode('.', $amount, 2);

        $words = self::integerToWords((int) $naira);
        $result = $words.' Naira';

        if ((int) $kobo > 0) {
            $result .= ' and '.self::integerToWords((int) $kobo).' Kobo';
        }

        return $result.' Only';
    }

    protected static function integerToWords(int $number): string
    {
        if ($number === 0) {
            return 'Zero';
        }

        $ones = ['', 'One', 'Two', 'Three', 'Four', 'Five', 'Six', 'Seven', 'Eight', 'Nine',
            'Ten', 'Eleven', 'Twelve', 'Thirteen', 'Fourteen', 'Fifteen', 'Sixteen',
            'Seventeen', 'Eighteen', 'Nineteen'];
        $tens = ['', '', 'Twenty', 'Thirty', 'Forty', 'Fifty', 'Sixty', 'Seventy', 'Eighty', 'Ninety'];

        $words = '';

        if ($number >= 1000000000000) {
            $words .= self::lessThanThousand((int) floor($number / 1000000000000), $ones, $tens).' Trillion ';
            $number %= 1000000000000;
        }

        if ($number >= 1000000000) {
            $words .= self::lessThanThousand((int) floor($number / 1000000000), $ones, $tens).' Billion ';
            $number %= 1000000000;
        }

        if ($number >= 1000000) {
            $words .= self::lessThanThousand((int) floor($number / 1000000), $ones, $tens).' Million ';
            $number %= 1000000;
        }

        if ($number >= 1000) {
            $words .= self::lessThanThousand((int) floor($number / 1000), $ones, $tens).' Thousand ';
            $number %= 1000;
        }

        if ($number > 0) {
            $words .= self::lessThanThousand($number, $ones, $tens);
        }

        return trim($words);
    }

    protected static function lessThanThousand(int $number, array $ones, array $tens): string
    {
        $words = '';

        if ($number >= 100) {
            $words .= $ones[(int) floor($number / 100)].' Hundred';
            $number %= 100;
            if ($number > 0) {
                $words .= ' and ';
            }
        }

        if ($number >= 20) {
            $words .= $tens[(int) floor($number / 10)];
            $number %= 10;
            if ($number > 0) {
                $words .= ' '.$ones[$number];
            }
        } elseif ($number > 0) {
            $words .= $ones[$number];
        }

        return $words;
    }
}
