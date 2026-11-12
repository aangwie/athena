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
        Schema::table('riwayat_pendidikan', function (Blueprint $table) {
            $table->string('ijazah_transkrip_path')->nullable();
            $table->string('akreditasi_prodi_path')->nullable();
            $table->string('disertasi_tesis_path')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('riwayat_pendidikan', function (Blueprint $table) {
            $table->dropColumn(['ijazah_transkrip_path', 'akreditasi_prodi_path', 'disertasi_tesis_path']);
        });
    }
};