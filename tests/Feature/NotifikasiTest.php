<?php

namespace Tests\Feature;

use App\Models\PengajuanDupak;
use App\Models\SyaratKhusus;
use App\Models\User;
use App\Support\Notifikasi;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class NotifikasiTest extends TestCase
{
    use RefreshDatabase;

    private function makeUser(string $role, string $suffix = '1'): User
    {
        return User::create([
            'nidn' => '9'.str_pad((string) (User::max('id') + 1), 9, '0', STR_PAD_LEFT),
            'name' => 'Dr. Ayu Lestari, M.Kom.',
            'email' => $role.$suffix.'@example.test',
            'program_studi' => 'Pendidikan Informatika',
            'jabatan_fungsional' => 'Lektor',
            'role' => $role,
            'is_active' => true,
            'password' => Hash::make('password'),
        ]);
    }

    private function makeDupak(User $dosen, string $status, int $tahun = 2026): PengajuanDupak
    {
        return $dosen->pengajuanDupak()->create([
            'tahun' => $tahun,
            'semester' => 'ganjil',
            'status' => $status,
            'diajukan_at' => now()->subDays(5),
            'dinilai_at' => now()->subDays(2),
        ]);
    }

    private function makeBerkas(User $dosen, string $jenis, string $status, int $tahun = 2026): SyaratKhusus
    {
        return SyaratKhusus::create([
            'user_id' => $dosen->id,
            'tahun' => $tahun,
            'semester' => 'ganjil',
            'bkd_status' => $jenis === 'bkd' ? $status : 'draf',
            'ikd_status' => $jenis === 'ikd' ? $status : 'draf',
        ]);
    }

    public function test_lonceng_admin_menampilkan_ajuan_dupak_dan_ikd(): void
    {
        $dosen = $this->makeUser('dosen');
        $this->makeDupak($dosen, 'diajukan_dupak');
        $this->makeBerkas($dosen, 'ikd', 'diajukan');

        $this->actingAs($this->makeUser('admin'))
            ->get('/dashboard')
            ->assertOk()
            ->assertSee('Pengajuan DUPAK masuk')
            ->assertSee('Pengajuan IKD masuk')
            ->assertSee('Notifikasi (2 baru)');
    }

    public function test_lonceng_tim_pak_menampilkan_ajuan_penilaian_pak(): void
    {
        $dosen = $this->makeUser('dosen');
        $this->makeDupak($dosen, 'diajukan');

        $this->actingAs($this->makeUser('tim_pak'))
            ->get('/dashboard')
            ->assertOk()
            ->assertSee('Ajuan penilaian PAK')
            ->assertSee('Notifikasi (1 baru)');
    }

    public function test_lonceng_dosen_hanya_menampilkan_respon_baik(): void
    {
        $dosen = $this->makeUser('dosen');
        $this->makeDupak($dosen, 'dinilai');
        $this->makeDupak($dosen, 'ditolak', 2025);
        $this->makeBerkas($dosen, 'ikd', 'diterima');

        $judul = Notifikasi::untuk($dosen->fresh())->pluck('judul')->sort()->values()->all();

        $this->assertSame([
            'Berkas IKD diterima',
            'Nilai PAK sudah dinilai TIM PAK',
        ], $judul);
    }

    public function test_lonceng_dosen_menampilkan_berkas_ditolak_dan_dihapus_admin(): void
    {
        $dosen = $this->makeUser('dosen');

        // Ditolak admin: berkas masih tersimpan, dosen cukup memperbaiki.
        SyaratKhusus::create([
            'user_id' => $dosen->id,
            'tahun' => 2026,
            'semester' => 'ganjil',
            'bkd_path' => 'syarat-khusus/2026/ganjil/bkd.pdf',
            'bkd_status' => 'ditolak',
            'bkd_catatan' => 'Scan BKD buram, unggah ulang.',
            'ikd_status' => 'draf',
        ]);

        // Dihapus admin: berkas sudah tidak ada, dosen harus unggah ulang.
        SyaratKhusus::create([
            'user_id' => $dosen->id,
            'tahun' => 2026,
            'semester' => 'genap',
            'bkd_status' => 'draf',
            'ikd_status' => 'ditolak',
            'ikd_catatan' => 'Berkas dihapus admin. Silakan unggah ulang berkas IKD.',
        ]);

        $notifikasi = Notifikasi::untuk($dosen->fresh());

        $this->assertSame([
            'Berkas BKD ditolak',
            'Berkas IKD dihapus admin',
        ], $notifikasi->pluck('judul')->sort()->values()->all());

        $this->assertStringContainsString(
            'Scan BKD buram, unggah ulang.',
            $notifikasi->firstWhere('judul', 'Berkas BKD ditolak')['keterangan'],
        );
        $this->assertStringContainsString(
            'unggah ulang berkas IKD',
            $notifikasi->firstWhere('judul', 'Berkas IKD dihapus admin')['keterangan'],
        );
    }

    public function test_membuka_notifikasi_menghapus_tanda_merah_lonceng(): void
    {
        $dosen = $this->makeUser('dosen');
        $this->makeDupak($dosen, 'diajukan_dupak');

        $admin = $this->makeUser('admin');

        $this->actingAs($admin)->get('/dashboard')->assertSee('Notifikasi (1 baru)');

        $this->postJson(route('notifikasi.baca'))
            ->assertOk()
            ->assertJson(['status' => 'ok']);

        $this->assertNotNull($admin->fresh()->notifikasi_dibaca_at);

        $this->get('/dashboard')
            ->assertOk()
            ->assertSee('0 baru')
            ->assertDontSee('Notifikasi (1 baru)');
    }
}
