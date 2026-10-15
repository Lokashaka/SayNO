<?php

namespace App\Services\Analysis;

use App\Models\ScamPattern;
use Illuminate\Support\Collection;

/**
 * Mencocokkan teks dengan pola modus penipuan di tabel scam_patterns.
 */
class TextAnalyzer
{
    private const MAX_LENGTH = 4000;

    /** @var Collection<int, ScamPattern>|null */
    private ?Collection $patterns = null;

    /**
     * Satu sinyal per kategori modus yang cocok, paling banyak `sayno.text_max_patterns`.
     *
     * @return list<Signal>
     */
    public function analyze(string $text): array
    {
        $text = $this->normalize(mb_substr($text, 0, self::MAX_LENGTH));
        $categories = [];

        foreach ($this->patterns() as $pattern) {
            if (isset($categories[$pattern->category]) || ! $this->matches($pattern, $text)) {
                continue;
            }

            $categories[$pattern->category] = true;

            if (count($categories) >= config('sayno.text_max_patterns')) {
                break;
            }
        }

        return array_map(
            fn (string $category) => Signal::make('text.pattern', ['category' => $category]),
            array_keys($categories),
        );
    }

    private function matches(ScamPattern $pattern, string $text): bool
    {
        if (! $pattern->is_regex) {
            return str_contains($text, $this->normalize($pattern->pattern));
        }

        $regex = '~'.str_replace('~', '\~', $pattern->pattern).'~iu';

        // Pola rusak dilewati, tidak menggagalkan pemeriksaan.
        return @preg_match($regex, $text) === 1;
    }

    private function normalize(string $text): string
    {
        return preg_replace('/\s+/u', ' ', mb_strtolower($text));
    }

    /**
     * @return Collection<int, ScamPattern>
     */
    private function patterns(): Collection
    {
        return $this->patterns ??= ScamPattern::query()->where('active', true)->orderBy('id')->get();
    }
}
