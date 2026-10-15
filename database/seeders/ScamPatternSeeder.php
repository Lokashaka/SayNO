<?php

namespace Database\Seeders;

use App\Models\ScamPattern;
use Illuminate\Database\Seeder;

/**
 * Pola modus penipuan umum. Regex dicocokkan tanpa membedakan huruf besar-kecil,
 * setelah spasi berlebih dirapikan. Kategori harus ada di lang/id/modus.php.
 */
class ScamPatternSeeder extends Seeder
{
    private const PATTERNS = [
        'hadiah' => [
            '(selamat|anda|nomor).{0,60}(menang|terpilih|mendapatkan|memenangkan).{0,60}(hadiah|undian|saldo|bonus|cashback|mobil|motor|rp ?[\d.]+)',
            '(klaim|ambil|cairkan) (hadiah|bonus|saldo)',
            'pajak hadiah',
        ],
        'paket' => [
            'paket.{0,40}(tertahan|ditahan|gagal (di)?kirim|alamat tidak (lengkap|ditemukan)|bea cukai|dikembalikan)',
            '(cek|lihat|lacak) (resi|paket).{0,40}(klik|unduh|download|link|tautan|apk)',
        ],
        'kerja' => [
            '(kerja|pekerjaan|lowongan|job)\s+(paruh waktu|part ?time|sampingan|dari rumah|online)',
            '(like|follow|subscribe|tugas|misi).{0,60}(dibayar|komisi|gaji|bayaran|rp ?[\d.]+)',
            'gaji\s+(harian|per hari)',
        ],
        'tagihan' => [
            '(tagihan|denda|tunggakan|tilang).{0,60}(segera|hari ini|batas|jatuh tempo|diblokir|dikenakan)',
            'surat tilang',
        ],
        'tarif' => [
            'tarif (transfer|transaksi).{0,60}(berubah|baru|naik|per ?bulan|\/bulan)',
            '(setuju|tidak setuju).{0,40}tarif',
        ],
        'pinjaman' => [
            'pinjaman.{0,60}(tanpa (jaminan|agunan|bi ?checking)|cair (cepat|hari ini|dalam)|bunga (rendah|0|nol))',
            '(dana|pinjaman) (cepat|kilat) cair',
        ],
        'keluarga' => [
            '(ini (aku|saya)|nomor (baru|lama hilang)|ganti nomor).{0,80}(transfer|kirim(in)? uang|pinjam|pulsa|tolong)',
            '\b(mama|mamah|papa|papah|ibu|bapak|ma|pa|bu|pak)\b.{0,60}(kecelakaan|ditahan|di ?rumah sakit|darurat)',
        ],
        'akun' => [
            '(akun|rekening|kartu|whatsapp|nomor) (anda )?(akan )?(diblokir|dinonaktifkan|ditangguhkan|dibekukan)',
            '(kirim|berikan|sebutkan|bacakan|masukkan) (kode )?(otp|kode verifikasi|pin)',
            'verifikasi (data|akun|identitas).{0,40}(klik|link|tautan|segera)',
        ],
        'investasi' => [
            '(profit|keuntungan|untung)\s+\d+\s*%.{0,40}(per ?(hari|minggu|bulan)|harian|mingguan)',
            '(trading|investasi|robot).{0,60}(pasti untung|tanpa risiko|dijamin|modal kecil)',
        ],
    ];

    public function run(): void
    {
        foreach (self::PATTERNS as $category => $patterns) {
            foreach ($patterns as $pattern) {
                ScamPattern::updateOrCreate(
                    ['category' => $category, 'pattern' => $pattern],
                    ['is_regex' => true, 'active' => true],
                );
            }
        }
    }
}
