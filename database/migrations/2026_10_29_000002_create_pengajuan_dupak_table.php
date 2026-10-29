<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Satu baris per dosen per periode: status usulan DUPAK yang dikirim ke TIM PAK.
     */
    public function up(): void
    {
        Schema::create('pengajuan_dupak', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->unsignedSmallInteger('tahun');
            $table->string('semester', 10);
            $table->string('status', 20)->default('diajukan');
            $table->timestamp('diajukan_at')->nullable();
            $table->timestamp('dinilai_at')->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'tahun', 'semester']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pengajuan_dupak');
    }
};
