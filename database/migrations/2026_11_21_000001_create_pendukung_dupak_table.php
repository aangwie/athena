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
        Schema::create('pendukung_dupak', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            // Jenis dokumen: check similarity, bukti korespondensi, profil SINTA, atau artikel.
            $table->string('jenis', 50);
            // Berkas PDF disimpan pada disk privat; tautan dipakai khusus jenis profil SINTA.
            $table->string('berkas_path', 2048)->nullable();
            $table->string('berkas_nama', 255)->nullable();
            $table->string('tautan', 2048)->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pendukung_dupak');
    }
};
