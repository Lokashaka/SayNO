<?php

// Balasan bot WhatsApp. Format tebal memakai *teks*. Lihat DESIGN.md bagian 9.
return [
    'greeting' => "Halo, saya *SayNO* 👋\nTeruskan ke sini *link*, *file APK*, atau *pesan* yang mencurigakan. Saya cek dan kasih tahu aman atau tidak.",

    'checking' => '⏳ Sedang memeriksa file Anda. Biasanya kurang dari 1 menit.',

    'too_large' => "Maaf, file ini lebih dari 100 MB, jadi belum bisa saya periksa.\nKalau file ini dikirim orang yang tidak Anda kenal, lebih aman langsung dihapus.",

    'unreadable' => "File ini tidak bisa dibaca sebagai aplikasi Android.\nKalau dikirim orang yang tidak Anda kenal, lebih aman langsung dihapus.",

    'unsupported_file' => "Saat ini saya bisa memeriksa *link*, *pesan teks*, dan *file APK*.\nFile ini bukan APK, jadi belum bisa saya periksa. Kalau ada link di pesannya, kirim ulang sebagai teks.",

    'error' => 'Maaf, ada gangguan saat memeriksa. Coba kirim ulang beberapa saat lagi.',

    'emergency_keyword' => 'SUDAH',
    'emergency' => "🚨 *Langkah darurat kalau aplikasi sudah terinstal:*\n1. Matikan data seluler dan Wi-Fi.\n2. Telepon bank Anda lewat nomor di balik kartu ATM, minta blokir sementara.\n3. Hapus aplikasi itu di Pengaturan > Aplikasi.\n4. Dari HP lain, ganti PIN dan kata sandi m-banking.\n5. Jangan berikan kode OTP kepada siapa pun.",

    'detail_link' => 'Detail: :url',
];
