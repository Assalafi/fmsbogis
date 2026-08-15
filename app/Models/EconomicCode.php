<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class EconomicCode extends BaseModel
{
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
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

    public function virementsIn(): HasMany
    {
        return $this->hasMany(Virement::class, 'to_economic_code_id');
    }

    public function virementsOut(): HasMany
    {
        return $this->hasMany(Virement::class, 'from_economic_code_id');
    }

    public function isRevenue(): bool
    {
        return $this->type === 'revenue';
    }

    public function isExpense(): bool
    {
        return $this->type === 'expense';
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    public function scopeRevenue($query)
    {
        return $query->where('type', 'revenue');
    }

    public function scopeExpense($query)
    {
        return $query->where('type', 'expense');
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }
}
