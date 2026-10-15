# DESIGN.md

Panduan identitas visual, UI, dan bahasa untuk **SayNO**.
Berlaku untuk web `sayno.lokashaka.id`, dashboard organisasi, balasan bot WhatsApp, paper, video, dan slide presentasi.

## 1. Karakter merek

**SayNO = berani menolak penipuan.** Nadanya seperti anak muda yang paham teknologi dan sabar menjelaskan ke orang tuanya: tegas soal bahaya, tidak menakut-nakuti, tidak menggurui.

| Kami | Kami bukan |
| --- | --- |
| Tegas dan jelas | Panik atau dramatis |
| Bahasa sehari-hari | Penuh istilah teknis |
| Memberi langkah konkret | Hanya memberi label |
| Jujur soal ketidakpastian | Mengaku 100% pasti |

## 2. Logo

**Konsep:** gelembung percakapan (tempat penipuan datang) yang berisi **tanda larangan** (menolak). Dibaca sekilas: "chat ini, bilang tidak".

| Versi | Pemakaian |
| --- | --- |
| Horizontal (ikon + wordmark) | Header web, paper, slide judul |
| Ikon saja | Foto profil WhatsApp bot, favicon, avatar tim di dasbor lomba |
| Versi gelap (di atas Navy) | Slide gelap, video, header dashboard |
| Satu warna | Cetak hitam putih, stempel |

Aturan:
- **Wordmark:** "Say" berwarna Navy, "NO" berwarna Merah Tolak, huruf Plus Jakarta Sans ExtraBold. "NO" selalu kapital.
- **Ruang kosong** di sekeliling logo minimal setinggi huruf "N".
- **Ukuran minimum:** ikon 24 px, logo horizontal 96 px lebar.
- **Jangan:** memakai hijau WhatsApp di logo, memutar, memberi bayangan, mengganti font, atau memisah warna "Say" dan "NO" menjadi sama.
- **Jangan memakai logo, warna, atau ikon milik WhatsApp, Meta, atau bank** di materi SayNO. Sebut nama mereka dalam teks biasa saja bila perlu.

