# CLAUDE.md

Panduan untuk Claude (dan anggota tim) saat bekerja di repo **SayNO**.
Baca file ini sebelum mengubah kode. Detail sistem ada di `ARCHITECTURE.md`, aturan tampilan dan bahasa ada di `DESIGN.md`.

## Tentang proyek

SayNO adalah sistem peringatan dini penipuan digital untuk lomba .id DeveloperDay 2026 (kategori Business Services, subkategori Security Solutions).

- **Untuk masyarakat (gratis):** bot WhatsApp. Pengguna meneruskan link, file APK, atau pesan mencurigakan, lalu bot membalas status **Aman / Waspada / Bahaya** dengan alasan berbahasa awam.
- **Untuk organisasi (berbayar, nanti):** dashboard yang menampilkan domain tiruan dan APK palsu yang memakai merek mereka.
- **Web** berjalan di `https://sayno.lokashaka.id` (subdomain .id, memenuhi aturan lomba).

Tenggat: submit paper dan video **25 Oktober 2026**. Final 10–11 November 2026.

## Stack

| Bagian | Teknologi |
| --- | --- |
| Backend dan web | Laravel (PHP 8.2+), Blade, Tailwind CSS |
| Database | MySQL (dari cPanel) |
| Antrean | Laravel Queue, driver `database`, dijalankan cron |
| WhatsApp | WhatsApp Cloud API resmi (Meta), lewat webhook |
| Cadangan WhatsApp | Baileys (Node.js) di Pterodactyl, hanya jika akun Meta bermasalah |
| API luar | Google Safe Browsing, RDAP, Gemini API (opsional) |
| Hosting | Shared hosting cPanel (Rumahweb), subdomain `sayno.lokashaka.id` |

## Batasan hosting (penting)

Kode harus jalan di **shared hosting cPanel paket terendah yang hanya mendukung PHP**: **tanpa terminal (SSH) dan tanpa symlink**. Akibatnya:

- **Tidak ada `composer`, `npm`, atau `php artisan` di server.** `vendor/` dan `public/build` dibuat di lokal lalu diupload (`php scripts/build-deploy.php`). Migrate, seed, dan cache lewat halaman `/_setup` yang dilindungi token. Lihat `DEPLOY.md`.
- **Tidak ada symlink.** Jangan memakai `storage:link` atau file publik di `storage/app/public`. Document root subdomain diarahkan ke `sayno/public` lewat menu cPanel.
- **Tugas terjadwal wajib `Schedule::call()` + `Artisan::call()`.** `Schedule::command()` dan `Schedule::exec()` membuat proses baru lewat `proc_open`. Test `HostingWithoutTerminalTest` menjaga aturan ini.

- **Tidak ada proses yang hidup terus.** Jangan memakai `queue:work` sebagai daemon, websocket, atau Laravel Octane/Reverb.
- **Anggap `exec()`, `shell_exec()`, dan `proc_open()` tidak tersedia.** Jangan bergantung pada program command-line (aapt, apktool, python).
- **Batas waktu eksekusi sekitar 30–60 detik.** Webhook wajib menjawab `200 OK` secepatnya; kerja berat masuk antrean.
- **Antrean dijalankan cron tiap menit** lewat menu Cron Jobs cPanel (tidak butuh terminal):
  ```
  * * * * * /usr/local/bin/php /home/USER/sayno/artisan schedule:run >> /dev/null 2>&1
  ```
  Di `routes/console.php`, `queue:work --stop-when-empty --max-time=50` dijadwalkan lewat `Schedule::call()`. Jika menu Cron Jobs tidak ada, pemanggil luar membuka `/_cron?token=...`.
- **Hanya pakai paket Composer murni PHP.** Ekstensi yang boleh diandalkan: `zip`, `curl`, `mbstring`, `openssl`, `pdo_mysql`, `intl` (cek dulu di menu "Select PHP Version").

## Perintah

```bash
composer install
cp .env.example .env && php artisan key:generate
php artisan migrate --seed                  # seed: domain resmi, pola modus, merek contoh
php artisan serve                           # lokal
php artisan test                            # wajib hijau sebelum push
php artisan queue:work --stop-when-empty    # jalankan antrean manual saat lokal
npm install && npm run build                # aset Tailwind; hasil build di-commit ke public/build
./vendor/bin/pint                           # format kode
```

Deploy ke cPanel (tanpa terminal, panduan lengkap di `DEPLOY.md`):

```bash
npm run build && php artisan test
php scripts/build-deploy.php                # dist/sayno-deploy-*.zip, sudah berisi vendor/ tanpa paket dev
```

Upload ZIP lewat File Manager, extract ke `/home/USER/sayno`, lalu arahkan document root subdomain `sayno.lokashaka.id` ke `sayno/public`. Migrate, seed, dan cache dijalankan dari `https://sayno.lokashaka.id/_setup` (aktif hanya jika `SAYNO_SETUP_TOKEN` diisi; kosongkan lagi setelah selesai).

## Struktur folder

