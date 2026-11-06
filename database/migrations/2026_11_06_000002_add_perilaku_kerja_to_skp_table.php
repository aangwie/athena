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
        Schema::table('skp', function (Blueprint $table) {
            $table->unsignedTinyInteger('nilai_orientasi')->nullable()->after('periode_akhir');
            $table->unsignedTinyInteger('nilai_integritas')->nullable()->after('nilai_orientasi');
            $table->unsignedTinyInteger('nilai_komitmen')->nullable()->after('nilai_integritas');
            $table->unsignedTinyInteger('nilai_disiplin')->nullable()->after('nilai_komitmen');
            $table->unsignedTinyInteger('nilai_kerjasama')->nullable()->after('nilai_disiplin');
            $table->unsignedTinyInteger('nilai_kepemimpinan')->nullable()->after('nilai_kerjasama');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('skp', function (Blueprint $table) {
            $table->dropColumn([
                'nilai_orientasi',
                'nilai_integritas',
                'nilai_komitmen',
                'nilai_disiplin',
                'nilai_kerjasama',
                'nilai_kepemimpinan',
            ]);
        });
    }
};