<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Identitas Kepala LLDIKTI dipakai pada blok "Menyetujui" di halaman cetak DUPAK.
     */
    public function up(): void
    {
        Schema::table('instansi', function (Blueprint $table) {
            $table->string('wilayah_lldikti', 50)->nullable()->after('kota');
            $table->string('nama_pimpinan_lldikti', 150)->nullable()->after('wilayah_lldikti');
            $table->string('nip_pimpinan_lldikti', 30)->nullable()->after('nama_pimpinan_lldikti');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('instansi', function (Blueprint $table) {
            $table->dropColumn(['wilayah_lldikti', 'nama_pimpinan_lldikti', 'nip_pimpinan_lldikti']);
        });
    }
};
