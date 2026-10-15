<?php

namespace Database\Seeders;

use App\Models\OfficialDomain;
use Illuminate\Database\Seeder;

/**
 * Domain resmi yang tidak terkait merek di tabel brands. Domain resmi dianggap aman
 * dan menjadi acuan deteksi domain tiruan.
 *
 * Jangan masukkan domain milik layanan besar yang punya banyak domain turunan
 * (misalnya Google), karena domain turunannya akan dianggap tiruan.
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
        ],
        'ewallet' => [
            'LinkAja' => ['linkaja.id'],
        ],
        'ecommerce' => [
            'Lazada' => ['lazada.co.id'],
            'Blibli' => ['blibli.com'],
            'Bukalapak' => ['bukalapak.com'],
            'Traveloka' => ['traveloka.com'],
            'tiket.com' => ['tiket.com'],
            'Grab' => ['grab.com'],
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
        ],
        'utility' => [
            'PLN' => ['pln.co.id'],
            'Pertamina' => ['pertamina.com', 'mypertamina.id'],
            'KAI' => ['kai.id'],
            'Telkomsel' => ['telkomsel.com'],
            'IndiHome' => ['indihome.co.id'],
            'XL' => ['xl.co.id'],
            'Indosat Ooredoo' => ['ioh.co.id'],
        ],
        'other' => [
            'WhatsApp' => ['whatsapp.com'],
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
