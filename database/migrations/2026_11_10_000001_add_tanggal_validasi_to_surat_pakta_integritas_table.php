<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tanggal pengesahan validasi dipakai pada baris "Kota, tanggal" di lembar validasi karya ilmiah.
     */
    public function up(): void
    {
        Schema::table('surat_pakta_integritas', function (Blueprint $table) {
            $table->date('tanggal_validasi')->nullable()->after('nomor_validasi');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('surat_pakta_integritas', function (Blueprint $table) {
            $table->dropColumn('tanggal_validasi');
        });
    }
};
