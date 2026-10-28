<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('instansi', function (Blueprint $table) {
            $table->longText('logo_base64')->nullable()->after('website');
        });

        // Pindahkan logo lama yang masih berupa berkas ke kolom base64.
        DB::table('instansi')->whereNotNull('logo_path')->get()->each(function (object $instansi): void {
            if (! Storage::disk('public')->exists($instansi->logo_path)) {
                return;
            }

            $mime = Storage::disk('public')->mimeType($instansi->logo_path) ?: 'image/png';
            $base64 = base64_encode(Storage::disk('public')->get($instansi->logo_path));

            DB::table('instansi')->where('id', $instansi->id)->update([
                'logo_base64' => "data:{$mime};base64,{$base64}",
            ]);

            Storage::disk('public')->delete($instansi->logo_path);
        });

        Schema::table('instansi', function (Blueprint $table) {
            $table->dropColumn('logo_path');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('instansi', function (Blueprint $table) {
            $table->string('logo_path')->nullable()->after('website');
        });

        // Kembalikan logo base64 menjadi berkas pada disk publik.
        DB::table('instansi')->whereNotNull('logo_base64')->get()->each(function (object $instansi): void {
            if (! preg_match('/^data:(image\/[a-zA-Z0-9.+-]+);base64,(.+)$/s', $instansi->logo_base64, $matches)) {
                return;
            }

            $extension = match ($matches[1]) {
                'image/jpeg' => 'jpg',
                'image/webp' => 'webp',
                default => 'png',
            };

            $path = sprintf('instansi/logo/logo_instansi_%s.%s', Str::random(16), $extension);

            Storage::disk('public')->put($path, base64_decode($matches[2]));

            DB::table('instansi')->where('id', $instansi->id)->update([
                'logo_path' => $path,
            ]);
        });

        Schema::table('instansi', function (Blueprint $table) {
            $table->dropColumn('logo_base64');
        });
    }
};
