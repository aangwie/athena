<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Nomor Surat Keputusan yang diisi admin saat menyetujui ajuan DUPAK.
     */
    public function up(): void
    {
        Schema::table('pengajuan_dupak', function (Blueprint $table) {
            $table->string('nomor_sk', 150)->nullable()->after('catatan');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pengajuan_dupak', function (Blueprint $table) {
            $table->dropColumn('nomor_sk');
        });
    }
};
