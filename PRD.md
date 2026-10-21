# PRD — SayNO (DeveloperDay 2026)

Diperbarui 15 Oktober 2026 · MOH DHIYAULHAQ

Dokumen terkait: `CLAUDE.md` (aturan kerja di repo), `ARCHITECTURE.md` (detail teknis), `DESIGN.md` (tampilan dan bahasa).

## Ringkasan produk

**SayNO** adalah sistem peringatan dini penipuan digital. Bagi masyarakat, SayNO hadir sebagai bot WhatsApp gratis: pengguna meneruskan link, file APK, atau pesan mencurigakan, lalu dalam hitungan detik mendapat status Aman, Waspada, atau Bahaya dengan penjelasan berbahasa awam, tanpa instal aplikasi apa pun.

Setiap pemeriksaan menjadi data ancaman terbaru. Bagi organisasi seperti bank, e-wallet, dan pemilik merek, data itu diolah menjadi peringatan saat domain atau APK yang meniru merek mereka mulai beredar. Seluruh produk berjalan di `https://sayno.lokashaka.id`.

| Aspek | Keputusan |
| --- | --- |
| Lomba | .id DeveloperDay 2026, Road to APICTA 2026 |
| Kategori | Business Services |
| Subkategori | Security Solutions |
| Alternatif subkategori | Inclusions & Community Services, Community Services |
| Domain | sayno.lokashaka.id |
| Batas submit paper dan video | 25 Oktober 2026 |
| Final | 10–11 November 2026, SMESCO Indonesia (menunggu konfirmasi) |
| Tim | Maksimal 3 orang |

Bobot penilaian Security Solutions (format APICTA): Uniqueness 25%, Market Potential 20%, Functionalities & Features 25%, Quality & Application of Technology 30%. Karena kualitas teknologi bobotnya paling besar, demo harus stabil dan analisisnya bisa dijelaskan.

## Latar belakang dan masalah

Penipuan digital di Indonesia paling banyak masuk lewat WhatsApp, dan korbannya sering tidak bisa membedakan pesan asli dari palsu. Tiga modus utama yang disasar produk ini:

- **APK palsu** berkedok undangan pernikahan, surat tilang, resi paket, atau tagihan. Setelah diinstal, aplikasi membaca SMS dan mencuri kode OTP bank.
- **Link palsu** yang meniru domain resmi, misalnya bank atau situs .go.id, dengan ejaan yang sedikit berbeda.
- **Pesan bujukan** seperti hadiah undian, paket tertahan, atau tawaran kerja paruh waktu.

Masalah intinya: pengguna awam, terutama orang tua, tidak punya cara cepat untuk bertanya "ini aman atau tidak?" di tempat penipuan itu datang, yaitu di WhatsApp.

Catatan: angka kerugian dan jumlah kasus untuk paper perlu dicari dari sumber resmi (misalnya OJK, Komdigi, atau BSSN) sebelum dikutip.

## Kenapa bukan sekadar edukasi

Edukasi dari influencer dan kampanye publik sudah ramai, tetapi korban tetap berjatuhan. SayNO tidak bersaing dengan edukasi; ia mengisi celah yang tidak bisa dijangkau edukasi.

| Aspek | Edukasi dan kampanye | SayNO |
| --- | --- | --- |
| Yang diberikan | Pola modus secara umum | Jawaban untuk file atau link spesifik yang baru diterima |
| Waktu | Sebelum kejadian, sering terlupa saat dibutuhkan | Tepat di detik pengguna ragu |
| Modus baru | Tertinggal sampai ada konten baru | Terdeteksi dari ciri teknisnya, bukan dari nama kedoknya |
| Pengirim yang dikenal (akun dibajak) | Sulit dicurigai | Tetap diperiksa apa adanya |
| Data yang dihasilkan | Tidak ada | Data ancaman real time untuk organisasi |

Influencer justru bisa menjadi saluran distribusi: ajakan "kalau ragu, teruskan dulu ke SayNO" mengubah edukasi menjadi tindakan.

## Model bisnis

Prinsipnya gratis untuk masyarakat, berbayar untuk organisasi. Bot publik berfungsi sebagai sensor yang mengumpulkan data penipuan terbaru; organisasi membayar untuk peringatan dan akses ke data tersebut. Model ini sejalan dengan kategori Business Services, yaitu solusi untuk organisasi.

