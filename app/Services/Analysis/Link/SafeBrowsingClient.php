<?php

namespace App\Services\Analysis\Link;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Google Safe Browsing Lookup API v4.
 *
 * URL hanya dikirim sebagai data ke Google; server SayNO tidak membukanya.
 * Kunci API dikirim lewat header agar tidak ikut tercatat di pesan error yang memuat URL.
 */
class SafeBrowsingClient
{
    /**
     * true jika masuk daftar hitam, false jika bersih, null jika tidak bisa dicek.
     */
    public function isListed(string $url): ?bool
    {
        $key = config('services.safe_browsing.key');

        if (blank($key)) {
            return null;
        }

        $cacheKey = 'safebrowsing:'.hash('sha256', $url);
        $cached = Cache::get($cacheKey);

        if ($cached !== null) {
            return $cached === 'listed';
        }

        try {
            $response = Http::acceptJson()
                ->withHeaders(['X-Goog-Api-Key' => $key])
                ->timeout(6)
                ->connectTimeout(4)
                ->post(config('services.safe_browsing.endpoint'), [
                    'client' => ['clientId' => 'sayno', 'clientVersion' => '1.0'],
                    'threatInfo' => [
                        'threatTypes' => ['MALWARE', 'SOCIAL_ENGINEERING', 'UNWANTED_SOFTWARE', 'POTENTIALLY_HARMFUL_APPLICATION'],
                        'platformTypes' => ['ANY_PLATFORM'],
                        'threatEntryTypes' => ['URL'],
                        'threatEntries' => [['url' => $url]],
                    ],
                ]);
        } catch (Throwable) {
            Log::warning('Safe Browsing tidak dapat dihubungi.');

            return null;
        }

        if (! $response->successful()) {
            Log::warning('Safe Browsing menolak permintaan.', ['status' => $response->status()]);

            return null;
        }

        $listed = ! empty($response->json('matches'));

        Cache::put($cacheKey, $listed ? 'listed' : 'clean', now()->addMinutes(config('services.safe_browsing.cache_minutes')));

        return $listed;
    }
}
