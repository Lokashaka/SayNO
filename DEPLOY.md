# DEPLOY.md

Cara memasang SayNO di cPanel paket terendah: **tanpa terminal (SSH), tanpa symlink, tanpa Composer di server**. Semua dikerjakan lewat File Manager, menu cPanel, dan browser.

Kondisi server saat ini (Rumahweb, akun `compiled`, dipasang 16 Oktober 2026):

| Hal | Nilai |
| --- | --- |
| Folder aplikasi | `/home/compiled/public_html/sayno` |
| Document root `sayno.lokashaka.id` | `public_html/sayno/public` (cPanel di server ini mewajibkan document root di dalam `public_html`) |
| PHP | `ea-php83`, diatur hanya untuk vhost `sayno.lokashaka.id` lewat MultiPHP; situs lain tetap 7.4 |
| Database | `compiled_sayno`, user `compiled_sayno` |
| Cron | `* * * * * /usr/local/bin/ea-php83 /home/compiled/public_html/sayno/artisan schedule:run >> /dev/null 2>&1` |
| Batas upload PHP | 10 MB, terkunci di paket ini (`.user.ini` dan `php.ini` per folder tidak berpengaruh). Tidak masalah: APK di atas batas ini dibaca di browser. |

## 0. Sekali saja: cek cPanel

Di **MultiPHP Manager**, set **PHP 8.3** khusus untuk domain SayNO (Laravel 13 tidak jalan di 8.2). Jangan ubah versi untuk seluruh akun, karena situs lain bisa rusak. Ekstensi yang dibutuhkan: `intl`, `curl`, `mbstring`, `openssl`, `pdo_mysql`, `fileinfo`. Halaman `/_setup` memeriksa semuanya.

Batas upload tidak perlu diubah. SayNO membaca `upload_max_filesize` dan `post_max_size` saat berjalan (`App\Support\UploadLimit`): APK sampai batas itu diunggah utuh, sedangkan yang lebih besar (sampai 100 MB) dibaca di browser dan hanya manifest-nya yang dikirim.

## 1. Di komputer lokal: buat paket

```bash
npm run build
php artisan test
php scripts/build-deploy.php
```

Hasilnya `dist/sayno-deploy-<waktu>.zip` (sekitar 8 MB). Isinya sudah lengkap dengan `vendor/`, jadi server tidak perlu Composer. File `.env`, `tests/`, dan `node_modules/` tidak ikut.

Buat juga isi `.env` produksi (lihat langkah 4). Untuk `APP_KEY`, jalankan di lokal:

```bash
php artisan key:generate --show
```

## 2. Upload dan extract

1. **File Manager** → buka `public_html/`.
2. **Upload** file ZIP, lalu klik kanan → **Extract**. Hasilnya folder `public_html/sayno/`.
3. Pindahkan ZIP keluar dari `public_html` (ke `/home/USER/`) atau hapus.

Server ini mewajibkan document root di dalam `public_html`, jadi kode ikut berada di sana. Folder itu juga terbuka lewat domain utama (`compiledaily.net/sayno/...`). Yang menjaganya adalah `sayno/.htaccess`, yang meneruskan semua permintaan ke `public/` dan memblokir file berawalan titik. **Jangan hapus `sayno/.htaccess`.** Setelah deploy, pastikan alamat berikut menjawab 404:

```
https://compiledaily.net/sayno/.env
https://compiledaily.net/sayno/composer.json
https://compiledaily.net/sayno/vendor/autoload.php
```

## 3. Subdomain dan SSL

**Domains** → `sayno.lokashaka.id` → **Document Root** `public_html/sayno/public`. Ini tidak memakai symlink; cPanel langsung mengarahkan subdomain ke folder itu.

Lalu **SSL/TLS Status** → **Run AutoSSL** jika sertifikatnya belum ada.

## 4. Database dan `.env`

1. **MySQL Databases**: buat database, buat user, lalu tambahkan user ke database dengan **ALL PRIVILEGES**.
2. File Manager → `sayno/` → salin `.env.example` menjadi `.env` → **Edit**:

```dotenv
APP_ENV=production
APP_DEBUG=false
APP_KEY=base64:...            # dari "php artisan key:generate --show"
APP_URL=https://sayno.lokashaka.id

DB_CONNECTION=mysql
DB_HOST=localhost
DB_PORT=3306
DB_DATABASE=USER_sayno
DB_USERNAME=USER_sayno
DB_PASSWORD=...

SAYNO_CONTACT_EMAIL=...       # kotak masuk aktif, dipakai untuk permintaan hapus data
SAYNO_SETUP_TOKEN=...         # teks acak minimal 32 karakter
SAFE_BROWSING_KEY=...         # opsional, tapi disarankan
```

