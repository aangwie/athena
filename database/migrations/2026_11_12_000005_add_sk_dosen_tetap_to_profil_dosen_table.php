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
        Schema::table('profil_dosen', function (Blueprint $table) {
            $table->string('sk_dosen_tetap_path')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('profil_dosen', function (Blueprint $table) {
            $table->dropColumn('sk_dosen_tetap_path');
        });
    }
};