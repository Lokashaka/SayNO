<?php

namespace Tests\Unit\Reply;

use App\Services\Checks\CheckService;
use App\Services\Reply\ReplyComposer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use PHPUnit\Framework\Attributes\Test;
use Tests\Support\AxmlBuilder;
use Tests\TestCase;

class ReplyComposerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();

        config(['services.safe_browsing.key' => null]);
        Http::preventStrayRequests();
        Http::fake(['rdap.org/*' => Http::response(['events' => [
            ['eventAction' => 'registration', 'eventDate' => '2015-01-01T00:00:00Z'],
        ]])]);
    }

    #[Test]
    public function a_dangerous_apk_reply_follows_the_template(): void
    {
        $path = AxmlBuilder::apk(['manifest', ['package' => 'com.contoh.undangan'], [
            ['uses-permission', ['name' => 'android.permission.READ_SMS']],
        ]]);

        $check = app(CheckService::class)->checkApk($path, 'Undangan_Pernikahan.apk', 'wa');
        unlink($path);

        $reply = app(ReplyComposer::class)->whatsApp($check, 'Undangan_Pernikahan.apk');
        $lines = explode("\n", $reply);

        $this->assertSame('🔴 *BAHAYA, jangan diinstal.*', $lines[0]);
        $this->assertStringContainsString('Aplikasi ini bisa membaca SMS Anda', $reply);
        $this->assertStringContainsString("Yang sebaiknya Anda lakukan:\n1. Hapus file ini.", $reply);
        $this->assertLessThanOrEqual(8, count($lines));
    }

    #[Test]
    public function a_safe_reply_stays_humble(): void
    {
        $check = app(CheckService::class)->checkMessage('https://www.tokopedia.com', 'wa');

        $reply = app(ReplyComposer::class)->whatsApp($check);

        $this->assertStringStartsWith('🟢 *Tidak ditemukan tanda bahaya.*', $reply);
        $this->assertStringContainsString('situs resmi Tokopedia', $reply);
        $this->assertStringNotContainsString('dijamin', mb_strtolower($reply));
    }

    #[Test]
    public function a_caution_reply_names_the_scam_pattern(): void
    {
        $check = app(CheckService::class)->checkMessage('Paket Anda tertahan karena alamat tidak lengkap, cek di bit.ly/abc123', 'wa');

        $summary = app(ReplyComposer::class)->summary($check);

        $this->assertSame('caution', $summary['status']->value);
        $this->assertContains('Isi pesan mirip modus "paket tertahan".', $summary['reasons']);
        $this->assertContains('Link memakai penyingkat alamat, jadi tujuan aslinya tersembunyi.', $summary['reasons']);
    }
}
