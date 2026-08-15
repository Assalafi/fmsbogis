<?php

namespace App\Support;

use App\Models\FiscalYear;

class ActiveFiscalYear
{
    public const SESSION_KEY = 'active_fiscal_year_id';

    public static function get(): ?FiscalYear
    {
        $id = session(self::SESSION_KEY);

        if ($id) {
            $year = FiscalYear::find($id);
            if ($year) {
                return $year;
            }
        }

        return FiscalYear::where('status', 'open')
            ->orderBy('start_date')
            ->first();
    }

    public static function id(): ?string
    {
        return self::get()?->id;
    }

    public static function set(string $fiscalYearId): void
    {
        session([self::SESSION_KEY => $fiscalYearId]);
    }
}
