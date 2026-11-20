<?php

/**
 * Skrip bantu pembuatan tangkapan layar buku manual Athena.
 *
 * - passwords-set     : ganti sementara password akun demo menjadi kata sandi manual
 * - passwords-restore : kembalikan hash password asli
 * - demo-create       : buat ajuan DUPAK contoh untuk tangkapan layar admin/TIM PAK
 * - demo-delete       : hapus ajuan DUPAK contoh yang dibuat demo-create
 *
 * Pemakaian (dari akar proyek):
 *   php docs/manual/tools/helper.php passwords-set
 *   php docs/manual/tools/helper.php demo-create
 *   php docs/manual/tools/helper.php demo-delete
 *   php docs/manual/tools/helper.php passwords-restore
 *
 * Berkas status disimpan di storage/app/manual-shots/ sehingga tidak masuk repositori.
 */

require dirname(__DIR__, 3).'/vendor/autoload.php';

$app = require dirname(__DIR__, 3).'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\KegiatanDupak;
use App\Models\PengajuanDupak;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

$aksi = $argv[1] ?? null;
$folder = dirname(__DIR__, 3).'/storage/app/manual-shots';

if (! is_dir($folder)) {
    mkdir($folder, 0777, true);
}

$berkasPassword = $folder.'/password-asli.json';
$berkasDemo = $folder.'/demo-ids.json';

/** Akun demo yang dipakai untuk tangkapan layar. */
$akun = [
    1 => 'Admin Kepegawaian',
    5 => 'Wulan Trisnawaty (admin, dosen, TIM PAK)',
    12 => 'Caraka Yoga Aprilya (dosen)',
    28 => 'Bakti Sutopo (pejabat penilai SKP)',
    34 => 'Lina Erviana (dosen dengan data pengajuan)',
    41 => 'Aditya Prihantara (dosen dinilai SKP)',
];

switch ($aksi) {
    case 'passwords-set':
        $asli = [];

        foreach (array_keys($akun) as $id) {
            $user = User::findOrFail($id);
            $asli[$id] = $user->password;
            $user->forceFill(['password' => Hash::make('AthenaManual2026!')])->save();
            echo "Password sementara disiapkan: {$user->nidn} ({$user->name})\n";
        }

        file_put_contents($berkasPassword, json_encode($asli, JSON_PRETTY_PRINT));
        echo "Hash asli disimpan di {$berkasPassword}\n";
        break;

    case 'passwords-restore':
        if (! is_file($berkasPassword)) {
            echo "Tidak ada berkas hash asli.\n";
            break;
        }

        $asli = json_decode((string) file_get_contents($berkasPassword), true);

        foreach ($asli as $id => $hash) {
            User::whereKey($id)->update(['password' => $hash]);
        }

        unlink($berkasPassword);
        echo 'Password asli dikembalikan untuk '.count($asli)." akun.\n";
        break;

    case 'demo-create':
        $dosen = User::findOrFail(34);
        $timPak = User::findOrFail(5);
        $ids = ['pengajuan' => [], 'kegiatan' => []];

        $contoh = [
            ['tahun' => 2025, 'semester' => 'genap', 'status' => 'diajukan', 'tim_pak_id' => $timPak->id],
            ['tahun' => 2025, 'semester' => 'ganjil', 'status' => 'menunggu_penugasan', 'tim_pak_id' => null],
            ['tahun' => 2024, 'semester' => 'genap', 'status' => 'diajukan_dupak', 'tim_pak_id' => $timPak->id],
        ];

        $sumber = KegiatanDupak::query()
            ->where('user_id', $dosen->id)
            ->where('tahun', 2026)
            ->where('semester', 'ganjil')
            ->orderBy('id')
            ->limit(4)
            ->get();

        foreach ($contoh as $index => $baris) {
            $pengajuan = PengajuanDupak::updateOrCreate(
                ['user_id' => $dosen->id, 'tahun' => $baris['tahun'], 'semester' => $baris['semester']],
                [
                    'tim_pak_id' => $baris['tim_pak_id'],
                    'status' => $baris['status'],
                    'diajukan_at' => now()->subDays(9 - $index),
                    'dinilai_at' => $baris['status'] === 'diajukan_dupak' ? now()->subDays(2) : null,
                    'catatan' => 'Data contoh untuk buku manual.',
                ],
            );

            $ids['pengajuan'][] = $pengajuan->id;

            foreach ($sumber as $kegiatan) {
                $baru = $kegiatan->replicate();
                $baru->tahun = $baris['tahun'];
                $baru->semester = $baris['semester'];
                $baru->nilai_baru = $kegiatan->nilai_baru ?: 1.5;
                $baru->save();
                $ids['kegiatan'][] = $baru->id;
            }

            echo "Dibuat ajuan #{$pengajuan->id} {$baris['tahun']}/{$baris['semester']} status {$baris['status']}\n";
        }

        file_put_contents($berkasDemo, json_encode($ids, JSON_PRETTY_PRINT));
        echo "ID contoh disimpan di {$berkasDemo}\n";
        break;

    case 'demo-delete':
        if (! is_file($berkasDemo)) {
            echo "Tidak ada berkas ID contoh.\n";
            break;
        }

        $ids = json_decode((string) file_get_contents($berkasDemo), true);

        DB::table('kegiatan_dupak')->whereIn('id', $ids['kegiatan'] ?? [])->delete();
        DB::table('pengajuan_dupak')->whereIn('id', $ids['pengajuan'] ?? [])->delete();

        unlink($berkasDemo);
        echo 'Data contoh dihapus: '.count($ids['pengajuan'] ?? []).' ajuan, '.count($ids['kegiatan'] ?? [])." kegiatan.\n";
        break;

    default:
        echo "Perintah tidak dikenal. Gunakan: passwords-set, passwords-restore, demo-create, demo-delete\n";
}

