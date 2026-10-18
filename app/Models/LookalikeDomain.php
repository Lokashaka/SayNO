<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'brand_id', 'domain', 'technique', 'source', 'registered_at', 'first_seen_at',
    'last_checked_at', 'status', 'times_reported', 'verdict', 'verdict_source',
])]
class LookalikeDomain extends Model
{
    // Penilaian domain. Null berarti belum ditinjau.
    public const SUSPICIOUS = 'suspicious'; // situs aktif meniru merek

    public const PARKED = 'parked';         // dijual, diparkir, atau kedaluwarsa

    public const UNRELATED = 'unrelated';   // usaha lain bernama sama, atau milik merek sendiri

    // Penilaian manual tidak ditimpa pemindaian otomatis.
    public const SOURCE_AUTO = 'auto';

    public const SOURCE_MANUAL = 'manual';

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

    /**
     * Domain yang tampil di daftar publik: masih terdaftar, dan dinilai meniru atau belum ditinjau.
     */
    #[Scope]
    protected function listed(Builder $query): void
    {
        $query->where('status', 'active')
            ->where(fn (Builder $query) => $query->whereNull('verdict')->orWhere('verdict', self::SUSPICIOUS));
    }

    /**
     * Penilaian terkuat untuk domain ini dari semua merek: meniru > pihak lain > diparkir.
     */
    public static function verdictFor(string ...$domains): ?string
    {
        $verdicts = static::query()
            ->whereIn('domain', array_unique($domains))
            ->where('status', 'active')
            ->whereNotNull('verdict')
            ->pluck('verdict');

        foreach ([self::SUSPICIOUS, self::UNRELATED, self::PARKED] as $verdict) {
            if ($verdicts->contains($verdict)) {
                return $verdict;
            }
        }

        return null;
    }
}