| Sumber pendapatan | Pembeli | Layanan | Skema |
| --- | --- | --- | --- |
| Pemantauan merek | Bank, e-wallet, e-commerce, BUMN | Peringatan saat muncul domain tiruan atau APK palsu yang memakai merek mereka, supaya cepat di-takedown | Langganan bulanan |
| API cek link dan APK | Fintech, layanan pelanggan, aplikasi lain | Fitur cek keamanan yang ditanam di produk mereka | Bayar per volume pemeriksaan |
| Data ancaman | Registrar domain, tim keamanan siber, regulator | Laporan domain dan APK penipuan yang sedang beredar di Indonesia | Langganan atau kontrak |
| Kemitraan dan CSR | Bank, operator seluler, pemerintah | Pendanaan program literasi digital dengan SayNO sebagai alatnya | Sponsor program |

Untuk prototype, sisi organisasi ditunjukkan lewat dashboard organisasi sederhana dengan satu merek contoh. Harga paket dan proyeksi pendapatan disusun di paper bagian Business Model.

## Tujuan dan metrik keberhasilan

Tujuan MVP: lolos 18 besar dengan demo yang mendeteksi tiga jenis ancaman secara andal di depan juri.

| Metrik | Target MVP |
| --- | --- |
| Waktu balasan cek link dan teks | Di bawah 10 detik (paling lambat sekitar 1 menit jika lewat antrean cron) |
| Waktu balasan cek APK | Di bawah 2 menit |
| Deteksi APK berbahaya pada set uji internal | Minimal 9 dari 10 sampel |
| Deteksi domain tiruan pada set uji internal | Minimal 9 dari 10 sampel |
| Salah tandai link resmi sebagai berbahaya | Maksimal 1 dari 20 link resmi |
| Uptime bot selama masa penjurian | Bot menyala dan membalas tiap kali dites |

Set uji berisi sampel yang dikumpulkan tim sendiri. APK berbahaya hanya dianalisis secara statis dan tidak pernah dijalankan.

## Target pengguna dan persona

Pengguna utama adalah orang yang menerima pesan mencurigakan; pengguna kedua adalah anggota keluarga yang biasa ditanya "ini aman tidak?". Pelanggan berbayarnya adalah organisasi yang mereknya sering ditiru penipu.

| Persona | Situasi | Kebutuhan |
| --- | --- | --- |
| Bu Sri, 58 tahun, ibu rumah tangga | Menerima file "Undangan_Pernikahan.apk" dari nomor tak dikenal | Jawaban jelas "jangan dibuka", tanpa istilah teknis |
| Dimas, 24 tahun, karyawan | Dapat link "cek resi paket" dan ragu | Cek cepat tanpa pindah aplikasi |
| Rina, 30 tahun, anak yang jadi tempat bertanya keluarga | Sering diteruskan pesan aneh oleh orang tua | Alat yang bisa dibagikan ke orang tuanya |
| Admin grup komunitas atau RT | Mau mengedukasi warga | Halaman edukasi modus terbaru di web |
| Tim keamanan bank atau e-wallet (pelanggan berbayar) | Nasabah tertipu situs dan APK yang meniru mereknya | Peringatan dini domain dan APK tiruan agar cepat di-takedown |

## Ruang lingkup MVP

MVP fokus pada tiga pemeriksaan lewat dua pintu masuk, bot WhatsApp dan web .id, ditambah dashboard organisasi sederhana untuk menunjukkan sisi bisnisnya.

**Masuk lingkup MVP**

- Bot WhatsApp lewat WhatsApp Cloud API resmi yang menerima teks, link, dan file APK
- Cek link: domain tiruan, umur domain, dan daftar hitam
- Cek APK: izin berbahaya dan identitas aplikasi
- Cek teks: pola modus penipuan umum
- Balasan berisi status (Aman, Waspada, Bahaya), alasan, dan saran tindakan
- Web di sayno.lokashaka.id: form cek link, upload APK, halaman edukasi modus, statistik ringkas
- Dashboard organisasi: daftar domain tiruan dan APK palsu yang terdeteksi untuk satu merek contoh
- Panel admin sederhana untuk melihat riwayat cek dan mengelola daftar domain resmi

**Di luar lingkup MVP**

- Menjalankan APK di sandbox (analisis dinamis)
- Aplikasi mobile terpisah
- Grup WhatsApp dan pemindaian otomatis pesan grup
- Pengiriman pesan duluan atau broadcast ke pengguna
- Integrasi pelaporan ke instansi resmi (masuk roadmap)
- Sistem langganan, akun, dan pembayaran untuk organisasi (masuk roadmap)

