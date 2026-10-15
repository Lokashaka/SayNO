<?php

namespace Tests\Feature;

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

        $this->mock(DnsResolver::class)
            ->shouldReceive('isRegistered')
            ->andReturnUsing(fn (string $domain) => in_array($domain, ['bca.id', 'bca.com'], true));

        $this->artisan('sayno:scan-lookalikes', ['brand' => 'bca', '--batch' => 5])->assertSuccessful();

        $found = LookalikeDomain::pluck('domain')->all();
        $this->assertContains('bca.id', $found);
        $this->assertNotContains('bca.co.id', $found, 'Domain resmi tidak boleh masuk daftar tiruan.');
        $this->assertSame('generator', LookalikeDomain::firstWhere('domain', 'bca.id')->source);
        $this->assertSame('2026-10-01', LookalikeDomain::firstWhere('domain', 'bca.id')->registered_at->toDateString());
    }
}
