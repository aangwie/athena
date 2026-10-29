<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('kegiatan_dupak', function (Blueprint $table) {
            // Rincian select bertingkat bidang B (nullable: bidang A tidak punya rincian).
            $table->string('sub_jenis', 40)->nullable()->after('jenis');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('kegiatan_dupak', function (Blueprint $table) {
            $table->dropColumn('sub_jenis');
        });
    }
};
