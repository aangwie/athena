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
            // Rincian tingkat ketiga bidang C (komponen → rincian → rincian kegiatan).
            $table->string('rincian', 60)->nullable()->after('sub_jenis');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('kegiatan_dupak', function (Blueprint $table) {
            $table->dropColumn('rincian');
        });
    }
};
