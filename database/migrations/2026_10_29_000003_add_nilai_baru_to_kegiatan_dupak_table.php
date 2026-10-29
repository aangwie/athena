<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Angka kredit hasil penilaian TIM PAK untuk tiap butir kegiatan.
     */
    public function up(): void
    {
        Schema::table('kegiatan_dupak', function (Blueprint $table) {
            $table->decimal('nilai_baru', 6, 2)->nullable()->after('link_bukti');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('kegiatan_dupak', function (Blueprint $table) {
            $table->dropColumn('nilai_baru');
        });
    }
};
