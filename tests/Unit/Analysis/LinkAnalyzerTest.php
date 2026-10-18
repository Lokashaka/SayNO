<?php

namespace Tests\Unit\Analysis;

use App\Enums\Status;
use App\Models\Brand;
use App\Models\LookalikeDomain;
use App\Services\Analysis\LinkAnalyzer;
use App\Services\Analysis\Signal;
use App\Services\Scoring\RiskScorer;
use Database\Seeders\BrandSeeder;
use Database\Seeders\OfficialDomainSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class LinkAnalyzerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed([BrandSeeder::class, OfficialDomainSeeder::class]);
        config(['services.safe_browsing.key' => null]);

        Http::preventStrayRequests();
    }

    #[Test]
    #[DataProvider('lookalikes')]
    public function it_detects_lookalike_domains(string $domain, string $brandName): void
    {
        $signals = $this->analyze($domain);
        $lookalike = $this->find($signals, 'link.lookalike');

        $this->assertNotNull($lookalike, "{$domain} seharusnya terdeteksi meniru {$brandName}.");
        $this->assertSame($brandName, $lookalike->meta['name']);
        $this->assertSame(50, $lookalike->weight);
    }

    #[Test]
    #[DataProvider('officials')]
    public function it_recognizes_official_domains(string $url): void
    {
        $signals = $this->analyze($url);

        $this->assertNotNull($this->find($signals, 'link.official'), "{$url} seharusnya dikenali sebagai resmi.");
        $this->assertSame(0, array_sum(array_map(fn (Signal $s) => $s->weight, $signals)));
        Http::assertNothingSent();
    }

    #[Test]
    #[DataProvider('benign')]
    public function it_does_not_flag_unrelated_legitimate_sites(string $url): void
    {
        $this->assertNull($this->find($this->analyze($url), 'link.lookalike'), "{$url} tidak boleh dianggap tiruan.");
    }

    #[Test]
    public function it_flags_new_domains_from_rdap(): void
    {
        Http::fake(['rdap.org/domain/promo-baru.xyz' => Http::response($this->rdap(now()->subDays(3)->toIso8601String()))]);

        $signals = $this->analyze('https://promo-baru.xyz/klaim');
        $signal = $this->find($signals, 'link.new_domain');

        $this->assertSame(25, $signal->weight);
        $this->assertSame(3, $signal->meta['age_days']);
        $this->assertSame(3, $signals[0]->meta['age_days']);
    }

    #[Test]
    public function it_survives_rdap_failures(): void
    {
        Http::fake(['rdap.org/*' => Http::response('', 500)]);

        $signals = $this->analyze('contoh-situs-baru.xyz');

        $this->assertSame('link.domain', $signals[0]->code);
        $this->assertArrayNotHasKey('registered_at', $signals[0]->meta);
    }

    #[Test]
    public function it_flags_blacklisted_urls_and_sends_the_key_as_a_header(): void
    {
        config(['services.safe_browsing.key' => 'kunci-uji']);
        Http::fake([
            'safebrowsing.googleapis.com/*' => Http::response(['matches' => [['threatType' => 'SOCIAL_ENGINEERING']]]),
        ]);

        $signals = $this->analyze('http://situs-jahat.example.com/login');

        $this->assertSame(100, $this->find($signals, 'link.blacklisted')->weight);
        $this->assertSame('listed', $signals[0]->meta['safe_browsing']);

        Http::assertSent(fn ($request) => str_contains($request->url(), 'safebrowsing')
            && $request->hasHeader('X-Goog-Api-Key', 'kunci-uji')
            && ! str_contains($request->url(), 'kunci-uji'));
    }

    #[Test]
    public function it_flags_url_shorteners_without_opening_them(): void
    {
        $signals = $this->analyze('https://bit.ly/3xYzAbc');

        $this->assertSame(10, $this->find($signals, 'link.shortener')->weight);
        Http::assertNotSent(fn ($request) => str_contains($request->url(), 'bit.ly/3xYzAbc'));
    }

    #[Test]
    public function it_detects_unicode_homographs(): void
    {
        // "tоkopedia" dengan huruf о Kiril.
        $lookalike = $this->find($this->analyze('https://tоkopedia.com'), 'link.lookalike');

        $this->assertSame('homoglyph', $lookalike->meta['technique']);
    }

    #[Test]
    public function a_domain_already_known_to_imitate_the_brand_is_dangerous(): void
    {
        $this->review('bca-care.com', LookalikeDomain::SUSPICIOUS);

        $signals = $this->analyze('https://bca-care.com/login');

        $this->assertNotNull($this->find($signals, 'link.lookalike'));
        $this->assertSame(30, $this->find($signals, 'link.known_lookalike')->weight);
        $this->assertSame(Status::Danger, app(RiskScorer::class)->score($signals)->status);
    }

    #[Test]
    public function a_same_name_business_is_not_called_an_imitation(): void
    {
        $this->review('bni.com', LookalikeDomain::UNRELATED);

        $signals = $this->analyze('https://bni.com');
        $otherOwner = $this->find($signals, 'link.other_owner');

        $this->assertNull($this->find($signals, 'link.lookalike'));
        $this->assertSame('bni.co.id', $otherOwner->meta['official']);
        $this->assertStringContainsString('bukan situs resmi BNI', __($otherOwner->reason, array_filter($otherOwner->meta, 'is_scalar')));
        $this->assertSame(Status::Caution, app(RiskScorer::class)->score($signals)->status, 'Tetap diingatkan bahwa ini bukan situs resmi.');
    }

    #[Test]
    public function parked_or_unreviewed_domains_keep_the_normal_lookalike_score(): void
    {
        $this->review('tokopedi.com', LookalikeDomain::PARKED);

        $signals = $this->analyze('tokopedi.com');

        $this->assertSame(50, $this->find($signals, 'link.lookalike')->weight);
        $this->assertNull($this->find($signals, 'link.known_lookalike'));
    }

    public static function lookalikes(): array
    {
        return array_map(fn ($line) => array_map('trim', explode('|', $line)), self::lines('lookalike.txt'));
    }

    public static function officials(): array
    {
        return array_map(fn ($line) => [$line], self::lines('official.txt'));
    }

    public static function benign(): array
    {
        return array_map(fn ($line) => [$line], self::lines('benign.txt'));
    }

    /**
     * @return list<Signal>
     */
    private function analyze(string $url): array
    {
        // Domain lama, kecuali test sudah memasang respons RDAP sendiri.
        Http::fake(['rdap.org/*' => Http::response($this->rdap('2015-03-01T00:00:00Z'))]);

        return app(LinkAnalyzer::class)->analyze($url);
    }

    /**
     * @param  list<Signal>  $signals
     */
    private function find(array $signals, string $code): ?Signal
    {
        return collect($signals)->firstWhere('code', $code);
    }

    private function review(string $domain, string $verdict): void
    {
        LookalikeDomain::create([
            'brand_id' => Brand::first()->id,
            'domain' => $domain,
            'technique' => 'combo',
            'source' => 'generator',
            'first_seen_at' => now(),
            'verdict' => $verdict,
            'verdict_source' => LookalikeDomain::SOURCE_MANUAL,
        ]);
    }

    private function rdap(string $registeredAt): array
    {
        return ['events' => [['eventAction' => 'registration', 'eventDate' => $registeredAt]]];
    }

    private static function lines(string $file): array
    {
        $lines = file(__DIR__.'/../../Fixtures/links/'.$file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

        return array_values(array_filter($lines, fn ($line) => ! str_starts_with($line, '#')));
    }
}
