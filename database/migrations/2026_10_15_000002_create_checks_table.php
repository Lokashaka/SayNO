<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Tidak ada kolom isi pesan atau nomor asli. Lihat aturan privasi di CLAUDE.md.
        Schema::create('checks', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->string('source', 8);
            $table->string('sender_hash', 64)->nullable()->index();
            $table->string('input_type', 8);
            $table->string('domain')->nullable()->index();
            $table->string('file_sha256', 64)->nullable()->index();
            $table->unsignedTinyInteger('score');
            $table->string('status', 8)->index();
            $table->timestamps();
        });

        Schema::create('signals', function (Blueprint $table) {
            $table->id();
            $table->foreignUlid('check_id')->constrained()->cascadeOnDelete();
            $table->string('code', 48);
            $table->unsignedSmallInteger('weight');
            $table->json('meta')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('signals');
        Schema::dropIfExists('checks');
    }
};
