<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tanggal penilaian diisi pejabat penilai dan dicetak pada blok 9
     * ("DIBUAT TANGGAL, ...") halaman cetak SKP.
     */
    public function up(): void
    {
        Schema::table('skp', function (Blueprint $table) {
            $table->date('tanggal_penilaian')->nullable()->after('nilai_kepemimpinan');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('skp', function (Blueprint $table) {
            $table->dropColumn('tanggal_penilaian');
        });
    }
};
