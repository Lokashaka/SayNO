<?php

namespace Tests\Feature;

use App\Models\Brand;
use App\Models\LookalikeDomain;
use App\Services\Checks\CheckService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class PhishingListTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();

        config(['services.safe_browsing.key' => null]);
        Http::preventStrayRequests();
        Http::fake(['rdap.org/*' => Http::response(['events' => [
            ['eventAction' => 'registration', 'eventDate' => now()->subDays(2)->toIso8601String()],
        ]])]);

        $checks = app(CheckService::class);
        $checks->checkMessage('https://login.bca-co.id/tarif', 'web');
        $checks->checkMessage('bca-co.id', 'wa');
        $checks->checkMessage('Paket Anda tertahan, lengkapi alamat di https://tokopedia-promo.com/klaim pesan-rahasia-xyz', 'web');
        $checks->checkMessage('https://www.klikbca.com', 'web');
        $checks->checkMessage('https://contoh-biasa.com', 'web');
    }

    #[Test]
    public function it_lists_dangerous_domains_defanged_and_grouped(): void
    {
        $this->get('/daftar-phishing')->assertOk()
            ->assertSee('Daftar link phishing')
            ->assertSee('2 domain tercatat')
            ->assertSee('bca-co[.]id')
            ->assertSee('Meniru situs resmi BCA')
            ->assertSee('2 kali')
            ->assertSee('tokopedia-promo[.]com')
            ->assertDontSee('klikbca')
            ->assertDontSee('contoh-biasa')
            ->assertDontSee('pesan-rahasia-xyz')
            ->assertDontSee('href="https://bca-co.id', false);
    }

    #[Test]
    public function it_can_be_searched_with_a_pasted_link(): void
    {
        $this->get('/daftar-phishing?q='.urlencode('https://www.bca-co.id/login'))
            ->assertSee('bca-co[.]id')
            ->assertDontSee('tokopedia-promo[.]com');
    }

    #[Test]
    public function a_domain_that_is_not_listed_is_not_called_safe(): void
    {
        $this->get('/daftar-phishing?q=situs-lain.com')
            ->assertSee('situs-lain.com belum ada di daftar ini.')
            ->assertSee('Belum tercatat bukan berarti aman.');
    }

    #[Test]
    public function search_input_cannot_act_as_a_like_wildcard(): void
    {
        $this->get('/daftar-phishing?q=%25')->assertSee('belum ada di daftar ini.');
    }

    #[Test]
    public function it_lists_registered_lookalike_domains_from_the_scanner(): void
    {
        $bca = Brand::where('slug', 'bca')->firstOrFail();

        $domains = [
            'klikbca-promo.com' => ['active', null],
            'bca-care.com' => ['active', LookalikeDomain::SUSPICIOUS],
            'bca-co.id' => ['active', null],
            'k1ikbca.com' => ['gone', null],
            'klik-bca.com' => ['active', LookalikeDomain::PARKED],
            'bca.co' => ['active', LookalikeDomain::UNRELATED],
        ];

        foreach ($domains as $domain => [$status, $verdict]) {
            LookalikeDomain::updateOrCreate(['brand_id' => $bca->id, 'domain' => $domain], [
                'technique' => 'combo', 'source' => 'generator', 'first_seen_at' => now(), 'status' => $status, 'verdict' => $verdict,
            ]);
        }

        $this->get('/daftar-phishing')->assertOk()
            ->assertSee('Domain tiruan terdaftar')
            ->assertSee('klikbca-promo[.]com')
            ->assertSee('nama resmi ditambah kata lain')
            ->assertSee('Tidak diketahui')
            ->assertSee('4 domain tercatat')
            ->assertSeeInOrder(['bca-care[.]com', 'Aktif meniru', 'klikbca-promo[.]com', 'Baru ditemukan'])
            ->assertDontSee('k1ikbca')
            ->assertDontSee('klik-bca[.]com')
            ->assertDontSee('bca[.]co<', false)
            ->assertDontSee('href="https://klikbca-promo', false);

        $this->get('/daftar-phishing?q=klikbca-promo.com')
            ->assertSee('klikbca-promo[.]com')
            ->assertDontSee('Dinyatakan Bahaya')
            ->assertDontSee('tokopedia-promo[.]com');

        $this->get('/')->assertSee('/daftar-phishing#tiruan', false)
            ->assertSeeInOrder(['Domain tiruan dipantau', '2']);
    }

    #[Test]
    public function it_offers_the_whatsapp_check(): void
    {
        config(['sayno.whatsapp_number' => '62881036271708']);

        $this->get('/daftar-phishing')->assertSee('https://wa.me/62881036271708', false);
    }
}
