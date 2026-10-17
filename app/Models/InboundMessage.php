<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['wamid', 'channel', 'sender_hash', 'type', 'check_id', 'received_at', 'processed_at'])]
class InboundMessage extends Model
{
    protected function casts(): array
    {
        return [
            'received_at' => 'datetime',
            'processed_at' => 'datetime',
        ];
    }

    public static function hashSender(string $phone): string
    {
        return hash_hmac('sha256', $phone, (string) config('app.key'));
    }
}
