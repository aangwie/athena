<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Berita Acara Komite Integritas Akademik (BAKI) yang menindaklanjuti
     * berita acara persetujuan senat untuk seorang dosen.
     */
    public function up(): void
    {
        Schema::create('surat_baki', function (Blueprint $table) {
            $table->id();
            $table->string('nomor_surat', 100);
            // Usulan senat (BAP) yang ditindaklanjuti; menyimpan dosen, nomor, dan tanggal BAP.
            $table->foreignId('surat_bap_senat_id')->constrained('surat_bap_senat')->cascadeOnDelete();
            $table->date('tanggal_surat');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('surat_baki');
    }
};
