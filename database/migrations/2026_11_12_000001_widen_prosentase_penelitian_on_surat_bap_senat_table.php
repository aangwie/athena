<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Prosentase proporsi penelitian kini dipilih dari daftar tetap 150 s.d. 1050,
     * sehingga kolom tinyint (maks. 255) dilebarkan menjadi smallint (maks. 65535).
     */
    public function up(): void
    {
        Schema::table('surat_bap_senat', function (Blueprint $table) {
            $table->unsignedSmallInteger('prosentase_penelitian')->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('surat_bap_senat', function (Blueprint $table) {
            $table->unsignedTinyInteger('prosentase_penelitian')->change();
        });
    }
};
