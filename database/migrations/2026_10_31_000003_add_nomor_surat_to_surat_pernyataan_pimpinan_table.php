<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Nomor surat pernyataan, diisi manual oleh admin.
     */
    public function up(): void
    {
        Schema::table('surat_pernyataan_pimpinan', function (Blueprint $table) {
            $table->string('nomor_surat', 100)->nullable()->after('tanggal_surat');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('surat_pernyataan_pimpinan', function (Blueprint $table) {
            $table->dropColumn('nomor_surat');
        });
    }
};
