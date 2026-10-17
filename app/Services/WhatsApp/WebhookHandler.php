<?php

namespace App\Services\WhatsApp;

use App\Jobs\AnalyzeIncomingMessage;
use App\Models\InboundMessage;
use Illuminate\Support\Carbon;

/**
 * Mengambil pesan dari payload webhook Meta, mencatatnya sekali (idempoten),
 * lalu menjadwalkan analisis. Isi pesan hanya diteruskan ke job terenkripsi.
 */
class WebhookHandler
{
    private const APK_MIME = 'application/vnd.android.package-archive';

    /**
     * @return int Jumlah pesan baru yang dijadwalkan.
     */
    public function handle(array $payload): int
    {
        $queued = 0;

        foreach ((array) ($payload['entry'] ?? []) as $entry) {
            foreach ((array) ($entry['changes'] ?? []) as $change) {
                $value = (array) ($change['value'] ?? []);

                if (($change['field'] ?? null) !== 'messages' || ! $this->isOurNumber($value)) {
                    continue;
                }

                foreach ((array) ($value['messages'] ?? []) as $message) {
                    $queued += $this->accept((array) $message) ? 1 : 0;
                }
            }
        }

        return $queued;
    }

    private function accept(array $message): bool
    {
        $wamid = (string) ($message['id'] ?? '');
        $from = preg_replace('/\D/', '', (string) ($message['from'] ?? ''));
        $type = (string) ($message['type'] ?? 'unknown');

        if ($wamid === '' || $from === '' || $type === 'reaction') {
            return false;
        }

        $receivedAt = isset($message['timestamp']) ? Carbon::createFromTimestamp((int) $message['timestamp']) : now();

        // Meta bisa mengirim ulang pesan yang sama; wamid unik mencegah balasan ganda.
        $inserted = InboundMessage::query()->insertOrIgnore([
            'wamid' => $wamid,
            'channel' => 'meta',
            'sender_hash' => InboundMessage::hashSender($from),
            'type' => mb_substr($type, 0, 16),
            'received_at' => $receivedAt,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        if ($inserted === 0) {
            return false;
        }

        $job = new AnalyzeIncomingMessage(
            wamid: $wamid,
            to: $from,
            kind: $this->kind($type, $message),
            text: $this->text($type, $message),
            mediaId: $message['document']['id'] ?? null,
            fileName: $message['document']['filename'] ?? null,
        );

        // Teks dan link cukup cepat: dianalisis setelah Meta menerima 200 OK, jika server
        // bisa menutup koneksi lebih dulu. APK selalu lewat antrean (cron) karena berat.
        if ($job->kind !== AnalyzeIncomingMessage::KIND_APK && $this->canFinishResponseEarly()) {
            dispatch($job)->afterResponse();
        } else {
            dispatch($job);
        }

        return true;
    }

    private function kind(string $type, array $message): string
    {
        if ($type === 'text') {
            return AnalyzeIncomingMessage::KIND_TEXT;
        }

        if ($type === 'document') {
            $name = mb_strtolower((string) ($message['document']['filename'] ?? ''));
            $mime = (string) ($message['document']['mime_type'] ?? '');

            if (str_ends_with($name, '.apk') || $mime === self::APK_MIME) {
                return AnalyzeIncomingMessage::KIND_APK;
            }
        }

        // Gambar atau video dengan keterangan: keterangannya diperiksa seperti teks.
        if ($this->text($type, $message) !== null) {
            return AnalyzeIncomingMessage::KIND_TEXT;
        }

        return $type === 'document' ? AnalyzeIncomingMessage::KIND_OTHER_FILE : AnalyzeIncomingMessage::KIND_UNSUPPORTED;
    }

    private function text(string $type, array $message): ?string
    {
        $text = match ($type) {
            'text' => $message['text']['body'] ?? null,
            'image', 'video', 'document' => $message[$type]['caption'] ?? null,
            default => null,
        };

        return is_string($text) && trim($text) !== '' ? mb_substr($text, 0, 4000) : null;
    }

    private function isOurNumber(array $value): bool
    {
        $ours = (string) config('services.meta.phone_number_id');
        $incoming = (string) ($value['metadata']['phone_number_id'] ?? '');

        return $ours === '' || $incoming === '' || hash_equals($ours, $incoming);
    }

    private function canFinishResponseEarly(): bool
    {
        return function_exists('fastcgi_finish_request') || function_exists('litespeed_finish_request');
    }
}
