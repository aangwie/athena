<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Masa kerja golongan (lama & baru) pada profil dosen dan riwayat angka kredit
     * beserta tahun pengajuannya.
     */
    public function up(): void
    {
        Schema::table('profil_dosen', function (Blueprint $table) {
            // Tahun & bulan disimpan terpisah, sama seperti masa kerja SK.
            foreach (['lama', 'baru'] as $jenis) {
                $table->unsignedSmallInteger("masa_kerja_golongan_{$jenis}_tahun")->nullable();
                $table->unsignedTinyInteger("masa_kerja_golongan_{$jenis}_bulan")->nullable();
            }
        });

        Schema::create('riwayat_angka_kredit', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->unsignedSmallInteger('tahun');
            $table->decimal('angka_kredit', 8, 2);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('riwayat_angka_kredit');

        Schema::table('profil_dosen', function (Blueprint $table) {
            $table->dropColumn([
                'masa_kerja_golongan_lama_tahun',
                'masa_kerja_golongan_lama_bulan',
                'masa_kerja_golongan_baru_tahun',
                'masa_kerja_golongan_baru_bulan',
            ]);
        });
    }
};
