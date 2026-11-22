<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** Kolom berkas bukti syarat khusus; setiap berkas menyimpan path dan nama aslinya. */
    private const BERKAS = [
        'pakta_integritas',
        'pakta_validasi',
        'disertasi',
        'check_similarity',
        'korespondensi',
        'artikel',
    ];

    /**
     * Ubah tabel pendukung DUPAK menjadi formulir Syarat Khusus DUPAK: satu baris
     * per dosen berisi data publikasi dan enam berkas bukti.
     */
    public function up(): void
    {
        Schema::table('pendukung_dupak', function (Blueprint $table) {
            $table->string('judul_publikasi', 255)->nullable()->after('user_id');
            $table->string('kategori_kegiatan', 255)->nullable();
            $table->string('jenis_publikasi', 255)->nullable();
            $table->string('nama_jurnal', 255)->nullable();
            $table->string('volume', 50)->nullable();
            $table->string('nomor', 50)->nullable();
            $table->date('tahun_terbit')->nullable();
            $table->string('halaman', 50)->nullable();
            $table->string('issn', 50)->nullable();
            $table->string('penerbit', 255)->nullable();
            // URL & path memakai TEXT agar ukuran baris MySQL tetap di bawah batas 65.535 byte.
            $table->text('doi')->nullable();
            $table->text('tautan_jurnal')->nullable();
            $table->text('tautan_artikel')->nullable();
            $table->string('penulis', 20)->nullable();
            $table->text('tautan_sinta')->nullable();

            foreach (self::BERKAS as $berkas) {
                $table->text('berkas_'.$berkas.'_path')->nullable();
                $table->string('berkas_'.$berkas.'_nama', 255)->nullable();
            }
        });

        $this->pindahkanDataLama();

        Schema::table('pendukung_dupak', function (Blueprint $table) {
            $table->dropColumn(['jenis', 'berkas_path', 'berkas_nama', 'tautan']);
            // Formulir baru hanya menyimpan satu entri per dosen.
            $table->unique('user_id');
        });
    }

    /**
     * Petakan dokumen lama ke kolom berkas baru, lalu sisakan satu baris per dosen.
     */
    private function pindahkanDataLama(): void
    {
        $peta = [
            'check_similarity' => 'berkas_check_similarity',
            'bukti_korespondensi' => 'berkas_korespondensi',
            'artikel' => 'berkas_artikel',
        ];

        $barisUtama = [];

        foreach (DB::table('pendukung_dupak')->orderBy('id')->get() as $baris) {
            $idUtama = $barisUtama[$baris->user_id] ??= $baris->id;

            if (isset($peta[$baris->jenis]) && filled($baris->berkas_path)) {
                DB::table('pendukung_dupak')->where('id', $idUtama)->update([
                    $peta[$baris->jenis].'_path' => $baris->berkas_path,
                    $peta[$baris->jenis].'_nama' => $baris->berkas_nama,
                ]);
            }

            if ($baris->jenis === 'profil_sinta' && filled($baris->tautan)) {
                DB::table('pendukung_dupak')->where('id', $idUtama)->update([
                    'tautan_sinta' => $baris->tautan,
                ]);
            }

            if ($baris->id !== $idUtama) {
                DB::table('pendukung_dupak')->where('id', $baris->id)->delete();
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pendukung_dupak', function (Blueprint $table) {
            $table->dropUnique(['user_id']);
            $table->dropColumn([
                'judul_publikasi',
                'kategori_kegiatan',
                'jenis_publikasi',
                'nama_jurnal',
                'volume',
                'nomor',
                'tahun_terbit',
                'halaman',
                'issn',
                'penerbit',
                'doi',
                'tautan_jurnal',
                'tautan_artikel',
                'penulis',
                'tautan_sinta',
                ...array_map(fn (string $berkas) => 'berkas_'.$berkas.'_path', self::BERKAS),
                ...array_map(fn (string $berkas) => 'berkas_'.$berkas.'_nama', self::BERKAS),
            ]);
            $table->string('jenis', 50)->nullable();
            $table->string('berkas_path', 2048)->nullable();
            $table->string('berkas_nama', 255)->nullable();
            $table->string('tautan', 2048)->nullable();
        });
    }
};
