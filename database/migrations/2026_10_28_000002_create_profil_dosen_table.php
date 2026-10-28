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
        Schema::create('profil_dosen', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('tempat_lahir')->nullable();
            $table->date('tanggal_lahir')->nullable();
            $table->text('alamat')->nullable();
            $table->string('no_hp', 30)->nullable();
            $table->enum('status_pernikahan', ['lajang', 'menikah'])->default('lajang');
            $table->string('nama_pasangan')->nullable();
            $table->enum('pekerjaan_pasangan', ['asn', 'non_asn'])->nullable();
            $table->string('nip_pasangan', 30)->nullable();
            $table->string('tempat_lahir_pasangan')->nullable();
            $table->date('tanggal_lahir_pasangan')->nullable();
            $table->date('tanggal_nikah')->nullable();
            $table->timestamps();
        });

        Schema::create('anak_dosen', function (Blueprint $table) {
            $table->id();
            $table->foreignId('profil_dosen_id')->constrained('profil_dosen')->cascadeOnDelete();
            $table->string('nama');
            $table->string('tempat_lahir')->nullable();
            $table->date('tanggal_lahir')->nullable();
            $table->enum('jenis_kelamin', ['L', 'P']);
            $table->string('pekerjaan')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('anak_dosen');
        Schema::dropIfExists('profil_dosen');
    }
};
