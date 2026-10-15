<?php

namespace App\Services\Analysis;

use App\Services\Analysis\Link\DomainParser;

/**
 * Membuat variasi ejaan domain merek yang mungkin didaftarkan penipu.
 */
class LookalikeGenerator
{
    private const HOMOGLYPHS = ['o' => '0', 'l' => '1', 'i' => '1', 'm' => 'rn', 'w' => 'vv'];

    private const SUFFIXES = ['id', 'my.id', 'web.id', 'biz.id', 'co.id', 'com', 'net', 'co', 'online', 'site', 'xyz', 'info'];

    private const PREFIXES = ['login', 'cs', 'promo', 'my', 'klik'];

    private const POSTFIXES = ['promo', 'cs', 'login', 'official', 'co', 'id', 'care', 'mobile'];

    // Salah ketik pada nama pendek menghasilkan terlalu banyak kata lain yang sah.
    private const TYPO_MIN_LENGTH = 5;

    public function __construct(private DomainParser $parser) {}

    /**
     * @return list<array{domain: string, technique: string}>
     */
    public function generate(string $primaryDomain): array
    {
        $primaryDomain = mb_strtolower($primaryDomain);
        $label = $this->parser->label($primaryDomain);
        $suffix = substr($primaryDomain, strlen($label) + 1);
        $variants = [];

        $add = function (string $candidateLabel, string $candidateSuffix, string $technique) use (&$variants, $primaryDomain) {
            $domain = $candidateLabel.'.'.$candidateSuffix;

            if ($domain !== $primaryDomain
                && ! isset($variants[$domain])
                && preg_match('/^[a-z0-9](?:[a-z0-9-]{0,61}[a-z0-9])?$/', $candidateLabel)) {
                $variants[$domain] = $technique;
            }
        };

        foreach (self::SUFFIXES as $other) {
            $add($label, $other, 'tld');
        }

        foreach (self::HOMOGLYPHS as $from => $to) {
            foreach ($this->positions($label, $from) as $position) {
                $add(substr_replace($label, $to, $position, 1), $suffix, 'homoglyph');
            }
        }

        foreach ([$suffix, 'id', 'com'] as $targetSuffix) {
            foreach (self::PREFIXES as $prefix) {
                $add($prefix.'-'.$label, $targetSuffix, 'combo');
                $add($prefix.$label, $targetSuffix, 'combo');
            }

            foreach (self::POSTFIXES as $postfix) {
                $add($label.'-'.$postfix, $targetSuffix, 'combo');
            }
        }

        if (strlen($label) >= self::TYPO_MIN_LENGTH) {
            foreach ($this->typos($label) as $typo) {
                $add($typo, $suffix, 'typo');
            }
        }

        return array_map(
            fn (string $domain, string $technique) => ['domain' => $domain, 'technique' => $technique],
            array_keys($variants),
            $variants,
        );
    }

    /**
     * Huruf hilang, huruf ganda, dan huruf bertukar tempat.
     *
     * @return list<string>
     */
    private function typos(string $label): array
    {
        $typos = [];
        $length = strlen($label);

        for ($i = 0; $i < $length; $i++) {
            $typos[] = substr($label, 0, $i).substr($label, $i + 1);
            $typos[] = substr($label, 0, $i + 1).$label[$i].substr($label, $i + 1);

            if ($i < $length - 1 && $label[$i] !== $label[$i + 1]) {
                $typos[] = substr($label, 0, $i).$label[$i + 1].$label[$i].substr($label, $i + 2);
            }
        }

        return array_values(array_unique($typos));
    }

    /**
     * @return list<int>
     */
    private function positions(string $haystack, string $needle): array
    {
        $positions = [];
        $offset = 0;

        while (($position = strpos($haystack, $needle, $offset)) !== false) {
            $positions[] = $position;
            $offset = $position + 1;
        }

        return $positions;
    }
}
