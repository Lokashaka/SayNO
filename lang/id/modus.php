<?php

// Contoh pesan disamarkan: nama, nomor, dan link diganti.
return [
    'categories' => [
        'hadiah' => 'hadiah undian',
        'paket' => 'paket tertahan',
        'kerja' => 'kerja paruh waktu',
        'tagihan' => 'tagihan atau denda',
        'tarif' => 'tarif transfer bank berubah',
        'pinjaman' => 'pinjaman cepat cair',
        'keluarga' => 'keluarga minta uang',
        'akun' => 'akun diblokir',
        'investasi' => 'investasi untung pasti',
        'lainnya' => 'penipuan',
    ],

    'items' => [
        [
            'slug' => 'apk-undangan',
            'title' => 'File APK berkedok undangan atau surat',
            'summary' => 'File bernama "Undangan Pernikahan" atau "Surat Tilang" ternyata aplikasi yang mencuri kode OTP.',
            'example' => "Assalamualaikum, mohon kehadirannya di acara pernikahan kami 🙏\n📎 Undangan_Pernikahan_Digital.apk",
            'signs' => [
                'Nama file berakhiran .apk, padahal katanya undangan, surat, atau resi.',
                'Dikirim dari nomor tak dikenal, atau dari kenalan yang akunnya dibajak.',
                'Setelah dibuka, aplikasi meminta izin membaca SMS.',
            ],
            'avoid' => [
                'Undangan dan surat asli berbentuk gambar atau PDF, bukan aplikasi.',
                'Jangan instal aplikasi dari chat. Unduh hanya dari Play Store.',
                'Teruskan dulu ke SayNO kalau ragu.',
            ],
        ],
        [
            'slug' => 'paket-tertahan',
            'title' => 'Paket tertahan atau cek resi',
            'summary' => 'Pesan mengaku dari kurir, meminta Anda membuka link atau file untuk mengecek paket.',
            'example' => 'Paket Anda tertahan karena alamat tidak lengkap. Lengkapi data di: kurir-cek-resi[.]xyz/id',
            'signs' => [
                'Anda tidak merasa sedang menunggu paket.',
                'Link-nya bukan situs resmi kurir, atau memakai penyingkat alamat.',
                'Diminta membayar biaya kecil atau mengisi data kartu.',
            ],
            'avoid' => [
                'Cek resi langsung di aplikasi atau situs resmi kurir.',
                'Kurir tidak meminta pembayaran lewat link di chat.',
            ],
        ],
        [
            'slug' => 'tarif-transfer',
            'title' => 'Tarif transfer bank berubah',
            'summary' => 'Pesan mengaku dari bank, meminta Anda memilih tarif baru lewat link, lalu meminta PIN dan OTP.',
            'example' => 'Nasabah Yth, mulai besok tarif transfer berubah menjadi Rp150.000/bulan. Jika tidak setuju isi formulir: bank-tarif-baru[.]my[.]id',
            'signs' => [
                'Bank tidak mengubah tarif lewat pesan WhatsApp pribadi.',
                'Alamat situs mirip nama bank, tapi ada tambahan kata atau akhiran berbeda.',
                'Diminta mengisi nomor kartu, PIN, atau kode OTP.',
            ],
            'avoid' => [
                'Hubungi bank lewat nomor di balik kartu ATM atau aplikasi resminya.',
                'Jangan pernah memberikan PIN atau OTP kepada siapa pun, termasuk petugas bank.',
            ],
        ],
        [
            'slug' => 'hadiah-undian',
            'title' => 'Hadiah undian',
            'summary' => 'Anda dikabarkan menang undian, tetapi harus membayar pajak atau biaya admin dulu.',
            'example' => 'Selamat! Nomor Anda terpilih mendapatkan hadiah Rp25.000.000 dari program undian. Klaim sekarang di: hadiah-resmi[.]site',
            'signs' => [
                'Anda tidak pernah ikut undian tersebut.',
                'Diminta transfer uang untuk pajak atau biaya pengiriman hadiah.',
                'Ada batas waktu yang membuat terburu-buru.',
            ],
            'avoid' => [
                'Hadiah asli tidak meminta Anda membayar lebih dulu.',
                'Cek pengumuman di situs atau akun resmi penyelenggara.',
            ],
        ],
        [
            'slug' => 'kerja-paruh-waktu',
            'title' => 'Kerja paruh waktu: like dan subscribe',
            'summary' => 'Ditawari uang untuk menyukai video, lalu diminta "deposit" untuk tugas berikutnya.',
            'example' => 'Halo kak, kami butuh pekerja paruh waktu. Cukup like video YouTube, dibayar Rp50.000 per tugas. Minat? Balas YA',
            'signs' => [
                'Bayaran tidak masuk akal untuk pekerjaan yang sangat mudah.',
                'Awalnya dibayar kecil, lalu diminta setor uang untuk tugas lebih besar.',
                'Dipindahkan ke grup Telegram dengan "mentor".',
            ],
            'avoid' => [
                'Pekerjaan asli tidak meminta Anda menyetor uang.',
                'Jangan lanjutkan meski sudah pernah dibayar kecil.',
            ],
        ],
        [
            'slug' => 'keluarga-nomor-baru',
            'title' => 'Keluarga ganti nomor dan butuh uang',
            'summary' => 'Pesan mengaku dari anak atau saudara yang ganti nomor, lalu meminta transfer darurat.',
            'example' => 'Ma, ini aku, nomor lama hilang. Tolong transfer dulu ya, nanti aku ganti. Lagi darurat 🙏',
            'signs' => [
                'Nomor baru dan langsung meminta uang.',
                'Menolak ditelepon dengan berbagai alasan.',
                'Rekening tujuan atas nama orang lain.',
            ],
            'avoid' => [
                'Telepon nomor lama atau keluarga lain untuk memastikan.',
                'Tanyakan hal yang hanya diketahui keluarga.',
            ],
        ],
    ],
];
