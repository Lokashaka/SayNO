# ARCHITECTURE.md

Gambaran teknis sistem **SayNO**: komponen, alur data, endpoint, database, dan cara deploy.
Untuk aturan kerja di repo lihat `CLAUDE.md`; untuk tampilan dan teks lihat `DESIGN.md`.

## 1. Gambaran besar

Semua logika ada di satu aplikasi Laravel di shared hosting cPanel. Meta (WhatsApp Cloud API) hanya mengantar pesan masuk dan keluar.

```
                   ┌──────────────────────────── cPanel (domain .id) ────────────────────────────┐
Pengguna WA ⇄ Meta │ Webhook ─► Antrean (DB) ─► Analyzer ─► RiskScorer ─► ReplyComposer ─► API Meta │
 Cloud API ───────►│                                │                                              │
                   │ Web publik ──── cek manual ────┘          MySQL: checks, signals, brands, …   │
Pengguna web ─────►│ Dashboard organisasi ◄──────── data domain tiruan & APK palsu ───────────────│
                   └───────────────────────────────────────────────┬──────────────────────────────┘
                                                                    ▼
                                             API luar: Safe Browsing, RDAP, Gemini (opsional)

Cadangan: Baileys (Pterodactyl) ─► POST /api/bot/inbound (token) ─► pipeline yang sama
```

| Komponen | Tempat | Tugas |
| --- | --- | --- |
| WhatsApp Cloud API | Server Meta | Mengirim pesan masuk ke webhook, menerima balasan |
| Webhook Laravel | cPanel | Verifikasi tanda tangan, simpan pesan, masukkan ke antrean |
| Antrean + cron | cPanel | Menjalankan analisis di luar request webhook |
| Analyzer | cPanel | Cek link, APK, teks; menghasilkan sinyal |
| RiskScorer | cPanel | Menggabungkan sinyal menjadi skor dan status |
| ReplyComposer | cPanel | Menyusun balasan bahasa awam |
| Web publik | cPanel | Cek manual, edukasi, statistik, kebijakan privasi |
| Dashboard organisasi | cPanel | Domain tiruan dan APK palsu per merek |
| Gateway cadangan | Pterodactyl | Baileys, hanya dipakai jika akun Meta bermasalah |

## 2. Alur pesan WhatsApp

```
1. Pengguna meneruskan pesan ke nomor SayNO
2. Meta  ──POST /webhook/whatsapp──►  Laravel
3. Laravel: cek X-Hub-Signature-256 → simpan inbound_messages (unik per wamid) → dispatch job → 200 OK
4. Cron (tiap menit): schedule:run → queue:work --stop-when-empty --max-time=50
5. Job AnalyzeIncomingMessage:
     a. tentukan jenis isi: link / APK / teks
     b. APK: kirim "Sedang diperiksa…" → unduh media lewat Graph API → simpan di quarantine
     c. jalankan analyzer yang sesuai → kumpulan Signal
     d. RiskScorer → skor 0–100 + status
     e. ReplyComposer → teks balasan
     f. CloudApiClient → POST /{phone_number_id}/messages
     g. simpan Check + Signal, hapus file APK
```

Waktu balasan yang diharapkan: teks dan link sekitar 1 menit (menunggu cron), APK di bawah 2 menit. Kalau hosting mengizinkan, link dan teks boleh dianalisis langsung di request webhook (target di bawah 10 detik) dengan tetap menjawab Meta lebih dulu memakai `dispatch()->afterResponse()`.

## 3. Endpoint

| Method | Path | Fungsi | Proteksi |
| --- | --- | --- | --- |
| GET | `/webhook/whatsapp` | Verifikasi webhook Meta | `hub.verify_token` |
| POST | `/webhook/whatsapp` | Pesan masuk dan status pengiriman | `X-Hub-Signature-256` |
| POST | `/api/bot/inbound` | Pesan masuk dari Baileys (cadangan) | Header `X-Bot-Token` |
| GET | `/api/bot/outbox` | Balasan siap kirim untuk Baileys (polling) | Header `X-Bot-Token` |
| GET | `/` | Beranda: cek link, upload APK | Publik, rate limit |
| POST | `/cek/link` | Cek link dari web | Publik, rate limit, CSRF |
| POST | `/cek/apk` | Upload APK utuh dari web | Publik, rate limit, CSRF, maks 20 MB |
| POST | `/cek/apk-manifest` | APK 20–100 MB: browser hanya mengirim `AndroidManifest.xml` + SHA-256 | Publik, rate limit, CSRF, manifest maks 4 MB |
| GET | `/daftar-phishing` | Daftar publik domain berstatus Bahaya (titik disamarkan), bisa dicari | Publik |
| GET | `/hasil/{check}` | Halaman hasil (ID acak, bukan angka urut) | Publik |
| GET | `/modus` | Edukasi modus penipuan | Publik |
| GET | `/privasi` | Kebijakan privasi (syarat terbit aplikasi Meta); `#hapus-data` untuk instruksi penghapusan data | Publik |
| GET | `/syarat-ketentuan` | Syarat dan ketentuan (syarat terbit aplikasi Meta) | Publik |
| GET | `/org/{brand}` | Dashboard organisasi | Login sederhana atau token demo |
| GET, POST | `/_setup` | Pengganti terminal: migrate, seed, cache, cek kesiapan server | `SAYNO_SETUP_TOKEN`; 404 jika kosong |
| GET | `/_cron` | Cadangan cron untuk pemanggil luar | `?token=` = `SAYNO_CRON_TOKEN`; 404 jika kosong |
| GET | `/admin/*` | Panel admin | Login admin |

