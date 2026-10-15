<?php

namespace App\Services\Scoring;

use App\Enums\Status;
use App\Services\Analysis\Signal;

/**
 * Menggabungkan sinyal menjadi skor 0–100 dan status.
 */
class RiskScorer
{
    /**
     * @param  list<Signal>  $signals
     */
    public function score(array $signals): RiskScore
    {
        $isOfficial = collect($signals)->contains(fn (Signal $signal) => $signal->code === 'link.official');

        $counted = array_filter($signals, function (Signal $signal) use ($isOfficial) {
            if ($signal->isInfo()) {
                return false;
            }

            // Domain resmi menihilkan sinyal link. Pola modus di teks tetap dihitung,
            // karena penipu kadang menyisipkan link resmi di pesan bujukan.
            return ! ($isOfficial && str_starts_with($signal->code, 'link.'));
        });

        $score = min(100, array_sum(array_map(fn (Signal $signal) => $signal->weight, $counted)));

        usort($counted, fn (Signal $a, Signal $b) => $b->weight <=> $a->weight);

        return new RiskScore(
            score: $score,
            status: Status::fromScore($score),
            reasons: array_slice($counted, 0, config('sayno.max_reasons')),
            isOfficial: $isOfficial,
        );
    }
}
