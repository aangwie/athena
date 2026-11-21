<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Kategori kegiatan pada formulir Riwayat Karya Tulis (pilihan tetap yang
     * mengikuti kategori publikasi DUPAK Bidang C). Baris lama boleh kosong.
     */
    public function up(): void
    {
        Schema::table('karya_tulis', function (Blueprint $table) {
            $table->string('kategori_kegiatan')->nullable()->after('user_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('karya_tulis', function (Blueprint $table) {
            $table->dropColumn('kategori_kegiatan');
        });
    }
};
