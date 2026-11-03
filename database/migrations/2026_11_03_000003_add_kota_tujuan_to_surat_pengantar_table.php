<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Kota tujuan dicetak pada baris "di …" di bawah nama yang dituju.
     */
    public function up(): void
    {
        Schema::table('surat_pengantar', function (Blueprint $table) {
            $table->string('kota_tujuan', 100)->nullable()->after('tujuan');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('surat_pengantar', function (Blueprint $table) {
            $table->dropColumn('kota_tujuan');
        });
    }
};