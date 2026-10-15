<?php

// Halaman internal untuk operator, bukan untuk masyarakat.
return [
    'title' => 'Setup server',
    'login_lead' => 'Masukkan SAYNO_SETUP_TOKEN dari file .env.',
    'token' => 'Token setup',
    'login_submit' => 'Masuk',
    'lead' => 'Pengganti terminal untuk hosting tanpa SSH. Setelah deploy selesai, kosongkan SAYNO_SETUP_TOKEN di .env agar halaman ini tertutup.',
    'checks_title' => 'Kesiapan server',
    'required' => 'wajib',
    'optional' => 'opsional',
    'actions_title' => 'Perintah',
    'output_title' => 'Hasil perintah: :action',
    'actions' => [
        'migrate' => ['label' => 'Migrasi database', 'hint' => 'Membuat atau memperbarui tabel (php artisan migrate --force).'],
        'seed' => ['label' => 'Isi data awal', 'hint' => 'Domain resmi, pola modus, merek contoh. Aman diulang. Token dashboard muncul di hasil jika dibuat baru.'],
        'optimize' => ['label' => 'Bangun cache', 'hint' => 'Jalankan setelah .env final. Ulangi setiap kali .env atau kode berubah.'],
        'clear' => ['label' => 'Hapus cache', 'hint' => 'Pakai jika perubahan .env tidak terbaca.'],
    ],
];
