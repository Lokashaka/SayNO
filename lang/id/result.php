<?php

return [
    // Judul kartu hasil di web: satu kalimat, mulai dari kesimpulan.
    'title' => [
        'danger' => [
            'apk' => 'Jangan instal aplikasi ini.',
            'link' => 'Jangan buka link ini.',
            'text' => 'Jangan tanggapi pesan ini.',
        ],
        'caution' => [
            'apk' => 'Hati-hati, jangan instal dulu.',
            'link' => 'Hati-hati dengan link ini.',
            'text' => 'Hati-hati dengan pesan ini.',
        ],
        'safe' => [
            'apk' => 'Tidak ditemukan tanda bahaya.',
            'link' => 'Tidak ditemukan tanda bahaya.',
            'text' => 'Tidak ditemukan tanda bahaya.',
        ],
    ],

    // Baris pertama balasan WhatsApp.
    'headline' => [
        'danger' => [
            'apk' => 'BAHAYA, jangan diinstal.',
            'link' => 'BAHAYA, jangan dibuka.',
            'text' => 'BAHAYA, jangan ditanggapi.',
        ],
        'caution' => [
            'apk' => 'WASPADA',
            'link' => 'WASPADA',
            'text' => 'WASPADA',
        ],
        'safe' => [
            'apk' => 'Tidak ditemukan tanda bahaya.',
            'link' => 'Tidak ditemukan tanda bahaya.',
            'text' => 'Tidak ditemukan tanda bahaya.',
        ],
    ],

    'no_findings' => [
        'apk' => 'Aplikasi ini tidak meminta izin berbahaya yang kami periksa.',
        'link' => 'Situs ini tidak meniru situs resmi yang kami kenal dan tidak ada di daftar hitam.',
        'text' => 'Pesan ini tidak cocok dengan pola penipuan yang kami kenal.',
    ],

    'what_to_do' => 'Yang sebaiknya Anda lakukan:',
    'official_site' => 'Situs resmi: :domain',
    'file_name' => 'File ":name"',

    'actions' => [
        'danger' => [
            'apk' => [
                'Hapus file ini.',
                'Jangan klik apa pun dari pengirimnya, meski Anda kenal orangnya.',
                'Kalau sudah terinstal, matikan internet HP lalu hubungi bank Anda lewat nomor resmi.',
            ],
            'link' => [
                'Jangan isi nomor kartu, PIN, atau kode OTP di situs ini.',
                'Blokir dan laporkan pengirimnya.',
            ],
            'text' => [
                'Jangan balas dan jangan transfer uang.',
                'Blokir dan laporkan pengirimnya.',
            ],
        ],
        'caution' => [
            'apk' => [
                'Jangan instal kalau Anda tidak yakin asal file ini.',
                'Unduh aplikasi hanya dari Play Store, App Store, atau situs resmi pembuatnya.',
            ],
            'link' => [
                'Jangan isi data pribadi di situs ini.',
                'Buka aplikasi atau situs resminya langsung, jangan lewat link ini.',
            ],
            'text' => [
                'Jangan kirim data pribadi atau uang.',
                'Pastikan dulu lewat nomor atau situs resmi.',
            ],
        ],
        'safe' => [
            'apk' => [
                'Tetap instal aplikasi hanya dari Play Store, App Store, atau situs resmi pembuatnya.',
                'Jangan bagikan kode OTP ke siapa pun.',
            ],
            'link' => [
                'Tetap jangan bagikan kode OTP ke siapa pun.',
            ],
            'text' => [
                'Tetap jangan bagikan kode OTP ke siapa pun.',
            ],
        ],
    ],

    'age' => [
        'today' => 'hari ini',
        'days' => ':days hari lalu',
    ],

    'apk_unreadable' => 'File ini tidak bisa dibaca sebagai aplikasi Android. Kalau dikirim orang yang tidak Anda kenal, lebih aman langsung dihapus.',
    'apk_too_large' => 'Maaf, file ini lebih dari 20 MB, jadi belum bisa kami periksa. Kalau file ini dikirim orang yang tidak Anda kenal, lebih aman langsung dihapus.',
];
