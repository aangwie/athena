<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Kota instansi dipakai pada baris tanda tangan surat (mis. "Bandung, 31-10-2026").
     */
    public function up(): void
    {
        Schema::table('instansi', function (Blueprint $table) {
            $table->string('kota', 100)->nullable()->after('alamat');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('instansi', function (Blueprint $table) {
            $table->dropColumn('kota');
        });
    }
};
