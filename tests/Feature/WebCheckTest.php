<?php

namespace Tests\Feature;

use App\Enums\Status;
use App\Models\Check;
use App\Models\FakeApk;
use App\Models\LookalikeDomain;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\Test;
use Tests\Support\AxmlBuilder;
use Tests\TestCase;

class WebCheckTest extends TestCase
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
    }

    #[Test]
    public function the_public_pages_render(): void
    {
        $this->get('/')->assertOk()
            ->assertSee('Ragu sama pesan yang masuk? Teruskan dulu ke SayNO.')
            ->assertSee('Tempel link yang mencurigakan di sini')
            ->assertSee('Seret file APK ke sini atau pilih file')
            ->assertSee('brand/ikon-warna.svg');

        $this->get('/modus')->assertOk()->assertSee('File APK berkedok undangan atau surat');
        $this->get('/privasi')->assertOk()->assertSee('Yang tidak kami simpan')->assertSee('Isi pesan yang Anda teruskan atau tempel.');
    }

    #[Test]
    public function the_legal_pages_needed_by_meta_are_public_and_linked(): void
    {
        config(['sayno.contact_email' => 'kontak@contoh.id']);

        $this->get('/privasi')->assertOk()
            ->assertSee('Kebijakan Privasi')
            ->assertSee('id="hapus-data"', false)
            ->assertSee('Kirim email ke kontak@contoh.id dengan judul &quot;Hapus data SayNO&quot;.', false)
            ->assertSee('mailto:kontak@contoh.id', false)
            ->assertSee(route('terms'));

        $this->get('/syarat-ketentuan')->assertOk()
            ->assertSee('Syarat dan Ketentuan')
            ->assertSee('Hasil pemeriksaan bukan jaminan')
            ->assertSee(route('privacy'));

        $this->get('/')->assertSee(route('privacy'))->assertSee(route('terms'));
    }

    #[Test]
    public function the_whatsapp_button_only_appears_when_a_number_is_configured(): void
    {
        config(['sayno.whatsapp_number' => null]);
        $this->get('/')->assertDontSee('wa.me', false)->assertSee('Bot WhatsApp segera hadir');

        config(['sayno.whatsapp_number' => '+62 812-0000-0000']);
        $this->get('/')->assertSee('https://wa.me/6281200000000', false);
    }

    #[Test]
    public function a_lookalike_link_is_reported_as_danger_and_recorded_for_the_brand(): void
    {
        $response = $this->post('/cek/link', ['input' => 'https://login.bca-co.id/verifikasi']);

        $check = Check::sole();
        $response->assertRedirect(route('result', $check));

        $this->assertSame(Status::Danger, $check->status);
        $this->assertSame(75, $check->score);
        $this->assertSame('bca-co.id', $check->domain);
        $this->assertSame('web', $check->source);

        $this->get(route('result', $check))->assertOk()
            ->assertSee('Bahaya')
            ->assertSee('Jangan buka link ini.')
            ->assertSee('Situs bca-co.id meniru situs resmi BCA (bca.co.id).')
            ->assertSee('Situs ini baru dibuat 2 hari lalu.')
            ->assertSee('Situs resmi: bca.co.id')
            ->assertSee('Lihat detail teknis');

        $lookalike = LookalikeDomain::sole();
        $this->assertSame('bca-co.id', $lookalike->domain);
        $this->assertSame('user', $lookalike->source);
        $this->assertSame(1, $lookalike->times_reported);
    }

    #[Test]
    public function an_official_link_is_safe(): void
    {
        $this->post('/cek/link', ['input' => 'https://www.klikbca.com']);

        $check = Check::sole();
        $this->assertSame(Status::Safe, $check->status);

        $this->get(route('result', $check))
            ->assertSee('Tidak ditemukan tanda bahaya.')
            ->assertSee('Situs ini terdaftar sebagai situs resmi BCA.');
    }

    #[Test]
    public function a_pasted_message_is_checked_but_never_stored(): void
    {
        $message = 'Ma, ini aku. Nomor lama hilang, ini nomor baru. Tolong transfer dulu 2 juta ya, rahasia-unik-123';

        $this->post('/cek/link', ['input' => $message]);

        $check = Check::sole();
        $this->assertSame('text', $check->input_type->value);
        $this->assertSame(20, $check->score);

        foreach (['checks', 'signals', 'lookalike_domains', 'sessions'] as $table) {
            $this->assertFalse(
                DB::table($table)->get()->contains(fn ($row) => str_contains(json_encode($row), 'rahasia-unik-123')),
                "Isi pesan tidak boleh tersimpan di tabel {$table}.",
            );
        }

        $this->get(route('result', $check))->assertSee('Isi pesan mirip modus &quot;keluarga minta uang&quot;.', false);
    }

    #[Test]
    public function a_dangerous_apk_upload_is_analyzed_recorded_and_deleted(): void
    {
        Storage::fake('local');

        $path = AxmlBuilder::apk(['manifest', ['package' => 'com.mybca.update'], [
            ['uses-permission', ['name' => 'android.permission.READ_SMS']],
            ['uses-permission', ['name' => 'android.permission.RECEIVE_SMS']],
        ]]);
        $file = new UploadedFile($path, 'Undangan_Pernikahan.apk', 'application/vnd.android.package-archive', null, true);

        $response = $this->post('/cek/apk', ['apk' => $file]);

        $check = Check::sole();
        $response->assertRedirect(route('result', $check))->assertSessionHas('file_name', 'Undangan_Pernikahan.apk');

        $this->assertSame(Status::Danger, $check->status);
        $this->assertSame(hash_file('sha256', $path), $check->file_sha256);
        $this->assertSame([], Storage::disk('local')->allFiles(), 'File APK harus dihapus setelah dianalisis.');

        $fake = FakeApk::sole();
        $this->assertSame('com.mybca.update', $fake->package_name);
        $this->assertSame('bca', $fake->brand->slug);

        $this->withSession(['file_name' => 'Undangan_Pernikahan.apk'])
            ->get(route('result', $check))
            ->assertSee('Jangan instal aplikasi ini.')
            ->assertSee('Undangan_Pernikahan.apk')
            ->assertSee('Aplikasi ini bisa membaca SMS Anda, termasuk kode OTP bank.');

        unlink($path);
    }

    #[Test]
    public function an_unreadable_apk_gets_a_polite_message(): void
    {
        Storage::fake('local');

        $file = UploadedFile::fake()->createWithContent('Surat_Tilang.apk', 'bukan file zip');

        $this->post('/cek/apk', ['apk' => $file])
            ->assertSessionHasErrors(['apk' => __('result.apk_unreadable')]);

        $this->assertSame(0, Check::count());
        $this->assertSame([], Storage::disk('local')->allFiles());
    }

    #[Test]
    public function apks_over_20_mb_and_other_file_types_are_rejected(): void
    {
        $this->post('/cek/apk', ['apk' => UploadedFile::fake()->create('besar.apk', 21 * 1024)])
            ->assertSessionHasErrors(['apk' => 'Maaf, file ini lebih dari 20 MB, jadi belum bisa kami periksa.']);

        $this->post('/cek/apk', ['apk' => UploadedFile::fake()->create('foto.jpg', 10)])
            ->assertSessionHasErrors('apk');

        $this->assertSame(0, Check::count());
    }

    #[Test]
    public function a_large_apk_is_checked_from_its_manifest_without_uploading_the_file(): void
    {
        Storage::fake('local');

        $manifest = (new AxmlBuilder)->build(['manifest', ['package' => 'com.contoh.resi'], [
            ['uses-permission', ['name' => 'android.permission.READ_SMS']],
            ['uses-permission', ['name' => 'android.permission.RECEIVE_SMS']],
        ]]);
        $sha256 = hash('sha256', 'isi file 45 MB');

        $response = $this->post('/cek/apk-manifest', [
            'manifest' => UploadedFile::fake()->createWithContent('AndroidManifest.xml', $manifest),
            'sha256' => strtoupper($sha256),
            'file_name' => 'Cek_Resi_Paket.apk',
            'file_size' => 45 * 1024 * 1024,
        ]);

        $check = Check::sole();
        $response->assertRedirect(route('result', $check))->assertSessionHas('file_name', 'Cek_Resi_Paket.apk');

        $this->assertSame(Status::Danger, $check->status);
        $this->assertSame($sha256, $check->file_sha256);
        $this->assertSame([], Storage::disk('local')->allFiles());

        $this->get(route('result', $check))
            ->assertSee('Browser Anda (file tidak diunggah)')
            ->assertSee('45,0 MB');
    }

    #[Test]
    public function the_manifest_route_rejects_bad_input_with_a_message_on_the_upload_area(): void
    {
        $manifest = (new AxmlBuilder)->build(['manifest', ['package' => 'com.contoh']]);
        $valid = [
            'manifest' => UploadedFile::fake()->createWithContent('AndroidManifest.xml', $manifest),
            'sha256' => str_repeat('a', 64),
            'file_name' => 'aplikasi.apk',
            'file_size' => 30 * 1024 * 1024,
        ];

        $this->from('/')->post('/cek/apk-manifest', ['file_size' => 101 * 1024 * 1024] + $valid)
            ->assertRedirect('/')
            ->assertSessionHasErrors(['apk' => __('web.home.apk_too_large')]);

        $this->from('/')->post('/cek/apk-manifest', ['sha256' => 'bukan-hash'] + $valid)
            ->assertSessionHasErrors(['apk' => __('result.apk_unreadable')]);

        $this->from('/')->post('/cek/apk-manifest', ['manifest' => UploadedFile::fake()->createWithContent('AndroidManifest.xml', 'rusak')] + $valid)
            ->assertSessionHasErrors(['apk' => __('result.apk_unreadable')]);

        $this->assertSame(0, Check::count());
    }

    #[Test]
    public function the_home_page_offers_whatsapp_and_large_apk_support(): void
    {
        config(['sayno.whatsapp_number' => '62881036271708']);

        $this->get('/')
            ->assertSee('https://wa.me/62881036271708?text=Halo%20SayNO%2C%20tolong%20cek%20ini%3A', false)
            ->assertSee('Maksimal 100 MB')
            ->assertSee('data-browser-max-bytes="104857600"', false)
            ->assertSee(route('check.apk-manifest'));
    }

    #[Test]
    public function checks_are_rate_limited(): void
    {
        config(['sayno.web_rate_limit' => 2]);

        $this->post('/cek/link', ['input' => 'contoh.com'])->assertRedirect();
        $this->post('/cek/link', ['input' => 'contoh.com'])->assertRedirect();
        $this->post('/cek/link', ['input' => 'contoh.com'])->assertSessionHasErrors('throttle');

        $this->assertSame(2, Check::count());
    }

    #[Test]
    public function result_ids_are_not_sequential(): void
    {
        $this->post('/cek/link', ['input' => 'contoh.com']);

        $this->assertMatchesRegularExpression('/^[0-9a-hjkmnp-tv-z]{26}$/', Check::sole()->id);
        $this->get('/hasil/1')->assertNotFound();
    }
}
