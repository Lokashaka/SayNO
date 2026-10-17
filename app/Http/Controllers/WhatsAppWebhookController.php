<?php

namespace App\Http\Controllers;

use App\Services\WhatsApp\WebhookHandler;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class WhatsAppWebhookController extends Controller
{
    /**
     * Verifikasi webhook oleh Meta: kembalikan hub.challenge jika verify token cocok.
     * PHP mengubah "hub.mode" di query string menjadi "hub_mode".
     */
    public function verify(Request $request): Response
    {
        $token = (string) config('services.meta.verify_token');

        if ($token === ''
            || $request->query('hub_mode') !== 'subscribe'
            || ! hash_equals($token, (string) $request->query('hub_verify_token'))) {
            abort(403);
        }

        return response((string) $request->query('hub_challenge'), 200, ['Content-Type' => 'text/plain']);
    }

    /**
     * Pesan masuk. Tanda tangan sudah diperiksa middleware; jawab 200 secepatnya.
     */
    public function receive(Request $request, WebhookHandler $handler): Response
    {
        $handler->handle((array) $request->json()->all());

        return response('EVENT_RECEIVED', 200, ['Content-Type' => 'text/plain']);
    }
}
