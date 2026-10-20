<?php

use App\Models\OfficialDomain;
use Database\Seeders\OfficialDomainSeeder;
use Illuminate\Database\Migrations\Migration;

/**
 * Domain resmi tambahan 20 Oktober 2026 (Google, Meta, TikTok, bank daerah, Bea Cukai, dan
 * lainnya) untuk server yang sudah berjalan. Seeder aman dijalankan ulang. Database baru
 * dilewati karena domain resmi diisi oleh db:seed.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (OfficialDomain::query()->doesntExist()) {
            return;
        }

        (new OfficialDomainSeeder)->run();
    }

    public function down(): void
    {
        // Domain resmi tidak dihapus; daftar lama tetap bagian dari daftar baru.
    }
};
