<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Virement extends BaseModel
{
    protected function casts(): array
    {
        return parent::casts() + [
            'amount' => 'decimal:2',
            'date' => 'date',
            'approved_at' => 'datetime',
        ];
    }

    public function fiscalYear(): BelongsTo
    {
        return $this->belongsTo(FiscalYear::class);
    }

    public function fromEconomicCode(): BelongsTo
    {
        return $this->belongsTo(EconomicCode::class, 'from_economic_code_id');
    }

    public function toEconomicCode(): BelongsTo
    {
        return $this->belongsTo(EconomicCode::class, 'to_economic_code_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function isApproved(): bool
    {
        return $this->status === 'approved';
    }

    public function isCrossType(): bool
    {
        return $this->fromEconomicCode->account_type !== $this->toEconomicCode->account_type;
    }
}
