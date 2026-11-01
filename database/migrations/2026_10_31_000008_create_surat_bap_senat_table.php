<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Berita acara persetujuan senat atas usulan kenaikan jabatan akademik dosen.
     */
    public function up(): void
    {
        Schema::create('surat_bap_senat', function (Blueprint $table) {
            $table->id();
            $table->string('nomor_bap', 100);
            $table->date('tanggal_bap');
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('bidang_keilmuan');
            $table->unsignedTinyInteger('prosentase_penelitian');
            // Daftar user_id (tabel senat) yang hadir pada rapat.
            $table->json('anggota_senat_ids');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('surat_bap_senat');
    }
};
