<?php

namespace App\Jobs;

use App\Models\InboundMessage;
use App\Services\Analysis\ApkUnreadableException;
use App\Services\Analysis\Link\DomainParser;
use App\Services\Checks\CheckService;
use App\Services\Reply\ReplyComposer;
use App\Services\WhatsApp\CloudApiClient;
use Illuminate\Contracts\Queue\ShouldBeEncrypted;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Throwable;

/**
 * Analisis satu pesan WhatsApp lalu balas lewat Cloud API.
 *
 * Payload antrean dienkripsi (nomor dan isi pesan hanya ada di sini, sementara).
 * Yang disimpan permanen hanya Check (domain/hash/skor) dan penanda InboundMessage.
 */
class AnalyzeIncomingMessage implements ShouldBeEncrypted, ShouldQueue
{
    use Queueable;

    public const KIND_TEXT = 'text';

    public const KIND_APK = 'apk';

    public const KIND_OTHER_FILE = 'other_file';

    public const KIND_UNSUPPORTED = 'unsupported';

    public int $tries = 1;

    public int $timeout = 150;

    public function __construct(
        public readonly string $wamid,
        public readonly string $to,
        public readonly string $kind,
        public readonly ?string $text = null,
        public readonly ?string $mediaId = null,
        public readonly ?string $fileName = null,
    ) {}

    public function handle(CheckService $checks, ReplyComposer $composer, CloudApiClient $whatsapp): void
    {
        $inbound = InboundMessage::query()->where('wamid', $this->wamid)->first();

        // Jendela layanan 24 jam: pesan yang terlalu lama tertahan tidak dibalas lagi.
        if ($inbound === null || $inbound->processed_at !== null
            || $inbound->received_at->lessThan(now()->subHours(config('sayno.whatsapp.reply_window_hours')))) {
            return;
        }

        $inbound->update(['processed_at' => now()]);

        try {
            $reply = match ($this->kind) {
                self::KIND_TEXT => $this->replyToText($checks, $composer, $inbound),
                self::KIND_APK => $this->replyToApk($checks, $composer, $whatsapp, $inbound),
                self::KIND_OTHER_FILE => __('whatsapp.unsupported_file'),
                default => __('whatsapp.greeting'),
            };
        } catch (Throwable $e) {
            report($e);
            $reply = __('whatsapp.error');
        }

        $whatsapp->sendText($this->to, $reply, $this->wamid);
    }

    private function replyToText(CheckService $checks, ReplyComposer $composer, InboundMessage $inbound): string
    {
        $text = trim((string) $this->text);

        if (mb_strtoupper($text) === __('whatsapp.emergency_keyword')) {
            return __('whatsapp.emergency');
        }

        $hasLink = app(DomainParser::class)->extractUrls($text, 1) !== [];

        if (! $hasLink && mb_strlen($text) < config('sayno.whatsapp.greeting_max_length')) {
            return __('whatsapp.greeting');
        }

        $check = $checks->checkMessage($text, 'wa', $inbound->sender_hash);
        $inbound->update(['check_id' => $check->id]);

        return $this->withDetailLink($composer->whatsApp($check), $check->id);
    }

    private function replyToApk(CheckService $checks, ReplyComposer $composer, CloudApiClient $whatsapp, InboundMessage $inbound): string
    {
        $maxBytes = (int) config('sayno.whatsapp.max_apk_bytes');
        $media = $whatsapp->mediaInfo((string) $this->mediaId);

        if ($media['file_size'] !== null && $media['file_size'] > $maxBytes) {
            return __('whatsapp.too_large');
        }

        $whatsapp->sendText($this->to, __('whatsapp.checking'), $this->wamid);

        $disk = Storage::disk('local');
        $relative = config('sayno.apk.quarantine_dir').'/'.Str::random(40).'.apk';
        $disk->makeDirectory(config('sayno.apk.quarantine_dir'));

        try {
            $whatsapp->downloadMedia($media['url'], $disk->path($relative), $maxBytes);
            $check = $checks->checkApk($disk->path($relative), $this->safeFileName(), 'wa', $inbound->sender_hash);
        } catch (ApkUnreadableException) {
            return __('whatsapp.unreadable');
        } finally {
            // APK tidak pernah disimpan setelah dianalisis.
            $disk->delete($relative);
        }

        $inbound->update(['check_id' => $check->id]);

        return $this->withDetailLink($composer->whatsApp($check, $this->safeFileName()), $check->id);
    }

    private function withDetailLink(string $reply, string $checkId): string
    {
        return $reply."\n\n".__('whatsapp.detail_link', ['url' => route('result', $checkId)]);
    }

    private function safeFileName(): string
    {
        $name = trim(preg_replace('/[^\p{L}\p{N} ._()-]+/u', '_', (string) $this->fileName));

        return Str::limit($name !== '' ? $name : 'file.apk', 80);
    }
}
