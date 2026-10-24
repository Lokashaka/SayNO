# SayNO — Katakan tidak pada penipu

Sistem peringatan dini penipuan digital lewat WhatsApp. Teruskan link, file APK, atau pesan mencurigakan ke bot SayNO, lalu dapatkan status **Aman / Waspada / Bahaya** beserta alasannya dalam bahasa sehari-hari.

*A WhatsApp-native early warning system for digital fraud in Indonesia, built for .id DeveloperDay 2026 (Business Services – Security Solutions).*

| | |
| --- | --- |
| Web | [sayno.lokashaka.id](https://sayno.lokashaka.id) |
| Bot WhatsApp | [+62 851-1141-2715](https://wa.me/6285111412715) |
| Video demo | [youtu.be/My3CTzoWnyk](https://youtu.be/My3CTzoWnyk) |
| Daftar phishing | [sayno.lokashaka.id/daftar-phishing](https://sayno.lokashaka.id/daftar-phishing) |

## Cara kerja

1. **Teruskan.** Pengguna meneruskan link, APK, atau pesan ke bot, atau menempelkannya di web.
2. **Periksa.** SayNO mengenali jenis isi lalu menjalankan analyzer yang sesuai.
3. **Putuskan.** Pengguna menerima status, maksimal tiga alasan, dan langkah yang perlu dilakukan. Membalas `SUDAH` memunculkan langkah darurat jika APK telanjur terinstal.

Setiap pemeriksaan, ditambah pemindaian variasi nama merek tiap jam, mengisi satu database ancaman. Database itu dipakai dashboard berbayar yang menunjukkan domain tiruan dan APK palsu yang memakai nama sebuah merek.

## Fitur

| Fitur | Ringkasan |
| --- | --- |
| Cek link | Domain tiruan (salah ketik, huruf tiruan seperti 0/o dan 4/a, nama merek + kata lain, akhiran lain), umur domain dari RDAP, Google Safe Browsing, penyingkat URL. Link tidak pernah dibuka server. |
| Cek APK | ZIP dibaca dengan parser sendiri dan `AndroidManifest.xml` biner didekode dengan parser AXML PHP murni. Menandai izin baca SMS, Accessibility Service, overlay, pemasangan aplikasi lain, penyebaran lewat SMS, peniruan merek, dan nama file berkedok dokumen. APK tidak pernah dijalankan. |
| APK besar | File 20–100 MB dibaca di browser; yang dikirim hanya manifest dan SHA-256. |
| Cek teks | Pola 9 modus: hadiah, paket, kerja paruh waktu, tagihan, tarif bank, pinjaman, keluarga, akun diblokir, investasi. |
| Pemindai domain tiruan | Variasi ejaan domain merek dicek bertahap tiap jam lewat DNS dan RDAP. |
| Daftar phishing publik | `/daftar-phishing`, domain ditulis dengan `[.]` agar tidak bisa diklik. |
| Dashboard merek | `/org/{merek}` dengan token: domain tiruan, teknik, tanggal daftar, dan APK palsu. |

## Skor risiko

Setiap analyzer mengembalikan daftar `Signal` (kode, bobot, alasan). Skor adalah jumlah bobot, maksimal 100:

| Skor | Status |
| --- | --- |
| 0–29 | Aman |
| 30–59 | Waspada |
| 60–100 | Bahaya |

Bobot dan ambang hanya diatur di [`config/sayno.php`](config/sayno.php).

## Stack

| Bagian | Teknologi |
| --- | --- |
| Backend dan web | Laravel 13 (PHP 8.3), Blade, Tailwind CSS 4, Vite |
| Database dan antrean | MySQL, Laravel Queue driver `database` yang dijalankan cron tiap menit |
| WhatsApp | WhatsApp Cloud API resmi (Meta) lewat webhook |
| Data luar | Google Safe Browsing v4, RDAP (termasuk PANDI untuk .id), DNS |
| Hosting | Shared hosting cPanel khusus PHP, tanpa SSH dan tanpa symlink |

## Menjalankan di lokal

```bash
composer install
cp .env.example .env && php artisan key:generate
php artisan migrate --seed                  # domain resmi, pola modus, merek contoh
npm install && npm run build
php artisan serve
php artisan queue:work --stop-when-empty    # jalankan antrean secara manual
```

Isi `.env` untuk fitur WhatsApp dan Safe Browsing: `META_ACCESS_TOKEN`, `META_APP_SECRET`, `META_VERIFY_TOKEN`, `META_PHONE_NUMBER_ID`, `SAFE_BROWSING_KEY`. Tanpa nilai itu, web tetap bisa dipakai untuk cek link, APK, dan teks.

### Test dan format

```bash
php artisan test
./vendor/bin/pint
```

### APK demo

`php scripts/make-demo-apks.php` membuat enam APK sintetis di `dist/demo-apk/` (hanya manifest, tidak bisa diinstal) untuk demo status Aman, Waspada, dan Bahaya.

## Deploy

Server tidak punya terminal, jadi `vendor/` dan `public/build` disiapkan di lokal:

```bash
npm run build && php artisan test
php scripts/build-deploy.php                # dist/sayno-deploy-*.zip
```

Upload ZIP lewat File Manager cPanel, lalu jalankan migrate, seed, dan cache dari `/_setup`. Langkah lengkapnya ada di [ARCHITECTURE.md](ARCHITECTURE.md#8-deploy).

## Keamanan dan privasi

- Setiap request webhook diverifikasi dengan `X-Hub-Signature-256`.
- Link pengguna tidak pernah dibuka server (mencegah SSRF).
- APK hanya dibaca sebagai ZIP, disimpan di luar `public/`, lalu dihapus setelah dianalisis.
- Nomor pengirim disimpan sebagai hash HMAC-SHA-256; isi pesan tidak disimpan.
- Bot hanya membalas di dalam jendela layanan 24 jam, tanpa broadcast.

## Dokumentasi

| Dokumen | Isi |
| --- | --- |
| [PRD.md](PRD.md) | Kebutuhan produk, persona, prioritas fitur |
| [ARCHITECTURE.md](ARCHITECTURE.md) | Arsitektur, alur pesan, endpoint, database, deploy |
| [DESIGN.md](DESIGN.md) | Aturan tampilan, nada, dan bahasa |
| [CLAUDE.md](CLAUDE.md) | Panduan kerja di repo ini |

## Tim

Mahasiswa Universitas Negeri Malang:

- **Moh Dhiyaulhaq Ulumuddin** — ketua tim, backend dan analisis
- **Mohammad Nugroho Candy Pratama** — integrasi WhatsApp dan web
- **Izaz Saputra** — riset, model bisnis, paper, dan desain
