<?php

namespace Database\Seeders;

use App\Models\Instansi;
use App\Models\JabatanFungsional;
use App\Models\JabatanStruktural;
use App\Models\Kepangkatan;
use App\Models\PengajuanJabatan;
use App\Models\ProfilDosen;
use App\Models\RiwayatPendidikan;
use App\Models\StrukturInstansi;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed data awal aplikasi.
     */
    public function run(): void
    {
        $this->seedKepegawaian();

        $admin = User::updateOrCreate(
            ['nidn' => '0000000001'],
            [
                'name' => 'Admin Kepegawaian',
                'email' => 'admin@athena.test',
                'program_studi' => 'Rektorat',
                'jabatan_fungsional' => 'Administrator',
                'role' => 'admin',
                'roles' => ['admin'],
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
                'roles' => ['dosen'],
            ],
            [
                'nidn' => '1990011220',
                'name' => 'Budi Santoso, M.Pd.',
                'email' => 'budi.santoso@athena.test',
                'program_studi' => 'Pendidikan Matematika',
                'jabatan_fungsional' => 'Asisten Ahli',
                'roles' => ['dosen'],
            ],
            [
                'nidn' => '1985041722',
                'name' => 'Dr. Citra Maharani, M.Si.',
                'email' => 'citra.maharani@athena.test',
                'program_studi' => 'Pendidikan Biologi',
                'jabatan_fungsional' => 'Lektor Kepala',
                'roles' => ['dosen', 'tim_pak'],
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
                'pendidikan' => [
                    ['jenjang' => 'sd', 'nama_institusi' => 'SD Negeri 1 Bandung', 'jurusan' => null, 'tahun_masuk' => 1993, 'tahun_lulus' => 1999],
                    ['jenjang' => 'smp', 'nama_institusi' => 'SMP Negeri 2 Bandung', 'jurusan' => null, 'tahun_masuk' => 1999, 'tahun_lulus' => 2002],
                    ['jenjang' => 'sma', 'nama_institusi' => 'SMA Negeri 3 Bandung', 'jurusan' => 'Ilmu Pengetahuan Alam', 'tahun_masuk' => 2002, 'tahun_lulus' => 2005],
                    ['jenjang' => 's1', 'nama_institusi' => 'Universitas Pendidikan Indonesia', 'jurusan' => 'Pendidikan Informatika', 'tahun_masuk' => 2005, 'tahun_lulus' => 2009],
                    ['jenjang' => 's2', 'nama_institusi' => 'Institut Teknologi Bandung', 'jurusan' => 'Ilmu Komputer', 'tahun_masuk' => 2010, 'tahun_lulus' => 2012],
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
                'pendidikan' => [
                    ['jenjang' => 'sd', 'nama_institusi' => 'SD Negeri 1 Sleman', 'jurusan' => null, 'tahun_masuk' => 1996, 'tahun_lulus' => 2002],
                    ['jenjang' => 'smp', 'nama_institusi' => 'SMP Negeri 1 Sleman', 'jurusan' => null, 'tahun_masuk' => 2002, 'tahun_lulus' => 2005],
                    ['jenjang' => 'sma', 'nama_institusi' => 'SMA Negeri 1 Yogyakarta', 'jurusan' => 'Ilmu Pengetahuan Alam', 'tahun_masuk' => 2005, 'tahun_lulus' => 2008],
                    ['jenjang' => 's1', 'nama_institusi' => 'Universitas Gadjah Mada', 'jurusan' => 'Pendidikan Matematika', 'tahun_masuk' => 2008, 'tahun_lulus' => 2012],
                    ['jenjang' => 's2', 'nama_institusi' => 'Universitas Negeri Yogyakarta', 'jurusan' => 'Pendidikan Matematika', 'tahun_masuk' => 2013, 'tahun_lulus' => 2015],
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
                'pendidikan' => [
                    ['jenjang' => 'sd', 'nama_institusi' => 'SD Negeri 1 Surabaya', 'jurusan' => null, 'tahun_masuk' => 1991, 'tahun_lulus' => 1997],
                    ['jenjang' => 'smp', 'nama_institusi' => 'SMP Negeri 1 Surabaya', 'jurusan' => null, 'tahun_masuk' => 1997, 'tahun_lulus' => 2000],
                    ['jenjang' => 'sma', 'nama_institusi' => 'SMA Negeri 1 Surabaya', 'jurusan' => 'Ilmu Pengetahuan Alam', 'tahun_masuk' => 2000, 'tahun_lulus' => 2003],
                    ['jenjang' => 's1', 'nama_institusi' => 'Institut Teknologi Sepuluh Nopember', 'jurusan' => 'Teknik Informatika', 'tahun_masuk' => 2003, 'tahun_lulus' => 2007],
                    ['jenjang' => 's2', 'nama_institusi' => 'Institut Teknologi Sepuluh Nopember', 'jurusan' => 'Teknik Informatika', 'tahun_masuk' => 2008, 'tahun_lulus' => 2010],
                    ['jenjang' => 's3', 'nama_institusi' => 'Institut Teknologi Sepuluh Nopember', 'jurusan' => 'Teknik Informatika', 'tahun_masuk' => 2011, 'tahun_lulus' => 2016],
                ],
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

            $this->seedPendidikan($user, $profilData[$user->nidn]['pendidikan']);
        }

        $this->seedInstansi($dosen);
    }

    /**
     * Seed profil instansi dan struktur pejabat demonstrasi.
     *
     * @param  Collection<int, User>  $dosenUsers
     */
    private function seedInstansi($dosenUsers): void
    {
        $instansi = Instansi::query()->firstOrNew([]);

        if (! $instansi->exists) {
            $instansi->fill([
                'nama_instansi' => 'Universitas Athena Nusantara',
                'nomor_sk_operasional' => 'SK-MENDIKBUD/001/ATHENA/2026',
                'alamat' => 'Jl. Pendidikan No. 1, Bandung, Jawa Barat 40123',
                'no_telp' => '(022) 1234567',
                'website' => 'https://athena.ac.id',
            ]);
        }

        if (! $instansi->logo_base64) {
            $instansi->logo_base64 = $this->buatLogoContoh();
        }

        $instansi->save();

        $susunan = [
            'Rektor' => 0,
            'Wakil Rektor' => 1,
            'Dekan' => 2,
            'Ketua Program Studi' => 1,
        ];

        foreach ($susunan as $namaJabatan => $index) {
            $jabatan = JabatanStruktural::query()->where('nama', $namaJabatan)->first();
            $pejabat = $dosenUsers->values()->get($index);

            if (! $jabatan || ! $pejabat) {
                continue;
            }

            StrukturInstansi::query()->updateOrCreate([
                'jabatan_struktural_id' => $jabatan->id,
                'user_id' => $pejabat->id,
            ]);
        }
    }

    /**
     * Buat logo instansi contoh (PNG) dalam bentuk data URI base64.
     */
    private function buatLogoContoh(): string
    {
        $size = 480;
        $image = imagecreatetruecolor($size, $size);

        $teal = imagecolorallocate($image, 15, 118, 110);
        $dark = imagecolorallocate($image, 13, 92, 86);
        $white = imagecolorallocate($image, 255, 255, 255);

        imagefill($image, 0, 0, $teal);
        imagefilledellipse($image, intdiv($size, 2), intdiv($size, 2), 400, 400, $dark);

        imagesetthickness($image, 34);
        imageline($image, 240, 105, 145, 350, $white);
        imageline($image, 240, 105, 335, 350, $white);
        imageline($image, 188, 280, 292, 280, $white);

        $teks = 'ATHENA';
        $lebarTeks = imagefontwidth(5) * strlen($teks);
        imagestring($image, 5, intdiv($size - $lebarTeks, 2), 398, $teks, $white);

        ob_start();
        imagepng($image);
        $biner = (string) ob_get_clean();
        imagedestroy($image);

        return 'data:image/png;base64,'.base64_encode($biner);
    }

    /**
     * Seed data master menu Kepegawaian.
     */
    private function seedKepegawaian(): void
    {
        $data = [
            JabatanFungsional::class => [
                'Asisten Ahli',
                'Lektor',
                'Lektor Kepala',
                'Profesor',
            ],
            JabatanStruktural::class => [
                'Rektor',
                'Wakil Rektor',
                'Dekan',
                'Wakil Dekan',
                'Ketua Program Studi',
                'Sekretaris Program Studi',
            ],
            Kepangkatan::class => [
                'Penata Muda',
                'Penata Muda Tingkat I',
                'Penata',
                'Penata Tingkat I',
                'Pembina',
                'Pembina Tingkat I',
            ],
        ];

        foreach ($data as $model => $namaList) {
            foreach ($namaList as $nama) {
                $model::firstOrCreate(['nama' => $nama]);
            }
        }
    }

    /**
     * Buat data riwayat pendidikan demonstrasi beserta berkas PDF contoh.
     *
     * @param  list<array<string, mixed>>  $items
     */
    private function seedPendidikan(User $user, array $items): void
    {
        $user->riwayatPendidikan()->get()->each(function (RiwayatPendidikan $riwayat) {
            Storage::disk('local')->delete(array_filter([
                $riwayat->ijazah_path,
                $riwayat->transkrip_path,
            ]));

            $riwayat->delete();
        });

        foreach ($items as $item) {
            $jenjang = $item['jenjang'];
            $isPerguruanTinggi = in_array($jenjang, RiwayatPendidikan::PERGURUAN_TINGGI, true);

            $user->riwayatPendidikan()->create($item + [
                'user_id' => $user->id,
                'ijazah_path' => $this->simpanPdfContoh($user, $jenjang, 'Ijazah '.strtoupper($jenjang)),
                'transkrip_path' => $isPerguruanTinggi
                    ? $this->simpanPdfContoh($user, $jenjang, 'Transkrip '.strtoupper($jenjang))
                    : null,
            ]);
        }
    }

    /**
     * Simpan PDF contoh dengan pola nama jenjang_nidn_kode_acak.pdf.
     */
    private function simpanPdfContoh(User $user, string $jenjang, string $judul): string
    {
        $nama = sprintf('%s_%s_%s.pdf', $jenjang, $user->nidn, Str::random(16));
        $path = "riwayat-pendidikan/{$user->nidn}/{$nama}";

        Storage::disk('local')->put($path, $this->pdfSederhana($judul));

        return $path;
    }

    /**
     * PDF minimal untuk keperluan data demonstrasi.
     */
    private function pdfSederhana(string $judul): string
    {
        $judul = str_replace(['\\', '(', ')'], ['\\\\', '\\(', '\\)'], $judul);
        $content = "BT /F1 18 Tf 60 780 Td (Athena - Data Demonstrasi) Tj 0 -28 Td ({$judul}) Tj ET";

        $objects = [
            '<< /Type /Catalog /Pages 2 0 R >>',
            '<< /Type /Pages /Kids [3 0 R] /Count 1 >>',
            '<< /Type /Page /Parent 2 0 R /MediaBox [0 0 595 842] /Resources << /Font << /F1 5 0 R >> >> /Contents 4 0 R >>',
            '<< /Length '.strlen($content)." >>\nstream\n{$content}\nendstream",
            '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica >>',
        ];

        $pdf = "%PDF-1.4\n";
        $offsets = [];

        foreach ($objects as $index => $object) {
            $offsets[] = strlen($pdf);
            $pdf .= ($index + 1)." 0 obj\n{$object}\nendobj\n";
        }

        $xref = strlen($pdf);
        $pdf .= "xref\n0 ".(count($objects) + 1)."\n0000000000 65535 f \n";

        foreach ($offsets as $offset) {
            $pdf .= sprintf("%010d 00000 n \n", $offset);
        }

        $pdf .= "trailer\n<< /Size ".(count($objects) + 1)." /Root 1 0 R >>\nstartxref\n{$xref}\n%%EOF\n";

        return $pdf;
    }
}
