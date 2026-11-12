<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Satu dosen boleh merangkap jabatan senat (mis. Ketua merangkap Anggota),
     * sehingga jabatan kedua disimpan pada kolom terpisah. Baris senat tetap
     * satu per dosen agar daftar hadir dan dokumen cetak tidak berganda.
     */
    public function up(): void
    {
        Schema::table('senat', function (Blueprint $table) {
            $table->string('jabatan_tambahan', 20)->nullable()->after('jabatan');
        });
    }

    public function down(): void
    {
        Schema::table('senat', function (Blueprint $table) {
            $table->dropColumn('jabatan_tambahan');
        });
    }
};
