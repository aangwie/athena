<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Keberatan dosen yang dinilai: isi ajuan, tanggal ajuan, dan tanggal persetujuan.
     * Ketiganya dipakai halaman SKP-Dosen dan blok 5 halaman cetak SKP.
     */
    public function up(): void
    {
        Schema::table('skp', function (Blueprint $table) {
            $table->text('keberatan')->nullable()->after('tanggal_penilaian');
            $table->date('tanggal_keberatan')->nullable()->after('keberatan');
            $table->date('disetujui_pada')->nullable()->after('tanggal_keberatan');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('skp', function (Blueprint $table) {
            $table->dropColumn(['keberatan', 'tanggal_keberatan', 'disetujui_pada']);
        });
    }
};
