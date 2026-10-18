<?php

namespace Tests\Feature;

use App\Models\Brand;
use App\Models\LookalikeDomain;
use App\Services\Analysis\Link\DnsResolver;
use App\Services\Analysis\LookalikeGenerator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ScanLookalikesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    #[Test]
    public function the_generator_builds_common_variations(): void
    {
        $variants = collect(app(LookalikeGenerator::class)->generate('tokopedia.com'))->pluck('technique', 'domain');

        $this->assertSame('tld', $variants['tokopedia.id']);
        $this->assertSame('homoglyph', $variants['t0kopedia.com']);
        $this->assertSame('combo', $variants['tokopedia-promo.com']);
        $this->assertSame('typo', $variants['tokopeda.com']);
        $this->assertArrayNotHasKey('tokopedia.com', $variants);
    }

    #[Test]
    public function short_brand_names_get_no_typo_variants(): void
    {
        $techniques = collect(app(LookalikeGenerator::class)->generate('bca.co.id'))->pluck('technique')->unique();

        $this->assertNotContains('typo', $techniques);
    }

    #[Test]
    public function the_scan_records_registered_variants_in_batches(): void
    {
        Http::preventStrayRequests();
        Http::fake(['rdap.org/*' => Http::response(['events' => [
            ['eventAction' => 'registration', 'eventDate' => '2026-10-01T00:00:00Z'],
        ]])]);

        $dns = $this->mock(DnsResolver::class);
        $dns->shouldReceive('isRegistered')
            ->andReturnUsing(fn (string $domain) => in_array($domain, ['bca.id', 'bca.com'], true));
        $dns->shouldReceive('nameservers')->andReturn(['ns1.contoh-hosting.net']);

        $this->artisan('sayno:scan-lookalikes', ['brand' => 'bca', '--batch' => 5])->assertSuccessful();

        $found = LookalikeDomain::pluck('domain')->all();
        $this->assertContains('bca.id', $found);
        $this->assertNotContains('bca.co.id', $found, 'Domain resmi tidak boleh masuk daftar tiruan.');
        $this->assertSame('generator', LookalikeDomain::firstWhere('domain', 'bca.id')->source);
        $this->assertSame('2026-10-01', LookalikeDomain::firstWhere('domain', 'bca.id')->registered_at->toDateString());
        $this->assertNull(LookalikeDomain::firstWhere('domain', 'bca.id')->verdict, 'Server DNS biasa dibiarkan untuk ditinjau.');
    }

    #[Test]
    public function the_scan_marks_parked_and_brand_protected_domains_but_keeps_manual_reviews(): void
    {
        Http::fake(['rdap.org/*' => Http::response([], 404)]);
        $bca = Brand::where('slug', 'bca')->sole();

        LookalikeDomain::create([
            'brand_id' => $bca->id, 'domain' => 'bca.net', 'technique' => 'tld', 'source' => 'generator',
            'first_seen_at' => now(), 'verdict' => LookalikeDomain::SUSPICIOUS, 'verdict_source' => LookalikeDomain::SOURCE_MANUAL,
        ]);

        $nameservers = [
            'bca.info' => ['ns1.dan.com', 'ns2.dan.com'],
            'mybca.com' => ['dns1.cscdns.net'],
            'bca.net' => ['ns1.sedoparking.com'],
        ];

        $dns = $this->mock(DnsResolver::class);
        $dns->shouldReceive('isRegistered')->andReturnUsing(fn (string $domain) => isset($nameservers[$domain]));
        $dns->shouldReceive('nameservers')->andReturnUsing(fn (string $domain) => $nameservers[$domain] ?? []);

        $this->artisan('sayno:scan-lookalikes', ['brand' => 'bca', '--batch' => 500])->assertSuccessful();

        $verdicts = LookalikeDomain::pluck('verdict', 'domain');
        $this->assertSame(LookalikeDomain::PARKED, $verdicts['bca.info']);
        $this->assertSame(LookalikeDomain::UNRELATED, $verdicts['mybca.com']);
        $this->assertSame(LookalikeDomain::SUSPICIOUS, $verdicts['bca.net'], 'Tinjauan manual tidak boleh ditimpa.');
    }
}
