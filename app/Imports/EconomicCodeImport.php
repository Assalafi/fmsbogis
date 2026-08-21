<?php

namespace App\Imports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class EconomicCodeImport implements ToCollection, WithHeadingRow
{
    /**
     * @var array<int, array{code: string, name: string, description: string}>
     */
    public array $rows = [];

    public function collection(Collection $collection): void
    {
        $this->rows = $collection
            ->map(function ($row) {
                return [
                    'code' => trim((string) ($row['code'] ?? '')),
                    'name' => trim((string) ($row['name'] ?? '')),
                    'description' => trim((string) ($row['description'] ?? $row['description1'] ?? '')),
                ];
            })
            ->filter(fn ($row) => $row['code'] !== '')
            ->values()
            ->all();
    }
}
