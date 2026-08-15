<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BankReconciliation extends BaseModel
{
    protected function casts(): array
    {
        return parent::casts() + [
            'reconciliation_date' => 'date',
            'cashbook_balance' => 'decimal:2',
            'bank_statement_balance' => 'decimal:2',
            'adjusted_cashbook_balance' => 'decimal:2',
            'adjusted_bank_balance' => 'decimal:2',
            'difference' => 'decimal:2',
            'approved_at' => 'datetime',
        ];
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    public function bankStatement(): BelongsTo
    {
        return $this->belongsTo(BankStatement::class);
    }

    public function preparer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'prepared_by');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function items(): HasMany
    {
        return $this->hasMany(BankReconciliationItem::class);
    }

    public function isApproved(): bool
    {
        return $this->status === 'approved';
    }
}
