<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Surat pernyataan pimpinan untuk usulan jabatan akademik dosen.
     */
    public function up(): void
    {
        Schema::create('surat_pernyataan_pimpinan', function (Blueprint $table) {
            $table->id();
            $table->foreignId('struktur_instansi_id')->constrained('struktur_instansi')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->date('tanggal_surat');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('surat_pernyataan_pimpinan');
    }
};
