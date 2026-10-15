<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('brands', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('primary_domain');
            $table->json('official_packages')->nullable();
            $table->json('keywords')->nullable();
            $table->string('access_token_hash', 64)->nullable();
            $table->timestamps();
        });

        Schema::create('official_domains', function (Blueprint $table) {
            $table->id();
            $table->foreignId('brand_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name');
            $table->string('domain')->unique();
            $table->string('category', 32);
            $table->timestamps();
        });

        Schema::create('scam_patterns', function (Blueprint $table) {
            $table->id();
            $table->string('category', 32);
            $table->string('pattern', 500);
            $table->boolean('is_regex')->default(false);
            $table->boolean('active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('scam_patterns');
        Schema::dropIfExists('official_domains');
        Schema::dropIfExists('brands');
    }
};