## Alur pengguna

Pengguna hanya melakukan satu hal: meneruskan pesan mencurigakan ke nomor bot.

**Alur bot WhatsApp**

1. Pengguna menyimpan nomor bot atau membuka link `wa.me` dari web.
2. Pengguna meneruskan pesan, link, atau file APK ke bot.
3. Bot langsung membalas "Sedang diperiksa…" untuk APK atau file besar.
4. Bot mengirim hasil: status, alasan dalam 2–3 poin, dan saran tindakan.
5. Bot menawarkan tautan ke halaman edukasi modus terkait di web.

**Alur web**

1. Pengguna membuka halaman utama dan memilih Cek Link atau Upload APK.
2. Sistem menampilkan hasil yang sama dengan bot, plus detail teknis yang bisa dibuka.
3. Pengguna bisa membaca daftar modus terbaru dan cara menghindarinya.

**Contoh balasan bot** (template lengkap ada di `DESIGN.md`)

> 🔴 *BAHAYA, jangan diinstal.* File "Undangan_Pernikahan.apk" bisa membaca SMS Anda, termasuk kode OTP bank. Hapus file ini dan jangan klik apa pun dari pengirimnya.

## Kebutuhan fungsional

Prioritas P0 wajib ada saat submit 25 Oktober; P1 dikerjakan sebelum final; P2 masuk roadmap.

| ID | Kebutuhan | Prioritas |
| --- | --- | --- |
| F1 | Webhook Laravel menerima teks, link, dan dokumen dari WhatsApp Cloud API dan memverifikasi tanda tangan Meta | P0 |
| F2 | Laravel mendeteksi jenis isi: link, file APK, atau teks biasa | P0 |
| F3 | Cek link: bandingkan domain dengan daftar domain resmi (kemiripan ejaan dan karakter tiruan seperti 0/o, 1/l) | P0 |
| F4 | Cek link: umur domain lewat RDAP dan pencocokan daftar hitam (Google Safe Browsing) | P0 |
| F5 | Cek APK: unduh file lewat API Meta, buka sebagai ZIP, baca AndroidManifest, daftar izin berbahaya (READ_SMS, RECEIVE_SMS, BIND_ACCESSIBILITY_SERVICE, dan lainnya) | P0 |
| F6 | Cek teks: cocokkan dengan pola modus penipuan umum | P0 |
| F7 | Hitung skor risiko dan kirim balasan berbahasa awam lewat API Meta | P0 |
| F8 | Web: form cek link dan upload APK dengan hasil yang sama seperti bot | P0 |
| F9 | Simpan riwayat cek (hash file, domain, skor, waktu) tanpa menyimpan isi pesan pribadi | P0 |
| F16 | Dashboard organisasi: untuk satu merek contoh, tampilkan domain tiruan yang aktif (dari variasi ejaan yang dibuat sistem dan dari data pemeriksaan pengguna) serta APK palsu yang memakai nama merek | P0 |
| F10 | Halaman edukasi modus penipuan | P1 |
| F11 | Panel admin: kelola daftar domain resmi dan pola modus, lihat statistik | P1 |
| F12 | Penjelasan tambahan berbasis AI (Gemini API) untuk teks yang ambigu | P1 |
| F13 | Cache hasil: link atau APK yang sama tidak dianalisis ulang | P1 |
| F15 | Verifikasi bisnis Meta agar nama SayNO tampil di daftar chat semua pengguna | P1 |
| F17 | Peringatan email ke organisasi saat domain tiruan baru terdeteksi | P1 |
| F14 | Tombol "Laporkan" untuk menambah sampel ke database ancaman | P2 |
| F18 | Ekstensi browser: cek link di browser dan WhatsApp Web sebelum dibuka | P2 |
| F19 | Aplikasi Android dengan tombol mengambang (seperti aplikasi auto clicker): cek link atau file di layar hanya saat tombol diketuk, tanpa meneruskan. Di iOS lewat menu Share dan Shortcut, karena iOS tidak mengizinkan tombol mengambang | P2 |
| F20 | AI support khusus partner berbayar: analisis pesan dan APK yang memakai merek mereka, ringkasan serangan bulanan, draf permintaan takedown dan peringatan nasabah | P2 |

## Kebutuhan non-fungsional

