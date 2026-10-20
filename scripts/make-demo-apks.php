<?php

/**
 * Membuat APK tiruan untuk demo dan video: ZIP berisi AndroidManifest.xml biner dengan
 * izin yang meniru modus nyata. Bukan aplikasi sungguhan, tidak bisa diinstal, dan tidak
 * berbahaya, tetapi dibaca SayNO persis seperti APK asli (yang diperiksa hanya manifest).
 *
 * Jalankan di komputer lokal (butuh vendor/ dengan paket dev):
 *   php scripts/make-demo-apks.php
 *
 * Hasil: dist/demo-apk/*.apk. Kirim ke bot WhatsApp atau unggah di web.
 * Di paper dan video, sebut sebagai sampel sintetis, bukan malware asli.
 */

use Tests\Support\AxmlBuilder;

require dirname(__DIR__).'/vendor/autoload.php';

$output = dirname(__DIR__).'/dist/demo-apk';

$sms = [
    ['uses-permission', ['name' => 'android.permission.READ_SMS']],
    ['uses-permission', ['name' => 'android.permission.RECEIVE_SMS']],
];

$smsReceiver = ['receiver', ['name' => '.SmsReceiver'], [
    ['intent-filter', [], [['action', ['name' => 'android.provider.Telephony.SMS_RECEIVED']]]],
]];

$accessibilityService = ['service', ['name' => '.Helper', 'permission' => 'android.permission.BIND_ACCESSIBILITY_SERVICE']];

$internet = ['uses-permission', ['name' => 'android.permission.INTERNET']];

// [nama file, manifest, perkiraan hasil]
$samples = [
    [
        'Undangan_Pernikahan_Digital.apk',
        ['manifest', ['package' => 'com.undangan.digital', 'versionName' => '1.0'], [
            ...$sms,
            $internet,
            ['application', ['label' => 'Undangan Digital'], [$smsReceiver]],
        ]],
        'Bahaya: baca SMS (OTP), berkedok undangan',
    ],
    [
        'Surat_Tilang_ETLE.apk',
        ['manifest', ['package' => 'id.etle.surat', 'versionName' => '2.1'], [
            ['uses-permission', ['name' => 'android.permission.SYSTEM_ALERT_WINDOW']],
            ['uses-permission', ['name' => 'android.permission.REQUEST_INSTALL_PACKAGES']],
            $internet,
            ['application', ['label' => 'E-Tilang'], [$accessibilityService]],
        ]],
        'Bahaya: kendali layar, tampilan palsu, pasang aplikasi lain, berkedok surat tilang',
    ],
    [
        'Resi_Paket_Kiriman.apk',
        ['manifest', ['package' => 'com.cekresi.kiriman', 'versionName' => '1.3'], [
            ...$sms,
            ['uses-permission', ['name' => 'android.permission.READ_CONTACTS']],
            ['uses-permission', ['name' => 'android.permission.SEND_SMS']],
            $internet,
            ['application', ['label' => 'Cek Resi'], [$smsReceiver]],
        ]],
        'Bahaya: baca SMS, menyebar lewat SMS ke kontak, berkedok resi',
    ],
    [
        'BCA_Mobile_Update.apk',
        ['manifest', ['package' => 'com.bca.mobile.update', 'versionName' => '5.0'], [
            ...$sms,
            $internet,
            ['application', ['label' => 'BCA mobile'], [$smsReceiver, $accessibilityService]],
        ]],
        'Bahaya: meniru aplikasi BCA (muncul di dashboard /org/bca), baca SMS, kendali layar',
    ],
    [
        'Kalkulator_Pro.apk',
        ['manifest', ['package' => 'com.kalkulator.pro', 'versionName' => '3.2'], [
            ['uses-permission', ['name' => 'android.permission.SYSTEM_ALERT_WINDOW']],
            ['uses-permission', ['name' => 'android.permission.REQUEST_INSTALL_PACKAGES']],
            $internet,
            ['application', ['label' => 'Kalkulator Pro']],
        ]],
        'Waspada: tampilan di atas aplikasi lain dan bisa memasang aplikasi lain',
    ],
    [
        'Catatan_Belanja.apk',
        ['manifest', ['package' => 'com.contoh.catatanbelanja', 'versionName' => '1.0'], [
            $internet,
            ['application', ['label' => 'Catatan Belanja']],
        ]],
        'Aman: tidak ada izin berbahaya',
    ],
];

if (! class_exists(ZipArchive::class)) {
    fwrite(STDERR, "Ekstensi zip PHP belum aktif.\n");
    exit(1);
}

if (! is_dir($output) && ! mkdir($output, 0755, true)) {
    fwrite(STDERR, "Folder {$output} tidak bisa dibuat.\n");
    exit(1);
}

foreach ($samples as [$fileName, $manifest, $expected]) {
    $path = $output.'/'.$fileName;

    $zip = new ZipArchive;
    $zip->open($path, ZipArchive::CREATE | ZipArchive::OVERWRITE);
    $zip->addFromString('AndroidManifest.xml', (new AxmlBuilder)->build($manifest));
    // Isi pengganti agar struktur menyerupai APK; tidak berisi kode.
    $zip->addFromString('classes.dex', 'dex');
    $zip->close();

    echo str_pad($fileName, 34).$expected.PHP_EOL;
}

echo PHP_EOL.count($samples).' file dibuat di '.$output.PHP_EOL;
