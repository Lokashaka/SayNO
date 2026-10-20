<?php

namespace Tests\Feature;

use App\Jobs\AnalyzeIncomingMessage;
use App\Models\Check;
use App\Models\InboundMessage;
use Illuminate\Contracts\Queue\ShouldBeEncrypted;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Sleep;
use Illuminate\Testing\TestResponse;
use PHPUnit\Framework\Attributes\Test;
use Tests\Support\AxmlBuilder;
use Tests\TestCase;

class WhatsAppWebhookTest extends TestCase
{
    use RefreshDatabase;

    private const SENDER = '6281234567890';

    private const MEDIA_URL = 'https://lookaside.fbsbx.com/whatsapp_business/attachments/?mid=123';

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();

        config([
            'services.meta.app_secret' => 'rahasia-aplikasi-uji',
            'services.meta.verify_token' => 'token-verifikasi-uji',
            'services.meta.access_token' => 'token-akses-uji',
            'services.meta.phone_number_id' => '1111',
            'services.safe_browsing.key' => null,
        ]);

        Storage::fake('local');
        Http::preventStrayRequests();
    }

    #[Test]
    public function meta_can_verify_the_webhook(): void
    {
        $this->get('/webhook/whatsapp?hub.mode=subscribe&hub.verify_token=token-verifikasi-uji&hub.challenge=12345')
            ->assertOk()
            ->assertSeeText('12345');

        $this->get('/webhook/whatsapp?hub.mode=subscribe&hub.verify_token=salah&hub.challenge=12345')->assertForbidden();

        config(['services.meta.verify_token' => null]);
        $this->get('/webhook/whatsapp?hub.mode=subscribe&hub.verify_token=&hub.challenge=12345')->assertForbidden();
    }

    #[Test]
    public function unsigned_or_wrongly_signed_requests_are_rejected(): void
    {
        $body = json_encode($this->payload([$this->textMessage('wamid.A', 'https://bca-co.id')]));

        $this->call('POST', '/webhook/whatsapp', [], [], [], ['CONTENT_TYPE' => 'application/json'], $body)->assertForbidden();
        $this->call('POST', '/webhook/whatsapp', [], [], [], [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_X_HUB_SIGNATURE_256' => 'sha256='.hash_hmac('sha256', $body, 'kunci-lain'),
        ], $body)->assertForbidden();

        config(['services.meta.app_secret' => null]);
        $this->postSigned($this->payload([$this->textMessage('wamid.A', 'x')]), 'rahasia-aplikasi-uji')->assertForbidden();

        $this->assertSame(0, InboundMessage::count());
    }

    #[Test]
    public function a_forwarded_scam_link_gets_a_danger_reply_without_storing_the_message(): void
    {
        $this->fakeMeta();

        $this->postSigned($this->payload([
            $this->textMessage('wamid.LINK1', 'Nasabah Yth, tarif transfer berubah. Isi formulir: https://login.bca-co.id/tarif rahasia-unik-777'),
        ]))->assertOk()->assertSeeText('EVENT_RECEIVED');

        $sent = $this->sentMessages();
        $this->assertCount(1, $sent);
        $this->assertSame(self::SENDER, $sent[0]['to']);
        $this->assertSame('wamid.LINK1', $sent[0]['context']['message_id']);
        $this->assertFalse($sent[0]['text']['preview_url']);
        $this->assertStringStartsWith('🔴 *BAHAYA, jangan dibuka.*', $sent[0]['text']['body']);
        $this->assertStringContainsString('meniru situs resmi BCA', $sent[0]['text']['body']);
        $this->assertStringContainsString(route('result', Check::sole()), $sent[0]['text']['body']);

        $check = Check::sole();
        $this->assertSame('wa', $check->source);
        $this->assertSame(InboundMessage::hashSender(self::SENDER), $check->sender_hash);
        $this->assertSame($check->id, InboundMessage::sole()->check_id);

        foreach (['checks', 'signals', 'inbound_messages', 'jobs', 'failed_jobs'] as $table) {
            $dump = json_encode(DB::table($table)->get());
            $this->assertStringNotContainsString(self::SENDER, $dump, "Nomor asli tidak boleh tersimpan di {$table}.");
            $this->assertStringNotContainsString('rahasia-unik-777', $dump, "Isi pesan tidak boleh tersimpan di {$table}.");
        }
    }

    #[Test]
    public function a_message_redelivered_by_meta_is_answered_only_once(): void
    {
        $this->fakeMeta();
        $payload = $this->payload([$this->textMessage('wamid.SAMA', 'https://bit.ly/3abcdef paket Anda tertahan di gudang')]);

        $this->postSigned($payload)->assertOk();
        $this->postSigned($payload)->assertOk();

        $this->assertCount(1, $this->sentMessages());
        $this->assertSame(1, InboundMessage::count());
    }

    #[Test]
    public function short_greetings_and_the_emergency_keyword_get_fixed_replies(): void
    {
        $this->fakeMeta();

        $this->postSigned($this->payload([
            $this->textMessage('wamid.HALO', 'Halo'),
            $this->textMessage('wamid.SUDAH', 'sudah'),
        ]));

        $bodies = array_column(array_column($this->sentMessages(), 'text'), 'body');
        $this->assertSame(__('whatsapp.greeting'), $bodies[0]);
        $this->assertSame(__('whatsapp.emergency'), $bodies[1]);
        $this->assertSame(0, Check::count());
    }

    #[Test]
    public function a_dangerous_apk_is_downloaded_from_meta_analyzed_and_deleted(): void
    {
        $apk = file_get_contents(AxmlBuilder::apk(['manifest', ['package' => 'com.undangan.digital'], [
            ['uses-permission', ['name' => 'android.permission.READ_SMS']],
        ]]));
        $this->fakeMeta(media: ['url' => self::MEDIA_URL, 'mime_type' => 'application/vnd.android.package-archive', 'file_size' => strlen($apk)], download: $apk);

        $this->postSigned($this->payload([$this->documentMessage('wamid.APK1', 'Undangan_Pernikahan.apk', 'application/vnd.android.package-archive')]));

        $bodies = array_column(array_column($this->sentMessages(), 'text'), 'body');
        $this->assertSame(__('whatsapp.checking'), $bodies[0]);
        $this->assertStringStartsWith('🔴 *BAHAYA, jangan diinstal.*', $bodies[1]);
        $this->assertStringContainsString('File "Undangan_Pernikahan.apk"', $bodies[1]);
        $this->assertStringContainsString('membaca SMS', $bodies[1]);
        $this->assertSame(hash('sha256', $apk), Check::sole()->file_sha256);
        $this->assertSame([], Storage::disk('local')->allFiles(), 'APK harus dihapus setelah dianalisis.');

        Http::assertSent(fn (Request $request) => $request->url() === self::MEDIA_URL && $request->hasHeader('Authorization', 'Bearer token-akses-uji'));
    }

    #[Test]
    public function a_slow_dns_lookup_for_meta_media_is_retried(): void
    {
        Sleep::fake();
        $apk = file_get_contents(AxmlBuilder::apk(['manifest', ['package' => 'com.bca.mobile.update'], [
            ['uses-permission', ['name' => 'android.permission.READ_SMS']],
        ]]));

        Http::fake([
            'graph.facebook.com/*/1111/messages' => Http::response(['messages' => [['id' => 'wamid.balasan']]]),
            'graph.facebook.com/*' => Http::response(['url' => self::MEDIA_URL, 'file_size' => strlen($apk)]),
            // Seperti di server: resolusi DNS lookaside.fbsbx.com sempat habis waktu.
            'lookaside.fbsbx.com/*' => Http::sequence()
                ->pushFailedConnection('cURL error 28: Resolving timed out after 5000 milliseconds')
                ->push($apk),
        ]);

        $this->postSigned($this->payload([$this->documentMessage('wamid.DNS', 'BCA_Mobile_Update.apk', 'application/vnd.android.package-archive')]));

        $bodies = array_column(array_column($this->sentMessages(), 'text'), 'body');
        $this->assertCount(2, $bodies);
        $this->assertStringStartsWith('🔴 *BAHAYA, jangan diinstal.*', $bodies[1]);
        $this->assertSame(hash('sha256', $apk), Check::sole()->file_sha256);
    }

    #[Test]
    public function apks_over_100_mb_are_refused_without_downloading(): void
    {
        $this->fakeMeta(media: ['url' => self::MEDIA_URL, 'file_size' => 150 * 1024 * 1024]);

        $this->postSigned($this->payload([$this->documentMessage('wamid.BESAR', 'Game_Mod.apk', 'application/vnd.android.package-archive')]));

        $this->assertSame(__('whatsapp.too_large'), $this->sentMessages()[0]['text']['body']);
        Http::assertNotSent(fn (Request $request) => $request->url() === self::MEDIA_URL);
    }

    #[Test]
    public function media_is_never_downloaded_from_a_host_outside_meta(): void
    {
        $this->fakeMeta(media: ['url' => 'https://penyerang.example/apk', 'file_size' => 1000]);

        $this->postSigned($this->payload([$this->documentMessage('wamid.HOST', 'a.apk', 'application/vnd.android.package-archive')]));

        $this->assertSame(__('whatsapp.error'), $this->sentMessages()[1]['text']['body']);
        Http::assertNotSent(fn (Request $request) => str_contains($request->url(), 'penyerang.example'));
    }

    #[Test]
    public function other_files_and_media_get_helpful_replies(): void
    {
        $this->fakeMeta();

        $this->postSigned($this->payload([
            $this->documentMessage('wamid.PDF', 'Undangan.pdf', 'application/pdf'),
            ['from' => self::SENDER, 'id' => 'wamid.IMG', 'timestamp' => (string) now()->timestamp, 'type' => 'image', 'image' => ['id' => '9', 'mime_type' => 'image/jpeg']],
            ['from' => self::SENDER, 'id' => 'wamid.REACT', 'timestamp' => (string) now()->timestamp, 'type' => 'reaction', 'reaction' => ['emoji' => '👍']],
        ]));

        $bodies = array_column(array_column($this->sentMessages(), 'text'), 'body');
        $this->assertSame([__('whatsapp.unsupported_file'), __('whatsapp.greeting')], $bodies);
    }

    #[Test]
    public function captions_on_images_are_checked_like_text(): void
    {
        $this->fakeMeta();

        $this->postSigned($this->payload([
            ['from' => self::SENDER, 'id' => 'wamid.CAP', 'timestamp' => (string) now()->timestamp, 'type' => 'image', 'image' => ['id' => '9', 'caption' => 'Cek resi paket Anda di https://jne-cekresi.xyz/lacak']],
        ]));

        $this->assertStringContainsString('meniru situs resmi JNE', $this->sentMessages()[0]['text']['body']);
    }

    #[Test]
    public function status_updates_and_other_numbers_are_ignored(): void
    {
        $this->fakeMeta();

        $statuses = $this->payload([]);
        $statuses['entry'][0]['changes'][0]['value']['statuses'] = [['id' => 'wamid.X', 'status' => 'delivered']];
        $this->postSigned($statuses)->assertOk();

        $otherNumber = $this->payload([$this->textMessage('wamid.LAIN', 'https://bca-co.id')]);
        $otherNumber['entry'][0]['changes'][0]['value']['metadata']['phone_number_id'] = '9999';
        $this->postSigned($otherNumber)->assertOk();

        $this->assertSame([], $this->sentMessages());
        $this->assertSame(0, InboundMessage::count());
    }

    #[Test]
    public function messages_older_than_the_service_window_are_not_answered(): void
    {
        $this->fakeMeta();
        $old = $this->textMessage('wamid.LAMA', 'https://bca-co.id');
        $old['timestamp'] = (string) now()->subDays(2)->timestamp;

        $this->postSigned($this->payload([$old]))->assertOk();

        $this->assertSame([], $this->sentMessages());
    }

    #[Test]
    public function the_queued_payload_is_encrypted(): void
    {
        $this->assertContains(ShouldBeEncrypted::class, class_implements(AnalyzeIncomingMessage::class));
    }

    private function fakeMeta(?array $media = null, ?string $download = null): void
    {
        Http::fake([
            'graph.facebook.com/*/1111/messages' => Http::response(['messages' => [['id' => 'wamid.balasan']]]),
            'graph.facebook.com/*' => Http::response($media ?? ['error' => ['message' => 'tidak ada']], $media ? 200 : 404),
            'lookaside.fbsbx.com/*' => Http::response($download ?? ''),
            'rdap.org/*' => Http::response(['events' => [['eventAction' => 'registration', 'eventDate' => now()->subDays(2)->toIso8601String()]]]),
        ]);
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function sentMessages(): array
    {
        return Http::recorded(fn (Request $request) => str_ends_with($request->url(), '/1111/messages'))
            ->map(fn (array $pair) => $pair[0]->data())
            ->values()
            ->all();
    }

    private function postSigned(array $payload, ?string $secret = null): TestResponse
    {
        $body = json_encode($payload);

        return $this->call('POST', '/webhook/whatsapp', [], [], [], [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_X_HUB_SIGNATURE_256' => 'sha256='.hash_hmac('sha256', $body, $secret ?? 'rahasia-aplikasi-uji'),
        ], $body);
    }

    private function payload(array $messages): array
    {
        return [
            'object' => 'whatsapp_business_account',
            'entry' => [[
                'id' => 'WABA',
                'changes' => [[
                    'field' => 'messages',
                    'value' => array_filter([
                        'messaging_product' => 'whatsapp',
                        'metadata' => ['display_phone_number' => '62881036271708', 'phone_number_id' => '1111'],
                        'contacts' => [['profile' => ['name' => 'Bu Sri'], 'wa_id' => self::SENDER]],
                        'messages' => $messages ?: null,
                    ]),
                ]],
            ]],
        ];
    }

    private function textMessage(string $wamid, string $body): array
    {
        return ['from' => self::SENDER, 'id' => $wamid, 'timestamp' => (string) now()->timestamp, 'type' => 'text', 'text' => ['body' => $body]];
    }

    private function documentMessage(string $wamid, string $fileName, string $mime): array
    {
        return ['from' => self::SENDER, 'id' => $wamid, 'timestamp' => (string) now()->timestamp, 'type' => 'document', 'document' => ['id' => '555', 'filename' => $fileName, 'mime_type' => $mime]];
    }
}
