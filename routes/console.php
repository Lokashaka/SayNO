<?php

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schedule;

/*
| Dijalankan tiap menit oleh Cron Jobs cPanel:
|   /usr/local/bin/php /home/USER/sayno/artisan schedule:run >> /dev/null 2>&1
| atau, jika cron tidak tersedia, oleh pemanggil luar ke /_cron (lihat CronController).
|
| Semua tugas memakai Schedule::call + Artisan::call agar berjalan di proses yang sama.
| Schedule::command membuat proses baru lewat proc_open, yang tidak tersedia di hosting.
*/

// Tanda cron hidup, ditampilkan di halaman /_setup.
Schedule::call(fn () => Cache::forever('scheduler:last_run', now()->toDateTimeString()))
    ->name('heartbeat')
    ->everyMinute();

Schedule::call(fn () => Artisan::call('queue:work', [
    '--stop-when-empty' => true,
    '--max-time' => config('sayno.queue_max_time'),
    '--tries' => 3,
]))
    ->name('queue')
    ->everyMinute()
    ->withoutOverlapping(5);

Schedule::call(fn () => Artisan::call('sayno:scan-lookalikes', ['--batch' => config('sayno.lookalike_batch')]))
    ->name('scan-lookalikes')
    ->hourly()
    ->withoutOverlapping(10);
