<?php

namespace App\Services\Analysis\Link;

use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Mengambil tanggal registrasi domain lewat RDAP (rdap.org meneruskan .id ke PANDI).
 *
 * Yang dihubungi hanya server RDAP, bukan situs milik domain tersebut.
 */
class RdapClient
{
    public function registrationDate(string $domain): ?CarbonImmutable
    {
        if (! preg_match('/^[a-z0-9.-]+$/', $domain)) {
            return null;
        }

        $cached = Cache::get($this->cacheKey($domain));

        if ($cached !== null) {
            return $cached === 'unknown' ? null : CarbonImmutable::parse($cached);
        }

        $date = $this->fetch($domain);

        // Kegagalan disimpan singkat agar server RDAP tidak dibanjiri permintaan.
        Cache::put(
            $this->cacheKey($domain),
            $date?->toDateString() ?? 'unknown',
            $date ? now()->addHours(config('services.rdap.cache_hours')) : now()->addMinutes(15),
        );

        return $date;
    }

    private function fetch(string $domain): ?CarbonImmutable
    {
        try {
            $response = Http::acceptJson()
                ->timeout(6)
                ->connectTimeout(4)
                ->get(config('services.rdap.endpoint').$domain);
        } catch (Throwable) {
            Log::warning('RDAP tidak dapat dihubungi.', ['domain' => $domain]);

            return null;
        }

        if (! $response->successful()) {
            return null;
        }

        foreach ((array) $response->json('events', []) as $event) {
            if (($event['eventAction'] ?? null) === 'registration' && ! empty($event['eventDate'])) {
                try {
                    return CarbonImmutable::parse($event['eventDate']);
                } catch (Throwable) {
                    return null;
                }
            }
        }

        return null;
    }

    private function cacheKey(string $domain): string
    {
        return 'rdap:'.$domain;
    }
}
