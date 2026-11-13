<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Jumlah kuorum pada BAP senat dapat diisi otomatis (mengikuti jumlah anggota senat
     * yang dipilih) atau manual (angka yang diisi admin). Pilihan mode disimpan pada
     * kuorum_mode; angka manual disimpan pada jumlah_kuorum.
     */
    public function up(): void
    {
        Schema::table('surat_bap_senat', function (Blueprint $table) {
            $table->string('kuorum_mode')->default('otomatis');
            $table->unsignedSmallInteger('jumlah_kuorum')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('surat_bap_senat', function (Blueprint $table) {
            $table->dropColumn(['kuorum_mode', 'jumlah_kuorum']);
        });
    }
};
