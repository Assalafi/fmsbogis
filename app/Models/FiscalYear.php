<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;

class FiscalYear extends BaseModel
{
    protected function casts(): array
    {
        return parent::casts() + [
            'start_date' => 'date',
            'end_date' => 'date',
        ];
    }

    public function budgets(): HasMany
    {
        return $this->hasMany(EconomicCodeBudget::class);
    }

    public function receipts(): HasMany
    {
        return $this->hasMany(Receipt::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function virements(): HasMany
    {
        return $this->hasMany(Virement::class);
    }

    public function isOpen(): bool
    {
        return $this->status === 'open';
    }

    public function scopeOpen($query)
    {
        return $query->where('status', 'open');
    }
}
