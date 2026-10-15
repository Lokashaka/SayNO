<?php

use App\Http\Controllers\CheckController;
use App\Http\Controllers\CronController;
use App\Http\Controllers\OrgDashboardController;
use App\Http\Controllers\PageController;
use App\Http\Controllers\SetupController;
use Illuminate\Support\Facades\Route;

Route::get('/', [PageController::class, 'home'])->name('home');
Route::get('/modus', [PageController::class, 'modus'])->name('modus');
Route::get('/daftar-phishing', [PageController::class, 'phishing'])->name('phishing');
Route::get('/privasi', [PageController::class, 'privacy'])->name('privacy');
Route::get('/syarat-ketentuan', [PageController::class, 'terms'])->name('terms');

Route::middleware('throttle:checks')->group(function () {
    Route::post('/cek/link', [CheckController::class, 'link'])->name('check.link');
    Route::post('/cek/apk', [CheckController::class, 'apk'])->name('check.apk');
    Route::post('/cek/apk-manifest', [CheckController::class, 'apkManifest'])->name('check.apk-manifest');
});

Route::get('/hasil/{check}', [CheckController::class, 'show'])->name('result');

// Pengganti terminal di hosting tanpa SSH. 404 jika token belum diisi di .env.
Route::get('/_setup', [SetupController::class, 'show'])->name('setup.show');
Route::post('/_setup/masuk', [SetupController::class, 'login'])->middleware('throttle:org-login')->name('setup.login');
Route::post('/_setup/jalankan', [SetupController::class, 'run'])->middleware('throttle:org-login')->name('setup.run');
Route::get('/_cron', CronController::class)->middleware('throttle:cron')->name('cron');

Route::get('/org/{brand}', [OrgDashboardController::class, 'show'])->name('org.show');
Route::post('/org/{brand}/masuk', [OrgDashboardController::class, 'login'])->middleware('throttle:org-login')->name('org.login');
Route::post('/org/{brand}/keluar', [OrgDashboardController::class, 'logout'])->name('org.logout');
