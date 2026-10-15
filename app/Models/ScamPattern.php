<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['category', 'pattern', 'is_regex', 'active'])]
class ScamPattern extends Model
{
    protected function casts(): array
    {
        return [
            'is_regex' => 'boolean',
            'active' => 'boolean',
        ];
    }
}
