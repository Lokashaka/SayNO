<?php

namespace App\Http\Controllers;

use App\Support\UploadLimit;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Throwable;

/**
 * Pengganti terminal untuk hosting cPanel tanpa SSH: migrate, seed, cache, dan cek kesiapan.
 *
 * Hanya aktif jika SAYNO_SETUP_TOKEN diisi (minimal 32 karakter). Kosongkan lagi setelah
 * deploy; semua rute di sini lalu menjawab 404.
 */
class SetupController extends Controller
{
    private const SESSION_KEY = 'setup_ok';

    private const ACTIONS = [
        'migrate' => ['migrate', ['--force' => true]],
        'seed' => ['db:seed', ['--force' => true]],
        'optimize' => ['optimize', []],
        'clear' => ['optimize:clear', []],
    ];

    private const EXTENSIONS = ['intl', 'curl', 'mbstring', 'openssl', 'pdo_mysql', 'fileinfo', 'tokenizer', 'xml', 'ctype'];

    public function show(Request $request): View
    {
        $this->ensureEnabled();

        if (! $request->session()->get(self::SESSION_KEY)) {
            return view('setup.login');
        }

        return view('setup.panel', [
            'checks' => $this->checks(),
            'output' => session('output'),
            'ranAction' => session('action'),
        ]);
    }

    public function login(Request $request): RedirectResponse
    {
        $this->ensureEnabled();
        $request->validate(['token' => ['required', 'string', 'max:200']]);

        if (! hash_equals((string) config('sayno.setup_token'), (string) $request->input('token'))) {
            return back()->withErrors(['token' => __('web.org.login_failed')]);
        }

        $request->session()->regenerate();
        $request->session()->put(self::SESSION_KEY, true);

        return redirect()->route('setup.show');
    }

    public function run(Request $request): RedirectResponse
    {
        $this->ensureEnabled();
        abort_unless($request->session()->get(self::SESSION_KEY), 403);

        $action = $request->validate(['action' => ['required', 'in:'.implode(',', array_keys(self::ACTIONS))]])['action'];
        [$command, $arguments] = self::ACTIONS[$action];

        @set_time_limit(120);

        try {
            Artisan::call($command, $arguments);
            $output = Artisan::output();
        } catch (Throwable $e) {
            report($e);
            $output = 'Gagal: '.$e->getMessage();
        }

        return redirect()->route('setup.show')->with([
            'action' => $action,
            'output' => trim(preg_replace('/\e\[[\d;]*m/', '', $output)) ?: 'Selesai tanpa keluaran.',
        ]);
    }

    private function ensureEnabled(): void
    {
        abort_if(strlen((string) config('sayno.setup_token')) < 32, 404);
    }

    /**
     * @return list<array{label: string, ok: bool, value: string, required: bool}>
     */
    private function checks(): array
    {
        $checks = [
            $this->check('Versi PHP (minimal 8.3)', version_compare(PHP_VERSION, '8.3.0', '>='), PHP_VERSION),
        ];

        foreach (self::EXTENSIONS as $extension) {
            $checks[] = $this->check("Ekstensi {$extension}", extension_loaded($extension), extension_loaded($extension) ? 'aktif' : 'tidak aktif');
        }

        foreach (['storage/app/private', 'storage/framework/cache', 'storage/framework/sessions', 'storage/framework/views', 'storage/logs', 'bootstrap/cache'] as $path) {
            $writable = is_dir(base_path($path)) && is_writable(base_path($path));
            $checks[] = $this->check("Folder {$path} bisa ditulis", $writable, $writable ? 'ya' : 'tidak (ubah izin ke 755 atau 775)');
        }

        // File APK di atas batas ini dibaca di browser, jadi batas kecil tidak menghalangi.
        $uploadMb = round(UploadLimit::apkServerBytes() / 1048576, 1);
        $checks[] = $this->check('Batas upload APK utuh ke server', true, "{$uploadMb} MB (php: ".ini_get('upload_max_filesize').' / post '.ini_get('post_max_size').'); file lebih besar dibaca di browser', required: false);

        $checks[] = $this->check('APP_KEY terisi', filled(config('app.key')), filled(config('app.key')) ? 'ya' : 'belum');
        $checks[] = $this->check('APP_DEBUG mati', ! config('app.debug'), config('app.debug') ? 'masih true' : 'false');
        $checks[] = $this->check('APP_URL memakai https', str_starts_with((string) config('app.url'), 'https://'), (string) config('app.url'));

        try {
            DB::connection()->getPdo();
            $checks[] = $this->check('Koneksi database', true, DB::connection()->getDatabaseName());
        } catch (Throwable) {
            $checks[] = $this->check('Koneksi database', false, 'gagal, periksa DB_* di .env');
        }

        // Halaman ini memakai cache file; tanda cron ditulis scheduler ke cache utama.
        try {
            $lastRun = Cache::store(config('sayno.primary_cache_store'))->get('scheduler:last_run');
        } catch (Throwable) {
            $lastRun = null;
        }
        $cronOk = $lastRun !== null && now()->diffInMinutes($lastRun, true) <= 3;
        $checks[] = $this->check('Cron berjalan (3 menit terakhir)', $cronOk, $lastRun ?? 'belum pernah');

        $checks[] = $this->check('Fungsi checkdnsrr (pemindaian domain)', function_exists('checkdnsrr'), function_exists('checkdnsrr') ? 'ada' : 'tidak ada', required: false);
        $checks[] = $this->check('SAFE_BROWSING_KEY terisi', filled(config('services.safe_browsing.key')), filled(config('services.safe_browsing.key')) ? 'ya' : 'belum', required: false);
        $checks[] = $this->check('Batas waktu eksekusi PHP', true, ini_get('max_execution_time').' detik', required: false);

        return $checks;
    }

    private function check(string $label, bool $ok, string $value, bool $required = true): array
    {
        return compact('label', 'ok', 'value', 'required');
    }
}
