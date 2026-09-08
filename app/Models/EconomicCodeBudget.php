<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class EconomicCodeBudget extends BaseModel
{
    protected function casts(): array
    {
        return parent::casts() + [
            'original_budget' => 'decimal:2',
            'supplementary_budget' => 'decimal:2',
            'virement_in' => 'decimal:2',
            'virement_out' => 'decimal:2',
            'revised_budget' => 'decimal:2',
            'approved_at' => 'datetime',
            'source_available_funds' => 'decimal:2',
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

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function approvals(): HasMany
    {
        return $this->hasMany(BudgetApproval::class);
    }

    public function isApproved(): bool
    {
        return $this->status === 'approved';
    }

    public function scopeAuthoritative($query)
    {
        return $query
            ->where($this->qualifyColumn('source_system'), 'ebudget')
            ->where($this->qualifyColumn('source_active'), true)
            ->where($this->qualifyColumn('status'), 'approved');
    }
}
