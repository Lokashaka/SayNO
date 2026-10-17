<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

/**
 * Tolak request webhook yang tanda tangan X-Hub-Signature-256-nya tidak cocok
 * dengan HMAC SHA-256 body mentah memakai META_APP_SECRET. Gagal tertutup:
 * jika App Secret belum diisi, semua request ditolak.
 */
class VerifyMetaSignature
{
    public function handle(Request $request, Closure $next): Response
    {
        $secret = (string) config('services.meta.app_secret');
        $signature = (string) $request->header('X-Hub-Signature-256');

        if ($secret === '') {
            Log::warning('WhatsApp: META_APP_SECRET belum diisi, webhook ditolak.');
            abort(403);
        }

        $expected = 'sha256='.hash_hmac('sha256', $request->getContent(), $secret);

        if (! hash_equals($expected, $signature)) {
            abort(403);
        }

        return $next($request);
    }
}
