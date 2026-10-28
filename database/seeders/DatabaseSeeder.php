<?php

namespace Database\Seeders;

use App\Models\PengajuanJabatan;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed data awal aplikasi.
     */
    public function run(): void
    {
        $admin = User::updateOrCreate(
            ['nidn' => '0000000001'],
            [
                'name' => 'Admin Kepegawaian',
                'email' => 'admin@athena.test',
                'program_studi' => 'Rektorat',
                'jabatan_fungsional' => 'Administrator',
                'role' => 'admin',
                'is_active' => true,
                'password' => Hash::make('password'),
            ],
        );

        $dosen = collect([
            [
                'nidn' => '1987654321',
                'name' => 'Dr. Ayu Lestari, M.Kom.',
                'email' => 'ayu.lestari@athena.test',
                'program_studi' => 'Pendidikan Informatika',
                'jabatan_fungsional' => 'Lektor',
            ],
            [
                'nidn' => '1990011220',
                'name' => 'Budi Santoso, M.Pd.',
                'email' => 'budi.santoso@athena.test',
                'program_studi' => 'Pendidikan Matematika',
                'jabatan_fungsional' => 'Asisten Ahli',
            ],
            [
                'nidn' => '1985041722',
                'name' => 'Dr. Citra Maharani, M.Si.',
                'email' => 'citra.maharani@athena.test',
                'program_studi' => 'Pendidikan Biologi',
                'jabatan_fungsional' => 'Lektor Kepala',
            ],
        ])->map(fn (array $data) => User::updateOrCreate(
            ['nidn' => $data['nidn']],
            $data + [
                'role' => 'dosen',
                'is_active' => true,
                'password' => Hash::make('password'),
            ],
        ));

        $statuses = ['draft', 'diajukan', 'diverifikasi', 'disetujui', 'ditolak'];

        foreach ($dosen as $index => $user) {
            foreach (range(1, 3) as $iteration) {
                PengajuanJabatan::updateOrCreate(
                    ['nomor_pengajuan' => sprintf('PJA-%s-%03d', now()->format('Y'), ($index * 3) + $iteration)],
                    [
                        'user_id' => $user->id,
                        'jabatan_asal' => $user->jabatan_fungsional,
                        'jabatan_tujuan' => $iteration === 1 ? 'Lektor Kepala' : 'Lektor',
                        'program_studi' => $user->program_studi,
                        'periode' => now()->subMonths($iteration)->translatedFormat('F Y'),
                        'tanggal_pengajuan' => now()->subDays(($index * 12) + ($iteration * 6)),
                        'status' => $statuses[($index + $iteration) % count($statuses)],
                        'catatan' => 'Data demonstrasi untuk pengembangan awal.',
                    ],
                );
            }
        }
    }
}
