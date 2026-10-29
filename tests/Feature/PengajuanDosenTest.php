<?php

namespace Tests\Feature;

use App\Http\Controllers\PengajuanDosenController;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class PengajuanDosenTest extends TestCase
{
    use RefreshDatabase;

    private function makeUser(string $role): User
    {
        return User::create([
            'nidn' => $role === 'dosen' ? '1987654321' : '0000000001',
            'name' => $role === 'dosen' ? 'Dr. Ayu Lestari, M.Kom.' : 'Admin Kepegawaian',
            'email' => $role.'@example.test',
            'program_studi' => 'Pendidikan Informatika',
            'jabatan_fungsional' => 'Lektor',
            'role' => $role,
            'is_active' => true,
            'password' => Hash::make('password'),
        ]);
    }

    public function test_dosen_can_open_pengajuan_baru_page_with_four_komponen_tabs(): void
    {
        $response = $this->actingAs($this->makeUser('dosen'))
            ->get('/profil/pengajuan/create?tahun=2026&semester=ganjil')
            ->assertOk()
            ->assertSee('Pengajuan Baru')
            ->assertSee('2026/2027 · Ganjil')
            ->assertSee('Ubah Periode');

        foreach (PengajuanDosenController::KOMPONEN as $key => $item) {
            $response->assertSee('data-tab-target="'.$key.'"', false)
                ->assertSee('data-tab-panel="'.$key.'"', false)
                ->assertSee($item['label']);
        }

        $this->assertCount(4, PengajuanDosenController::KOMPONEN);
    }

    public function test_dashboard_pengajuan_baru_button_opens_periode_selection(): void
    {
        $this->actingAs($this->makeUser('dosen'))
            ->get('/dashboard')
            ->assertOk()
            ->assertSee(route('profil.pengajuan.periode'), false);
    }

    public function test_pengajuan_baru_requires_tahun_and_semester_selection_first(): void
    {
        $dosen = $this->makeUser('dosen');

        $this->actingAs($dosen)
            ->get('/profil/pengajuan/create')
            ->assertRedirect(route('profil.pengajuan.periode'));

        $this->actingAs($dosen)
            ->get('/profil/pengajuan/create?tahun=2026&semester=semester_palsu')
            ->assertRedirect(route('profil.pengajuan.periode'));

        $this->actingAs($dosen)
            ->get('/profil/pengajuan/periode')
            ->assertOk()
            ->assertSee('Pilih Periode Pengajuan')
            ->assertSee('Tahun Akademik')
            ->assertSee('name="tahun"', false)
            ->assertSee('name="semester"', false);
    }

    public function test_admin_cannot_open_pengajuan_baru_page(): void
    {
        $this->actingAs($this->makeUser('admin'))
            ->get('/profil/pengajuan/create')
            ->assertForbidden();
    }

    public function test_dosen_can_add_pendidikan_entry_with_link_bukti(): void
    {
        $dosen = $this->makeUser('dosen');

        $this->actingAs($dosen)
            ->post('/profil/pengajuan/pendidikan', [
                'tahun' => 2026,
                'semester' => 'ganjil',
                'jenis' => 'diklat_prajabatan',
                'nama_kegiatan' => 'Diklat Prajabatan CPNS Golongan III Angkatan XLV',
                'link_bukti' => 'https://drive.google.com/file/d/abc123/view',
            ])
            ->assertRedirect(route('profil.pengajuan.create', ['tahun' => 2026, 'semester' => 'ganjil']).'#pendidikan');

        $this->assertDatabaseHas('kegiatan_dupak', [
            'user_id' => $dosen->id,
            'komponen' => 'pendidikan',
            'tahun' => 2026,
            'semester' => 'ganjil',
            'jenis' => 'diklat_prajabatan',
            'nama_kegiatan' => 'Diklat Prajabatan CPNS Golongan III Angkatan XLV',
            'link_bukti' => 'https://drive.google.com/file/d/abc123/view',
        ]);

        $this->actingAs($dosen)
            ->get('/profil/pengajuan/create?tahun=2026&semester=ganjil')
            ->assertOk()
            ->assertSee('Diklat Prajabatan CPNS Golongan III Angkatan XLV')
            ->assertSee('Pendidikan dan Pelatihan Prajabatan')
            ->assertSee('2026/2027 · Ganjil')
            ->assertSee('https://drive.google.com/file/d/abc123/view')
            ->assertSee('data-confirm="Hapus entri Pendidikan dan Pelatihan Prajabatan?"', false);
    }

    public function test_pendidikan_entry_requires_valid_jenis_nama_kegiatan_and_link(): void
    {
        $this->actingAs($this->makeUser('dosen'))
            ->from('/profil/pengajuan/create?tahun=2026&semester=ganjil')
            ->post('/profil/pengajuan/pendidikan', ['jenis' => 'gelar_palsu', 'link_bukti' => 'bukan-link'])
            ->assertRedirect('/profil/pengajuan/create?tahun=2026&semester=ganjil')
            ->assertSessionHasErrors(['tahun', 'semester', 'jenis', 'nama_kegiatan', 'link_bukti']);

        $this->assertDatabaseCount('kegiatan_dupak', 0);
    }

    public function test_dosen_can_open_edit_form_prefilled_with_own_entry(): void
    {
        $dosen = $this->makeUser('dosen');

        $entri = $dosen->kegiatanDupak()->create([
            'komponen' => 'pendidikan',
            'tahun' => 2025,
            'semester' => 'genap',
            'jenis' => 'pendidikan_formal',
            'nama_kegiatan' => 'Ijazah S1 Pendidikan Informatika',
            'link_bukti' => 'https://example.test/ijazah.pdf',
        ]);

        $this->actingAs($dosen)
            ->get('/profil/pengajuan/create?edit='.$entri->id)
            ->assertOk()
            ->assertSee('Edit Entri Bidang A')
            ->assertSee('Simpan Perubahan')
            ->assertSee('2025/2026 · Genap')
            ->assertSee('name="tahun" value="2025"', false)
            ->assertSee('name="semester" value="genap"', false)
            ->assertSee('value="Ijazah S1 Pendidikan Informatika"', false)
            ->assertSee('value="https://example.test/ijazah.pdf"', false)
            ->assertSee(route('profil.pengajuan.pendidikan.update', $entri), false)
            ->assertSee('aria-label="Edit entri Pendidikan Formal"', false)
            ->assertSee('aria-label="Hapus entri Pendidikan Formal"', false);
    }

    public function test_dosen_can_update_own_pendidikan_entry_only(): void
    {
        $dosen = $this->makeUser('dosen');
        $lain = User::create([
            'nidn' => '1990010120',
            'name' => 'Dosen Lain',
            'email' => 'lain@example.test',
            'role' => 'dosen',
            'is_active' => true,
            'password' => Hash::make('password'),
        ]);

        $entri = $dosen->kegiatanDupak()->create([
            'komponen' => 'pendidikan',
            'tahun' => 2025,
            'semester' => 'genap',
            'jenis' => 'pendidikan_formal',
            'nama_kegiatan' => 'Ijazah S1 Pendidikan Informatika',
            'link_bukti' => 'https://example.test/ijazah.pdf',
        ]);

        $perubahan = [
            'tahun' => 2025,
            'semester' => 'genap',
            'jenis' => 'diklat_prajabatan',
            'nama_kegiatan' => 'Diklat Prajabatan CPNS Golongan III Angkatan XLVI',
            'link_bukti' => 'https://drive.google.com/file/d/baru456/view',
        ];

        $this->actingAs($lain)
            ->put('/profil/pengajuan/pendidikan/'.$entri->id, $perubahan)
            ->assertNotFound();

        $this->actingAs($dosen)
            ->put('/profil/pengajuan/pendidikan/'.$entri->id, $perubahan)
            ->assertRedirect(route('profil.pengajuan.create', ['tahun' => 2025, 'semester' => 'genap']).'#pendidikan');

        $this->assertDatabaseHas('kegiatan_dupak', [
            'id' => $entri->id,
            'user_id' => $dosen->id,
            'komponen' => 'pendidikan',
            ...$perubahan,
        ]);
        $this->assertDatabaseCount('kegiatan_dupak', 1);
    }

    public function test_pendidikan_update_requires_valid_data(): void
    {
        $dosen = $this->makeUser('dosen');

        $entri = $dosen->kegiatanDupak()->create([
            'komponen' => 'pendidikan',
            'tahun' => 2025,
            'semester' => 'genap',
            'jenis' => 'pendidikan_formal',
            'nama_kegiatan' => 'Ijazah S1 Pendidikan Informatika',
            'link_bukti' => 'https://example.test/ijazah.pdf',
        ]);

        $this->actingAs($dosen)
            ->from('/profil/pengajuan/create?tahun=2025&semester=genap')
            ->put('/profil/pengajuan/pendidikan/'.$entri->id, ['jenis' => 'gelar_palsu', 'link_bukti' => 'bukan-link'])
            ->assertRedirect('/profil/pengajuan/create?tahun=2025&semester=genap')
            ->assertSessionHasErrors(['tahun', 'semester', 'jenis', 'nama_kegiatan', 'link_bukti']);

        $this->assertDatabaseHas('kegiatan_dupak', ['id' => $entri->id, 'nama_kegiatan' => 'Ijazah S1 Pendidikan Informatika']);
    }

    public function test_dosen_can_delete_own_pendidikan_entry_only(): void
    {
        $dosen = $this->makeUser('dosen');
        $lain = User::create([
            'nidn' => '1990010120',
            'name' => 'Dosen Lain',
            'email' => 'lain@example.test',
            'role' => 'dosen',
            'is_active' => true,
            'password' => Hash::make('password'),
        ]);

        $entri = $dosen->kegiatanDupak()->create([
            'komponen' => 'pendidikan',
            'tahun' => 2025,
            'semester' => 'genap',
            'jenis' => 'pendidikan_formal',
            'nama_kegiatan' => 'Ijazah S1 Pendidikan Informatika',
            'link_bukti' => 'https://example.test/ijazah.pdf',
        ]);

        $this->actingAs($lain)
            ->delete('/profil/pengajuan/pendidikan/'.$entri->id)
            ->assertNotFound();

        $this->actingAs($dosen)
            ->delete('/profil/pengajuan/pendidikan/'.$entri->id)
            ->assertRedirect(route('profil.pengajuan.create', ['tahun' => 2025, 'semester' => 'genap']).'#pendidikan');

        $this->assertDatabaseCount('kegiatan_dupak', 0);
    }

    public function test_dosen_can_add_pelaksanaan_pendidikan_entry_with_rincian(): void
    {
        $dosen = $this->makeUser('dosen');

        $this->actingAs($dosen)
            ->post('/profil/pengajuan/pelaksanaan-pendidikan', [
                'tahun' => 2026,
                'semester' => 'ganjil',
                'jenis' => 'pengembangan_diri',
                'sub_jenis' => 'jam_641_960',
                'nama_kegiatan' => 'Workshop Peningkatan Kompetensi Dosen Vokasi',
                'link_bukti' => 'https://drive.google.com/file/d/bidangb/view',
            ])
            ->assertRedirect(route('profil.pengajuan.create', ['tahun' => 2026, 'semester' => 'ganjil']).'#pendidikan');

        $this->assertDatabaseHas('kegiatan_dupak', [
            'user_id' => $dosen->id,
            'komponen' => 'pelaksanaan_pendidikan',
            'tahun' => 2026,
            'semester' => 'ganjil',
            'jenis' => 'pengembangan_diri',
            'sub_jenis' => 'jam_641_960',
            'nama_kegiatan' => 'Workshop Peningkatan Kompetensi Dosen Vokasi',
            'link_bukti' => 'https://drive.google.com/file/d/bidangb/view',
        ]);

        $this->actingAs($dosen)
            ->get('/profil/pengajuan/create?tahun=2026&semester=ganjil')
            ->assertOk()
            ->assertSee('Bidang B — Pelaksanaan Pendidikan')
            ->assertSee('Daftar Entri Bidang B')
            ->assertSee('Melakukan Kegiatan Pengembangan Diri untuk Meningkatkan Kompetensi')
            ->assertSee('Lamanya 641-960 jam')
            ->assertSee('Workshop Peningkatan Kompetensi Dosen Vokasi')
            ->assertSee('data-confirm="Hapus entri Melakukan Kegiatan Pengembangan Diri untuk Meningkatkan Kompetensi?"', false);
    }

    public function test_pelaksanaan_pendidikan_entry_requires_rincian_when_komponen_has_options(): void
    {
        $this->actingAs($this->makeUser('dosen'))
            ->from('/profil/pengajuan/create?tahun=2026&semester=ganjil')
            ->post('/profil/pengajuan/pelaksanaan-pendidikan', [
                'tahun' => 2026,
                'semester' => 'ganjil',
                'jenis' => 'jabatan_pimpinan_pt',
                'nama_kegiatan' => 'Ketua Program Studi Pendidikan Informatika',
                'link_bukti' => 'https://drive.google.com/file/d/sk/view',
            ])
            ->assertRedirect('/profil/pengajuan/create?tahun=2026&semester=ganjil')
            ->assertSessionHasErrors(['sub_jenis']);

        $this->assertDatabaseCount('kegiatan_dupak', 0);
    }

    public function test_pelaksanaan_pendidikan_entry_without_rincian_stores_empty_sub_jenis(): void
    {
        $dosen = $this->makeUser('dosen');

        $this->actingAs($dosen)
            ->post('/profil/pengajuan/pelaksanaan-pendidikan', [
                'tahun' => 2026,
                'semester' => 'ganjil',
                'jenis' => 'perkuliahan',
                'sub_jenis' => 'pembimbing_utama',
                'nama_kegiatan' => 'Perkuliahan Pemrograman Web Kelas A',
                'link_bukti' => 'https://drive.google.com/file/d/kuliah/view',
            ])
            ->assertRedirect(route('profil.pengajuan.create', ['tahun' => 2026, 'semester' => 'ganjil']).'#pendidikan');

        $this->assertDatabaseHas('kegiatan_dupak', [
            'komponen' => 'pelaksanaan_pendidikan',
            'jenis' => 'perkuliahan',
            'sub_jenis' => null,
        ]);
    }

    public function test_dosen_can_update_and_delete_own_pelaksanaan_entry_only(): void
    {
        $dosen = $this->makeUser('dosen');
        $lain = User::create([
            'nidn' => '1990010120',
            'name' => 'Dosen Lain',
            'email' => 'lain@example.test',
            'role' => 'dosen',
            'is_active' => true,
            'password' => Hash::make('password'),
        ]);

        $entri = $dosen->kegiatanDupak()->create([
            'komponen' => 'pelaksanaan_pendidikan',
            'tahun' => 2025,
            'semester' => 'genap',
            'jenis' => 'pembimbingan_skripsi',
            'sub_jenis' => 'pembimbing_utama',
            'nama_kegiatan' => 'Pembimbing Utama Skripsi Mahasiswa',
            'link_bukti' => 'https://drive.google.com/file/d/skripsi/view',
        ]);

        $perubahan = [
            'tahun' => 2025,
            'semester' => 'genap',
            'jenis' => 'penguji_ujian_akhir',
            'sub_jenis' => 'ketua_penguji',
            'nama_kegiatan' => 'Ketua Penguji Sidang Skripsi',
            'link_bukti' => 'https://drive.google.com/file/d/penguji/view',
        ];

        $this->actingAs($lain)
            ->put('/profil/pengajuan/pelaksanaan-pendidikan/'.$entri->id, $perubahan)
            ->assertNotFound();

        $this->actingAs($dosen)
            ->put('/profil/pengajuan/pelaksanaan-pendidikan/'.$entri->id, $perubahan)
            ->assertRedirect(route('profil.pengajuan.create', ['tahun' => 2025, 'semester' => 'genap']).'#pendidikan');

        $this->assertDatabaseHas('kegiatan_dupak', [
            'id' => $entri->id,
            'komponen' => 'pelaksanaan_pendidikan',
            ...$perubahan,
        ]);

        // Entri bidang B tidak boleh dihapus lewat rute bidang A.
        $this->actingAs($dosen)
            ->delete('/profil/pengajuan/pendidikan/'.$entri->id)
            ->assertNotFound();

        $this->actingAs($lain)
            ->delete('/profil/pengajuan/pelaksanaan-pendidikan/'.$entri->id)
            ->assertNotFound();

        $this->actingAs($dosen)
            ->delete('/profil/pengajuan/pelaksanaan-pendidikan/'.$entri->id)
            ->assertRedirect(route('profil.pengajuan.create', ['tahun' => 2025, 'semester' => 'genap']).'#pendidikan');

        $this->assertDatabaseCount('kegiatan_dupak', 0);
    }

    public function test_dosen_can_open_pelaksanaan_edit_form_prefilled(): void
    {
        $dosen = $this->makeUser('dosen');

        $entri = $dosen->kegiatanDupak()->create([
            'komponen' => 'pelaksanaan_pendidikan',
            'tahun' => 2025,
            'semester' => 'genap',
            'jenis' => 'membimbing_dosen',
            'sub_jenis' => 'reguler',
            'nama_kegiatan' => 'Membimbing Dosen Asisten Ahli',
            'link_bukti' => 'https://drive.google.com/file/d/bimbing/view',
        ]);

        $this->actingAs($dosen)
            ->get('/profil/pengajuan/create?edit='.$entri->id)
            ->assertOk()
            ->assertSee('Edit Entri Bidang B')
            ->assertSee('2025/2026 · Genap')
            ->assertSee('value="membimbing_dosen" selected', false)
            ->assertSee('value="reguler" selected', false)
            ->assertSee('value="Membimbing Dosen Asisten Ahli"', false)
            ->assertSee(route('profil.pengajuan.pelaksanaan.update', $entri), false);
    }

    public function test_dosen_can_add_penelitian_entry_with_rincian_and_link_bukti(): void
    {
        $dosen = $this->makeUser('dosen');

        $this->actingAs($dosen)
            ->post('/profil/pengajuan/penelitian', [
                'tahun' => 2026,
                'semester' => 'ganjil',
                'jenis' => 'karya_ilmiah',
                'sub_jenis' => 'dipublikasikan',
                'rincian' => 'jurnal_internasional',
                'nama_kegiatan' => 'Artikel pada Jurnal Ilmiah Internasional Bereputasi',
                'link_bukti' => 'https://drive.google.com/file/d/jurnal/view',
            ])
            ->assertRedirect(route('profil.pengajuan.create', ['tahun' => 2026, 'semester' => 'ganjil']).'#penelitian');

        $this->assertDatabaseHas('kegiatan_dupak', [
            'user_id' => $dosen->id,
            'komponen' => 'penelitian',
            'tahun' => 2026,
            'semester' => 'ganjil',
            'jenis' => 'karya_ilmiah',
            'sub_jenis' => 'dipublikasikan',
            'rincian' => 'jurnal_internasional',
            'nama_kegiatan' => 'Artikel pada Jurnal Ilmiah Internasional Bereputasi',
            'link_bukti' => 'https://drive.google.com/file/d/jurnal/view',
        ]);

        $response = $this->actingAs($dosen)
            ->get('/profil/pengajuan/create?tahun=2026&semester=ganjil')
            ->assertOk()
            ->assertSee('Menghasilkan Karya Ilmiah')
            ->assertSee('Hasil Penelitian yang Dipublikasikan')
            ->assertSee('Bidang C — Penelitian')
            ->assertSee(route('profil.pengajuan.penelitian.store'), false)
            ->assertSee('data-reveal-select="#bukti-penelitian"', false)
            ->assertSee('data-dependent-select="#sub_jenis-penelitian"', false)
            ->assertSee('data-dependent-select="#rincian-penelitian"', false)
            ->assertSee('id="rincian-penelitian"', false)
            ->assertSee('data-confirm="Hapus entri Menghasilkan Karya Ilmiah?"', false);

        foreach (\App\Models\KegiatanDupak::JENIS_PENELITIAN as $label) {
            $response->assertSee($label);
        }

        foreach (\App\Models\KegiatanDupak::SUB_PENELITIAN['karya_ilmiah'] as $label) {
            $response->assertSee($label);
        }

        // Rincian tingkat ketiga ikut terkirim sebagai opsi select (dirender oleh JS).
        foreach (\App\Models\KegiatanDupak::RINCIAN_PENELITIAN['dipublikasikan'] as $label) {
            $response->assertSee($label);
        }
    }

    public function test_penelitian_entry_requires_rincian_only_for_komponen_with_sub_jenis(): void
    {
        $dosen = $this->makeUser('dosen');

        // Komponen yang punya rincian wajib memilih salah satu.
        $this->actingAs($dosen)
            ->from('/profil/pengajuan/create?tahun=2026&semester=ganjil')
            ->post('/profil/pengajuan/penelitian', [
                'tahun' => 2026,
                'semester' => 'ganjil',
                'jenis' => 'karya_seni',
                'nama_kegiatan' => 'Karya Seni Monumental',
                'link_bukti' => 'https://drive.google.com/file/d/seni/view',
            ])
            ->assertSessionHasErrors(['sub_jenis']);

        $this->actingAs($dosen)
            ->from('/profil/pengajuan/create?tahun=2026&semester=ganjil')
            ->post('/profil/pengajuan/penelitian', [
                'tahun' => 2026,
                'semester' => 'ganjil',
                'jenis' => 'karya_ilmiah',
                'sub_jenis' => 'rincian_palsu',
                'nama_kegiatan' => 'Artikel Ilmiah',
                'link_bukti' => 'https://drive.google.com/file/d/palsu/view',
            ])
            ->assertSessionHasErrors(['sub_jenis']);

        // Rincian tingkat ketiga wajib dipilih selama rincian komponen masih bercabang.
        $this->actingAs($dosen)
            ->from('/profil/pengajuan/create?tahun=2026&semester=ganjil')
            ->post('/profil/pengajuan/penelitian', [
                'tahun' => 2026,
                'semester' => 'ganjil',
                'jenis' => 'karya_ilmiah',
                'sub_jenis' => 'dipublikasikan',
                'nama_kegiatan' => 'Artikel Ilmiah',
                'link_bukti' => 'https://drive.google.com/file/d/palsu/view',
            ])
            ->assertSessionHasErrors(['rincian']);

        $this->actingAs($dosen)
            ->from('/profil/pengajuan/create?tahun=2026&semester=ganjil')
            ->post('/profil/pengajuan/penelitian', [
                'tahun' => 2026,
                'semester' => 'ganjil',
                'jenis' => 'karya_ilmiah',
                'sub_jenis' => 'dipublikasikan',
                'rincian' => 'rincian_palsu',
                'nama_kegiatan' => 'Artikel Ilmiah',
                'link_bukti' => 'https://drive.google.com/file/d/palsu/view',
            ])
            ->assertSessionHasErrors(['rincian']);

        $this->actingAs($dosen)
            ->from('/profil/pengajuan/create?tahun=2026&semester=ganjil')
            ->post('/profil/pengajuan/penelitian', ['jenis' => 'penelitian_palsu', 'link_bukti' => 'bukan-link'])
            ->assertSessionHasErrors(['tahun', 'semester', 'jenis', 'nama_kegiatan', 'link_bukti']);

        $this->assertDatabaseCount('kegiatan_dupak', 0);

        // Rincian komponen yang tidak bercabang tidak butuh rincian kegiatan.
        $this->actingAs($dosen)
            ->post('/profil/pengajuan/penelitian', [
                'tahun' => 2026,
                'semester' => 'ganjil',
                'jenis' => 'karya_ilmiah',
                'sub_jenis' => 'tidak_dipublikasikan',
                'nama_kegiatan' => 'Hasil Penelitian yang Belum Dipublikasikan',
                'link_bukti' => 'https://drive.google.com/file/d/belum/view',
            ])
            ->assertRedirect(route('profil.pengajuan.create', ['tahun' => 2026, 'semester' => 'ganjil']).'#penelitian');

        // Komponen tanpa rincian tetap boleh disimpan tanpa sub_jenis.
        $this->actingAs($dosen)
            ->post('/profil/pengajuan/penelitian', [
                'tahun' => 2026,
                'semester' => 'ganjil',
                'jenis' => 'terjemahan_buku',
                'sub_jenis' => 'sisa_pilihan_lama',
                'rincian' => 'sisa_rincian_lama',
                'nama_kegiatan' => 'Terjemahan Buku Ilmiah Manajemen Pendidikan',
                'link_bukti' => 'https://drive.google.com/file/d/terjemah/view',
            ])
            ->assertRedirect(route('profil.pengajuan.create', ['tahun' => 2026, 'semester' => 'ganjil']).'#penelitian');

        $this->assertDatabaseHas('kegiatan_dupak', [
            'komponen' => 'penelitian',
            'jenis' => 'terjemahan_buku',
            'sub_jenis' => null,
            'rincian' => null,
        ]);
    }

    public function test_dosen_can_update_and_delete_own_penelitian_entry_only(): void
    {
        $dosen = $this->makeUser('dosen');
        $lain = User::create([
            'nidn' => '1990010120',
            'name' => 'Dosen Lain',
            'email' => 'lain@example.test',
            'role' => 'dosen',
            'is_active' => true,
            'password' => Hash::make('password'),
        ]);

        $entri = $dosen->kegiatanDupak()->create([
            'komponen' => 'penelitian',
            'tahun' => 2025,
            'semester' => 'genap',
            'jenis' => 'karya_ilmiah',
            'sub_jenis' => 'dipublikasikan',
            'rincian' => 'seminar_nasional',
            'nama_kegiatan' => 'Pemakalah Seminar Nasional Informatika',
            'link_bukti' => 'https://drive.google.com/file/d/seminar/view',
        ]);

        $perubahan = [
            'tahun' => 2025,
            'semester' => 'genap',
            'jenis' => 'karya_seni',
            'sub_jenis' => 'nasional',
            // Pindah ke komponen tanpa rincian kegiatan: nilai lama harus ikut dikosongkan.
            'rincian' => null,
            'nama_kegiatan' => 'Karya Seni Pertunjukan Tradisional',
            'link_bukti' => 'https://drive.google.com/file/d/seni-baru/view',
        ];

        $this->actingAs($lain)
            ->put('/profil/pengajuan/penelitian/'.$entri->id, $perubahan)
            ->assertNotFound();

        $this->actingAs($dosen)
            ->put('/profil/pengajuan/penelitian/'.$entri->id, $perubahan)
            ->assertRedirect(route('profil.pengajuan.create', ['tahun' => 2025, 'semester' => 'genap']).'#penelitian');

        $this->assertDatabaseHas('kegiatan_dupak', [
            'id' => $entri->id,
            'komponen' => 'penelitian',
            ...$perubahan,
        ]);

        // Entri bidang C tidak boleh dihapus lewat rute bidang A maupun B.
        $this->actingAs($dosen)
            ->delete('/profil/pengajuan/pendidikan/'.$entri->id)
            ->assertNotFound();

        $this->actingAs($dosen)
            ->delete('/profil/pengajuan/pelaksanaan-pendidikan/'.$entri->id)
            ->assertNotFound();

        $this->actingAs($lain)
            ->delete('/profil/pengajuan/penelitian/'.$entri->id)
            ->assertNotFound();

        $this->actingAs($dosen)
            ->delete('/profil/pengajuan/penelitian/'.$entri->id)
            ->assertRedirect(route('profil.pengajuan.create', ['tahun' => 2025, 'semester' => 'genap']).'#penelitian');

        $this->assertDatabaseCount('kegiatan_dupak', 0);
    }

    public function test_dosen_can_open_penelitian_edit_form_prefilled(): void
    {
        $dosen = $this->makeUser('dosen');

        $entri = $dosen->kegiatanDupak()->create([
            'komponen' => 'penelitian',
            'tahun' => 2025,
            'semester' => 'genap',
            'jenis' => 'karya_ilmiah',
            'sub_jenis' => 'dipublikasikan',
            'rincian' => 'jurnal_nasional_terakreditasi',
            'nama_kegiatan' => 'Artikel pada Jurnal Nasional Terakreditasi Sinta 2',
            'link_bukti' => 'https://drive.google.com/file/d/sinta/view',
        ]);

        $this->actingAs($dosen)
            ->get('/profil/pengajuan/create?edit='.$entri->id)
            ->assertOk()
            ->assertSee('Edit Entri Bidang C')
            ->assertSee('2025/2026 · Genap')
            ->assertSee('value="karya_ilmiah" selected', false)
            ->assertSee('value="dipublikasikan" selected', false)
            ->assertSee('value="jurnal_nasional_terakreditasi" selected', false)
            ->assertSee('value="Artikel pada Jurnal Nasional Terakreditasi Sinta 2"', false)
            // Kolom komponen menampilkan rantai rincian sampai tingkat ketiga.
            ->assertSee('Hasil Penelitian yang Dipublikasikan · Jurnal Ilmiah Nasional Terakreditasi')
            ->assertSee(route('profil.pengajuan.penelitian.update', $entri), false)
            ->assertSee('aria-label="Hapus entri Menghasilkan Karya Ilmiah"', false);
    }

    public function test_dosen_can_add_abdimas_entry_with_rincian_and_link_bukti(): void
    {
        $dosen = $this->makeUser('dosen');

        $this->actingAs($dosen)
            ->post('/profil/pengajuan/abdimas', [
                'tahun' => 2026,
                'semester' => 'genap',
                'jenis' => 'latihan_penyuluhan',
                'sub_jenis' => 'terjadwal_satu_semester',
                'rincian' => 'nasional',
                'nama_kegiatan' => 'Penyuluhan Literasi Digital bagi UMKM Desa Sukamaju',
                'link_bukti' => 'https://drive.google.com/file/d/penyuluhan/view',
            ])
            ->assertRedirect(route('profil.pengajuan.create', ['tahun' => 2026, 'semester' => 'genap']).'#abdimas');

        $this->assertDatabaseHas('kegiatan_dupak', [
            'user_id' => $dosen->id,
            'komponen' => 'abdimas',
            'tahun' => 2026,
            'semester' => 'genap',
            'jenis' => 'latihan_penyuluhan',
            'sub_jenis' => 'terjadwal_satu_semester',
            'rincian' => 'nasional',
            'nama_kegiatan' => 'Penyuluhan Literasi Digital bagi UMKM Desa Sukamaju',
            'link_bukti' => 'https://drive.google.com/file/d/penyuluhan/view',
        ]);

        $response = $this->actingAs($dosen)
            ->get('/profil/pengajuan/create?tahun=2026&semester=genap')
            ->assertOk()
            ->assertSee('Bidang D — Pengabdian kepada Masyarakat')
            ->assertSee(route('profil.pengajuan.abdimas.store'), false)
            ->assertSee('data-reveal-select="#bukti-abdimas"', false)
            ->assertSee('data-dependent-select="#sub_jenis-abdimas"', false)
            ->assertSee('data-dependent-select="#rincian-abdimas"', false)
            ->assertSee('id="rincian-abdimas"', false)
            ->assertSee('data-confirm="Hapus entri Memberikan Latihan/Penyuluhan/Penataran/Ceramah pada Masyarakat?"', false);

        foreach (\App\Models\KegiatanDupak::JENIS_PENGABDIAN as $label) {
            $response->assertSee($label);
        }

        foreach (\App\Models\KegiatanDupak::SUB_PENGABDIAN['latihan_penyuluhan'] as $label) {
            $response->assertSee($label);
        }

        foreach (\App\Models\KegiatanDupak::SUB_PENGABDIAN['pelayanan_masyarakat'] as $label) {
            $response->assertSee($label);
        }

        // Tingkat pelaksanaan ikut terkirim sebagai opsi rincian kegiatan (dirender oleh JS).
        foreach (\App\Models\KegiatanDupak::RINCIAN_PENGABDIAN['kurang_satu_semester'] as $label) {
            $response->assertSee($label);
        }
    }

    public function test_abdimas_entry_requires_rincian_only_for_komponen_with_sub_jenis(): void
    {
        $dosen = $this->makeUser('dosen');

        // Komponen yang punya rincian wajib memilih salah satu.
        $this->actingAs($dosen)
            ->from('/profil/pengajuan/create?tahun=2026&semester=ganjil')
            ->post('/profil/pengajuan/abdimas', [
                'tahun' => 2026,
                'semester' => 'ganjil',
                'jenis' => 'latihan_penyuluhan',
                'nama_kegiatan' => 'Ceramah Kewirausahaan Pemuda',
                'link_bukti' => 'https://drive.google.com/file/d/ceramah/view',
            ])
            ->assertSessionHasErrors(['sub_jenis']);

        // Tingkat pelaksanaan wajib dipilih selama rincian komponen masih bercabang.
        $this->actingAs($dosen)
            ->from('/profil/pengajuan/create?tahun=2026&semester=ganjil')
            ->post('/profil/pengajuan/abdimas', [
                'tahun' => 2026,
                'semester' => 'ganjil',
                'jenis' => 'latihan_penyuluhan',
                'sub_jenis' => 'kurang_satu_semester',
                'nama_kegiatan' => 'Ceramah Kewirausahaan Pemuda',
                'link_bukti' => 'https://drive.google.com/file/d/ceramah/view',
            ])
            ->assertSessionHasErrors(['rincian']);

        $this->actingAs($dosen)
            ->from('/profil/pengajuan/create?tahun=2026&semester=ganjil')
            ->post('/profil/pengajuan/abdimas', [
                'tahun' => 2026,
                'semester' => 'ganjil',
                'jenis' => 'latihan_penyuluhan',
                'sub_jenis' => 'terjadwal_satu_semester',
                'rincian' => 'tingkat_palsu',
                'nama_kegiatan' => 'Pelayanan Konsultasi Sistem Informasi Desa',
                'link_bukti' => 'https://drive.google.com/file/d/palsu/view',
            ])
            ->assertSessionHasErrors(['rincian']);

        $this->actingAs($dosen)
            ->from('/profil/pengajuan/create?tahun=2026&semester=ganjil')
            ->post('/profil/pengajuan/abdimas', ['jenis' => 'abdimas_palsu', 'link_bukti' => 'bukan-link'])
            ->assertSessionHasErrors(['tahun', 'semester', 'jenis', 'nama_kegiatan', 'link_bukti']);

        $this->assertDatabaseCount('kegiatan_dupak', 0);

        // Rincian komponen dua tingkat (pelayanan masyarakat) tidak butuh tingkat pelaksanaan.
        $this->actingAs($dosen)
            ->post('/profil/pengajuan/abdimas', [
                'tahun' => 2026,
                'semester' => 'ganjil',
                'jenis' => 'pelayanan_masyarakat',
                'sub_jenis' => 'bidang_keahlian',
                'nama_kegiatan' => 'Pelayanan Konsultasi Sistem Informasi Desa',
                'link_bukti' => 'https://drive.google.com/file/d/pelayanan/view',
            ])
            ->assertRedirect(route('profil.pengajuan.create', ['tahun' => 2026, 'semester' => 'ganjil']).'#abdimas');

        $this->assertDatabaseHas('kegiatan_dupak', [
            'jenis' => 'pelayanan_masyarakat',
            'sub_jenis' => 'bidang_keahlian',
            'rincian' => null,
        ]);

        // Komponen tanpa rincian tetap boleh disimpan; pilihan lama harus dikosongkan.
        $this->actingAs($dosen)
            ->post('/profil/pengajuan/abdimas', [
                'tahun' => 2026,
                'semester' => 'ganjil',
                'jenis' => 'jabatan_pimpinan',
                'sub_jenis' => 'sisa_pilihan_lama',
                'rincian' => 'sisa_rincian_lama',
                'nama_kegiatan' => 'Ketua Lembaga Pengabdian Masyarakat',
                'link_bukti' => 'https://drive.google.com/file/d/jabatan/view',
            ])
            ->assertRedirect(route('profil.pengajuan.create', ['tahun' => 2026, 'semester' => 'ganjil']).'#abdimas');

        $this->assertDatabaseHas('kegiatan_dupak', [
            'komponen' => 'abdimas',
            'jenis' => 'jabatan_pimpinan',
            'sub_jenis' => null,
            'rincian' => null,
        ]);
    }

    public function test_dosen_can_update_delete_and_prefill_own_abdimas_entry_only(): void
    {
        $dosen = $this->makeUser('dosen');
        $lain = User::create([
            'nidn' => '1990010120',
            'name' => 'Dosen Lain',
            'email' => 'lain@example.test',
            'role' => 'dosen',
            'is_active' => true,
            'password' => Hash::make('password'),
        ]);

        $entri = $dosen->kegiatanDupak()->create([
            'komponen' => 'abdimas',
            'tahun' => 2025,
            'semester' => 'genap',
            'jenis' => 'pelayanan_masyarakat',
            'sub_jenis' => 'bidang_keahlian',
            'rincian' => 'lokal',
            'nama_kegiatan' => 'Pendampingan Digitalisasi Koperasi Desa',
            'link_bukti' => 'https://drive.google.com/file/d/koperasi/view',
        ]);

        $perubahan = [
            'tahun' => 2025,
            'semester' => 'genap',
            'jenis' => 'karya_pengabdian',
            'sub_jenis' => null,
            'rincian' => null,
            'nama_kegiatan' => 'Buku Panduan Pengelolaan Sampah Organik',
            'link_bukti' => 'https://drive.google.com/file/d/buku-abdimas/view',
        ];

        $this->actingAs($lain)
            ->put('/profil/pengajuan/abdimas/'.$entri->id, $perubahan)
            ->assertNotFound();

        $this->actingAs($dosen)
            ->put('/profil/pengajuan/abdimas/'.$entri->id, $perubahan)
            ->assertRedirect(route('profil.pengajuan.create', ['tahun' => 2025, 'semester' => 'genap']).'#abdimas');

        $this->assertDatabaseHas('kegiatan_dupak', [
            'id' => $entri->id,
            'komponen' => 'abdimas',
            ...$perubahan,
        ]);

        // Entri bidang D tidak boleh dihapus lewat rute bidang lain.
        foreach (['pendidikan', 'pelaksanaan-pendidikan', 'penelitian'] as $rute) {
            $this->actingAs($dosen)
                ->delete('/profil/pengajuan/'.$rute.'/'.$entri->id)
                ->assertNotFound();
        }

        $this->actingAs($lain)
            ->delete('/profil/pengajuan/abdimas/'.$entri->id)
            ->assertNotFound();

        // Form edit terisi ulang sesuai entri milik dosen tersebut.
        $this->actingAs($dosen)
            ->get('/profil/pengajuan/create?edit='.$entri->id)
            ->assertOk()
            ->assertSee('Edit Entri Bidang D')
            ->assertSee('2025/2026 · Genap')
            ->assertSee('value="karya_pengabdian" selected', false)
            ->assertSee('value="Buku Panduan Pengelolaan Sampah Organik"', false)
            ->assertSee(route('profil.pengajuan.abdimas.update', $entri), false)
            ->assertSee('aria-label="Hapus entri Membuat atau Menulis Karya Pengabdian"', false);

        $this->actingAs($dosen)
            ->delete('/profil/pengajuan/abdimas/'.$entri->id)
            ->assertRedirect(route('profil.pengajuan.create', ['tahun' => 2025, 'semester' => 'genap']).'#abdimas');

        $this->assertDatabaseCount('kegiatan_dupak', 0);
    }
}
