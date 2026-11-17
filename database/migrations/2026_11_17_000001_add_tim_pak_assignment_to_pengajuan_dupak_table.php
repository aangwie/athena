<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pengajuan_dupak', function (Blueprint $table) {
            $table->foreignId('tim_pak_id')->nullable()->after('user_id')->constrained('users')->nullOnDelete();
        });

        Schema::table('pengajuan_dupak', function (Blueprint $table) {
            $table->index(['status', 'tim_pak_id']);
        });
    }

    public function down(): void
    {
        Schema::table('pengajuan_dupak', function (Blueprint $table) {
            $table->dropIndex(['status', 'tim_pak_id']);
            $table->dropConstrainedForeignId('tim_pak_id');
        });
    }
};