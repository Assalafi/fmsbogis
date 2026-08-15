<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CashbookEntry extends BaseModel
{
    protected function casts(): array
    {
        return parent::casts() + [
            'date' => 'date',
            'receipt_amount' => 'decimal:2',
            'payment_amount' => 'decimal:2',
            'running_balance' => 'decimal:2',
        ];
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    public function economicCode(): BelongsTo
    {
        return $this->belongsTo(EconomicCode::class);
    }

    public function fiscalYear(): BelongsTo
    {
        return $this->belongsTo(FiscalYear::class);
    }

    public function sourceReceipt(): BelongsTo
    {
        return $this->belongsTo(Receipt::class, 'transaction_id')->where('transaction_type', 'receipt');
    }

    public function sourcePayment(): BelongsTo
    {
        return $this->belongsTo(Payment::class, 'transaction_id')->where('transaction_type', 'payment');
    }
}
