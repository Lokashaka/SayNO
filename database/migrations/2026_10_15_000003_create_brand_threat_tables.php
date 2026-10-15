<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lookalike_domains', function (Blueprint $table) {
            $table->id();
            $table->foreignId('brand_id')->constrained()->cascadeOnDelete();
            $table->string('domain');
            $table->string('technique', 32);
            $table->string('source', 16);
            $table->date('registered_at')->nullable();
            $table->timestamp('first_seen_at');
            $table->timestamp('last_checked_at')->nullable();
            $table->string('status', 8)->default('active');
            $table->unsignedInteger('times_reported')->default(0);
            $table->timestamps();

            $table->unique(['brand_id', 'domain']);
        });

        Schema::create('fake_apks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('brand_id')->constrained()->cascadeOnDelete();
            $table->string('file_sha256', 64);
            $table->string('package_name')->nullable();
            $table->string('app_label')->nullable();
            $table->json('permissions')->nullable();
            $table->timestamp('first_seen_at');
            $table->timestamp('last_seen_at');
            $table->unsignedInteger('times_seen')->default(1);
            $table->timestamps();

            $table->unique(['brand_id', 'file_sha256']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fake_apks');
        Schema::dropIfExists('lookalike_domains');
    }
};
