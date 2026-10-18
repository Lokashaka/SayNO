<?php

// Alasan dalam bahasa awam. Istilah teknis hanya di bagian "detail teknis".
return [
    'link' => [
        'official' => 'Situs ini terdaftar sebagai situs resmi :name.',
        'lookalike' => 'Situs :domain meniru situs resmi :name (:official).',
        'known_lookalike' => 'Situs ini sudah tercatat di daftar situs tiruan :name milik SayNO.',
        'other_owner' => 'Situs :domain bukan situs resmi :name (:official). Pemiliknya pihak lain, jadi jangan masukkan data akun :name di sana.',
        'new_domain' => 'Situs ini baru dibuat :age. Situs penipuan biasanya berumur pendek.',
        'blacklisted' => 'Situs ini tercatat sebagai situs berbahaya oleh Google Safe Browsing.',
        'shortener' => 'Link memakai penyingkat alamat, jadi tujuan aslinya tersembunyi.',
        'domain' => 'Domain :domain.',
    ],

    'apk' => [
        'read_sms' => 'Aplikasi ini bisa membaca SMS Anda, termasuk kode OTP bank.',
        'accessibility' => 'Aplikasi ini meminta izin mengendalikan layar HP Anda.',
        'brand_impersonation' => 'Aplikasi ini memakai nama :name, tetapi bukan aplikasi resmi :name.',
        'overlay' => 'Aplikasi ini bisa memunculkan tampilan palsu di atas aplikasi lain.',
        'install_packages' => 'Aplikasi ini bisa memasang aplikasi lain di HP Anda.',
        'spread_sms' => 'Aplikasi ini bisa membaca kontak dan mengirim SMS, cara umum untuk menyebar ke orang lain.',
        'document_disguise' => 'File ini menyamar sebagai dokumen (":keyword"). Dokumen asli tidak berbentuk aplikasi.',
        'info' => 'Identitas aplikasi.',
    ],

    'text' => [
        'pattern' => 'Isi pesan mirip modus ":category".',
    ],

    'techniques' => [
        'tld' => 'akhiran domain berbeda',
        'homoglyph' => 'huruf tiruan',
        'subdomain' => 'nama resmi dipakai di depan domain lain',
        'combo' => 'nama resmi ditambah kata lain',
        'typo' => 'salah ketik yang disengaja',
    ],
];
