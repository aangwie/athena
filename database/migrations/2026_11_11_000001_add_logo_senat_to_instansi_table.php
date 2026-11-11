<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Logo senat disimpan sebagai data URI base64, sama seperti logo instansi,
     * agar dapat langsung dipakai sebagai atribut src pada halaman dan dokumen.
     */
    public function up(): void
    {
        Schema::table('instansi', function (Blueprint $table) {
            $table->longText('logo_senat_base64')->nullable()->after('logo_base64');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('instansi', function (Blueprint $table) {
            $table->dropColumn('logo_senat_base64');
        });
    }
};
