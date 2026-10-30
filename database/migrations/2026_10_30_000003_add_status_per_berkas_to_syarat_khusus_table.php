<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Status & catatan verifikasi per berkas (BKD dan IKD terpisah).
     * Kolom status/catatan lama tetap dipakai sebagai agregat satu ajuan.
     */
    public function up(): void
    {
        Schema::table('syarat_khusus', function (Blueprint $table) {
            $table->string('bkd_status', 20)->default('draf')->after('catatan');
            $table->string('ikd_status', 20)->default('draf')->after('bkd_status');
            $table->text('bkd_catatan')->nullable()->after('ikd_status');
            $table->text('ikd_catatan')->nullable()->after('bkd_catatan');
        });

        // Data lama: keputusan sebelumnya berlaku untuk kedua berkas.
        DB::table('syarat_khusus')->update([
            'bkd_status' => DB::raw('status'),
            'ikd_status' => DB::raw('status'),
            'bkd_catatan' => DB::raw('catatan'),
            'ikd_catatan' => DB::raw('catatan'),
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('syarat_khusus', function (Blueprint $table) {
            $table->dropColumn(['bkd_status', 'ikd_status', 'bkd_catatan', 'ikd_catatan']);
        });
    }
};
