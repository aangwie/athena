<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('profil_dosen', function (Blueprint $table) {
            // Bidang ilmu diisi manual oleh dosen (bukan data master prodi).
            $table->string('bidang_ilmu', 100)->nullable()->after('nuptk');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('profil_dosen', function (Blueprint $table) {
            $table->dropColumn('bidang_ilmu');
        });
    }
};