File sumber logo ada di Figma: [SayNO — Logo](https://www.figma.com/design/5ADmLdWw6lYQW9IJPL1yP0). Ikon tersedia sebagai komponen `Ikon / Warna`, `Ikon / Gelap`, dan `Ikon / Satu warna`; ekspor SVG untuk web dan PNG 640×640 untuk foto profil bot.

## 3. Warna

### Warna merek

| Token | Hex | Pemakaian |
| --- | --- | --- |
| `navy-900` | `#0F1E3D` | Warna utama: teks judul, tombol utama, latar header gelap |
| `navy-700` | `#1F3366` | Hover tombol utama, ikon |
| `red-500` (Merah Tolak) | `#E5383B` | "NO" di logo, tanda larangan, aksen sangat terbatas |
| `paper` | `#F6F7FB` | Latar halaman |
| `white` | `#FFFFFF` | Kartu, panel |
| `ink` | `#121826` | Teks isi |
| `ink-muted` | `#5B6478` | Teks pendukung, keterangan |
| `line` | `#E3E6EE` | Garis, border |

Merah Tolak **bukan** warna tombol dan **bukan** warna status Bahaya, supaya halaman tidak terasa "semua berbahaya".

### Warna status

Status selalu ditampilkan dengan **warna + ikon + kata**. Jangan mengandalkan warna saja.

| Status | Latar | Teks/ikon | Ikon | Kata |
| --- | --- | --- | --- | --- |
| Aman | `#E7F6EE` | `#157A52` | Centang dalam lingkaran | **AMAN** |
| Waspada | `#FFF4DB` | `#8A5A00` | Segitiga tanda seru | **WASPADA** |
| Bahaya | `#FDE8E8` | `#B42318` | Segi delapan tanda silang | **BAHAYA** |

Semua pasangan teks dan latar di atas memenuhi kontras WCAG AA untuk teks normal.

## 4. Tipografi

Font: **Plus Jakarta Sans** (Google Fonts, gratis, buatan desainer Indonesia). Cadangan: `system-ui, sans-serif`.

| Gaya | Ukuran / tinggi baris | Ketebalan |
| --- | --- | --- |
| Display (hero) | 40/48 (mobile 32/40) | 800 |
| H1 | 32/40 | 700 |
| H2 | 24/32 | 700 |
| H3 | 18/26 | 600 |
| Isi | 16/26 | 400 |
| Kecil | 14/20 | 500 |
| Label status | 13/16, kapital, spasi huruf 0.04em | 700 |

Ukuran teks isi minimal **16 px**; target pengguna termasuk orang tua.

## 5. Ruang, sudut, bayangan

- Skala jarak: 4, 8, 12, 16, 24, 32, 48, 64 px.
- Sudut: tombol dan input 10 px, kartu 16 px, badge status 999 px (pil).
- Bayangan hanya untuk kartu yang mengambang: `0 1px 2px rgba(15,30,61,.06), 0 8px 24px rgba(15,30,61,.08)`.
- Lebar konten maksimal 720 px untuk halaman cek, 1120 px untuk dashboard.
- Gutter samping mobile 16 px; tidak ada scroll horizontal.

## 6. Komponen UI

**Tombol utama:** latar `navy-900`, teks putih, tinggi 48 px. Satu tombol utama per layar.
**Tombol sekunder:** latar putih, border `line`, teks `navy-900`.
**Input link:** tinggi 56 px, placeholder "Tempel link yang mencurigakan di sini", tombol "Cek sekarang" menempel di kanan (di mobile, di bawah).
**Area upload APK:** kotak bergaris putus-putus, teks "Seret file APK ke sini atau pilih file", keterangan "Maksimal 100 MB. File tidak dijalankan. File besar dibaca di browser Anda; yang dikirim hanya daftar izin aplikasinya."
**Kartu hasil:**
1. Badge status (warna + ikon + kata) di paling atas.
2. Judul satu kalimat, misalnya "Jangan instal aplikasi ini."
3. Maksimal tiga alasan, masing-masing satu baris.
4. Kotak "Yang sebaiknya Anda lakukan" berisi 1–3 langkah.
5. Tautan "Lihat detail teknis" yang bisa dibuka-tutup (domain, umur domain, izin APK).

**Tabel dashboard organisasi:** kolom Domain, Teknik peniruan, Terdaftar, Pertama terlihat, Status. Baris baru (≤ 7 hari) diberi titik penanda. Angka ringkasan di atas tabel: domain tiruan aktif, APK palsu terdeteksi, laporan pengguna minggu ini.

**Ikon:** satu set garis (Lucide atau Tabler), tebal garis 1.75, ukuran 20/24 px.

## 7. Halaman

| Halaman | Isi utama |
| --- | --- |
| Beranda `/` | Hero "Ragu sama pesan yang masuk? Teruskan dulu ke SayNO.", tombol "Chat SayNO di WhatsApp" (link `wa.me`), form cek link, upload APK, tiga modus terbaru, statistik singkat |
| Hasil `/hasil/{id}` | Kartu hasil, tombol "Bagikan peringatan ini" |
| Modus `/modus` | Kartu per modus: contoh pesan (disamarkan), ciri-ciri, cara menghindari |
| Privasi `/privasi` | Data yang disimpan dan yang tidak, berapa lama, cara menghubungi |
| Dashboard `/org/{brand}` | Ringkasan angka, tabel domain tiruan, tabel APK palsu |

## 8. Bahasa dan nada

- Pakai **"Anda"**, kalimat pendek, satu gagasan per kalimat.
- Mulai dari kesimpulan, baru alasan.
- Ganti istilah teknis: "izin membaca SMS" bukan "permission READ_SMS"; "situs palsu yang meniru" bukan "phishing domain lookalike". Istilah teknis hanya di bagian "detail teknis".
- Jangan menyalahkan pengguna ("kenapa diklik?"). Fokus pada langkah berikutnya.
- Untuk status Aman, tetap rendah hati: "Tidak ditemukan tanda bahaya", bukan "Dijamin aman".
- Emoji hanya satu di awal balasan bot sebagai penanda status: 🟢 Aman, 🟡 Waspada, 🔴 Bahaya.

## 9. Template balasan bot WhatsApp

Panjang balasan maksimal sekitar 6 baris agar terbaca tanpa scroll. Format tebal memakai `*teks*` (format WhatsApp).

**Bahaya (APK)**
```
🔴 *BAHAYA, jangan diinstal.*
File "Undangan_Pernikahan.apk" bisa *membaca SMS Anda*, termasuk kode OTP bank.

Yang sebaiknya Anda lakukan:
1. Hapus file ini.
2. Jangan klik apa pun dari pengirimnya, meski Anda kenal orangnya.
Kalau sudah terinstal, balas *SUDAH* untuk langkah darurat.
```

**Bahaya (link)**
```
🔴 *BAHAYA, jangan dibuka.*
Situs bca-co.id *meniru* situs resmi bank dan baru dibuat 3 hari lalu.

Jangan isi nomor kartu, PIN, atau kode OTP di situs ini.
Situs resmi: bca.co.id
```

**Waspada**
```
🟡 *WASPADA*
Pesan ini mirip modus "paket tertahan". Link di dalamnya memakai penyingkat alamat, jadi tujuan aslinya tersembunyi.

Cek langsung lewat aplikasi atau situs resmi kurir, jangan lewat link ini.
```

**Aman**
```
🟢 *Tidak ditemukan tanda bahaya.*
Situs ini terdaftar sebagai situs resmi Tokopedia.

Tetap jangan bagikan kode OTP ke siapa pun.
```

**Sedang diperiksa**
```
⏳ Sedang memeriksa file Anda. Biasanya kurang dari 1 menit.
```

**Sapaan pertama / pesan tidak dikenali**
```
Halo, saya *SayNO* 👋
Teruskan ke sini *link*, *file APK*, atau *pesan* yang mencurigakan. Saya cek dan kasih tahu aman atau tidak.
```

**Gagal / file terlalu besar**
```
Maaf, file ini lebih dari 20 MB, jadi belum bisa saya periksa.
Kalau file ini dikirim orang yang tidak Anda kenal, lebih aman langsung dihapus.
```

Contoh merek dan domain di atas hanya ilustrasi untuk demo; di produk, nama diambil dari tabel `official_domains`.

## 10. Aksesibilitas

- Kontras minimal WCAG AA; status tidak hanya dibedakan dengan warna.
- Semua kontrol bisa dipakai dengan keyboard dan punya fokus terlihat (cincin 2 px `navy-700`).
- Target sentuh minimal 44×44 px.
- Gambar dan ikon bermakna punya teks alternatif.
- Halaman tetap terbaca saat ukuran teks diperbesar 200%.

## 11. Token untuk Tailwind

```js
// tailwind.config.js (potongan)
theme: {
  extend: {
    colors: {
      navy: { 700: '#1F3366', 900: '#0F1E3D' },
      tolak: '#E5383B',
      paper: '#F6F7FB',
      ink: { DEFAULT: '#121826', muted: '#5B6478' },
      line: '#E3E6EE',
      safe:    { bg: '#E7F6EE', fg: '#157A52' },
      caution: { bg: '#FFF4DB', fg: '#8A5A00' },
      danger:  { bg: '#FDE8E8', fg: '#B42318' },
    },
    fontFamily: { sans: ['"Plus Jakarta Sans"', 'system-ui', 'sans-serif'] },
    borderRadius: { btn: '10px', card: '16px' },
  },
},
```
