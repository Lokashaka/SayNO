<?php

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schedule;

/*
| Dijalankan tiap menit oleh Cron Jobs cPanel:
|   /usr/local/bin/ea-php83 /home/USER/public_html/sayno/artisan schedule:run >> /dev/null 2>&1
| atau, jika cron tidak tersedia, oleh pemanggil luar ke /_cron (lihat CronController).
|
| Semua tugas memakai Schedule::call + Artisan::call agar berjalan di proses yang sama.
| Schedule::command membuat proses baru lewat proc_open, yang tidak tersedia di hosting.
*/

// Migrasi tanpa terminal dan tanpa token: buat file storage/app/private/run-migrations
// lewat File Manager. Cron berikutnya menjalankan migrate sekali, menghapus file penanda,
// dan mencatat hasilnya di storage/logs/migrate.log.
Schedule::call(function () {
    $flag = storage_path('app/private/run-migrations');

    if (! is_file($flag)) {
        return;
    }

    // Dihapus lebih dulu agar migrasi yang gagal tidak diulang tiap menit.
    @unlink($flag);

    try {
        Artisan::call('migrate', ['--force' => true]);
        $output = Artisan::output();
    } catch (Throwable $e) {
        report($e);
        $output = 'Gagal: '.$e->getMessage();
    }

    file_put_contents(
        storage_path('logs/migrate.log'),
        '['.now()->toDateTimeString().'] '.trim(preg_replace('/\e\[[\d;]*m/', '', $output)).PHP_EOL,
        FILE_APPEND,
    );
})
    ->name('migrate-on-flag')
    ->everyMinute()
    ->withoutOverlapping(10);

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
