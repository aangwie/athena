<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Karya ilmiah dosen yang dipilih admin untuk dicetak pada pakta integritas.
     * Kosong = cetak semua karya ilmiah dosen.
     */
    public function up(): void
    {
        Schema::table('surat_pakta_integritas', function (Blueprint $table) {
            $table->json('karya_tulis_ids')->nullable()->after('tanggal_pakta');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('surat_pakta_integritas', function (Blueprint $table) {
            $table->dropColumn('karya_tulis_ids');
        });
    }
};
