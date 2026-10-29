<?php

namespace Tests\Feature;

use App\Models\KegiatanDupak;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class PengajuanTimPakTest extends TestCase
{
    use RefreshDatabase;

    private function makeUser(string $role, string $suffix = '1'): User
    {
        return User::create([
            'nidn' => '9'.str_pad((string) (User::max('id') + 1), 9, '0', STR_PAD_LEFT),
            'name' => $role === 'tim_pak' ? 'Prof. Budi Santoso, M.T.' : 'Dr. Ayu Lestari, M.Kom.',
            'email' => $role.$suffix.'@example.test',
            'program_studi' => 'Pendidikan Informatika',
            'jabatan_fungsional' => 'Lektor',
            'role' => $role,
            'is_active' => true,
            'password' => Hash::make('password'),
        ]);
    }

    private function makeKegiatan(User $user, array $attributes = []): KegiatanDupak
    {
        return $user->kegiatanDupak()->create([
            'komponen' => 'pendidikan',
            'tahun' => 2026,
            'semester' => 'ganjil',
            'jenis' => 'pendidikan_formal',
            'nama_kegiatan' => 'Ijazah Magister Teknologi Pendidikan',
            'link_bukti' => 'https://drive.google.com/file/d/ijazah/view',
            ...$attributes,
        ]);
    }

    public function test_dosen_can_send_usulan_to_tim_pak(): void
    {
        $dosen = $this->makeUser('dosen');
        $this->makeKegiatan($dosen);

        $this->actingAs($dosen)
            ->post('/profil/pengajuan/ajukan', ['tahun' => 2026, 'semester' => 'ganjil'])
            ->assertRedirect(route('profil.pengajuan.create', ['tahun' => 2026, 'semester' => 'ganjil']).'#review')
            ->assertSessionHas('status');

        $this->assertDatabaseHas('pengajuan_dupak', [
            'user_id' => $dosen->id,
            'tahun' => 2026,
            'semester' => 'ganjil',
            'status' => 'diajukan',
        ]);

        $this->actingAs($dosen)
            ->get('/profil/pengajuan/create?tahun=2026&semester=ganjil')
            ->assertOk()
            ->assertSee('Menunggu penilaian');
    }

    public function test_usulan_ditolak_bila_periode_belum_punya_kegiatan(): void
    {
        $dosen = $this->makeUser('dosen');

        $this->actingAs($dosen)
            ->post('/profil/pengajuan/ajukan', ['tahun' => 2026, 'semester' => 'ganjil'])
            ->assertSessionHas('error');

        $this->assertDatabaseCount('pengajuan_dupak', 0);
    }

    public function test_dosen_tidak_boleh_membuka_halaman_penilaian_tim_pak(): void
    {
        $dosen = $this->makeUser('dosen');

        $this->actingAs($dosen)->get('/tim-pak/pengajuan')->assertForbidden();

        // Detail pengajuan tidak terbuka untuk dosen (404 karena tidak ada data / middleware).
        $this->actingAs($dosen)->get('/tim-pak/pengajuan/1')->assertNotFound();
    }

    public function test_tim_pak_melihat_daftar_pengajuan_masuk(): void
    {
        $dosen = $this->makeUser('dosen');
        $this->makeKegiatan($dosen);

        $this->actingAs($dosen)->post('/profil/pengajuan/ajukan', ['tahun' => 2026, 'semester' => 'ganjil']);

        $this->actingAs($this->makeUser('tim_pak'))
            ->get('/tim-pak/pengajuan')
            ->assertOk()
            ->assertSee('Pengajuan DUPAK Masuk')
            ->assertSee($dosen->name)
            ->assertSee('2026/2027 · Ganjil')
            ->assertSee('Menunggu penilaian');
    }

    public function test_tim_pak_menyimpan_nilai_dan_jumlah_terhitung_otomatis(): void
    {
        $dosen = $this->makeUser('dosen');
        $formal = $this->makeKegiatan($dosen);
        $lain = $this->makeKegiatan($dosen, ['nama_kegiatan' => 'Ijazah Doktor Teknologi Pendidikan']);

        $this->actingAs($dosen)->post('/profil/pengajuan/ajukan', ['tahun' => 2026, 'semester' => 'ganjil']);

        $pengajuan = $dosen->pengajuanDupak()->firstOrFail();

        $this->actingAs($this->makeUser('tim_pak'))
            ->put('/tim-pak/pengajuan/'.$pengajuan->id, [
                'nilai' => [$formal->id => '1.5', $lain->id => '2.5'],
            ])
            ->assertRedirect(route('tim-pak.pengajuan.show', $pengajuan))
            ->assertSessionHas('status');

        $this->assertEquals(1.5, (float) $formal->fresh()->nilai_baru);
        $this->assertEquals(2.5, (float) $lain->fresh()->nilai_baru);
        $this->assertSame('dinilai', $pengajuan->fresh()->status);

        // Halaman penilaian menampilkan input nilai dan total bidang (1,5 + 2,5 = 4).
        $this->actingAs($this->makeUser('tim_pak', '2'))
            ->get('/tim-pak/pengajuan/'.$pengajuan->id)
            ->assertOk()
            ->assertSee('name="nilai['.$formal->id.']"', false)
            ->assertSee('data-jumlah-total="g0">4<', false);
    }

    public function test_nilai_hanya_tersimpan_untuk_kegiatan_pada_periode_pengajuan(): void
    {
        $dosen = $this->makeUser('dosen');
        $periode = $this->makeKegiatan($dosen);
        $periodeLain = $this->makeKegiatan($dosen, ['semester' => 'genap']);

        $this->actingAs($dosen)->post('/profil/pengajuan/ajukan', ['tahun' => 2026, 'semester' => 'ganjil']);
        $pengajuan = $dosen->pengajuanDupak()->firstOrFail();

        $this->actingAs($this->makeUser('tim_pak'))
            ->put('/tim-pak/pengajuan/'.$pengajuan->id, [
                'nilai' => [$periode->id => '2', $periodeLain->id => '9'],
            ]);

        $this->assertEquals(2, (float) $periode->fresh()->nilai_baru);
        $this->assertNull($periodeLain->fresh()->nilai_baru);
    }

    public function test_dosen_melihat_nilai_dari_tim_pak_pada_tab_review(): void
    {
        $dosen = $this->makeUser('dosen');
        $kegiatan = $this->makeKegiatan($dosen);

        $this->actingAs($dosen)->post('/profil/pengajuan/ajukan', ['tahun' => 2026, 'semester' => 'ganjil']);
        $pengajuan = $dosen->pengajuanDupak()->firstOrFail();

        $this->actingAs($this->makeUser('tim_pak'))
            ->put('/tim-pak/pengajuan/'.$pengajuan->id, ['nilai' => [$kegiatan->id => '3']]);

        $this->actingAs($dosen)
            ->get('/profil/pengajuan/create?tahun=2026&semester=ganjil')
            ->assertOk()
            ->assertSee('Sudah dinilai')
            ->assertSee('data-jumlah-total="g0">3<', false)
            ->assertDontSee('name="nilai['.$kegiatan->id.']"', false);
    }

    public function test_jumlah_total_mengakumulasi_seluruh_bidang(): void
    {
        $dosen = $this->makeUser('dosen');

        // Bidang I (Pendidikan) bernilai 1, Bidang III (Penelitian) bernilai 3 → total 4.
        $pendidikan = $this->makeKegiatan($dosen, ['nilai_baru' => 1]);
        $penelitian = $this->makeKegiatan($dosen, [
            'komponen' => 'penelitian',
            'jenis' => 'karya_ilmiah',
            'sub_jenis' => 'dipublikasikan',
            'rincian' => 'jurnal_internasional',
            'nama_kegiatan' => 'Artikel pada Jurnal Ilmiah Internasional Bereputasi',
            'nilai_baru' => 3,
        ]);

        $this->actingAs($dosen)->post('/profil/pengajuan/ajukan', ['tahun' => 2026, 'semester' => 'ganjil']);
        $pengajuan = $dosen->pengajuanDupak()->firstOrFail();

        // Halaman penilaian TIM PAK: jumlah per bidang 1 dan 3, jumlah total 4.
        $this->actingAs($this->makeUser('tim_pak'))
            ->get('/tim-pak/pengajuan/'.$pengajuan->id)
            ->assertOk()
            ->assertSee('Jumlah Total')
            ->assertSee('data-jumlah-total="g0">1<', false)
            ->assertSee('data-jumlah-total="g2">3<', false)
            ->assertSee('data-jumlah-grand-total>4<', false);

        // Dosen melihat akumulasi yang sama pada tab Review.
        $this->actingAs($dosen)
            ->get('/profil/pengajuan/create?tahun=2026&semester=ganjil')
            ->assertOk()
            ->assertSee('data-jumlah-grand-total>4<', false);

        $this->assertEquals(4, (float) ($pendidikan->fresh()->nilai_baru + $penelitian->fresh()->nilai_baru));
    }

    public function test_ajukan_dupak_hanya_setelah_nilai_tersedia(): void
    {
        $dosen = $this->makeUser('dosen');
        $kegiatan = $this->makeKegiatan($dosen);

        // Belum diajukan ke TIM PAK.
        $this->actingAs($dosen)
            ->post('/profil/pengajuan/ajukan-dupak', ['tahun' => 2026, 'semester' => 'ganjil'])
            ->assertSessionHas('error');

        $this->actingAs($dosen)->post('/profil/pengajuan/ajukan', ['tahun' => 2026, 'semester' => 'ganjil']);
        $pengajuan = $dosen->pengajuanDupak()->firstOrFail();

        $this->actingAs($this->makeUser('tim_pak'))
            ->put('/tim-pak/pengajuan/'.$pengajuan->id, ['nilai' => [$kegiatan->id => '2']]);

        $this->actingAs($dosen)
            ->post('/profil/pengajuan/ajukan-dupak', ['tahun' => 2026, 'semester' => 'ganjil'])
            ->assertSessionHas('status');

        $this->assertSame('diajukan_dupak', $pengajuan->fresh()->status);
    }

    public function test_tim_pak_hanya_bisa_melihat_setelah_dupak_disetujui(): void
    {
        $dosen = $this->makeUser('dosen');
        $kegiatan = $this->makeKegiatan($dosen, ['nilai_baru' => 2]);

        $this->actingAs($dosen)->post('/profil/pengajuan/ajukan', ['tahun' => 2026, 'semester' => 'ganjil']);
        $pengajuan = $dosen->pengajuanDupak()->firstOrFail();
        $pengajuan->update(['status' => 'disetujui']);

        $timPak = $this->makeUser('tim_pak');

        // Daftar pengajuan: aksi berubah menjadi Lihat saja.
        $this->actingAs($timPak)
            ->get('/tim-pak/pengajuan')
            ->assertOk()
            ->assertSee('Lihat')
            ->assertDontSee('Lihat / Ubah');

        // Halaman penilaian: read-only, tanpa input nilai, tombol Update Nilai nonaktif.
        $this->actingAs($timPak)
            ->get('/tim-pak/pengajuan/'.$pengajuan->id)
            ->assertOk()
            ->assertSee('hanya untuk dilihat')
            ->assertDontSee('name="nilai[', false)
            ->assertSee('text-sm" disabled>', false);

        // Simpan nilai ditolak walau tombolnya dipaksa lewat request langsung.
        $this->actingAs($timPak)
            ->put('/tim-pak/pengajuan/'.$pengajuan->id, ['nilai' => [$kegiatan->id => '9']])
            ->assertSessionHas('error');

        $this->assertSame(2.0, (float) $kegiatan->fresh()->nilai_baru);
        $this->assertSame('disetujui', $pengajuan->fresh()->status);
    }

    public function test_dosen_dapat_mencetak_dupak_periode_terpilih(): void
    {
        $dosen = $this->makeUser('dosen');
        $penilai = $this->makeUser('tim_pak');
        $kegiatan = $this->makeKegiatan($dosen, ['nilai_baru' => 2]);

        $this->actingAs($dosen)->post('/profil/pengajuan/ajukan', ['tahun' => 2026, 'semester' => 'ganjil']);

        $this->actingAs($dosen)
            ->get('/profil/pengajuan/cetak?tahun=2026&semester=ganjil')
            ->assertOk()
            ->assertSee('Daftar Usulan Penetapan Angka Kredit (DUPAK)')
            ->assertSee($kegiatan->nama_kegiatan)
            ->assertSee('2026/2027 · Ganjil')
            // Blok tanda tangan: NIDN dosen pengusul dan nama + NIDN penilai TIM PAK.
            ->assertSee('NIDN '.$dosen->nidn)
            ->assertSee($penilai->name)
            ->assertSee('NIDN '.$penilai->nidn);

        // Periode tanpa parameter valid dikembalikan ke pemilihan periode.
        $this->actingAs($dosen)
            ->get('/profil/pengajuan/cetak?tahun=2026&semester=pendek')
            ->assertRedirect(route('profil.pengajuan.periode'));
    }
}
