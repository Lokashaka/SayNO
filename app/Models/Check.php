<?php

namespace App\Models;

use App\Enums\InputType;
use App\Enums\Status;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Satu pemeriksaan. ID berupa ULID agar URL hasil tidak bisa ditebak berurutan.
 */
#[Fillable(['source', 'sender_hash', 'input_type', 'domain', 'file_sha256', 'score', 'status'])]
class Check extends Model
{
    use HasUlids;

    protected function casts(): array
    {
        return [
            'input_type' => InputType::class,
            'status' => Status::class,
            'score' => 'integer',
        ];
    }

    public function signals(): HasMany
    {
        return $this->hasMany(Signal::class);
    }
}
