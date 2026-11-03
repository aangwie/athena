<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tempat pelaksanaan & materi rapat dicetak pada halaman 2 (daftar hadir).
     */
    public function up(): void
    {
        Schema::table('surat_bap_senat', function (Blueprint $table) {
            $table->string('tempat_pelaksanaan', 150)->nullable()->after('prosentase_penelitian');
            $table->string('materi_rapat', 255)->nullable()->after('tempat_pelaksanaan');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('surat_bap_senat', function (Blueprint $table) {
            $table->dropColumn(['tempat_pelaksanaan', 'materi_rapat']);
        });
    }
};
