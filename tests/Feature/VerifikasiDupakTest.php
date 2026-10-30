<?php

namespace Tests\Feature;

use App\Models\PengajuanDupak;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class VerifikasiDupakTest extends TestCase
{
    use RefreshDatabase;

    private function makeUser(string $role, string $suffix = '1'): User
    {
        return User::create([
            'nidn' => '9'.str_pad((string) (User::max('id') + 1), 9, '0', STR_PAD_LEFT),
            'name' => $role === 'dosen' ? 'Dr. Ayu Lestari, M.Kom.' : 'Admin '.ucfirst($role),
            'email' => $role.$suffix.'@example.test',
            'program_studi' => 'Pendidikan Informatika',
            'jabatan_fungsional' => 'Lektor',
            'role' => $role,
            'is_active' => true,
            'password' => Hash::make('password'),
        ]);
    }

    private function makeDupak(User $dosen, string $status = 'diajukan_dupak', ?string $catatan = null): PengajuanDupak
    {
        $dosen->kegiatanDupak()->create([
            'komponen' => 'pendidikan',
            'tahun' => 2026,
            'semester' => 'ganjil',
            'jenis' => 'pendidikan_formal',
            'nama_kegiatan' => 'Ijazah Magister Teknologi Pendidikan',
            'link_bukti' => 'https://drive.google.com/file/d/ijazah/view',
            'nilai_baru' => 2,
        ]);

        return $dosen->pengajuanDupak()->create([
            'tahun' => 2026,
            'semester' => 'ganjil',
            'status' => $status,
            'diajukan_at' => now()->subDays(5),
            'dinilai_at' => now()->subDays(2),
            'catatan' => $catatan,
        ]);
    }

    public function test_dashboard_admin_menampilkan_ajuan_dupak_dengan_aksi_mata(): void
    {
        $dosen = $this->makeUser('dosen');
        $dupak = $this->makeDupak($dosen);

        $this->actingAs($this->makeUser('admin'))
            ->get('/dashboard')
            ->assertOk()
            ->assertSee('Ajuan DUPAK Masuk')
            ->assertSee($dosen->name)
            ->assertSee('2026/2027')
            ->assertSee(route('verifikasi-dupak.show', $dupak));
    }

    public function test_dashboard_admin_kartu_dan_tabel_mengikuti_status_dupak_dosen(): void
    {
        $ditolak = $this->makeUser('dosen');
        $this->makeDupak($ditolak, 'ditolak', 'Angka kredit kurang.');

        $menunggu = $this->makeUser('dosen', '2');
        $this->makeDupak($menunggu);

        $this->actingAs($this->makeUser('admin'))
            ->get('/dashboard')
            ->assertOk()
            // Kartu menghitung ajuan DUPAK dosen, bukan pengajuan jabatan lama.
            ->assertSee('Total Pengajuan')
            ->assertSee('Menunggu Verifikasi')
            ->assertSee('Ditolak')
            ->assertSee('2 data')
            // Tabel dashboard menampilkan ajuan DUPAK beserta dosen pengusulnya.
            ->assertSee($ditolak->name)
            ->assertSee('Angka kredit kurang.')
            ->assertDontSee('No. Pengajuan');
    }

    public function test_dashboard_dosen_tidak_menampilkan_ajuan_dupak(): void
    {
        $dosen = $this->makeUser('dosen');
        $this->makeDupak($dosen);

        $this->actingAs($dosen)
            ->get('/dashboard')
            ->assertOk()
            ->assertDontSee('Ajuan DUPAK Masuk');
    }

    public function test_dashboard_dosen_menampilkan_riwayat_ajuan_dupak_tanpa_kolom_aksi(): void
    {
        $dosen = $this->makeUser('dosen');
        $this->makeDupak($dosen, 'disetujui');

        $this->actingAs($dosen)
            ->get('/dashboard')
            ->assertOk()
            // Kartu ringkasan mengikuti status ajuan DUPAK.
            ->assertSee('Total Ajuan')
            ->assertSee('Menunggu Verifikasi')
            ->assertSee('Perlu Perbaikan')
            ->assertSee('Ditolak')
            // Tabel riwayat sama dengan menu Riwayat Pengajuan, tanpa kolom aksi.
            ->assertSee('Nilai PAK')
            ->assertSee('Hasil Pengajuan')
            ->assertSee('2026/2027 · Ganjil')
            ->assertDontSee('Aksi');
    }

    public function test_dashboard_tim_pak_memakai_tabel_pengajuan_masuk_tanpa_menu_riwayat(): void
    {
        $dosen = $this->makeUser('dosen');
        $dupak = $this->makeDupak($dosen);

        $this->actingAs($this->makeUser('tim_pak'))
            ->get('/dashboard')
            ->assertOk()
            // Tabelnya sama dengan menu Pengajuan Masuk: ada kolom aksi menuju penilaian.
            ->assertSee('Pengajuan DUPAK Masuk')
            ->assertSee($dosen->name)
            ->assertSee('2026/2027 · Ganjil')
            ->assertSee(route('tim-pak.pengajuan.show', $dupak))
            ->assertSee('Lihat / Ubah')
            // Menu Riwayat Pengajuan tidak tampil lagi untuk TIM PAK.
            ->assertDontSee('Riwayat Pengajuan');
    }

    public function test_admin_melihat_detail_dupak_read_only(): void
    {
        $dosen = $this->makeUser('dosen');
        $dupak = $this->makeDupak($dosen);

        $this->actingAs($this->makeUser('admin'))
            ->get(route('verifikasi-dupak.show', $dupak))
            ->assertOk()
            ->assertSee($dosen->name)
            ->assertSee('Keputusan Verifikasi')
            ->assertSee('Setujui')
            ->assertSee('Butuh Perbaikan')
            ->assertSee('Tolak')
            ->assertSee('Simpan')
            ->assertDontSee('name="nilai[', false);
    }

    public function test_admin_menyetujui_dupak(): void
    {
        $dosen = $this->makeUser('dosen');
        $dupak = $this->makeDupak($dosen);

        $this->actingAs($this->makeUser('admin'))
            ->put(route('verifikasi-dupak.update', $dupak), ['keputusan' => 'disetujui'])
            ->assertRedirect(route('verifikasi-dupak.show', $dupak))
            ->assertSessionHas('status');

        $dupak->refresh();

        $this->assertSame('disetujui', $dupak->status);
        $this->assertNull($dupak->catatan);

        $this->actingAs($this->makeUser('admin', '2'))
            ->get('/dashboard')
            ->assertSee('Disetujui');
    }

    public function test_butuh_perbaikan_wajib_menyertakan_alasan(): void
    {
        $dosen = $this->makeUser('dosen');
        $dupak = $this->makeDupak($dosen);

        $this->actingAs($this->makeUser('admin'))
            ->put(route('verifikasi-dupak.update', $dupak), ['keputusan' => 'perlu_perbaikan'])
            ->assertSessionHasErrors('catatan');

        $this->assertSame('diajukan_dupak', $dupak->fresh()->status);
    }

    public function test_admin_meminta_perbaikan_dengan_alasan(): void
    {
        $dosen = $this->makeUser('dosen');
        $dupak = $this->makeDupak($dosen);

        $this->actingAs($this->makeUser('admin'))
            ->put(route('verifikasi-dupak.update', $dupak), [
                'keputusan' => 'perlu_perbaikan',
                'catatan' => 'Bukti publikasi belum dilampirkan.',
            ])
            ->assertSessionHas('status');

        $dupak->refresh();

        $this->assertSame('perlu_perbaikan', $dupak->status);
        $this->assertSame('Bukti publikasi belum dilampirkan.', $dupak->catatan);
    }

    public function test_admin_menolak_dupak_dengan_alasan(): void
    {
        $dosen = $this->makeUser('dosen');
        $dupak = $this->makeDupak($dosen);

        $this->actingAs($this->makeUser('admin'))
            ->put(route('verifikasi-dupak.update', $dupak), [
                'keputusan' => 'ditolak',
                'catatan' => 'Angka kredit tidak memenuhi syarat jabatan tujuan.',
            ]);

        $dupak->refresh();

        $this->assertSame('ditolak', $dupak->status);
        $this->assertSame('Angka kredit tidak memenuhi syarat jabatan tujuan.', $dupak->catatan);

        $this->actingAs($this->makeUser('admin', '2'))
            ->get('/dashboard')
            ->assertSee('Ditolak')
            ->assertSee('Angka kredit tidak memenuhi syarat jabatan tujuan.');
    }

    public function test_dosen_dan_tim_pak_tidak_boleh_memutuskan_dupak(): void
    {
        $dosen = $this->makeUser('dosen');
        $dupak = $this->makeDupak($dosen);

        $this->actingAs($dosen)
            ->put(route('verifikasi-dupak.update', $dupak), ['keputusan' => 'disetujui'])
            ->assertForbidden();

        $this->actingAs($this->makeUser('tim_pak'))
            ->put(route('verifikasi-dupak.update', $dupak), ['keputusan' => 'disetujui'])
            ->assertForbidden();

        $this->assertSame('diajukan_dupak', $dupak->fresh()->status);
    }

    public function test_dupak_tetap_tampil_di_riwayat_dosen_setelah_diputuskan(): void
    {
        $dosen = $this->makeUser('dosen');
        $dupak = $this->makeDupak($dosen);

        $this->actingAs($this->makeUser('admin'))
            ->put(route('verifikasi-dupak.update', $dupak), [
                'keputusan' => 'perlu_perbaikan',
                'catatan' => 'Lengkapi bukti kegiatan.',
            ]);

        $this->actingAs($dosen)
            ->get('/profil/pengajuan/riwayat')
            ->assertOk()
            ->assertSee('Butuh Perbaikan');
    }
}
