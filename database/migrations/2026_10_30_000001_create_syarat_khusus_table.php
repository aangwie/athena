<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Berkas syarat khusus (BKD & IKD) pengajuan jabatan akademik pertama:
     * satu baris per dosen per periode.
     */
    public function up(): void
    {
        Schema::create('syarat_khusus', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->unsignedSmallInteger('tahun');
            $table->string('semester', 10);
            $table->string('bkd_path')->nullable();
            $table->string('ikd_path')->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'tahun', 'semester']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('syarat_khusus');
    }
};
