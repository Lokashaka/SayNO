<?php

namespace App\Services\WhatsApp;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;
use Throwable;

/**
 * WhatsApp Cloud API (Graph API Meta): kirim pesan dan unduh media.
 *
 * Token hanya dikirim di header Authorization, jadi tidak ikut tercatat di pesan error.
 */
class CloudApiClient
{
    /**
     * Kirim pesan teks. Pratinjau link dimatikan agar link berbahaya tidak ikut ditampilkan.
     */
    public function sendText(string $to, string $body, ?string $replyTo = null): bool
    {
        $payload = [
            'messaging_product' => 'whatsapp',
            'recipient_type' => 'individual',
            'to' => $to,
            'type' => 'text',
            'text' => ['body' => $body, 'preview_url' => false],
        ];

        if ($replyTo !== null) {
            $payload['context'] = ['message_id' => $replyTo];
        }

        try {
            $response = $this->graph()->post($this->url(config('services.meta.phone_number_id').'/messages'), $payload);
        } catch (Throwable) {
            Log::warning('WhatsApp: gagal menghubungi Graph API untuk mengirim pesan.');

            return false;
        }

        if (! $response->successful()) {
            Log::warning('WhatsApp: pesan ditolak Graph API.', [
                'status' => $response->status(),
                'error_code' => $response->json('error.code'),
            ]);

            return false;
        }

        return true;
    }

    /**
     * Informasi media: url unduhan sementara, mime_type, file_size.
     *
     * @return array{url: string, mime_type: ?string, file_size: ?int}
     */
    public function mediaInfo(string $mediaId): array
    {
        if (! preg_match('/^\d+$/', $mediaId)) {
            throw new RuntimeException('ID media tidak valid.');
        }

        $response = $this->graph()->get($this->url($mediaId));

        if (! $response->successful() || ! is_string($response->json('url'))) {
            throw new RuntimeException('Informasi media tidak bisa diambil.');
        }

        return [
            'url' => $response->json('url'),
            'mime_type' => $response->json('mime_type'),
            'file_size' => $response->json('file_size') !== null ? (int) $response->json('file_size') : null,
        ];
    }

    /**
     * Unduh media ke path lokal. Hanya dari host Meta; ukuran dibatasi.
     */
    public function downloadMedia(string $url, string $path, int $maxBytes): void
    {
        if (! $this->isMetaHost($url)) {
            throw new RuntimeException('URL media bukan milik Meta.');
        }

        $response = $this->graph()
            ->timeout(90)
            ->withOptions(['sink' => $path])
            ->get($url);

        if (! $response->successful()) {
            throw new RuntimeException('Media tidak bisa diunduh.');
        }

        clearstatcache(true, $path);

        if (! is_file($path) || filesize($path) === 0 || filesize($path) > $maxBytes) {
            throw new RuntimeException('Ukuran media tidak sesuai.');
        }
    }

    private function isMetaHost(string $url): bool
    {
        $host = parse_url($url, PHP_URL_HOST);

        if (parse_url($url, PHP_URL_SCHEME) !== 'https' || ! is_string($host)) {
            return false;
        }

        foreach (config('services.meta.media_hosts') as $allowed) {
            if ($host === $allowed || str_ends_with($host, '.'.$allowed)) {
                return true;
            }
        }

        return false;
    }

    private function graph(): PendingRequest
    {
        return Http::withToken((string) config('services.meta.access_token'))
            ->acceptJson()
            ->timeout(15)
            ->connectTimeout(5);
    }

    private function url(string $path): string
    {
        return rtrim(config('services.meta.graph_url'), '/').'/'.config('services.meta.graph_version').'/'.$path;
    }
}
