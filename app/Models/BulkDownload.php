<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BulkDownload extends BaseModel
{
    protected function casts(): array
    {
        return parent::casts() + [
            'receipt_ids' => 'array',
            'filters' => 'array',
        ];
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
