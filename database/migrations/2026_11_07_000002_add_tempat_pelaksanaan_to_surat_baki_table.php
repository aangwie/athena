<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tempat pelaksanaan rapat dicetak pada daftar hadir BAKI (halaman 2).
     */
    public function up(): void
    {
        Schema::table('surat_baki', function (Blueprint $table) {
            $table->string('tempat_pelaksanaan', 150)->nullable()->after('tanggal_surat');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('surat_baki', function (Blueprint $table) {
            $table->dropColumn('tempat_pelaksanaan');
        });
    }
};
