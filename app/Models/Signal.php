<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['check_id', 'code', 'weight', 'meta'])]
class Signal extends Model
{
    public $timestamps = false;

    protected function casts(): array
    {
        return [
            'weight' => 'integer',
            'meta' => 'array',
        ];
    }

    public function check(): BelongsTo
    {
        return $this->belongsTo(Check::class);
    }
}
