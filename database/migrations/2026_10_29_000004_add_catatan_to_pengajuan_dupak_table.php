<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Alasan perbaikan / penolakan yang ditulis admin saat memutuskan ajuan DUPAK.
     */
    public function up(): void
    {
        Schema::table('pengajuan_dupak', function (Blueprint $table) {
            $table->text('catatan')->nullable()->after('dinilai_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pengajuan_dupak', function (Blueprint $table) {
            $table->dropColumn('catatan');
        });
    }
};
