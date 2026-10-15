<?php

// Hanya aturan yang dipakai di form SayNO. Aturan lain memakai bahasa Inggris bawaan.
return [
    'required' => 'Kolom :attribute wajib diisi.',
    'string' => 'Kolom :attribute harus berupa teks.',
    'file' => ':attribute harus berupa file.',
    'uploaded' => ':attribute gagal diunggah. Pastikan ukurannya tidak lebih dari 20 MB.',
    'max' => [
        'file' => 'Maaf, file ini lebih dari 20 MB, jadi belum bisa kami periksa.',
        'string' => 'Teks terlalu panjang. Maksimal :max karakter.',
    ],
    'extensions' => 'Yang bisa diperiksa hanya file berakhiran .apk.',

    'attributes' => [
        'input' => 'link atau pesan',
        'apk' => 'File APK',
        'token' => 'token akses',
    ],
];
