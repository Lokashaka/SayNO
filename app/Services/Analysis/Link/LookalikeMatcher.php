<?php

namespace App\Services\Analysis\Link;

use App\Models\OfficialDomain;
use Illuminate\Support\Collection;

/**
 * Mencocokkan host dengan daftar domain resmi.
 *
 * Teknik peniruan yang dikenali:
 * - tld:       label sama persis, sufiks lain (bca.co.id -> bca.id.xyz tidak, tapi bca.com ya)
 * - homoglyph: karakter tiruan (0/o, 1/l, rn/m, huruf Kiril)
 * - subdomain: merek dipakai sebagai subdomain (bca.co.id.verify-akun.xyz)
 * - combo:     merek ditambah kata lain (bca-co.id, tokopedia-promo.com)
 * - typo:      salah ketik kecil (tokopedla.com)
 */
class LookalikeMatcher
{
    // Imbuhan yang sering dipakai penipu di sekitar nama merek pendek.
    private const AFFIXES = [
        'login', 'promo', 'cs', 'mobile', 'online', 'official', 'resmi', 'care', 'help', 'verify',
        'verifikasi', 'secure', 'hadiah', 'bonus', 'poin', 'klaim', 'update', 'layanan', 'bantuan',
        'app', 'apk', 'akun', 'bank', 'my', 'klik', 'web', 'co', 'net', 'info', 'pusat', 'center',
    ];

    /** @var Collection<int, OfficialDomain>|null */
    private ?Collection $officials = null;

    public function __construct(private DomainParser $parser) {}

    public function findOfficial(string $host): ?OfficialDomain
    {
        return $this->officials()->first(
            fn (OfficialDomain $official) => $host === $official->domain || str_ends_with($host, '.'.$official->domain),
        );
    }

    /**
     * @return array{official: OfficialDomain, technique: string}|null
     */
    public function findLookalike(string $host): ?array
    {
        $registrable = $this->parser->registrableDomain($host);
        $label = $this->parser->label($registrable);
        $variants = $this->parser->normalizedVariants($label);
        $tokens = $this->tokens($variants);
        $subLabels = $this->parser->subdomainLabels($host);
        $isIdn = $this->parser->hasNonAscii($host);

        $best = null;

        foreach ($this->officials() as $official) {
            $officialLabel = $this->parser->normalizedVariants($this->parser->label($official->domain))[0];

            if (strlen($officialLabel) < 3) {
                continue;
            }

            $technique = $this->technique($host, $label, $variants, $tokens, $subLabels, $isIdn, $official, $officialLabel);

            if ($technique !== null && ($best === null || $this->rank($technique) < $this->rank($best['technique']))) {
                $best = ['official' => $official, 'technique' => $technique];
            }
        }

        return $best;
    }

    /**
     * @param  list<string>  $variants
     * @param  list<string>  $tokens
     * @param  list<string>  $subLabels
     */
    private function technique(
        string $host,
        string $label,
        array $variants,
        array $tokens,
        array $subLabels,
        bool $isIdn,
        OfficialDomain $official,
        string $officialLabel,
    ): ?string {
        if (str_starts_with($host, $official->domain.'.') || str_contains($host, '.'.$official->domain.'.')) {
            return 'subdomain';
        }

        if (in_array($officialLabel, config('sayno.link.generic_labels'), true)) {
            return null;
        }

        if (in_array($officialLabel, $variants, true)) {
            return ($label === $officialLabel && ! $isIdn) ? 'tld' : 'homoglyph';
        }

        if (in_array($officialLabel, $subLabels, true)) {
            return 'subdomain';
        }

        if (count($tokens) > 1 && in_array($officialLabel, $tokens, true)) {
            return 'combo';
        }

        foreach ($variants as $variant) {
            if ($this->isCombo($variant, $officialLabel)) {
                return 'combo';
            }

            if ($this->isTypo($variant, $officialLabel)) {
                return 'typo';
            }
        }

        return null;
    }

    private function isCombo(string $variant, string $officialLabel): bool
    {
        $plain = str_replace('-', '', $variant);

        if ($plain === $officialLabel || ! str_contains($plain, $officialLabel)) {
            return false;
        }

        // Merek panjang cukup muncul di dalam label (tokopediapromo).
        if (strlen($officialLabel) >= 5) {
            return true;
        }

        // Merek pendek (bca, bri, dana) harus diapit imbuhan yang dikenal (bcamobile, klikbri),
        // agar kata biasa seperti "danamon" tidak ikut tertandai.
        foreach (self::AFFIXES as $affix) {
            if ($plain === $officialLabel.$affix || $plain === $affix.$officialLabel) {
                return true;
            }
        }

        return false;
    }

    private function isTypo(string $variant, string $officialLabel): bool
    {
        $length = strlen($officialLabel);

        if ($length < config('sayno.link.typo_min_length', 6)) {
            return false;
        }

        $maxDistance = $length >= config('sayno.link.typo_two_edits_min_length', 9) ? 2 : 1;
        $distance = levenshtein($variant, $officialLabel);

        return $distance > 0 && $distance <= $maxDistance;
    }

    /**
     * @param  list<string>  $variants
     * @return list<string>
     */
    private function tokens(array $variants): array
    {
        $tokens = [];

        foreach ($variants as $variant) {
            array_push($tokens, ...explode('-', $variant));
        }

        return array_values(array_unique(array_filter($tokens)));
    }

    private function rank(string $technique): int
    {
        return array_search($technique, ['tld', 'homoglyph', 'subdomain', 'combo', 'typo'], true);
    }

    /**
     * @return Collection<int, OfficialDomain>
     */
    private function officials(): Collection
    {
        return $this->officials ??= OfficialDomain::query()->orderBy('id')->get();
    }
}
