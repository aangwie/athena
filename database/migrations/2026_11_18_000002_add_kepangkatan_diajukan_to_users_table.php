<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Pangkat/golongan yang akan diajukan (target kenaikan) pada halaman
     * Status Jabatan Akademik. Diambil dari data master kepegawaian/kepangkatan,
     * terpisah dari riwayat kepangkatan yang berisi pangkat saat ini.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('kepangkatan_diajukan_id')->nullable()->after('jabatan_diajukan')
                ->constrained('kepangkatan')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('kepangkatan_diajukan_id');
        });
    }
};
