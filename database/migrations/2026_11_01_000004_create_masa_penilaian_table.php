<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Rentang masa penilaian per dosen per periode pengajuan DUPAK.
     */
    public function up(): void
    {
        Schema::create('masa_penilaian', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->unsignedSmallInteger('tahun');
            $table->string('semester', 10);
            $table->date('mulai');
            $table->date('akhir');
            $table->timestamps();

            $table->unique(['user_id', 'tahun', 'semester']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('masa_penilaian');
    }
};
