<?php

use App\Models\LookalikeDomain;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Hasil tinjauan manual 18 Oktober 2026 atas 133 domain tiruan pertama: server DNS,
 * tujuan pengalihan, dan judul halaman. Domain yang belum jelas dibiarkan tanpa penilaian.
 */
return new class extends Migration
{
    private const REVIEWED = [
        LookalikeDomain::SUSPICIOUS => [
            // Situs aktif dengan judul atau isi yang meniru layanan resmi.
            'bca-care.com', 'cstokopedia.com', 'shopee.biz.id', 'klikdana.com', 'my-shopee.com',
            // Mengalihkan ke situs asli (pola menyamarkan phishing), ke situs judi, atau sudah ditangguhkan hosting.
            'login-shopee.com', 'gojekk.com', 'bankmandiri-co.id',
            // Nama "cs-" dan "-official" baru dibuat, dengan situs yang menolak atau sedang disiapkan.
            'csbca.com', 'cs-bri.com', 'csdana.com', 'dana-official.com',
            'tokoopedia.com', 'tokopediia.com', 'okopedia.com', 'tokopeedia.com',
        ],
        LookalikeDomain::PARKED => [
            'klik-bca.com', 'bca-co.com', 'bca.online', 'bca.info',
            'bni-co.id', 'bni.biz.id', 'bni.xyz',
            'bri-co.id', 'bri.biz.id', 'bri.net', 'bri.web.id', 'mybri.com', 'promobri.com',
            'bankmandiri.co', 'bankmandiri.com', 'bankmandiri.info', 'bankmandiri.net',
            'dana.co.id', 'dana.my.id', 'dana.net', 'mydana.com',
            'gjek.com', 'goek.com', 'gojek.co', 'gojek.info', 'gojek.net', 'gojek.xyz', 'gojk.com', 'ojek.com',
            'myovo.com', 'ovo.xyz',
            'myshopee.com', 'shope.co.id', 'shopee.info', 'shopee.net', 'shopee.online', 'shopee.site', 'shopee.xyz',
            't0kopedia.com', 'tkopedia.com', 'tokoepdia.com', 'tokopeda.com', 'tokopedi.com', 'tokopedia-care.com',
            'tokopedia.co', 'tokopedia.xyz', 'tokopediaa.com', 'tokoppedia.com', 'tokpedia.com', 'toopedia.com',
        ],
        LookalikeDomain::UNRELATED => [
            // Dialihkan ke situs resmi, memakai DNS merek, atau dijaga registrar perlindungan merek.
            'bca.id', 'bca.com', 'klikbca.co.id', 'my-bca.com', 'mybca.com', 'bni.id',
            'ovo.co.id', 'shopee.co', 'shopee.id', 'tokopedia.id', 'tokopedia.net', 'tokopedia.web.id',
            // Usaha lain yang kebetulan bernama sama.
            'bca.co', 'klikbca.id', 'bni.com', 'bni.co', 'bni.net', 'bni.info', 'mybni.com', 'bni-login.com', 'klikbni.com',
            'bri.com', 'bri-co.com', 'bri.my.id', 'my-bri.com',
            'dana.com', 'dana.info', 'dana.xyz', 'klikdana.id',
            'gojeek.com', 'goje.com',
            'ovo.com', 'ovo.net', 'ovo.site', 'ovo.info', 'ovo.my.id', 'loginovo.com',
        ],
    ];

    public function up(): void
    {
        foreach (self::REVIEWED as $verdict => $domains) {
            DB::table('lookalike_domains')
                ->whereIn('domain', $domains)
                ->update(['verdict' => $verdict, 'verdict_source' => LookalikeDomain::SOURCE_MANUAL]);
        }
    }

    public function down(): void
    {
        DB::table('lookalike_domains')
            ->where('verdict_source', LookalikeDomain::SOURCE_MANUAL)
            ->update(['verdict' => null, 'verdict_source' => null]);
    }
};
