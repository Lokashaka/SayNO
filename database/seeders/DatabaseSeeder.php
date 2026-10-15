<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed: domain resmi, pola modus, dan merek contoh. Aman dijalankan ulang.
     */
    public function run(): void
    {
        $this->call([
            BrandSeeder::class,
            OfficialDomainSeeder::class,
            ScamPatternSeeder::class,
        ]);
    }
}