```
app/
  Http/Controllers/
    WhatsAppWebhookController.php   # verifikasi GET + terima POST dari Meta
    CheckController.php             # form cek link/APK di web
    OrgDashboardController.php      # dashboard organisasi
  Jobs/
    AnalyzeIncomingMessage.php      # dispatch dari webhook
  Services/
    WhatsApp/CloudApiClient.php     # kirim pesan, unduh media
    Analysis/LinkAnalyzer.php       # domain tiruan, umur domain, daftar hitam
    Analysis/ApkAnalyzer.php        # baca manifest, izin berbahaya
    Analysis/TextAnalyzer.php       # pola modus
    Analysis/LookalikeGenerator.php # variasi ejaan domain merek
    Scoring/RiskScorer.php          # gabungkan sinyal -> skor 0-100
    Reply/ReplyComposer.php         # susun balasan bahasa awam
  Models/                           # Check, Signal, OfficialDomain, ScamPattern, Brand, LookalikeDomain
config/sayno.php                    # bobot skor, ambang status, batas ukuran file
lang/id/                            # semua teks untuk pengguna
```

Aturan: controller tipis, logika di `Services/`. Setiap analyzer mengembalikan daftar `Signal` (kode, bobot, alasan), bukan langsung skor.

## Konvensi kode

- Nama kelas, method, variabel, dan kolom database dalam **bahasa Inggris**. Teks untuk pengguna dalam **bahasa Indonesia**.
- Semua teks balasan bot dan UI ada di `lang/id/*.php`, jangan di-hardcode di logika.
- Gaya kode PSR-12, format dengan Pint sebelum commit.
- Setiap analyzer punya unit test dengan contoh nyata di `tests/Fixtures/` (link, nama domain, manifest APK yang sudah diekstrak). **Jangan commit file APK berbahaya**; simpan manifest hasil ekstraksinya saja.
- Bobot skor dan ambang status hanya diubah di `config/sayno.php`, tidak di dalam kode.

## Aturan keamanan (tidak boleh dilanggar)

1. **APK tidak pernah dijalankan.** Hanya dibaca sebagai ZIP. Simpan di `storage/app/quarantine/` (di luar `public/`) dan hapus setelah dianalisis.
2. **Verifikasi setiap request webhook** dengan header `X-Hub-Signature-256` (HMAC SHA-256 dari body mentah, kunci `META_APP_SECRET`). Tolak dengan `403` jika tidak cocok.
3. **Rahasia hanya di `.env`:** `META_ACCESS_TOKEN`, `META_APP_SECRET`, `META_VERIFY_TOKEN`, `META_PHONE_NUMBER_ID`, `SAFE_BROWSING_KEY`, `GEMINI_API_KEY`. `.env` tidak pernah di-commit, dan nilainya tidak boleh muncul di log atau pesan error.
4. **Privasi:** simpan nomor pengirim sebagai hash (`hash_hmac('sha256', $phone, config('app.key'))`), simpan hash file dan domain, **jangan simpan isi pesan**.
5. **Batas ukuran APK.** File utuh yang diunggah ke server maksimal 20 MB. File 20–100 MB di web dibaca di browser (`resources/js/apk-manifest.js`); yang dikirim hanya `AndroidManifest.xml` dan SHA-256. Di atas 100 MB ditolak dengan balasan sopan.
6. **Jangan membuka link dari pengguna.** Cek link cukup dari nama domain, RDAP, dan Safe Browsing. Server tidak pernah melakukan request ke URL kiriman pengguna (mencegah SSRF).
7. **Bot hanya membalas** di dalam jendela layanan 24 jam. Tidak ada broadcast atau pesan promosi.

## Cara kerja webhook (ringkas)

1. `GET /webhook/whatsapp`: cocokkan `hub.verify_token` dengan `META_VERIFY_TOKEN`, lalu kembalikan `hub.challenge`.
2. `POST /webhook/whatsapp`: verifikasi tanda tangan, simpan ID pesan (idempoten, karena Meta bisa mengirim ulang), dispatch `AnalyzeIncomingMessage`, lalu langsung `200 OK`.
3. Job: unduh media jika ada, jalankan analyzer, `RiskScorer`, `ReplyComposer`, lalu kirim lewat `CloudApiClient`.
4. Untuk APK, kirim dulu balasan sementara "Sedang diperiksa…".

Route webhook dikecualikan dari CSRF (di `bootstrap/app.php`).

## Definisi selesai

Sebuah fitur dianggap selesai jika:
- test lulus dan Pint bersih,
- jalan di hosting cPanel, bukan hanya di lokal,
- teks pengguna sesuai `DESIGN.md` (status, nada, panjang balasan),
- tidak melanggar aturan keamanan di atas.

## Prioritas sampai 25 Oktober

Kerjakan **P0** dulu (lihat PRD): webhook, deteksi jenis isi, cek link, cek APK, cek teks, skor dan balasan, web cek manual, riwayat cek, dan dashboard organisasi untuk satu merek contoh. Fitur P1 (edukasi, panel admin, Gemini, cache, Baileys cadangan, email peringatan) dikerjakan setelah P0 stabil.
