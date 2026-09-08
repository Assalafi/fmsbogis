<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EBudgetSyncRun extends BaseModel
{
    protected $table = 'ebudget_sync_runs';

    protected function casts(): array
    {
        return parent::casts() + [
            'started_at' => 'datetime',
            'finished_at' => 'datetime',
            'budgets_received' => 'integer',
            'budgets_synced' => 'integer',
            'virements_received' => 'integer',
            'virements_synced' => 'integer',
        ];
    }

    public function fiscalYear(): BelongsTo
    {
        return $this->belongsTo(FiscalYear::class);
    }

    public function requester(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by');
    }
}
