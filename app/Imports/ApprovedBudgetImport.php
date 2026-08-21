<?php

namespace App\Imports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class ApprovedBudgetImport implements ToCollection, WithHeadingRow
{
    /**
     * @var array<int, array{code: string, amount: float}>
     */
    public array $rows = [];

    public function collection(Collection $collection): void
    {
        $this->rows = $collection
            ->map(function ($row) {
                $code = trim((string) ($row['economic_code'] ?? $row['code'] ?? ''));

                return [
                    'code' => $code,
                    'amount' => $this->normalizeAmount($row['amount'] ?? 0),
                ];
            })
            ->filter(fn ($row) => $row['code'] !== '')
            ->values()
            ->all();
    }

    protected function normalizeAmount($value): float
    {
        $value = (string) ($value ?? 0);
        $value = str_replace([',', '₦', ' '], '', trim($value));

        return (float) $value;
    }
}
