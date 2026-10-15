<?php

namespace App\Services\Analysis\Link;

/**
 * Mengambil link dari teks dan memecah nama domain.
 *
 * Hanya mengolah string; tidak pernah menghubungi alamat yang dikirim pengguna.
 */
class DomainParser
{
    // Karakter Unicode yang mirip huruf Latin (homograf), dipetakan ke huruf aslinya.
    private const CONFUSABLES = [
        'а' => 'a', 'е' => 'e', 'о' => 'o', 'р' => 'p', 'с' => 'c', 'у' => 'y', 'х' => 'x',
        'і' => 'i', 'ј' => 'j', 'ѕ' => 's', 'ԁ' => 'd', 'ɡ' => 'g', 'һ' => 'h', 'ӏ' => 'l',
        'ɑ' => 'a', 'ο' => 'o', 'ν' => 'v', 'ι' => 'i', 'κ' => 'k', 'τ' => 't', 'ρ' => 'p',
        'á' => 'a', 'à' => 'a', 'â' => 'a', 'ä' => 'a', 'ã' => 'a', 'å' => 'a',
        'é' => 'e', 'è' => 'e', 'ê' => 'e', 'ë' => 'e', 'í' => 'i', 'ì' => 'i', 'î' => 'i',
        'ï' => 'i', 'ó' => 'o', 'ò' => 'o', 'ô' => 'o', 'ö' => 'o', 'õ' => 'o',
        'ú' => 'u', 'ù' => 'u', 'û' => 'u', 'ü' => 'u', 'ý' => 'y', 'ñ' => 'n', 'ç' => 'c',
    ];

    /**
     * Daftar URL di dalam teks, sesuai urutan kemunculan.
     *
     * @return list<string>
     */
    public function extractUrls(string $text, int $limit = 5): array
    {
        $bareTlds = implode('|', array_map('preg_quote', config('sayno.link.bare_tlds')));

        $pattern = '~(?:https?://[^\s<>"\']+)'
            .'|(?<![@\w.-])(?:[\p{L}\p{N}](?:[\p{L}\p{N}-]{0,61}[\p{L}\p{N}])?\.)+(?:'.$bareTlds.')\b(?:/[^\s<>"\']*)?~iu';

        preg_match_all($pattern, $text, $matches);

        $urls = [];

        foreach ($matches[0] as $match) {
            $url = rtrim($match, '.,;:!?)]}*_~');

            if ($this->host($url) !== null && ! in_array($url, $urls, true)) {
                $urls[] = $url;
            }

            if (count($urls) >= $limit) {
                break;
            }
        }

        return $urls;
    }

    /**
     * Nama host dalam huruf kecil dan bentuk ASCII (punycode), tanpa "www.".
     */
    public function host(string $url): ?string
    {
        $url = trim($url);

        if (! preg_match('~^[a-z][a-z0-9+.-]*://~i', $url)) {
            $url = 'http://'.$url;
        }

        $host = parse_url($url, PHP_URL_HOST);

        if (! is_string($host) || $host === '') {
            return null;
        }

        $host = rtrim(mb_strtolower($host), '.');

        if (! preg_match('/^[\x00-\x7F]+$/', $host)) {
            $ascii = idn_to_ascii($host, IDNA_DEFAULT, INTL_IDNA_VARIANT_UTS46);

            if ($ascii === false) {
                return null;
            }

            $host = $ascii;
        }

        $host = preg_replace('/^www\d?\./', '', $host);

        // Hanya nama domain yang wajar: tanpa IP, port, atau karakter aneh.
        if (! preg_match('/^(?=.{4,253}$)([a-z0-9](?:[a-z0-9-]{0,61}[a-z0-9])?\.)+[a-z][a-z0-9-]{1,62}$/', $host)) {
            return null;
        }

        return $host;
    }

    /**
     * Bentuk Unicode dari host punycode, untuk ditampilkan dan dibandingkan.
     */
    public function toUnicode(string $host): string
    {
        if (! str_contains($host, 'xn--')) {
            return $host;
        }

        $unicode = idn_to_utf8($host, IDNA_DEFAULT, INTL_IDNA_VARIANT_UTS46);

        return $unicode === false ? $host : $unicode;
    }

    /**
     * Domain yang bisa didaftarkan, misalnya login.bca-co.id -> bca-co.id.
     */
    public function registrableDomain(string $host): string
    {
        $labels = explode('.', $host);

        if (count($labels) <= 2) {
            return $host;
        }

        $lastTwo = implode('.', array_slice($labels, -2));
        $take = in_array($lastTwo, config('sayno.link.multi_part_suffixes'), true) ? 3 : 2;

        return implode('.', array_slice($labels, -$take));
    }

    /**
     * Bagian nama sebelum sufiks, misalnya bca-co.id -> bca-co, pajak.go.id -> pajak.
     */
    public function label(string $registrableDomain): string
    {
        return explode('.', $registrableDomain)[0];
    }

    /**
     * Label host di depan registrable domain, misalnya bca.login.xyz.com -> [bca, login].
     *
     * @return list<string>
     */
    public function subdomainLabels(string $host): array
    {
        $registrable = $this->registrableDomain($host);

        if ($host === $registrable) {
            return [];
        }

        return explode('.', substr($host, 0, -strlen($registrable) - 1));
    }

    /**
     * Varian label setelah karakter tiruan dinormalkan (0->o, 1->l atau i, rn->m, vv->w, homograf).
     *
     * @return list<string>
     */
    public function normalizedVariants(string $label): array
    {
        $unicode = mb_strtolower($this->toUnicode($label));
        $base = strtr($unicode, self::CONFUSABLES);
        $base = str_replace(['0', 'rn', 'vv', '5', '3'], ['o', 'm', 'w', 's', 'e'], $base);

        return array_values(array_unique([
            $base,
            str_replace('1', 'l', $base),
            str_replace('1', 'i', $base),
        ]));
    }

    public function hasNonAscii(string $host): bool
    {
        return str_contains($host, 'xn--');
    }
}
