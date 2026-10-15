<?php

namespace Tests\Unit\Analysis;

use App\Services\Analysis\ApkAnalyzer;
use App\Services\Analysis\ApkUnreadableException;
use App\Services\Analysis\Signal;
use Database\Seeders\BrandSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\Support\AxmlBuilder;
use Tests\TestCase;
use ZipArchive;

class ApkAnalyzerTest extends TestCase
{
    use RefreshDatabase;

    /** @var list<string> */
    private array $tempFiles = [];

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(BrandSeeder::class);
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        foreach (array_filter($this->tempFiles, 'file_exists') as $file) {
            unlink($file);
        }
    }

    #[Test]
    public function it_flags_an_sms_stealer_disguised_as_a_wedding_invitation(): void
    {
        // Struktur manifest meniru APK "undangan" pencuri OTP yang beredar: baca/terima SMS,
        // receiver SMS_RECEIVED, layanan aksesibilitas, dan nama merek bank di paket.
        $path = $this->apk(['manifest', ['package' => 'com.bca.undangan.digital', 'versionName' => '1.0'], [
            ['uses-permission', ['name' => 'android.permission.READ_SMS']],
            ['uses-permission', ['name' => 'android.permission.RECEIVE_SMS']],
            ['uses-permission', ['name' => 'android.permission.INTERNET']],
            ['uses-permission', ['name' => 'android.permission.SYSTEM_ALERT_WINDOW']],
            ['application', ['label' => 'Undangan Digital'], [
                ['receiver', ['name' => '.SmsReceiver'], [
                    ['intent-filter', [], [['action', ['name' => 'android.provider.Telephony.SMS_RECEIVED']]]],
                ]],
                ['service', ['name' => '.Helper', 'permission' => 'android.permission.BIND_ACCESSIBILITY_SERVICE']],
            ]],
        ]]);

        $signals = app(ApkAnalyzer::class)->analyze($path, 'Undangan_Pernikahan_Digital.apk');
        $codes = array_map(fn (Signal $s) => $s->code, $signals);

        $this->assertSame('apk.info', $codes[0]);
        $this->assertSame('com.bca.undangan.digital', $signals[0]->meta['package']);
        $this->assertSame('Undangan Digital', $signals[0]->meta['label']);
        $this->assertSame(hash_file('sha256', $path), $signals[0]->meta['sha256']);
        $this->assertContains('apk.read_sms', $codes);
        $this->assertContains('apk.accessibility', $codes);
        $this->assertContains('apk.overlay', $codes);
        $this->assertContains('apk.document_disguise', $codes);
        $this->assertContains('apk.brand_impersonation', $codes);
        $this->assertSame('BCA', collect($signals)->firstWhere('code', 'apk.brand_impersonation')->meta['name']);
    }

    #[Test]
    public function it_does_not_flag_the_official_app_of_a_brand(): void
    {
        $path = $this->apk(['manifest', ['package' => 'com.bca'], [
            ['uses-permission', ['name' => 'android.permission.INTERNET']],
        ]]);

        $codes = array_map(fn (Signal $s) => $s->code, app(ApkAnalyzer::class)->analyze($path, 'BCA_mobile.apk'));

        $this->assertSame(['apk.info'], $codes);
    }

    #[Test]
    public function it_gives_no_risk_signals_for_a_harmless_app(): void
    {
        // Manifest asli dari APK contoh Android Studio.
        $path = $this->apk(['manifest', []], file_get_contents(__DIR__.'/../../Fixtures/apk/watchface.AndroidManifest.bin'));

        $signals = app(ApkAnalyzer::class)->analyze($path, 'ComplicationWatchFace.apk');

        $this->assertCount(1, $signals);
        $this->assertSame('androidx.wear.watchface.samples.app', $signals[0]->meta['package']);
        $this->assertContains('android.permission.WAKE_LOCK', $signals[0]->meta['permissions']);
    }

    #[Test]
    public function it_detects_contact_and_sms_spreading_and_installers(): void
    {
        $path = $this->apk(['manifest', ['package' => 'com.contoh.resi'], [
            ['uses-permission', ['name' => 'android.permission.READ_CONTACTS']],
            ['uses-permission', ['name' => 'android.permission.SEND_SMS']],
            ['uses-permission', ['name' => 'android.permission.REQUEST_INSTALL_PACKAGES']],
        ]]);

        $codes = array_map(fn (Signal $s) => $s->code, app(ApkAnalyzer::class)->analyze($path, 'Cek-Resi-JNE.apk'));

        $this->assertContains('apk.spread_sms', $codes);
        $this->assertContains('apk.install_packages', $codes);
        $this->assertContains('apk.document_disguise', $codes);
    }

    #[Test]
    public function it_reads_apks_whose_compression_method_was_tampered_with(): void
    {
        // Trik APK berbahaya: manifest disimpan tanpa kompresi, tetapi daftar isi ZIP
        // mengaku "deflate". ZipArchive gagal; Android dan SayNO tetap bisa membaca.
        $manifest = (new AxmlBuilder)->build(['manifest', ['package' => 'com.contoh.tilang'], [
            ['uses-permission', ['name' => 'android.permission.RECEIVE_SMS']],
        ]]);

        $path = $this->temp('');
        $zip = new ZipArchive;
        $zip->open($path, ZipArchive::CREATE | ZipArchive::OVERWRITE);
        $zip->addFromString('AndroidManifest.xml', $manifest);
        $zip->setCompressionName('AndroidManifest.xml', ZipArchive::CM_STORE);
        $zip->close();

        $bytes = file_get_contents($path);
        file_put_contents($path, substr_replace($bytes, pack('v', 8), strpos($bytes, "PK\x01\x02") + 10, 2));

        $signals = app(ApkAnalyzer::class)->analyze($path, 'E-Tilang.apk');

        $this->assertSame('com.contoh.tilang', $signals[0]->meta['package']);
        $this->assertContains('apk.read_sms', array_map(fn (Signal $s) => $s->code, $signals));
    }

    #[Test]
    public function a_manifest_alone_gives_the_same_signals_as_the_full_file(): void
    {
        $manifest = ['manifest', ['package' => 'com.bca.undangan'], [
            ['uses-permission', ['name' => 'android.permission.READ_SMS']],
        ]];
        $path = $this->apk($manifest);

        $fromFile = app(ApkAnalyzer::class)->analyze($path, 'Undangan.apk');
        $fromManifest = app(ApkAnalyzer::class)->analyzeManifest((new AxmlBuilder)->build($manifest), 'Undangan.apk', hash_file('sha256', $path), filesize($path));

        $codes = fn (array $signals) => array_map(fn (Signal $s) => $s->code, $signals);
        $this->assertSame($codes($fromFile), $codes($fromManifest));
        $this->assertSame('server', $fromFile[0]->meta['read_by']);
        $this->assertSame('browser', $fromManifest[0]->meta['read_by']);
    }

    #[Test]
    public function it_rejects_files_that_are_not_zip(): void
    {
        $path = $this->temp('bukan apk');

        $this->expectException(ApkUnreadableException::class);

        app(ApkAnalyzer::class)->analyze($path, 'file.apk');
    }

    #[Test]
    public function it_rejects_zips_without_a_manifest(): void
    {
        $path = $this->temp('');
        $zip = new ZipArchive;
        $zip->open($path, ZipArchive::CREATE | ZipArchive::OVERWRITE);
        $zip->addFromString('foto.jpg', 'bukan manifest');
        $zip->close();

        $this->expectException(ApkUnreadableException::class);

        app(ApkAnalyzer::class)->analyze($path, 'foto.apk');
    }

    #[Test]
    public function it_rejects_a_corrupt_manifest(): void
    {
        $path = $this->apk(['manifest', []], random_bytes(64));

        $this->expectException(ApkUnreadableException::class);

        app(ApkAnalyzer::class)->analyze($path, 'rusak.apk');
    }

    private function apk(array $manifest, ?string $raw = null): string
    {
        return $this->tempFiles[] = AxmlBuilder::apk($manifest, $raw);
    }

    private function temp(string $content): string
    {
        $path = tempnam(sys_get_temp_dir(), 'sayno-test-');
        file_put_contents($path, $content);

        return $this->tempFiles[] = $path;
    }
}
