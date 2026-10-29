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
        // 'pelaksanaan_pendidikan' (22 karakter) tidak muat di kolom varchar(20).
        Schema::table('kegiatan_dupak', function (Blueprint $table) {
            $table->string('komponen', 40)->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('kegiatan_dupak', function (Blueprint $table) {
            $table->string('komponen', 20)->change();
        });
    }
};
