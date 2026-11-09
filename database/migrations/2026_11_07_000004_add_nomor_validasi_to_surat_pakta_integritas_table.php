<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Nomor validasi surat dipakai pada judul lembar validasi karya ilmiah.
     */
    public function up(): void
    {
        Schema::table('surat_pakta_integritas', function (Blueprint $table) {
            $table->string('nomor_validasi', 150)->nullable()->after('tanggal_pakta');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('surat_pakta_integritas', function (Blueprint $table) {
            $table->dropColumn('nomor_validasi');
        });
    }
};