Semua kebutuhan ini harus bisa dipenuhi di shared hosting cPanel (PHP saja), tanpa server tambahan.

| Aspek | Kebutuhan |
| --- | --- |
| Domain | Seluruh produk berjalan di subdomain `sayno.lokashaka.id` di cPanel |
| HTTPS | SSL aktif (AutoSSL cPanel); wajib untuk webhook Meta |
| Keamanan webhook | Setiap request diverifikasi dengan tanda tangan X-Hub-Signature-256 dan App Secret; token akses dan App Secret disimpan di .env, tidak di-commit |
| Keamanan file | APK tidak pernah dijalankan; disimpan di luar folder publik dan dihapus setelah dianalisis |
| Privasi | Hanya menyimpan nomor yang di-hash, hash file, domain, dan skor; isi pesan tidak disimpan; kebijakan privasi tersedia di `/privasi` |
| Batas eksekusi | Webhook langsung menjawab 200 OK; analisis berjalan lewat antrean database + cron tiap menit, lalu balasan dikirim lewat API Meta |
| Ukuran file | Maksimal 20 MB per APK |
| Nomor bot | Nomor khusus yang tidak terpasang di aplikasi WhatsApp; satu nomor lain tetap di aplikasi WhatsApp untuk uji |
| Perilaku bot | Hanya membalas di dalam jendela layanan 24 jam; tidak mengirim pesan promosi |
| Aksesibilitas | Kontras WCAG AA, status tidak hanya dibedakan warna, teks isi minimal 16 px |
| Biaya | Rp 100.000 pendaftaran + domain; WhatsApp gratis untuk 1.000 pesan layanan per nomor per bulan; API lain memakai paket gratis |

## Arsitektur sistem

Semua logika ada di Laravel pada cPanel; WhatsApp Cloud API resmi dari Meta mengantar pesan lewat webhook, jadi tidak perlu server tambahan.

```
Pengguna WhatsApp ⇄ WhatsApp Cloud API (server Meta)
                          │ webhook HTTPS
                          ▼
┌──────────── cPanel · Laravel · sayno.lokashaka.id ────────────┐
│ Webhook Laravel ─► Antrean (database + cron)                  │
│        ─► Analisis (link, APK, teks) ─► API luar (gratis):    │
│                                         Safe Browsing, RDAP,  │
│                                         Gemini                │
│        ─► Skor risiko dan balasan ─► kirim lewat API Meta     │
│ Database: riwayat cek, domain resmi, merek, domain tiruan     │
│ Web publik (cek manual, edukasi) dan dashboard organisasi     │
└───────────────────────────────────────────────────────────────┘
Pengguna web ──► Web publik
```

Meta mengirim setiap pesan masuk ke webhook Laravel lewat HTTPS. Laravel memverifikasi tanda tangan Meta, menjawab 200 OK, lalu menjalankan analisis di antrean. Balasan dikirim ke pengguna lewat API Meta, masih di dalam jendela 24 jam. Dashboard organisasi membaca data yang sama dari database. Detail lengkap ada di `ARCHITECTURE.md`.

## Logika skor risiko

Skor 0–100 dihitung dari sinyal yang ditemukan, lalu dipetakan ke tiga status. Bobot di bawah adalah titik awal dan disetel ulang setelah diuji pada set uji (nilai final ada di `config/sayno.php`).

| Sinyal | Tambahan skor |
| --- | --- |
| Domain ada di daftar hitam Google Safe Browsing | +100 |
| APK meminta izin baca atau terima SMS | +60 |
| Domain mirip domain resmi tapi bukan domain resmi | +50 |
| APK meminta Accessibility Service | +40 |
| APK memakai nama merek tapi bukan aplikasi resmi merek itu | +40 |
| Domain berumur kurang dari 30 hari | +25 |
| APK dikirim dengan nama dokumen (undangan, surat, resi, tagihan) | +20 |
| Teks cocok dengan pola modus penipuan | +20 per pola, maksimal +40 |
| Link memakai penyingkat URL | +10 |
| Domain persis ada di daftar domain resmi | Skor jadi 0 |

| Skor | Status | Isi balasan |
| --- | --- | --- |
| 0–29 | Aman | Tidak ditemukan tanda bahaya, tetap waspada |
| 30–59 | Waspada | Ada tanda mencurigakan, jangan isi data pribadi |
| 60–100 | Bahaya | Jangan buka atau instal, hapus, blokir pengirim |

