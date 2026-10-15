<?php

namespace App\Services\Analysis;

use App\Services\Analysis\Link\DomainParser;
use App\Services\Analysis\Link\LookalikeMatcher;
use App\Services\Analysis\Link\RdapClient;
use App\Services\Analysis\Link\SafeBrowsingClient;

/**
 * Cek link dari nama domain, RDAP, dan Safe Browsing. Link tidak pernah dibuka.
 */
class LinkAnalyzer
{
    public function __construct(
        private DomainParser $parser,
        private LookalikeMatcher $matcher,
        private RdapClient $rdap,
        private SafeBrowsingClient $safeBrowsing,
    ) {}

    /**
     * Sinyal pertama selalu `link.domain` (info) yang memuat domain yang diperiksa.
     *
     * @return list<Signal>
     */
    public function analyze(string $url): array
    {
        $host = $this->parser->host($url);

        if ($host === null) {
            return [];
        }

        $domain = $this->parser->registrableDomain($host);
        $info = [
            'host' => $this->parser->toUnicode($host),
            'domain' => $this->parser->toUnicode($domain),
        ];
        $signals = [];

        $official = $this->matcher->findOfficial($host);

        if ($official !== null) {
            return [
                Signal::info('link.domain', $info),
                Signal::info('link.official', ['domain' => $official->domain, 'name' => $official->name]),
            ];
        }

        $lookalike = $this->matcher->findLookalike($host);

        if ($lookalike !== null) {
            $signals[] = Signal::make('link.lookalike', [
                'domain' => $info['domain'],
                'official' => $lookalike['official']->domain,
                'name' => $lookalike['official']->name,
                'brand_id' => $lookalike['official']->brand_id,
                'technique' => $lookalike['technique'],
            ]);
        }

        if (in_array($domain, config('sayno.link.shorteners'), true) || in_array($host, config('sayno.link.shorteners'), true)) {
            $signals[] = Signal::make('link.shortener', ['domain' => $info['domain']]);
        }

        $registeredAt = $this->rdap->registrationDate($domain);

        if ($registeredAt !== null) {
            $ageDays = (int) $registeredAt->diffInDays(now());
            $info['registered_at'] = $registeredAt->toDateString();
            $info['age_days'] = $ageDays;

            if ($ageDays < config('sayno.link.new_domain_days')) {
                $signals[] = Signal::make('link.new_domain', ['domain' => $info['domain'], 'age_days' => $ageDays]);
            }
        }

        $listed = $this->safeBrowsing->isListed($this->withScheme($url));
        $info['safe_browsing'] = match ($listed) {
            true => 'listed',
            false => 'clean',
            null => 'unchecked',
        };

        if ($listed === true) {
            $signals[] = Signal::make('link.blacklisted', ['domain' => $info['domain']]);
        }

        return [Signal::info('link.domain', $info), ...$signals];
    }

    private function withScheme(string $url): string
    {
        return preg_match('~^https?://~i', $url) ? $url : 'http://'.$url;
    }
}
