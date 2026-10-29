<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tambahkan kolom roles (satu user bisa punya banyak role) dan salin nilai role lama.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->json('roles')->nullable()->after('role');
        });

        DB::table('users')->select('id', 'role')->orderBy('id')->each(function (object $user): void {
            DB::table('users')->where('id', $user->id)->update([
                'roles' => json_encode([$user->role]),
            ]);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('roles');
        });
    }
};
