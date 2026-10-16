<?php

// Hanya aturan yang dipakai di form SayNO. Aturan lain memakai bahasa Inggris bawaan.
return [
    'required' => 'Kolom :attribute wajib diisi.',
    'string' => 'Kolom :attribute harus berupa teks.',
    'file' => ':attribute harus berupa file.',
    'uploaded' => ':attribute gagal diunggah. Aktifkan JavaScript atau pakai browser terbaru agar file besar dibaca di browser Anda.',
    'max' => [
        'file' => 'File ini terlalu besar untuk diunggah langsung. Aktifkan JavaScript atau pakai browser terbaru agar file besar dibaca di browser Anda.',
        'string' => 'Teks terlalu panjang. Maksimal :max karakter.',
    ],
    'extensions' => 'Yang bisa diperiksa hanya file berakhiran .apk.',

    'attributes' => [
        'input' => 'link atau pesan',
        'apk' => 'File APK',
        'token' => 'token akses',
    ],
];
