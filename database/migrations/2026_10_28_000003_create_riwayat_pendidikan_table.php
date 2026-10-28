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
        Schema::create('riwayat_pendidikan', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('jenjang', 10);
            $table->string('nama_institusi');
            $table->string('jurusan')->nullable();
            $table->unsignedSmallInteger('tahun_masuk');
            $table->unsignedSmallInteger('tahun_lulus')->nullable();
            $table->string('ijazah_path')->nullable();
            $table->string('transkrip_path')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'jenjang']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('riwayat_pendidikan');
    }
};
