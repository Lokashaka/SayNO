<?php

namespace App\Services\Scoring;

use App\Enums\Status;
use App\Services\Analysis\Signal;

final class RiskScore
{
    /**
     * @param  list<Signal>  $reasons  Sinyal berbobot terbesar, maksimal `sayno.max_reasons`.
     */
    public function __construct(
        public readonly int $score,
        public readonly Status $status,
        public readonly array $reasons,
        public readonly bool $isOfficial = false,
    ) {}
}
