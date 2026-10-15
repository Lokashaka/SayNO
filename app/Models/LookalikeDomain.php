<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'brand_id', 'domain', 'technique', 'source', 'registered_at', 'first_seen_at',
    'last_checked_at', 'status', 'times_reported',
])]
class LookalikeDomain extends Model
{
    protected function casts(): array
    {
        return [
            'registered_at' => 'date',
            'first_seen_at' => 'datetime',
            'last_checked_at' => 'datetime',
            'times_reported' => 'integer',
        ];
    }

    public function brand(): BelongsTo
    {
        return $this->belongsTo(Brand::class);
    }

    public function isNew(): bool
    {
        return $this->first_seen_at->greaterThan(now()->subDays(7));
    }
}
