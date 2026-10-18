<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Bobot sinyal
    |--------------------------------------------------------------------------
    |
    | Titik awal dari PRD. Setel ulang di sini setelah uji pada set sampel;
    | jangan mengubah bobot di dalam kode.
    |
    */

    'weights' => [
        'link.blacklisted' => 100,
        'link.lookalike' => 50,
        // Domain tiruan yang sudah tercatat aktif meniru (lookalike + ini = Bahaya).
        'link.known_lookalike' => 30,
        // Pengganti link.lookalike untuk domain milik usaha lain bernama sama (cukup Waspada).
        'link.other_owner' => 30,
        'link.new_domain' => 25,
        'link.shortener' => 10,

        'apk.read_sms' => 60,
        'apk.accessibility' => 40,
        'apk.brand_impersonation' => 40,
        'apk.overlay' => 20,
        'apk.install_packages' => 20,
        'apk.spread_sms' => 20,
        'apk.document_disguise' => 20,

        'text.pattern' => 20,
    ],

    // Sinyal teks dihitung per pola, dengan batas jumlah pola.
    'text_max_patterns' => 2,

    /*
    |--------------------------------------------------------------------------
    | Ambang status
    |--------------------------------------------------------------------------
    |
    | Skor 0–29 Aman, 30–59 Waspada, 60–100 Bahaya.
    |
    */

    'thresholds' => [
        'caution' => 30,
        'danger' => 60,
    ],

    // Jumlah alasan yang ditampilkan di balasan dan kartu hasil.
    'max_reasons' => 3,

    /*
    |--------------------------------------------------------------------------
    | Cek link
    |--------------------------------------------------------------------------
    */

    'link' => [
        'new_domain_days' => 30,

        // Salah ketik hanya dicek untuk nama merek minimal 6 huruf (1 huruf beda),
        // dan 9 huruf ke atas boleh beda 2 huruf. Nama pendek terlalu mirip kata biasa.
        'typo_min_length' => 6,
        'typo_two_edits_min_length' => 9,

        // Label resmi yang berupa kata umum. Hanya dicek dengan teknik subdomain,
        // agar situs seperti cnnindonesia.com tidak dianggap meniru indonesia.go.id.
        'generic_labels' => [
            'indonesia', 'pajak', 'lapor', 'kai', 'pos', 'online', 'bank', 'info',
        ],

        // Sufiks dua tingkat agar registrable domain benar (login.bca-co.id -> bca-co.id).
        'multi_part_suffixes' => [
            'co.id', 'go.id', 'ac.id', 'or.id', 'my.id', 'web.id', 'sch.id', 'net.id',
            'mil.id', 'biz.id', 'desa.id', 'ponpes.id',
            'co.uk', 'com.sg', 'com.my', 'com.au', 'co.jp', 'com.br',
        ],

        // TLD yang dikenali untuk alamat tanpa "http://", agar "file.apk" tidak dianggap link.
        'bare_tlds' => [
            'id', 'com', 'net', 'org', 'co', 'io', 'info', 'biz', 'xyz', 'top', 'site', 'online',
            'shop', 'store', 'me', 'ly', 'link', 'click', 'app', 'dev', 'cc', 'vip', 'live', 'tk',
            'ml', 'ga', 'cf', 'gq', 'buzz', 'icu', 'cyou', 'sbs', 'my', 'asia', 'pro', 'club',
            'website', 'space', 'fun', 'gl', 'gd', 'to', 'ws', 'cn', 'ru', 'tv', 'in', 'us',
        ],

        'shorteners' => [
            'bit.ly', 's.id', 'tinyurl.com', 't.co', 'cutt.ly', 'shorturl.at', 'rb.gy', 'is.gd',
            'ow.ly', 'goo.gl', 'tiny.cc', 'bitly.com', 'linktr.ee', 'shope.ee', 'v.gd',
            'clck.ru', 'rebrand.ly', 'short.io', 'heylink.me', 'lynk.id',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Cek APK
    |--------------------------------------------------------------------------
    */

    'apk' => [
        // Batas file APK utuh yang diunggah ke server (dibatasi upload_max_filesize hosting).
        'max_bytes' => 20 * 1024 * 1024,

        // Batas file yang diperiksa di browser: browser hanya mengirim AndroidManifest.xml
        // dan SHA-256, jadi ukuran file tidak membebani server. Sama dengan batas dokumen WhatsApp.
        'browser_max_bytes' => 100 * 1024 * 1024,

        // Batas ukuran AndroidManifest.xml yang diterima dari browser.
        'manifest_max_bytes' => 4 * 1024 * 1024,

        // Di luar public/. File dihapus setelah dianalisis.
        'quarantine_dir' => 'quarantine',

        // Kata di nama file yang menandakan APK berkedok dokumen.
        'document_keywords' => [
            'undangan', 'surat', 'tilang', 'resi', 'tagihan', 'bpjs', 'pdf', 'invoice',
            'paket', 'kurir', 'foto', 'dokumen', 'pajak', 'etilang', 'struk', 'nota',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Web
    |--------------------------------------------------------------------------
    */

    // Nomor bot dalam format internasional tanpa "+", untuk tautan wa.me.
    'whatsapp_number' => env('SAYNO_WA_NUMBER'),

    /*
    |--------------------------------------------------------------------------
    | Bot WhatsApp
    |--------------------------------------------------------------------------
    */

    'whatsapp' => [
        // APK dari WhatsApp diunduh utuh ke karantina (batas dokumen WhatsApp 100 MB).
        'max_apk_bytes' => 100 * 1024 * 1024,

        // Pesan teks pendek tanpa link dianggap sapaan, bukan bahan pemeriksaan.
        'greeting_max_length' => 30,

        // Balasan hanya dikirim di dalam jendela layanan 24 jam (diberi jarak aman).
        'reply_window_hours' => 23,
    ],

    // Batas cek per menit per alamat IP di web.
    'web_rate_limit' => (int) env('SAYNO_WEB_RATE_LIMIT', 10),

    // Token dashboard merek contoh, dipakai BrandSeeder. Kosong = dibuat acak saat seeding.
    'demo_brand_token' => env('SAYNO_DEMO_BRAND_TOKEN'),

    /*
    |--------------------------------------------------------------------------
    | Hosting tanpa terminal
    |--------------------------------------------------------------------------
    |
    | setup_token: membuka halaman /_setup (migrate, seed, cache). Minimal 32 karakter.
    |              Kosongkan lagi setelah deploy selesai; halaman lalu menjadi 404.
    | cron_token:  membuka /_cron untuk pemanggil luar jika Cron Jobs cPanel tidak ada.
    |
    */

    'setup_token' => env('SAYNO_SETUP_TOKEN'),
    'cron_token' => env('SAYNO_CRON_TOKEN'),

    // Batas waktu antrean per jalan. Lewat web (/_cron) dipotong agar di bawah batas eksekusi PHP.
    'queue_max_time' => (int) env('SAYNO_QUEUE_MAX_TIME', 50),
    'web_cron_max_time' => 20,

    // Jumlah variasi domain merek yang dicek per jam.
    'lookalike_batch' => (int) env('SAYNO_LOOKALIKE_BATCH', 15),

    // Penilaian otomatis domain tiruan dari server DNS-nya (dicocokkan dengan akhiran nama server).
    // Penilaian manual (verdict_source = manual) tidak ditimpa.
    'lookalike_nameservers' => [
        // Domain dijual, diparkir, atau kedaluwarsa.
        'parked' => [
            'sedoparking.com', 'afternic.com', 'dan.com', 'abovedomains.com', 'above.com', 'parkingcrew.net',
            'bodis.com', 'namefind.com', 'parklogic.com', 'dns-parking.com', 'dns-expired.com',
            'pulaudomain.com', 'namebrightdns.com', 'eftydns.com', 'namepros-dns.com', 'hugedomains.com',
            'dyna-ns.net', 'uniregistrymarket.link',
        ],
        // Registrar perlindungan merek; domainnya hampir selalu milik perusahaan besar.
        'unrelated' => [
            'cscdns.net', 'cscdns.uk', 'markmonitor.com', 'safenames.net', 'comlaude-dns.com',
        ],
    ],

    // Alamat kontak di halaman privasi.
    'contact_email' => env('SAYNO_CONTACT_EMAIL', 'halo@lokashaka.id'),

];
