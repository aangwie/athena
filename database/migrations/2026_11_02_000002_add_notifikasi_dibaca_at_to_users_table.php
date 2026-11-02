<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Penanda "notifikasi lonceng sudah dilihat" per user. Daftar notifikasinya
     * sendiri dihitung dari data pengajuan (App\Support\Notifikasi), jadi yang
     * perlu disimpan hanya waktu terakhir panel lonceng dibuka.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->timestamp('notifikasi_dibaca_at')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('notifikasi_dibaca_at');
        });
    }
};