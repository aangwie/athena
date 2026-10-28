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
        Schema::create('pengajuan_jabatan', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('nomor_pengajuan', 40)->unique();
            $table->string('jabatan_asal')->nullable();
            $table->string('jabatan_tujuan');
            $table->string('program_studi');
            $table->string('periode', 30);
            $table->date('tanggal_pengajuan');
            $table->enum('status', ['draft', 'diajukan', 'diverifikasi', 'disetujui', 'ditolak'])
                ->default('draft');
            $table->text('catatan')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pengajuan_jabatan');
    }
};
