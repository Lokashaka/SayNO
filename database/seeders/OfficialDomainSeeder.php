<?php

namespace Database\Seeders;

use App\Models\OfficialDomain;
use Illuminate\Database\Seeder;

/**
 * Domain resmi yang tidak terkait merek di tabel brands. Domain resmi dianggap aman
 * dan menjadi acuan deteksi domain tiruan.
 *
 * Label domain resmi menjadi acuan peniruan, jadi setiap domain lain yang memuat label
 * itu bisa dianggap tiruan. Untuk layanan besar (Google, Meta, TikTok), daftarkan juga
 * domain turunan yang dipakai sehari-hari (googleusercontent.com, instagr.am). Jangan
 * masukkan merek yang namanya kata umum (Apple, Office, Outlook), karena situs biasa
 * seperti pineapple.com ikut tertandai.
 */
class OfficialDomainSeeder extends Seeder
{
    private const DOMAINS = [
        'bank' => [
            'BTN' => ['btn.co.id'],
            'CIMB Niaga' => ['cimbniaga.co.id'],
            'Bank Danamon' => ['danamon.co.id'],
            'PermataBank' => ['permatabank.com'],
            'OCBC' => ['ocbc.id'],
            'Bank Syariah Indonesia' => ['bankbsi.co.id'],
            'Jenius' => ['jenius.com'],
            'Bank Jago' => ['jago.com'],
            'SeaBank' => ['seabank.co.id'],
            'Bank Indonesia' => ['bi.go.id'],
            'Maybank' => ['maybank.co.id'],
            'Bank Mega' => ['bankmega.com'],
            'Bank BJB' => ['bankbjb.co.id'],
            'Bank Jatim' => ['bankjatim.co.id'],
            'Bank DKI' => ['bankdki.co.id'],
            'Allo Bank' => ['allobank.com'],
            'blu by BCA Digital' => ['blubybcadigital.id'],
        ],
        'ewallet' => [
            'LinkAja' => ['linkaja.id'],
            'Kredivo' => ['kredivo.com', 'kredivo.id'],
            'Akulaku' => ['akulaku.com'],
            'PayPal' => ['paypal.com', 'paypal.me'],
        ],
        'ecommerce' => [
            'Lazada' => ['lazada.co.id'],
            'Blibli' => ['blibli.com'],
            'Bukalapak' => ['bukalapak.com'],
            'Traveloka' => ['traveloka.com'],
            'tiket.com' => ['tiket.com'],
            'Grab' => ['grab.com'],
            'Zalora' => ['zalora.co.id'],
        ],
        'courier' => [
            'JNE' => ['jne.co.id'],
            'J&T Express' => ['jet.co.id'],
            'Pos Indonesia' => ['posindonesia.co.id'],
            'SiCepat' => ['sicepat.com'],
            'AnterAja' => ['anteraja.id'],
            'Ninja Xpress' => ['ninjaxpress.co'],
            'Lion Parcel' => ['lionparcel.com'],
            'SPX Express' => ['spx.co.id'],
            'Paxel' => ['paxel.co'],
        ],
        'gov' => [
            'Direktorat Jenderal Pajak' => ['pajak.go.id'],
            'Kementerian Keuangan' => ['kemenkeu.go.id'],
            'BPJS Kesehatan' => ['bpjs-kesehatan.go.id'],
            'BPJS Ketenagakerjaan' => ['bpjsketenagakerjaan.go.id'],
            'Kementerian Komunikasi dan Digital' => ['komdigi.go.id'],
            'Polri' => ['polri.go.id'],
            'Otoritas Jasa Keuangan' => ['ojk.go.id'],
            'Kementerian Kesehatan' => ['kemkes.go.id'],
            'Kementerian Dalam Negeri' => ['kemendagri.go.id'],
            'BSSN' => ['bssn.go.id'],
            'LAPOR!' => ['lapor.go.id'],
            'Portal Indonesia' => ['indonesia.go.id'],
            'PANDI' => ['pandi.id'],
            'Bea Cukai' => ['beacukai.go.id'],
            'Kementerian Sosial' => ['kemensos.go.id'],
            'Kementerian Ketenagakerjaan' => ['kemnaker.go.id'],
            'Kartu Prakerja' => ['prakerja.go.id'],
            'BKN' => ['bkn.go.id'],
            'Imigrasi' => ['imigrasi.go.id'],
        ],
        'utility' => [
            'PLN' => ['pln.co.id'],
            'Pertamina' => ['pertamina.com', 'mypertamina.id'],
            'KAI' => ['kai.id'],
            'Telkomsel' => ['telkomsel.com'],
            'IndiHome' => ['indihome.co.id'],
            'XL' => ['xl.co.id'],
            'Indosat Ooredoo' => ['ioh.co.id'],
            'Smartfren' => ['smartfren.com'],
            'Pegadaian' => ['pegadaian.co.id'],
            'Taspen' => ['taspen.co.id'],
        ],
        'other' => [
            'WhatsApp' => ['whatsapp.com', 'whatsapp.net'],
            'Google' => [
                'google.com', 'google.co.id', 'gmail.com', 'youtube.com',
                'googleusercontent.com', 'googleapis.com', 'googlevideo.com', 'googleblog.com',
            ],
            'Facebook' => ['facebook.com', 'fb.me', 'facebookmail.com'],
            'Instagram' => ['instagram.com', 'instagr.am', 'cdninstagram.com'],
            'TikTok' => ['tiktok.com', 'tiktokv.com', 'tiktokcdn.com'],
            'Telegram' => ['telegram.org', 'telegram.me', 't.me'],
            'Netflix' => ['netflix.com'],
            'Microsoft' => ['microsoft.com', 'microsoftonline.com'],
            'SayNO' => ['sayno.lokashaka.id'],
        ],
    ];

    public function run(): void
    {
        foreach (self::DOMAINS as $category => $names) {
            foreach ($names as $name => $domains) {
                foreach ($domains as $domain) {
                    OfficialDomain::updateOrCreate(['domain' => $domain], [
                        'name' => $name,
                        'category' => $category,
                    ]);
                }
            }
        }
    }
}
