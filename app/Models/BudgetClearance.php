<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BudgetClearance extends BaseModel
{
    protected function casts(): array
    {
        return parent::casts() + [
            'approved_budget_snapshot' => 'decimal:2',
            'fund_available_before' => 'decimal:2',
            'amount' => 'decimal:2',
            'balance_after' => 'decimal:2',
            'approved_at' => 'datetime',
            'source_created_at' => 'datetime',
            'source_updated_at' => 'datetime',
            'source_synced_at' => 'datetime',
            'source_active' => 'boolean',
        ];
    }

    public function fiscalYear(): BelongsTo
    {
        return $this->belongsTo(FiscalYear::class);
    }

    public function economicCode(): BelongsTo
    {
        return $this->belongsTo(EconomicCode::class);
    }

    public function isApproved(): bool
    {
        return $this->status === 'approved';
    }
}
