<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'brand_id', 'file_sha256', 'package_name', 'app_label', 'permissions',
    'first_seen_at', 'last_seen_at', 'times_seen',
])]
class FakeApk extends Model
{
    protected function casts(): array
    {
        return [
            'permissions' => 'array',
            'first_seen_at' => 'datetime',
            'last_seen_at' => 'datetime',
            'times_seen' => 'integer',
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
