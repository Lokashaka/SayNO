<?php

return [
    'intro' => 'SayNO dibuat untuk melindungi Anda dari penipuan, jadi kami menyimpan data sesedikit mungkin. Halaman ini menjelaskan apa yang kami simpan, apa yang tidak, dan berapa lama.',

    'sections' => [
        [
            'title' => 'Yang kami simpan',
            'items' => [
                'Nama domain dari link yang Anda cek, misalnya contoh-situs.com.',
                'Sidik digital (hash SHA-256) dari file APK, bukan filenya. Untuk file besar, browser Anda yang membaca file; yang dikirim ke kami hanya daftar izin aplikasi (manifest) dan sidik digitalnya.',
                'Hasil pemeriksaan: skor, status, dan alasan.',
                'Untuk pengguna WhatsApp: nomor Anda dalam bentuk acak (hash) yang tidak bisa dikembalikan menjadi nomor asli.',
                'Waktu pemeriksaan.',
            ],
        ],
        [
            'title' => 'Yang tidak kami simpan',
            'items' => [
                'Isi pesan yang Anda teruskan atau tempel. Isi pesan hanya diproses sesaat untuk diperiksa, lalu dibuang.',
                'Nomor telepon asli dan nama profil WhatsApp Anda.',
                'File APK. File hanya dibaca, tidak pernah dijalankan, dan dihapus begitu selesai diperiksa.',
                'Halaman yang dituju link. Kami tidak membuka link yang Anda kirim.',
            ],
        ],
        [
            'title' => 'Pihak lain yang terlibat',
            'items' => [
                'Google Safe Browsing: alamat link dikirim ke Google untuk dicek di daftar situs berbahaya.',
                'RDAP (data pendaftaran domain): nama domain dikirim untuk mengetahui kapan domain dibuat.',
                'WhatsApp Cloud API dari Meta: mengantar pesan antara Anda dan bot SayNO.',
                'Kami tidak menjual atau membagikan data Anda untuk iklan.',
            ],
        ],
        [
            'title' => 'Data untuk organisasi',
            'items' => [
                'Organisasi pelanggan, misalnya bank, hanya melihat domain tiruan dan APK palsu yang memakai merek mereka.',
                'Organisasi tidak pernah melihat siapa yang melapor atau isi pesannya.',
                'Domain yang dinyatakan Bahaya ditampilkan di halaman Daftar phishing agar orang lain bisa waspada, tanpa data siapa yang memeriksanya.',
            ],
        ],
        [
            'title' => 'Berapa lama disimpan',
            'items' => [
                'Riwayat pemeriksaan disimpan paling lama 12 bulan untuk statistik dan peringatan dini, lalu dihapus.',
                'File APK dihapus segera setelah diperiksa.',
            ],
        ],
        [
            'id' => 'hapus-data',
            'title' => 'Cara meminta penghapusan data',
            'items' => [
                'Kirim email ke :email dengan judul "Hapus data SayNO".',
                'Untuk pengguna WhatsApp, sebutkan nomor yang Anda pakai. Kami mengubahnya menjadi hash yang sama untuk menemukan dan menghapus riwayat pemeriksaan Anda, lalu nomor itu tidak kami simpan.',
                'Penghapusan kami selesaikan paling lambat 14 hari kerja, dan Anda akan mendapat konfirmasi lewat email.',
            ],
        ],
        [
            'title' => 'Bot WhatsApp',
            'items' => [
                'Bot hanya membalas pesan yang Anda kirim. Kami tidak mengirim promosi atau pesan berantai.',
                'Untuk berhenti, cukup jangan kirim pesan lagi atau blokir nomor bot.',
            ],
        ],
    ],

    'law' => 'Pengelolaan data ini berpedoman pada Undang-Undang Nomor 27 Tahun 2022 tentang Pelindungan Data Pribadi.',

    'contact_title' => 'Hubungi kami',
    'contact' => 'Pertanyaan atau permintaan penghapusan data bisa dikirim ke :email.',
];
