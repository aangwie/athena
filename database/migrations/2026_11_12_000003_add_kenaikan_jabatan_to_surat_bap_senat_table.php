<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * BAP Senat kini mencatat tujuan kenaikan jabatan akademik (Belum ke Asisten Ahli
     * atau Asisten Ahli ke Lektor). Prosentase proporsi penelitian hanya relevan untuk
     * kenaikan Asisten Ahli ke Lektor, sehingga kolomnya dibuat boleh kosong.
     */
    public function up(): void
    {
        Schema::table('surat_bap_senat', function (Blueprint $table) {
            $table->string('kenaikan_jabatan')->nullable();
            $table->unsignedSmallInteger('prosentase_penelitian')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('surat_bap_senat', function (Blueprint $table) {
            $table->unsignedSmallInteger('prosentase_penelitian')->nullable(false)->change();
            $table->dropColumn('kenaikan_jabatan');
        });
    }
};
