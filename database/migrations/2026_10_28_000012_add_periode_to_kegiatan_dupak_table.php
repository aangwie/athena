<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('kegiatan_dupak', function (Blueprint $table) {
            $table->unsignedSmallInteger('tahun')->default(now()->year)->after('komponen');
            $table->string('semester', 10)->default('ganjil')->after('tahun');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('kegiatan_dupak', function (Blueprint $table) {
            $table->dropColumn(['tahun', 'semester']);
        });
    }
};
