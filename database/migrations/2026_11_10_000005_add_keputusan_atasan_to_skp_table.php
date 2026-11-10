<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Keputusan atasan pejabat penilai atas keberatan (blok 7) dan tanggal
     * persetujuan atasan (blok 11) pada halaman cetak SKP.
     */
    public function up(): void
    {
        Schema::table('skp', function (Blueprint $table) {
            $table->text('keputusan_keberatan')->nullable()->after('tanggal_tanggapan');
            $table->date('tanggal_keputusan')->nullable()->after('keputusan_keberatan');
            $table->date('disetujui_atasan_pada')->nullable()->after('tanggal_keputusan');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('skp', function (Blueprint $table) {
            $table->dropColumn(['keputusan_keberatan', 'tanggal_keputusan', 'disetujui_atasan_pada']);
        });
    }
};
