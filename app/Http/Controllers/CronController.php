<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Artisan;

/**
 * Cadangan jika Cron Jobs cPanel tidak tersedia: layanan luar (misalnya cron-job.org)
 * memanggil /_cron?token=... tiap menit. Hanya aktif jika SAYNO_CRON_TOKEN diisi.
 */
class CronController extends Controller
{
    public function __invoke(Request $request): Response
    {
        $token = (string) config('sayno.cron_token');

        abort_if(strlen($token) < 32, 404);
        abort_unless(hash_equals($token, (string) $request->query('token')), 404);

        @set_time_limit(60);

        // Lewat web, antrean dipotong lebih pendek agar tidak melewati batas eksekusi PHP.
        config(['sayno.queue_max_time' => config('sayno.web_cron_max_time')]);

        Artisan::call('schedule:run');

        return response('ok', 200, ['Content-Type' => 'text/plain', 'Cache-Control' => 'no-store']);
    }
}
