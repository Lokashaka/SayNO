<?php

namespace App\Services\Analysis;

/**
 * Satu temuan dari analyzer. Analyzer mengembalikan daftar Signal, bukan skor.
 *
 * Sinyal berbobot 0 adalah catatan informasi (misalnya umur domain) yang hanya
 * tampil di "detail teknis" dan tidak menambah skor.
 */
final class Signal
{
    public function __construct(
        public readonly string $code,
        public readonly int $weight,
        public readonly string $reason,
        public readonly array $meta = [],
    ) {}

    /**
     * Sinyal risiko; bobot diambil dari config/sayno.php.
     */
    public static function make(string $code, array $meta = []): self
    {
        // Kode berisi titik (link.lookalike), jadi tidak bisa dibaca lewat notasi titik config().
        return new self($code, (int) (config('sayno.weights')[$code] ?? 0), "signals.{$code}", $meta);
    }

    /**
     * Catatan informasi tanpa bobot.
     */
    public static function info(string $code, array $meta = []): self
    {
        return new self($code, 0, "signals.{$code}", $meta);
    }

    public function isInfo(): bool
    {
        return $this->weight === 0;
    }

    public function withWeight(int $weight): self
    {
        return new self($this->code, $weight, $this->reason, $this->meta);
    }
}
