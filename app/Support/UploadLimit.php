<?php

namespace App\Support;

/**
 * Batas file APK utuh yang benar-benar bisa diterima server.
 *
 * Diambil yang terkecil dari config dan batas upload PHP di hosting, karena paket
 * hosting murah sering mengunci upload_max_filesize (misalnya 10 MB) tanpa bisa diubah.
 * File di atas batas ini dibaca di browser dan hanya manifest-nya yang dikirim.
 */
class UploadLimit
{
    // Ruang untuk field form lain di dalam POST.
    private const POST_OVERHEAD = 64 * 1024;

    public static function apkServerBytes(): int
    {
        $limits = [(int) config('sayno.apk.max_bytes')];

        $upload = self::iniBytes(ini_get('upload_max_filesize'));
        $post = self::iniBytes(ini_get('post_max_size'));

        if ($upload > 0) {
            $limits[] = $upload;
        }

        if ($post > 0) {
            $limits[] = $post - self::POST_OVERHEAD;
        }

        return max(1024, min($limits));
    }

    public static function iniBytes(string|false $value): int
    {
        $value = trim((string) $value);
        $number = (int) $value;

        return match (strtolower(substr($value, -1))) {
            'g' => $number * 1024 ** 3,
            'm' => $number * 1024 ** 2,
            'k' => $number * 1024,
            default => $number,
        };
    }
}
