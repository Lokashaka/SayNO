<?php

namespace Tests\Feature;

use App\Models\Brand;
use App\Models\LookalikeDomain;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class OrgDashboardTest extends TestCase
{
    use RefreshDatabase;

    private Brand $brand;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();

        $this->brand = Brand::where('slug', 'bca')->sole();
        $this->brand->update(['access_token_hash' => hash('sha256', 'token-rahasia')]);

        LookalikeDomain::create([
            'brand_id' => $this->brand->id,
            'domain' => 'bca-co.id',
            'technique' => 'combo',
            'source' => 'user',
            'first_seen_at' => now()->subDay(),
            'last_checked_at' => now(),
            'times_reported' => 3,
        ]);
    }

    #[Test]
    public function it_asks_for_a_token_first(): void
    {
        $this->get('/org/bca')->assertOk()->assertSee('Masuk dashboard BCA')->assertDontSee('bca-co.id');
    }

    #[Test]
    public function a_wrong_token_is_rejected(): void
    {
        $this->post('/org/bca/masuk', ['token' => 'salah'])->assertSessionHasErrors('token');
        $this->get('/org/bca')->assertDontSee('bca-co.id');
    }

    #[Test]
    public function the_right_token_opens_the_dashboard(): void
    {
        $this->post('/org/bca/masuk', ['token' => 'token-rahasia'])->assertRedirect('/org/bca');

        $this->get('/org/bca')->assertOk()
            ->assertSee('Domain tiruan aktif')
            ->assertSee('bca-co.id')
            ->assertSee('nama resmi ditambah kata lain')
            ->assertSee('Laporan pengguna')
            ->assertSee('Belum ada APK palsu yang terdeteksi.');
    }

    #[Test]
    public function access_to_one_brand_does_not_open_another(): void
    {
        $this->post('/org/bca/masuk', ['token' => 'token-rahasia']);

        $this->get('/org/bri')->assertSee('Masuk dashboard BRI');
    }

    #[Test]
    public function unknown_brands_return_404(): void
    {
        $this->get('/org/tidak-ada')->assertNotFound();
    }
}
