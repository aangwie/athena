<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Nomor WhatsApp akun, dipakai untuk notifikasi pengajuan. Dosen mengisinya
     * lewat profil (kolom profil_dosen.no_hp); kolom ini menjadi jalur untuk
     * akun yang tidak punya profil dosen, mis. TIM PAK dan admin kepegawaian.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('no_hp', 25)->nullable()->after('pangkat');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('no_hp');
        });
    }
};