<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Jenis kelamin dosen/tendik pada profil, diisi lewat halaman Data Pribadi.
     * Nilainya 'L' (Laki-laki) atau 'P' (Perempuan).
     */
    public function up(): void
    {
        Schema::table('profil_dosen', function (Blueprint $table) {
            $table->string('jenis_kelamin', 20)->nullable()->after('agama');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('profil_dosen', function (Blueprint $table) {
            $table->dropColumn('jenis_kelamin');
        });
    }
};
