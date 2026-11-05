<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Jabatan akademik yang akan diajukan (target kenaikan) pada halaman
     * Status Jabatan Akademik. Disimpan terpisah dari `jabatan_fungsional`
     * yang berisi jabatan akademik saat ini.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('jabatan_diajukan', 100)->nullable()->after('pangkat');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('jabatan_diajukan');
        });
    }
};
