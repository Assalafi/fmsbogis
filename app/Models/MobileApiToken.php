<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MobileApiToken extends BaseModel
{
    protected $guarded = [];

    protected function casts(): array
    {
        return parent::casts() + [
            'expires_at' => 'datetime',
            'last_used_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function isExpired(): bool
    {
        return $this->expires_at !== null && $this->expires_at->isPast();
    }
}
