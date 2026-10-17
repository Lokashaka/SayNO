<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Hanya penanda pesan masuk (agar kiriman ulang Meta tidak diproses dua kali).
        // Tidak ada isi pesan, nama, atau nomor asli.
        Schema::create('inbound_messages', function (Blueprint $table) {
            $table->id();
            $table->string('wamid')->unique();
            $table->string('channel', 8);
            $table->string('sender_hash', 64)->index();
            $table->string('type', 16);
            $table->foreignUlid('check_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamp('received_at');
            $table->timestamp('processed_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inbound_messages');
    }
};