3. Izin folder (klik kanan → **Change Permissions**): `storage/` dan `bootstrap/cache/` beserta isinya `755`. Pakai `775` jika halaman setup menyebut folder tidak bisa ditulis.

## 5. Pengganti terminal: halaman `/_setup`

Buka `https://sayno.lokashaka.id/_setup` lalu masukkan `SAYNO_SETUP_TOKEN`.

1. Periksa **Kesiapan server**. Semua yang bertanda wajib harus hijau, kecuali Cron (diatur di langkah 6).
2. Tekan **Migrasi database**.
3. Tekan **Isi data awal**. Jika `SAYNO_DEMO_BRAND_TOKEN` kosong, token dashboard `/org/bca` muncul di hasil. **Catat token itu**; database hanya menyimpan hash-nya.
4. Tekan **Bangun cache**.

| Dulu (dengan terminal) | Sekarang |
| --- | --- |
| `php artisan migrate --force` | Tombol **Migrasi database** |
| `php artisan db:seed --force` | Tombol **Isi data awal** |
| `php artisan config:cache`, `route:cache` | Tombol **Bangun cache** |
| `php artisan optimize:clear` | Tombol **Hapus cache** |

## 6. Cron (antrean dan pemindaian domain tiruan)

**Pilihan A, menu Cron Jobs cPanel** (tidak butuh terminal). Tambahkan tugas **Once Per Minute** (`* * * * *`):

```
/usr/local/bin/ea-php83 /home/USER/public_html/sayno/artisan schedule:run >> /dev/null 2>&1
```

Pakai `ea-php83`, versi yang sama dengan web. `/usr/local/bin/php` di server ini adalah PHP 7.4, sehingga cron akan gagal.

**Pilihan B, jika menu Cron Jobs tidak ada.** Isi `SAYNO_CRON_TOKEN` di `.env` (teks acak minimal 32 karakter), tekan **Bangun cache**, lalu daftarkan URL berikut di layanan pemanggil gratis seperti cron-job.org, setiap 1 menit:

```
https://sayno.lokashaka.id/_cron?token=ISI_SAYNO_CRON_TOKEN
```

Setelah 1–3 menit, baris **Cron berjalan** di halaman setup harus hijau.

Semua tugas terjadwal berjalan di proses yang sama, tanpa `exec`, `shell_exec`, atau `proc_open`.

## 7. Tutup halaman setup

Kosongkan `SAYNO_SETUP_TOKEN=` di `.env`. Mulai saat itu `/_setup` menjawab 404, dan perubahan langsung berlaku. (Jika baru saja menekan **Bangun cache**, isi token lama sebentar, tekan **Hapus cache**, lalu kosongkan token.)

## Memperbarui versi

1. Di lokal: `npm run build`, `php artisan test`, `php scripts/build-deploy.php`.
2. Upload ZIP ke `public_html/`, lalu Extract dan timpa `sayno/`. `.env` di server tidak tersentuh karena tidak ada di ZIP. Pindahkan ZIP keluar dari `public_html`.
3. Isi `SAYNO_SETUP_TOKEN`, buka `/_setup`, lalu tekan **Hapus cache**, **Migrasi database**, dan **Bangun cache**. Setelah itu kosongkan token lagi.

## Masalah umum

| Gejala | Penyebab dan solusi |
| --- | --- |
| Halaman putih atau error 500 | Versi PHP di bawah 8.3, atau `storage/` tidak bisa ditulis. Lihat `storage/logs/laravel.log` di File Manager. |
| "Your Composer dependencies require PHP >= 8.3" | Ganti versi PHP di Select PHP Version. |
| Perubahan `.env` tidak terbaca | Cache konfigurasi lama. Tekan **Hapus cache** lalu **Bangun cache**. |
| Upload APK besar gagal | File di atas batas upload PHP harus lewat jalur browser. Pastikan JavaScript aktif dan browser cukup baru (butuh `DecompressionStream`). |
| `/_setup` error 500 sebelum migrasi | Versi lama memakai sesi database. Versi sekarang memakai sesi file khusus untuk `/_setup`. |
| `/_setup` 404 | `SAYNO_SETUP_TOKEN` kosong atau kurang dari 32 karakter, atau cache konfigurasi lama. |
