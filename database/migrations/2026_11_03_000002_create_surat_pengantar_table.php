<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Surat pengantar usulan jabatan akademik: satu dosen (pribadi) atau
     * banyak dosen sekaligus (kolektif) yang dicetak sebagai tabel.
     */
    public function up(): void
    {
        Schema::create('surat_pengantar', function (Blueprint $table) {
            $table->id();
            $table->string('nomor_surat', 100);
            $table->string('lampiran', 100)->nullable();
            $table->string('perihal');
            $table->string('tujuan', 150)->nullable();
            $table->date('tanggal_surat');
            $table->boolean('kolektif')->default(false);
            // Daftar user_id dosen yang diusulkan pada surat ini.
            $table->json('dosen_ids');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('surat_pengantar');
    }
};