Setiap balasan menyebut alasan utamanya, supaya juri dan pengguna bisa melihat hasilnya bukan tebakan.

## Risiko dan mitigasi

Risiko terbesar adalah akun Meta belum terverifikasi saat penjurian, sehingga bot hanya bisa membalas nomor uji; verifikasi diajukan sejak awal dan web tetap bisa dipakai.

| Risiko | Dampak | Mitigasi |
| --- | --- | --- |
| Verifikasi bisnis Meta lambat | Juri tidak bisa mencoba bot | Ajukan verifikasi sekarang; sementara daftarkan nomor juri sebagai penerima uji; web tetap bisa dipakai |
| Aplikasi Meta belum diterbitkan | Webhook hanya menerima pesan uji dari dasbor | Terbitkan aplikasi setelah webhook dan halaman kebijakan privasi siap |
| Token akses sementara kedaluwarsa | Bot berhenti membalas | Pakai token permanen dari System User |
| Batas waktu eksekusi PHP di cPanel | Analisis APK besar gagal | Webhook langsung 200 OK, analisis lewat antrean + cron |
| Salah tandai link resmi | Kepercayaan juri turun | Daftar domain resmi diisi sejak awal; uji 20 link resmi sebelum submit |
| Parser APK PHP gagal membaca manifest | Cek APK tidak jalan | Uji library di hosting pada hari pertama; cadangan: analisis di server lain |
| Subdomain atau SSL belum aktif | Webhook Meta tidak bisa dipasang, karya tidak sah | Pasang `sayno.lokashaka.id` dan AutoSSL di hari pertama |
| Juri meragukan model bisnis | Nilai Market Potential turun | Dashboard organisasi di demo dan paket harga yang jelas di paper |

## Jadwal dan pembagian tim

Semua bagian ditargetkan selesai 24 Oktober, supaya 25 Oktober hanya untuk submit dan cadangan kalau ada masalah.

| Pekerjaan | Tanggal |
| --- | --- |
| Persiapan dan daftar | 15–16 Oktober |
| Webhook dan akun Meta | 15–18 Oktober |
| Analisis dan skor | 17–21 Oktober |
| Paper dan video | 18–24 Oktober |
| Web .id dan dashboard | 19–22 Oktober |
| Uji dan perbaikan | 22–23 Oktober |
| **Submit karya** | **25 Oktober** |

Setelah submit, waktu sampai final 10–11 November dipakai untuk fitur P1, latihan presentasi, dan menjaga bot tetap menyala selama seleksi paper.

| Anggota | Tanggung jawab utama |
| --- | --- |
| Orang 1, backend dan analisis | Cek link, cek APK, cek teks, skor risiko, deteksi variasi domain merek, antrean |
| Orang 2, integrasi dan web | Akun Meta dan webhook WhatsApp Cloud API, web, dashboard organisasi, panel admin |
| Orang 3, paper dan presentasi | Data kasus penipuan, model bisnis dan harga paket, paper 13 bagian bahasa Inggris, video demo, desain UI, set uji |

## Keputusan yang sudah diambil

- [x] Nama produk: **SayNO**
- [x] Domain: **sayno.lokashaka.id**
- [x] Kategori: Business Services, subkategori Security Solutions (masih bisa diubah sebelum karya dikirim)
- [x] Jalur WhatsApp utama: WhatsApp Cloud API resmi, tanpa jalur tidak resmi
- [x] Logo: [SayNO — Logo di Figma](https://www.figma.com/design/5ADmLdWw6lYQW9IJPL1yP0)

## Pertanyaan terbuka

- [ ] Apakah panitia memberi domain .id gratis atau kredit cloud, dan kapan diberikan?
- [ ] API dan dataset apa yang disediakan panitia?
- [ ] Versi PHP di cPanel dan apakah ekstensi zip, curl, mbstring aktif
- [ ] Apakah cPanel mendukung cron job tiap menit?
- [ ] Apakah library parser APK untuk PHP berjalan di hosting ini?
- [ ] Kapan verifikasi bisnis Meta selesai, dan nomor mana yang didaftarkan sebagai nomor bot?
- [ ] Merek apa yang dipakai sebagai contoh di dashboard organisasi?
- [ ] Berapa harga paket organisasi (perlu riset harga layanan pemantauan merek sejenis)?
- [ ] Siapa ketua tim dan siapa memegang tiap bagian?
