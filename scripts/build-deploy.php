<?php

/**
 * Membuat ZIP siap upload untuk cPanel tanpa terminal dan tanpa symlink.
 *
 * Jalankan di komputer lokal (bukan di server):
 *   npm run build
 *   php scripts/build-deploy.php
 *
 * Hasil: dist/sayno-deploy-<waktu>.zip berisi folder "sayno/" lengkap dengan vendor/
 * (tanpa paket dev). Upload lewat File Manager, lalu Extract di /home/USER/.
 * Panduan lengkap: DEPLOY.md.
 */
date_default_timezone_set('Asia/Jakarta');

$root = dirname(__DIR__);
$stage = $root.'/build/deploy/sayno';
$dist = $root.'/dist';

// Yang tidak ikut ke server.
$excluded = [
    '.git', '.github', '.idea', '.vscode', '.claude', '.phpunit.cache', '_app', 'build', 'dist',
    'node_modules', 'scripts', 'tests', 'vendor',
    '.env', '.env.backup', '.env.production', '.phpunit.result.cache',
    'database/database.sqlite', 'public/hot', 'storage',
];

if (! is_file($root.'/public/build/manifest.json')) {
    fail('public/build/manifest.json tidak ada. Jalankan "npm run build" dulu.');
}

step('Menyiapkan folder build');
removeDirectory($root.'/build/deploy');
mkdir($stage, 0755, true);

step('Menyalin kode aplikasi');
copyTree($root, $stage, $root, $excluded);

// bootstrap/cache berisi daftar provider lokal (termasuk paket dev); server membuatnya ulang.
foreach (glob($stage.'/bootstrap/cache/*.php') as $file) {
    unlink($file);
}

step('Menyiapkan struktur storage kosong');
foreach ([
    'app/private', 'app/public', 'framework/cache/data', 'framework/sessions',
    'framework/views', 'framework/testing', 'logs',
] as $dir) {
    mkdir($stage.'/storage/'.$dir, 0755, true);
    file_put_contents($stage.'/storage/'.$dir.'/.gitignore', "*\n!.gitignore\n");
}

step('Memasang vendor tanpa paket dev (composer install --no-dev)');
$composer = getenv('COMPOSER_BIN') ?: 'composer';
passthru(
    escapeshellcmd($composer).' install --no-dev --optimize-autoloader --no-interaction --no-progress --no-scripts --working-dir='.escapeshellarg($stage),
    $exitCode,
);

if ($exitCode !== 0) {
    fail('composer install gagal.');
}

step('Membuat ZIP');
if (! is_dir($dist)) {
    mkdir($dist, 0755, true);
}

$zipPath = $dist.'/sayno-deploy-'.date('Ymd-His').'.zip';
$zip = new ZipArchive;
$zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE);
$count = 0;

$iterator = new RecursiveIteratorIterator(
    new RecursiveDirectoryIterator($stage, FilesystemIterator::SKIP_DOTS),
    RecursiveIteratorIterator::SELF_FIRST,
);

foreach ($iterator as $item) {
    $relative = 'sayno/'.str_replace('\\', '/', substr($item->getPathname(), strlen($stage) + 1));

    if ($item->isDir()) {
        $zip->addEmptyDir($relative);
    } else {
        $zip->addFile($item->getPathname(), $relative);
        $count++;
    }
}

$zip->close();
removeDirectory($root.'/build/deploy');

$size = round(filesize($zipPath) / 1024 / 1024, 1);
echo PHP_EOL."Selesai: {$zipPath}".PHP_EOL."{$count} file, {$size} MB.".PHP_EOL;
echo 'Langkah berikutnya ada di DEPLOY.md.'.PHP_EOL;

function copyTree(string $from, string $to, string $root, array $excluded): void
{
    foreach (scandir($from) as $name) {
        if ($name === '.' || $name === '..') {
            continue;
        }

        $source = $from.'/'.$name;
        $relative = ltrim(str_replace('\\', '/', substr($source, strlen($root))), '/');

        if (in_array($relative, $excluded, true) || is_link($source)) {
            continue;
        }

        if (is_dir($source)) {
            mkdir($to.'/'.$name, 0755, true);
            copyTree($source, $to.'/'.$name, $root, $excluded);
        } else {
            copy($source, $to.'/'.$name);
        }
    }
}

function removeDirectory(string $path): void
{
    if (! is_dir($path)) {
        return;
    }

    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($path, FilesystemIterator::SKIP_DOTS),
        RecursiveIteratorIterator::CHILD_FIRST,
    );

    foreach ($iterator as $item) {
        $item->isDir() ? rmdir($item->getPathname()) : unlink($item->getPathname());
    }

    rmdir($path);
}

function step(string $message): void
{
    echo '> '.$message.PHP_EOL;
}

function fail(string $message): never
{
    fwrite(STDERR, 'Gagal: '.$message.PHP_EOL);
    exit(1);
}
