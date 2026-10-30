<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Status verifikasi berkas syarat khusus oleh admin beserta catatannya.
     */
    public function up(): void
    {
        Schema::table('syarat_khusus', function (Blueprint $table) {
            $table->string('status', 20)->default('draf')->after('semester');
            $table->text('catatan')->nullable()->after('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('syarat_khusus', function (Blueprint $table) {
            $table->dropColumn(['status', 'catatan']);
        });
    }
};
