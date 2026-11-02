<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Pangkat/golongan dosen disimpan terpisah dari jabatan akademik,
     * supaya form data dosen bisa memuat keduanya.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('pangkat')->nullable()->after('jabatan_fungsional');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('pangkat');
        });
    }
};