## 4. Analyzer

Setiap analyzer menerima input dan mengembalikan `Signal[]`:

```php
final class Signal {
    public function __construct(
        public string $code,     // contoh: 'apk.read_sms'
        public int $weight,      // bobot dari config/sayno.php
        public string $reason,   // kunci terjemahan, contoh: 'signals.apk.read_sms'
        public array $meta = [], // data pendukung, contoh: ['domain' => 'bca-co.id']
    ) {}
}
```

### LinkAnalyzer
1. Ekstrak URL dari teks, normalisasi (huruf kecil, buang `www.`, ubah punycode `xn--` ke Unicode untuk perbandingan).
2. Ambil *registrable domain* (misalnya `login.bca-co.id` → `bca-co.id`), memperhatikan sufiks `.co.id`, `.go.id`, `.ac.id`, `.my.id`, `.web.id`, dan lainnya.
3. **Domain resmi persis** ada di `official_domains` → sinyal `link.official`, skor akhir dipaksa 0.
4. **Mirip domain resmi:** bandingkan label utama dengan setiap domain resmi memakai `levenshtein()` (jarak ≤ 2), normalisasi karakter tiruan (`0→o`, `1→l`, `rn→m`, `vv→w`), dan cek apakah nama merek muncul sebagai bagian dari domain lain (`bca-co.id`, `bca.co.id.xyz`).
5. **Umur domain:** RDAP (`https://rdap.org/domain/{domain}`, untuk .id lewat RDAP PANDI) → tanggal registrasi. Hasil di-cache 24 jam.
6. **Daftar hitam:** Google Safe Browsing Lookup API v4. Di-cache 1 jam.
7. **Penyingkat URL** (bit.ly, s.id, dan sejenisnya) → sinyal `link.shortener`. URL tidak dibuka.

### ApkAnalyzer
1. Ambil `AndroidManifest.xml` dengan membaca struktur ZIP sendiri (central directory), tanpa `ZipArchive`. Metode kompresi tidak dipercaya, karena APK berbahaya sering memalsukannya; "deflate" dan "disimpan" dicoba dan hasilnya divalidasi sebagai XML biner Android. File utuh di server maksimal 20 MB. File 20–100 MB dari web dibaca browser dengan logika yang sama (`resources/js/apk-manifest.js`), dan yang dikirim hanya manifest + SHA-256.
2. Decode manifest biner (AXML) dengan parser PHP murni dari Composer. Ambil `package`, label aplikasi, versi, dan daftar `uses-permission`.
3. Sinyal izin berbahaya:

| Izin | Sinyal |
| --- | --- |
| `READ_SMS`, `RECEIVE_SMS` | `apk.read_sms` |
| `BIND_ACCESSIBILITY_SERVICE` (pada service) | `apk.accessibility` |
| `SYSTEM_ALERT_WINDOW` | `apk.overlay` |
| `REQUEST_INSTALL_PACKAGES` | `apk.install_packages` |
| `READ_CONTACTS` + `SEND_SMS` | `apk.spread_sms` |

4. Nama file berkedok dokumen (`undangan`, `surat`, `tilang`, `resi`, `tagihan`, `bpjs`, `pdf`) → `apk.document_disguise`.
5. Nama merek di label atau nama paket, tapi bukan paket resmi merek itu → `apk.brand_impersonation`, dicatat ke tabel `fake_apks` untuk dashboard organisasi.
6. Simpan SHA-256 file; hasil untuk hash yang sama dipakai ulang (cache).

### TextAnalyzer
Mencocokkan teks dengan pola di tabel `scam_patterns` (regex atau kata kunci, per kategori: hadiah, paket, kerja paruh waktu, tagihan, pinjaman, keluarga darurat). Maksimal dua pola dihitung. Opsional: jika skor masih ambigu (30–59), kirim teks ke Gemini untuk penjelasan tambahan. Gemini hanya menambah penjelasan, tidak menentukan skor.

### LookalikeGenerator (untuk dashboard organisasi)
Dari domain merek (misalnya `contohbank.co.id`), buat variasi: huruf hilang, huruf ganda, huruf tertukar, karakter tiruan, tanda hubung, sufiks lain (`.id`, `.my.id`, `.web.id`, `.com`), dan imbuhan umum (`login-`, `-promo`, `-cs`). Variasi yang terdaftar dicek lewat `dns_get_record()` dan RDAP, lalu disimpan di `lookalike_domains`. Dijalankan terjadwal (misalnya sekali sehari) dalam batch kecil agar tidak melewati batas waktu PHP.

## 5. Skor risiko

