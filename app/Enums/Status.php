<?php

namespace App\Enums;

enum Status: string
{
    case Safe = 'safe';
    case Caution = 'caution';
    case Danger = 'danger';

    public static function fromScore(int $score): self
    {
        $thresholds = config('sayno.thresholds');

        return match (true) {
            $score >= $thresholds['danger'] => self::Danger,
            $score >= $thresholds['caution'] => self::Caution,
            default => self::Safe,
        };
    }

    public function label(): string
    {
        return __("status.{$this->value}.label");
    }

    public function emoji(): string
    {
        return match ($this) {
            self::Safe => '🟢',
            self::Caution => '🟡',
            self::Danger => '🔴',
        };
    }
}
