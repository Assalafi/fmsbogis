<?php

namespace App\Imports;

use App\Models\BankStatement;
use App\Models\BankStatementLine;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class BankStatementImport implements ToCollection, WithHeadingRow
{
    public function __construct(private BankStatement $statement)
    {
    }

    public function collection(Collection $rows)
    {
        foreach ($rows as $row) {
            $date = $this->resolveDate($row);
            $debit = $this->toDecimal($row['debit'] ?? $row['withdrawals'] ?? 0);
            $credit = $this->toDecimal($row['credit'] ?? $row['deposits'] ?? 0);
            $balance = isset($row['balance']) && $row['balance'] !== '' ? $this->toDecimal($row['balance']) : null;

            if ($date === null) {
                continue;
            }

            BankStatementLine::create([
                'bank_statement_id' => $this->statement->id,
                'date_of_transaction' => $date,
                'reference' => $row['reference'] ?? $row['ref'] ?? null,
                'description' => $row['description'] ?? $row['details'] ?? $row['narrative'] ?? null,
                'debit' => $debit,
                'credit' => $credit,
                'balance' => $balance,
                'match_status' => 'unmatched',
            ]);
        }
    }

    protected function resolveDate($row): ?string
    {
        foreach (['date_of_transaction', 'date', 'transaction_date', 'value_date'] as $key) {
            if (! empty($row[$key])) {
                return \Carbon\Carbon::parse($row[$key])->format('Y-m-d');
            }
        }

        return null;
    }

    protected function toDecimal($value): string
    {
        if ($value === null || $value === '') {
            return '0.00';
        }

        $value = str_replace([',', '₦', ' ', "\u{00A0}"], '', (string) $value);

        return number_format((float) $value, 2, '.', '');
    }
}
