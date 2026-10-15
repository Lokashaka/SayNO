# DEPLOY.md

Cara memasang SayNO di cPanel paket terendah: **tanpa terminal (SSH), tanpa symlink, tanpa Composer di server**. Semua dikerjakan lewat File Manager, menu cPanel, dan browser.

## 0. Sekali saja: cek cPanel

Di **Select PHP Version** (atau **MultiPHP Manager**):
- Pilih **PHP 8.3** atau lebih baru (Laravel 13 tidak jalan di 8.2).
- Aktifkan ekstensi: `intl`, `curl`, `mbstring`, `openssl`, `pdo_mysql`, `fileinfo`.
- Di tab **Options**: `upload_max_filesize` dan `post_max_size` minimal `25M`.

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

1. **File Manager** → buka folder home (`/home/USER/`, satu tingkat di atas `public_html`).
2. **Upload** file ZIP, lalu klik kanan → **Extract**. Hasilnya folder `/home/USER/sayno/`.
3. Hapus file ZIP setelah extract.

Jangan extract ke dalam `public_html`. Kode aplikasi harus berada di luar folder publik.

## 3. Subdomain dan SSL

**Domains** (atau **Subdomains**) → buat `sayno.lokashaka.id` dengan **Document Root** `sayno/public`.

Ini tidak memakai symlink; cPanel langsung mengarahkan subdomain ke folder itu.

> Jika Document Root tidak bisa diubah dan terkunci ke `sayno`: tidak apa-apa. File `sayno/.htaccess` meneruskan semua permintaan ke `public/`, sehingga `.env` dan `vendor/` tetap tidak bisa dibuka dari browser. Setelah selesai, cek bahwa `https://sayno.lokashaka.id/.env` menjawab 404 atau 403.

Lalu **SSL/TLS Status** → **Run AutoSSL** untuk subdomain tersebut.

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
/usr/local/bin/php /home/USER/sayno/artisan schedule:run >> /dev/null 2>&1
```

Jika cron memakai versi PHP yang salah (halaman setup tetap "Cron belum pernah"), ganti `/usr/local/bin/php` dengan `/opt/alt/php83/usr/bin/php`.

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
2. Upload ZIP, lalu Extract dan timpa `sayno/`. `.env` di server tidak tersentuh karena tidak ada di ZIP.
3. Isi `SAYNO_SETUP_TOKEN`, buka `/_setup`, lalu tekan **Hapus cache**, **Migrasi database**, dan **Bangun cache**. Setelah itu kosongkan token lagi.

## Masalah umum

| Gejala | Penyebab dan solusi |
| --- | --- |
| Halaman putih atau error 500 | Versi PHP di bawah 8.3, atau `storage/` tidak bisa ditulis. Lihat `storage/logs/laravel.log` di File Manager. |
| "Your Composer dependencies require PHP >= 8.3" | Ganti versi PHP di Select PHP Version. |
| Perubahan `.env` tidak terbaca | Cache konfigurasi lama. Tekan **Hapus cache** lalu **Bangun cache**. |
| Upload APK besar gagal | Naikkan `upload_max_filesize` dan `post_max_size` di Select PHP Version → Options. |
| `/_setup` 404 | `SAYNO_SETUP_TOKEN` kosong atau kurang dari 32 karakter, atau cache konfigurasi lama. |
