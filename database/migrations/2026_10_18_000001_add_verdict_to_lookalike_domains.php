<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('lookalike_domains', function (Blueprint $table) {
            $table->string('verdict', 16)->nullable()->after('status');
            $table->string('verdict_source', 8)->nullable()->after('verdict');
        });
    }

    public function down(): void
    {
        Schema::table('lookalike_domains', function (Blueprint $table) {
            $table->dropColumn(['verdict', 'verdict_source']);
        });
    }
};
