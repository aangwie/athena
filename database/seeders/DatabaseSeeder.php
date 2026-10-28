<?php

namespace Database\Seeders;

use App\Models\PengajuanJabatan;
use App\Models\ProfilDosen;
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

        $profilData = [
            '1987654321' => [
                'profil' => [
                    'tempat_lahir' => 'Bandung',
                    'tanggal_lahir' => '1987-05-14',
                    'alamat' => 'Jl. Cihampelas No. 112, Bandung, Jawa Barat',
                    'no_hp' => '0812-3456-7890',
                    'status_pernikahan' => 'menikah',
                    'nama_pasangan' => 'Rizky Pratama, S.T.',
                    'pekerjaan_pasangan' => 'asn',
                    'nip_pasangan' => '198503122010011002',
                    'tempat_lahir_pasangan' => 'Jakarta',
                    'tanggal_lahir_pasangan' => '1985-03-12',
                    'tanggal_nikah' => '2012-06-30',
                ],
                'anak' => [
                    [
                        'nama' => 'Nadia Lestari',
                        'tempat_lahir' => 'Bandung',
                        'tanggal_lahir' => '2014-02-11',
                        'jenis_kelamin' => 'P',
                        'pekerjaan' => 'Pelajar',
                    ],
                    [
                        'nama' => 'Fajar Pratama',
                        'tempat_lahir' => 'Bandung',
                        'tanggal_lahir' => '2018-09-05',
                        'jenis_kelamin' => 'L',
                        'pekerjaan' => 'Pelajar',
                    ],
                ],
            ],
            '1990011220' => [
                'profil' => [
                    'tempat_lahir' => 'Yogyakarta',
                    'tanggal_lahir' => '1990-01-12',
                    'alamat' => 'Perum Griya Asri Blok C-7, Sleman, DI Yogyakarta',
                    'no_hp' => '0857-1122-3344',
                    'status_pernikahan' => 'menikah',
                    'nama_pasangan' => 'Sinta Dewi, S.E.',
                    'pekerjaan_pasangan' => 'non_asn',
                    'nip_pasangan' => null,
                    'tempat_lahir_pasangan' => 'Sleman',
                    'tanggal_lahir_pasangan' => '1992-07-19',
                    'tanggal_nikah' => '2016-11-12',
                ],
                'anak' => [
                    [
                        'nama' => 'Aditya Santoso',
                        'tempat_lahir' => 'Sleman',
                        'tanggal_lahir' => '2019-04-23',
                        'jenis_kelamin' => 'L',
                        'pekerjaan' => 'Pelajar',
                    ],
                ],
            ],
            '1985041722' => [
                'profil' => [
                    'tempat_lahir' => 'Surabaya',
                    'tanggal_lahir' => '1985-04-17',
                    'alamat' => 'Jl. Diponegoro No. 88, Surabaya, Jawa Timur',
                    'no_hp' => '0813-9988-7766',
                    'status_pernikahan' => 'lajang',
                ],
                'anak' => [],
            ],
        ];

        foreach ($dosen as $user) {
            if (! isset($profilData[$user->nidn])) {
                continue;
            }

            $profil = ProfilDosen::updateOrCreate(
                ['user_id' => $user->id],
                $profilData[$user->nidn]['profil'],
            );

            $profil->anak()->delete();

            foreach ($profilData[$user->nidn]['anak'] as $anak) {
                $profil->anak()->create($anak);
            }
        }
    }
}
