<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BankStatementLine extends BaseModel
{
    protected function casts(): array
    {
        return parent::casts() + [
            'date_of_transaction' => 'date',
            'debit' => 'decimal:2',
            'credit' => 'decimal:2',
            'balance' => 'decimal:2',
        ];
    }

    public function bankStatement(): BelongsTo
    {
        return $this->belongsTo(BankStatement::class);
    }

    public function amount(): string
    {
        return $this->debit > 0 ? $this->debit : $this->credit;
    }

    public function isCredit(): bool
    {
        return (float) $this->credit > 0;
    }
}
