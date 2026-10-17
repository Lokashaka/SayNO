<?php

use App\Http\Controllers\WhatsAppWebhookController;
use App\Http\Middleware\VerifyMetaSignature;
use Illuminate\Support\Facades\Route;

/*
| Webhook WhatsApp Cloud API. Dimuat tanpa grup "web": tanpa sesi, cookie, dan CSRF,
| karena pengirimnya server Meta. Keamanan POST memakai tanda tangan X-Hub-Signature-256.
*/

Route::get('/webhook/whatsapp', [WhatsAppWebhookController::class, 'verify'])->name('webhook.whatsapp.verify');
Route::post('/webhook/whatsapp', [WhatsAppWebhookController::class, 'receive'])
    ->middleware(VerifyMetaSignature::class)
    ->name('webhook.whatsapp.receive');
