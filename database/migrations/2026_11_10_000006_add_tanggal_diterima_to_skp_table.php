<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tanggal SKP disimpan admin, tercetak pada blok 10
     * ("DITERIMA TANGGAL, ... DOSEN YANG DINILAI") halaman cetak SKP.
     */
    public function up(): void
    {
        Schema::table('skp', function (Blueprint $table) {
            $table->date('tanggal_diterima')->nullable()->after('disetujui_atasan_pada');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('skp', function (Blueprint $table) {
            $table->dropColumn('tanggal_diterima');
        });
    }
};
