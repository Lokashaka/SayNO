<?php

namespace App\Console\Commands;

use App\Models\Brand;
use App\Models\LookalikeDomain;
use App\Models\OfficialDomain;
use App\Services\Analysis\Link\DnsResolver;
use App\Services\Analysis\Link\RdapClient;
use App\Services\Analysis\LookalikeGenerator;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;

/**
 * Memeriksa variasi domain merek sedikit demi sedikit, agar tidak melewati batas
 * waktu PHP di shared hosting. Posisi terakhir disimpan di cache.
 */
#[Signature('sayno:scan-lookalikes {brand? : Slug merek} {--batch=30 : Jumlah variasi per merek per jalan}')]
#[Description('Cek variasi domain tiruan merek lewat DNS dan RDAP')]
class ScanLookalikes extends Command
{
    public function handle(LookalikeGenerator $generator, DnsResolver $dns, RdapClient $rdap): int
    {
        $brands = Brand::query()
            ->when($this->argument('brand'), fn ($query, $slug) => $query->where('slug', $slug))
            ->get();

        $officialDomains = OfficialDomain::query()->pluck('domain')->all();

        foreach ($brands as $brand) {
            $variants = array_values(array_filter(
                $generator->generate($brand->primary_domain),
                fn (array $variant) => ! in_array($variant['domain'], $officialDomains, true),
            ));

            if ($variants === []) {
                continue;
            }

            $cursorKey = "lookalike-cursor:{$brand->id}";
            $cursor = (int) Cache::get($cursorKey, 0) % count($variants);
            $batch = array_slice([...$variants, ...$variants], $cursor, min((int) $this->option('batch'), count($variants)));
            $found = 0;

            foreach ($batch as $variant) {
                $found += $this->scan($brand, $variant, $dns, $rdap) ? 1 : 0;
            }

            Cache::forever($cursorKey, ($cursor + count($batch)) % count($variants));

            $this->info("{$brand->name}: ".count($batch).' variasi dicek, '.$found.' terdaftar ('.count($variants).' total).');
        }

        return self::SUCCESS;
    }

    /**
     * @param  array{domain: string, technique: string}  $variant
     */
    private function scan(Brand $brand, array $variant, DnsResolver $dns, RdapClient $rdap): bool
    {
        $existing = LookalikeDomain::query()
            ->where('brand_id', $brand->id)
            ->where('domain', $variant['domain'])
            ->first();

        if (! $dns->isRegistered($variant['domain'])) {
            $existing?->update(['status' => 'gone', 'last_checked_at' => now()]);

            return false;
        }

        $lookalike = $existing ?? new LookalikeDomain([
            'brand_id' => $brand->id,
            'domain' => $variant['domain'],
            'technique' => $variant['technique'],
            'source' => 'generator',
            'first_seen_at' => now(),
            'times_reported' => 0,
        ]);

        $lookalike->fill([
            'registered_at' => $lookalike->registered_at ?? $rdap->registrationDate($variant['domain']),
            'last_checked_at' => now(),
            'status' => 'active',
        ])->save();

        return true;
    }
}
