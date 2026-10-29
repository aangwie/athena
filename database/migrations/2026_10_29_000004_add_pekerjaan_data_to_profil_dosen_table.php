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
        Schema::table('profil_dosen', function (Blueprint $table) {
            $table->string('agama', 20)->nullable();
            $table->string('niy_npp', 30)->nullable();
            $table->string('nuptk', 30)->nullable();

            // Tanggal dan masa kerja tiap SK: tahun & bulan disimpan terpisah.
            foreach (['pplp', 'jafa', 'impasing'] as $sk) {
                $table->date("sk_{$sk}_tanggal")->nullable();
                $table->unsignedSmallInteger("sk_{$sk}_tahun")->nullable();
                $table->unsignedTinyInteger("sk_{$sk}_bulan")->nullable();
            }
        });

        Schema::create('riwayat_kepangkatan', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('kepangkatan_id')->nullable()->constrained('kepangkatan')->nullOnDelete();
            $table->date('tmt');
            $table->timestamps();
        });

        Schema::create('riwayat_jabatan_struktural', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('jabatan_struktural_id')->nullable()->constrained('jabatan_struktural')->nullOnDelete();
            $table->foreignId('jabatan_fungsional_id')->nullable()->constrained('jabatan_fungsional')->nullOnDelete();
            $table->date('tmt');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('riwayat_jabatan_struktural');
        Schema::dropIfExists('riwayat_kepangkatan');

        Schema::table('profil_dosen', function (Blueprint $table) {
            $table->dropColumn([
                'agama',
                'niy_npp',
                'nuptk',
                'sk_pplp_tanggal',
                'sk_pplp_tahun',
                'sk_pplp_bulan',
                'sk_jafa_tanggal',
                'sk_jafa_tahun',
                'sk_jafa_bulan',
                'sk_impasing_tanggal',
                'sk_impasing_tahun',
                'sk_impasing_bulan',
            ]);
        });
    }
};
