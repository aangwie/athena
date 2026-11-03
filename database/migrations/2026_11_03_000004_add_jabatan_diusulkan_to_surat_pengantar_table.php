<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Jabatan akademik yang diusulkan pada surat pengantar perorangan,
     * dicetak sebagai baris "Diusulkan" di bawah jabatan akademik saat ini.
     */
    public function up(): void
    {
        Schema::table('surat_pengantar', function (Blueprint $table) {
            $table->string('jabatan_diusulkan', 100)->nullable()->after('kota_tujuan');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('surat_pengantar', function (Blueprint $table) {
            $table->dropColumn('jabatan_diusulkan');
        });
    }
};
