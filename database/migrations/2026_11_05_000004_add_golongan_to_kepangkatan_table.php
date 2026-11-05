<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Golongan ruang pangkat (mis. "IIIa") disimpan terpisah dari nama pangkat
     * supaya bisa dicetak dalam format "IIIa / Penata Muda" pada DUPAK.
     */
    public function up(): void
    {
        Schema::table('kepangkatan', function (Blueprint $table) {
            $table->string('golongan', 20)->nullable()->after('nama');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('kepangkatan', function (Blueprint $table) {
            $table->dropColumn('golongan');
        });
    }
};