Bobot ada di `config/sayno.php` dan disetel ulang setelah uji pada set sampel.

| Sinyal | Bobot |
| --- | --- |
| `link.blacklisted` | +100 |
| `apk.read_sms` | +60 |
| `link.lookalike` | +50 |
| `apk.accessibility` | +40 |
| `apk.brand_impersonation` | +40 |
| `link.new_domain` (< 30 hari) | +25 |
| `apk.overlay`, `apk.install_packages` | +20 |
| `apk.document_disguise` | +20 |
| `text.pattern` | +20 per pola, maks +40 |
| `link.shortener` | +10 |
| `link.official` | skor dipaksa 0 |

Skor = min(100, jumlah bobot). Status: **0–29 Aman**, **30–59 Waspada**, **60–100 Bahaya**. Balasan menyebut maksimal tiga alasan dengan bobot terbesar.

## 6. Database

```
inbound_messages   id, wamid (unik), channel (meta|baileys), sender_hash, type, check_id, received_at, processed_at   -- tanpa isi pesan
checks             id (ulid), source (wa|web), sender_hash, input_type (link|apk|text),
                   domain, file_sha256, score, status (safe|caution|danger), created_at
signals            id, check_id, code, weight, meta (json)
outbound_messages  id, check_id, channel, recipient_hash, body, sent_at, error   -- outbox untuk Baileys
official_domains   id, brand_id (nullable), domain, category (bank|ewallet|gov|ecommerce|…)
scam_patterns      id, category, pattern, is_regex, active
brands             id, name, slug, primary_domain, official_packages (json)
lookalike_domains  id, brand_id, domain, technique, registered_at, first_seen_at, status (active|gone)
fake_apks          id, brand_id, file_sha256, package_name, app_label, first_seen_at, times_seen
api_cache          key, value, expires_at   -- atau pakai cache driver database
```

Yang **tidak** disimpan: isi pesan, nomor telepon asli, file APK setelah dianalisis.

## 7. Konfigurasi (.env)

```
APP_URL=https://sayno.lokashaka.id
DB_*                         # dari cPanel
QUEUE_CONNECTION=database
CACHE_STORE=database

META_APP_SECRET=
META_ACCESS_TOKEN=           # token permanen dari System User
META_VERIFY_TOKEN=           # teks acak buatan sendiri
META_PHONE_NUMBER_ID=
META_WABA_ID=
META_GRAPH_VERSION=v25.0

SAFE_BROWSING_KEY=
GEMINI_API_KEY=              # opsional
BOT_FALLBACK_TOKEN=          # untuk Baileys cadangan
```

## 8. Deploy

Hosting tidak punya terminal dan tidak mendukung symlink. Langkahnya:

1. Di lokal: `npm run build`, `php artisan test`, lalu `php scripts/build-deploy.php` → ZIP berisi kode dan `vendor/` tanpa paket dev.
2. Upload dan extract ke `public_html/sayno` lewat File Manager. Server mewajibkan document root di dalam `public_html`; `sayno/.htaccess` meneruskan semua permintaan ke `public/` dan memblokir file berawalan titik, jadi jangan dihapus.
3. Arahkan document root subdomain `sayno.lokashaka.id` ke `public_html/sayno/public`, set PHP 8.3 khusus vhost ini lewat MultiPHP, lalu jalankan AutoSSL.
4. Buat database MySQL dan user-nya, isi `.env` lewat File Manager (termasuk `SAYNO_SETUP_TOKEN`).
5. Buka `/_setup`: migrasi database, isi data awal, bangun cache. Lalu kosongkan `SAYNO_SETUP_TOKEN`.
6. Cron Jobs cPanel: `* * * * * /usr/local/bin/ea-php83 /home/USER/public_html/sayno/artisan schedule:run >> /dev/null 2>&1` (`/usr/local/bin/php` di server ini PHP 7.4). Jika tidak tersedia, pemanggil luar membuka `/_cron?token=...` tiap menit.

Pembaruan berikutnya cukup mengganti file yang berubah. Migrasi baru dijalankan tanpa token dengan membuat file kosong `storage/app/private/run-migrations`: cron menjalankan `migrate --force` sekali, menghapus file itu, dan mencatat hasilnya di `storage/logs/migrate.log`. Unggah migrasi lebih dulu dan tunggu hasilnya sebelum mengunggah kode yang memakai kolom baru.
7. Di Meta: isi Callback URL `https://sayno.lokashaka.id/webhook/whatsapp` dan Verify Token, lalu subscribe kolom `messages`.
8. Buat token permanen lewat System User, simpan ke `.env`, lalu **Bangun cache** di `/_setup`.
9. Terbitkan aplikasi Meta setelah `/privasi` dan `/syarat-ketentuan` online.

## 9. Batasan yang disengaja

- Tidak ada analisis dinamis APK (sandbox). Analisis hanya statis.
- Tidak ada proses yang berjalan terus di cPanel; semua latar belakang lewat cron.
- Dashboard organisasi untuk lomba hanya memakai satu merek contoh dan login sederhana.
- Baileys bukan jalur utama dan hanya dinyalakan jika akun Meta bermasalah.
