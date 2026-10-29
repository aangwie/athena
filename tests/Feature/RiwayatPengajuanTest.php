<?php

namespace Tests\Feature;

use App\Models\KegiatanDupak;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class RiwayatPengajuanTest extends TestCase
{
    use RefreshDatabase;

    private function makeDosen(string $suffix = '1'): User
    {
        return User::create([
            'nidn' => '19876543'.$suffix,
            'name' => 'Dr. Ayu Lestari, M.Kom.',
            'email' => 'dosen'.$suffix.'@example.test',
            'program_studi' => 'Pendidikan Informatika',
            'jabatan_fungsional' => 'Lektor',
            'role' => 'dosen',
            'is_active' => true,
            'password' => Hash::make('password'),
        ]);
    }

    private function makeKegiatan(User $user, float $nilai, array $attributes = []): KegiatanDupak
    {
        return $user->kegiatanDupak()->create([
            'komponen' => 'pendidikan',
            'tahun' => 2026,
            'semester' => 'ganjil',
            'jenis' => 'pendidikan_formal',
            'nama_kegiatan' => 'Ijazah Magister Teknologi Pendidikan',
            'link_bukti' => 'https://drive.google.com/file/d/ijazah/view',
            'nilai_baru' => $nilai,
            ...$attributes,
        ]);
    }

    public function test_dosen_can_open_riwayat_page_with_two_tabs(): void
    {
        $this->actingAs($this->makeDosen())
            ->get(route('profil.pengajuan.riwayat'))
            ->assertOk()
            ->assertSee('Riwayat Nilai PAK')
            ->assertSee('Riwayat Ajuan DUPAK')
            ->assertSee('data-tab-target="nilai-pak"', false)
            ->assertSee('data-tab-target="dupak"', false);
    }

    public function test_riwayat_nilai_pak_lists_periode_and_total_nilai(): void
    {
        $dosen = $this->makeDosen();
        $this->makeKegiatan($dosen, 1);
        $this->makeKegiatan($dosen, 2.5, ['nama_kegiatan' => 'Ijazah Doktor Teknologi Pendidikan']);

        $dosen->pengajuanDupak()->create([
            'tahun' => 2026,
            'semester' => 'ganjil',
            'status' => 'dinilai',
            'diajukan_at' => now()->subDays(10),
            'dinilai_at' => now()->subDays(2),
        ]);

        $this->actingAs($dosen)
            ->get(route('profil.pengajuan.riwayat'))
            ->assertOk()
            ->assertSee('2026/2027 · Ganjil')
            ->assertSee('3,5')
            ->assertSee('Sudah dinilai')
            ->assertSee('Ajukan Ulang')
            ->assertSee('/profil/pengajuan/create?tahun=2026&amp;semester=ganjil', false);
    }

    public function test_dosen_can_ajukan_ulang_nilai_pak(): void
    {
        $dosen = $this->makeDosen();
        $this->makeKegiatan($dosen, 2);

        $pengajuan = $dosen->pengajuanDupak()->create([
            'tahun' => 2026,
            'semester' => 'ganjil',
            'status' => 'dinilai',
            'diajukan_at' => now()->subDays(10),
            'dinilai_at' => now()->subDays(2),
        ]);

        $this->actingAs($dosen)
            ->post(route('profil.pengajuan.ajukan-ulang'), ['tahun' => 2026, 'semester' => 'ganjil'])
            ->assertRedirect(route('profil.pengajuan.riwayat'))
            ->assertSessionHas('status');

        $pengajuan->refresh();

        $this->assertSame('diajukan', $pengajuan->status);
        $this->assertNull($pengajuan->dinilai_at);
    }

    public function test_ajukan_ulang_ditolak_bila_nilai_pak_belum_ada(): void
    {
        $dosen = $this->makeDosen();
        $this->makeKegiatan($dosen, 2);

        $pengajuan = $dosen->pengajuanDupak()->create([
            'tahun' => 2026,
            'semester' => 'ganjil',
            'status' => 'diajukan',
            'diajukan_at' => now()->subDays(10),
        ]);

        $this->actingAs($dosen)
            ->post(route('profil.pengajuan.ajukan-ulang'), ['tahun' => 2026, 'semester' => 'ganjil'])
            ->assertRedirect(route('profil.pengajuan.riwayat'))
            ->assertSessionHas('error');

        $this->assertSame('diajukan', $pengajuan->fresh()->status);
    }

    public function test_riwayat_ajuan_dupak_lists_submitted_dupak(): void
    {
        $dosen = $this->makeDosen();
        $this->makeKegiatan($dosen, 4);

        $dosen->pengajuanDupak()->create([
            'tahun' => 2025,
            'semester' => 'genap',
            'status' => 'diajukan_dupak',
            'diajukan_at' => now()->subMonths(6),
            'dinilai_at' => now()->subMonths(3),
        ]);

        $this->actingAs($dosen)
            ->get(route('profil.pengajuan.riwayat'))
            ->assertOk()
            ->assertSee('2025/2026 · Genap')
            ->assertSee('Diajukan DUPAK')
            ->assertSee('Lihat')
            ->assertSee('Cetak PDF')
            ->assertSee('/profil/pengajuan/cetak?tahun=2025&amp;semester=genap', false)
            ->assertSee('/profil/pengajuan/cetak?tahun=2025&amp;semester=genap&amp;print=1', false);
    }

    public function test_halaman_lihat_dupak_otomatis_membuka_dialog_cetak_bila_print(): void
    {
        $dosen = $this->makeDosen();
        $this->makeKegiatan($dosen, 4);

        $this->actingAs($dosen)
            ->get(route('profil.pengajuan.cetak', ['tahun' => 2026, 'semester' => 'ganjil']))
            ->assertOk()
            ->assertDontSee('window.addEventListener', false);

        $this->actingAs($dosen)
            ->get(route('profil.pengajuan.cetak', ['tahun' => 2026, 'semester' => 'ganjil', 'print' => 1]))
            ->assertOk()
            ->assertSee('window.addEventListener', false);
    }

    public function test_perbaikan_dan_ajukan_ulang_nonaktif_setelah_dupak_disetujui(): void
    {
        $dosen = $this->makeDosen();
        $this->makeKegiatan($dosen, 4);

        $pengajuan = $dosen->pengajuanDupak()->create([
            'tahun' => 2026,
            'semester' => 'ganjil',
            'status' => 'disetujui',
            'diajukan_at' => now()->subDays(10),
            'dinilai_at' => now()->subDays(2),
        ]);

        // Tombol Perbaikan tidak lagi berupa tautan ke halaman entri.
        $this->actingAs($dosen)
            ->get(route('profil.pengajuan.riwayat'))
            ->assertOk()
            ->assertSee('Perbaikan')
            ->assertSee('pointer-events-none', false)
            ->assertDontSee('/profil/pengajuan/create?tahun=2026', false);

        // Tombol Ajukan ke TIM PAK nonaktif pada tab Review periode tersebut.
        $this->actingAs($dosen)
            ->get(route('profil.pengajuan.create', ['tahun' => 2026, 'semester' => 'ganjil']))
            ->assertOk()
            ->assertSee('disabled>Ajukan ke TIM PAK', false);

        // Kedua aksi juga ditolak di server.
        $this->actingAs($dosen)
            ->post(route('profil.pengajuan.ajukan'), ['tahun' => 2026, 'semester' => 'ganjil'])
            ->assertSessionHas('error');

        $this->actingAs($dosen)
            ->post(route('profil.pengajuan.ajukan-ulang'), ['tahun' => 2026, 'semester' => 'ganjil'])
            ->assertSessionHas('error');

        $this->assertSame('disetujui', $pengajuan->fresh()->status);
    }

    public function test_riwayat_only_lists_own_pengajuan(): void
    {
        $dosen = $this->makeDosen();
        $lain = $this->makeDosen('2');

        $lain->pengajuanDupak()->create([
            'tahun' => 2024,
            'semester' => 'genap',
            'status' => 'dinilai',
            'diajukan_at' => now()->subYears(2),
            'dinilai_at' => now()->subYears(2),
        ]);

        $this->actingAs($dosen)
            ->get(route('profil.pengajuan.riwayat'))
            ->assertOk()
            ->assertDontSee('2024/2025 · Genap');
    }

    public function test_admin_cannot_open_riwayat_pengajuan_page(): void
    {
        $admin = User::create([
            'nidn' => '0000000001',
            'name' => 'Admin Kepegawaian',
            'email' => 'admin@example.test',
            'role' => 'admin',
            'is_active' => true,
            'password' => Hash::make('password'),
        ]);

        $this->actingAs($admin)
            ->get(route('profil.pengajuan.riwayat'))
            ->assertForbidden();
    }
}
