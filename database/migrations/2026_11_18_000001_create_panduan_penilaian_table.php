<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Panduan penilaian diisi admin (berkas PDF atau tautan berkas) dan dibaca TIM PAK.
     * Hanya satu baris yang dipakai; baris lama ditimpa saat admin menyimpan ulang.
     */
    public function up(): void
    {
        Schema::create('panduan_penilaian', function (Blueprint $table) {
            $table->id();
            $table->string('tipe', 10)->default('file');
            $table->string('path')->nullable();
            $table->string('nama_asli', 150)->nullable();
            $table->text('url')->nullable();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('panduan_penilaian');
    }
};
