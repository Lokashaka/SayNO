<?php

namespace Database\Seeders;

use App\Models\Brand;
use App\Models\OfficialDomain;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

/**
 * Merek yang sering ditiru, beserta domain dan nama paket aplikasi resminya.
 *
 * Nama paket dipakai untuk membedakan APK resmi dari APK palsu. Periksa ulang di
 * Play Store sebelum demo; nama paket yang salah membuat aplikasi asli dianggap palsu.
 */
class BrandSeeder extends Seeder
{
    // Merek contoh untuk dashboard organisasi saat lomba.
    public const DEMO_BRAND = 'bca';

    private const BRANDS = [
        [
            'name' => 'BCA',
            'slug' => 'bca',
            'primary_domain' => 'bca.co.id',
            'domains' => ['bca.co.id', 'klikbca.com', 'bcasyariah.co.id'],
            'category' => 'bank',
            'packages' => ['com.bca', 'com.bca.mybca.omni.android'],
            'keywords' => ['bca', 'klikbca', 'mybca'],
        ],
        [
            'name' => 'BRI',
            'slug' => 'bri',
            'primary_domain' => 'bri.co.id',
            'domains' => ['bri.co.id'],
            'category' => 'bank',
            'packages' => ['id.co.bri.brimo'],
            'keywords' => ['bri', 'brimo'],
        ],
        [
            'name' => 'Bank Mandiri',
            'slug' => 'mandiri',
            'primary_domain' => 'bankmandiri.co.id',
            'domains' => ['bankmandiri.co.id'],
            'category' => 'bank',
            'packages' => ['id.bmri.livin'],
            'keywords' => ['mandiri', 'livin'],
        ],
        [
            'name' => 'BNI',
            'slug' => 'bni',
            'primary_domain' => 'bni.co.id',
            'domains' => ['bni.co.id'],
            'category' => 'bank',
            'packages' => ['src.com.bni', 'id.bni.wondr'],
            'keywords' => ['bni', 'wondr'],
        ],
        [
            'name' => 'DANA',
            'slug' => 'dana',
            'primary_domain' => 'dana.id',
            'domains' => ['dana.id'],
            'category' => 'ewallet',
            'packages' => ['id.dana'],
            'keywords' => ['dana'],
        ],
        [
            'name' => 'OVO',
            'slug' => 'ovo',
            'primary_domain' => 'ovo.id',
            'domains' => ['ovo.id'],
            'category' => 'ewallet',
            'packages' => ['ovo.id'],
            'keywords' => ['ovo'],
        ],
        [
            'name' => 'Gojek',
            'slug' => 'gojek',
            'primary_domain' => 'gojek.com',
            'domains' => ['gojek.com', 'gopay.co.id'],
            'category' => 'ewallet',
            'packages' => ['com.gojek.app', 'com.gojek.gopay'],
            'keywords' => ['gojek', 'gopay'],
        ],
        [
            'name' => 'Tokopedia',
            'slug' => 'tokopedia',
            'primary_domain' => 'tokopedia.com',
            'domains' => ['tokopedia.com', 'tokopedia.link'],
            'category' => 'ecommerce',
            'packages' => ['com.tokopedia.tkpd'],
            'keywords' => ['tokopedia'],
        ],
        [
            'name' => 'Shopee',
            'slug' => 'shopee',
            'primary_domain' => 'shopee.co.id',
            'domains' => ['shopee.co.id', 'shopee.com', 'shopeepay.co.id'],
            'category' => 'ecommerce',
            'packages' => ['com.shopee.id'],
            'keywords' => ['shopee', 'shopeepay'],
        ],
    ];

    public function run(): void
    {
        foreach (self::BRANDS as $data) {
            $brand = Brand::updateOrCreate(['slug' => $data['slug']], [
                'name' => $data['name'],
                'primary_domain' => $data['primary_domain'],
                'official_packages' => $data['packages'],
                'keywords' => $data['keywords'],
            ]);

            foreach ($data['domains'] as $domain) {
                OfficialDomain::updateOrCreate(['domain' => $domain], [
                    'brand_id' => $brand->id,
                    'name' => $data['name'],
                    'category' => $data['category'],
                ]);
            }
        }

        $this->ensureDemoToken();
    }

    private function ensureDemoToken(): void
    {
        $brand = Brand::where('slug', self::DEMO_BRAND)->first();
        $token = config('sayno.demo_brand_token');

        if (blank($token) && $brand->access_token_hash !== null) {
            return;
        }

        $generated = blank($token);
        $token = $generated ? Str::random(32) : $token;
        $brand->update(['access_token_hash' => hash('sha256', $token)]);

        if ($generated) {
            $this->command?->warn("Token dashboard /org/{$brand->slug}: {$token}");
            $this->command?->warn('Simpan token ini; yang disimpan di database hanya hash-nya.');
        }
    }
}
