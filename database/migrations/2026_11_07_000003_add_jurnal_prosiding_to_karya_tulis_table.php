<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Bidang tambahan karya ilmiah: jurnal (nama & alamat web) dan prosiding
     * (nama, ISSN, URL, URL plagiasi). Hanya diisi sesuai jenis karya yang dipilih.
     */
    public function up(): void
    {
        Schema::table('karya_tulis', function (Blueprint $table) {
            $table->string('nama_jurnal')->nullable()->after('link_bukti');
            $table->string('url_jurnal', 2048)->nullable()->after('nama_jurnal');
            $table->string('nama_prosiding')->nullable()->after('url_jurnal');
            $table->string('issn', 50)->nullable()->after('nama_prosiding');
            $table->string('url_prosiding', 2048)->nullable()->after('issn');
            $table->string('url_plagiasi', 2048)->nullable()->after('url_prosiding');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('karya_tulis', function (Blueprint $table) {
            $table->dropColumn([
                'nama_jurnal',
                'url_jurnal',
                'nama_prosiding',
                'issn',
                'url_prosiding',
                'url_plagiasi',
            ]);
        });
    }
};
