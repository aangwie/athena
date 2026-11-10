<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tanggapan pejabat penilai atas keberatan dosen + tanggal tanggapan.
     * Dipakai blok 6 halaman cetak SKP.
     */
    public function up(): void
    {
        Schema::table('skp', function (Blueprint $table) {
            $table->text('tanggapan_keberatan')->nullable()->after('disetujui_pada');
            $table->date('tanggal_tanggapan')->nullable()->after('tanggapan_keberatan');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('skp', function (Blueprint $table) {
            $table->dropColumn(['tanggapan_keberatan', 'tanggal_tanggapan']);
        });
    }
};
