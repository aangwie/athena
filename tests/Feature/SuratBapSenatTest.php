<?php

namespace Tests\Feature;

use App\Models\Instansi;
use App\Models\Senat;
use App\Models\SuratBapSenat;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class SuratBapSenatTest extends TestCase
{
    use RefreshDatabase;

    private function makeUser(string $role, ?string $nidn = null, ?string $name = null): User
    {
        $nidn ??= $role === 'admin' ? '0000000001' : '1987654321';

        return User::create([
            'nidn' => $nidn,
            'name' => $name ?? ($role === 'dosen' ? 'Dr. Ayu Lestari, M.Kom.' : 'Admin Kepegawaian'),
            'email' => $nidn.'@example.test',
            'program_studi' => 'Pendidikan Informatika',
            'jabatan_fungsional' => 'Lektor',
            'role' => $role,
            'is_active' => true,
            'password' => Hash::make('password'),
        ]);
    }

    /**
     * @return array{0: User, 1: Senat, 2: Senat}
     */
    private function makeSenat(): array
    {
        $ketua = $this->makeUser('dosen', '198001012010011001', 'Dr. Budi Santoso, M.Pd.');
        $anggota = $this->makeUser('dosen', '199001122010012002', 'Rina Wijaya, M.Kom.');

        return [
            $ketua,
            Senat::create(['user_id' => $ketua->id, 'jabatan' => 'ketua']),
            Senat::create(['user_id' => $anggota->id, 'jabatan' => 'anggota']),
        ];
    }

    public function test_bap_senat_tab_shows_form_fields_and_senat_multi_select(): void
    {
        $this->makeSenat();

        $this->actingAs($this->makeUser('admin'))
            ->get(route('surat.index'))
            ->assertOk()
            ->assertSee('Data Berita Acara Persetujuan Senat')
            ->assertSee('Nomor BAP')
            ->assertSee('Tanggal')
            ->assertSee('DD-MM-YYYY')
            ->assertSee('data-date-picker', false)
            ->assertSee('Nama Dosen')
            ->assertSee('Bidang Keilmuan')
            ->assertSee('name="bidang_keilmuan"', false)
            ->assertSee('data-select-search="Cari bidang atau rumpun ilmu…"', false)
            // Bidang keilmuan dikelompokkan per rumpun memakai <optgroup> (dropdown + sub).
            ->assertSee('<optgroup label="A. Komputer &amp; Teknologi">', false)
            ->assertSee('<optgroup label="B. Bahasa &amp; Linguistik">', false)
            ->assertSee('<optgroup label="C. Pendidikan &amp; Keguruan">', false)
            ->assertSee('<optgroup label="D. Humaniora, Sosial &amp; Keagamaan">', false)
            ->assertSee('APLIKASI PERANGKAT LUNAK SITUS (20.101.1003)')
            ->assertSee('PENDIDIKAN BAHASA INGGRIS (50.132.1020)')
            ->assertSee('PENDIDIKAN MATEMATIKA (50.132.1057)')
            ->assertSee('KEAGAMAAN (60.101.1001)')
            ->assertSee('Pilihan Kenaikan Jabatan Akademik')
            ->assertSee('name="kenaikan_jabatan"', false)
            ->assertSee('<option value="belum_asisten_ahli"', false)
            ->assertSee('Belum ke Asisten Ahli')
            ->assertSee('<option value="asisten_ahli_ke_lektor"', false)
            ->assertSee('Asisten Ahli ke Lektor')
            ->assertSee('Prosentase Proporsi Penelitian')
            // Prosentase proporsi penelitian berupa dropdown dengan pilihan tetap.
            ->assertSee('name="prosentase_penelitian"', false)
            ->assertSee('data-select-search="Cari prosentase…"', false)
            ->assertSee('<option value="150"', false)
            ->assertSee('<option value="200"', false)
            ->assertSee('<option value="300"', false)
            ->assertSee('<option value="400"', false)
            ->assertSee('<option value="550"', false)
            ->assertSee('<option value="700"', false)
            ->assertSee('<option value="850"', false)
            ->assertSee('<option value="1050"', false)
            ->assertSee('Tempat Pelaksanaan')
            ->assertSee('name="tempat_pelaksanaan"', false)
            ->assertSee('Materi Rapat')
            ->assertSee('name="materi_rapat"', false)
            ->assertSee('Anggota Senat')
            ->assertSee('data-select-search="Cari nama dosen…"', false)
            ->assertSee('data-select-search="Cari nama anggota senat…"', false)
            ->assertSee('name="anggota_senat_ids[]" multiple', false)
            ->assertSee('Dr. Budi Santoso, M.Pd.')
            ->assertSee('Rina Wijaya, M.Kom.')
            ->assertSee('Daftar Berita Acara Persetujuan Senat')
            ->assertSee('data-datatable', false);
    }

    public function test_admin_can_save_and_update_bap_senat(): void
    {
        [$dosen, $ketua, $anggota] = $this->makeSenat();
        $admin = $this->makeUser('admin');

        $this->actingAs($admin)
            ->post(route('surat.bap-senat.store'), [
                'nomor_bap' => '001/BAP-SENAT/X/2026',
                'tanggal_bap' => '31-10-2026',
                'dosen_id' => $dosen->id,
                'bidang_keilmuan' => 'APLIKASI PERANGKAT LUNAK SITUS',
                'kenaikan_jabatan' => 'asisten_ahli_ke_lektor',
                'prosentase_penelitian' => 150,
                'tempat_pelaksanaan' => 'Ruang Rapat Senat Lt. 2',
                'materi_rapat' => 'Pemberian pertimbangan kenaikan jabatan akademik dosen',
                'anggota_senat_ids' => [$ketua->user_id, $anggota->user_id],
            ])
            ->assertRedirect(route('surat.index').'#bap-senat')
            ->assertSessionHas('status', 'BAP senat berhasil disimpan.');

        $bap = SuratBapSenat::query()->firstOrFail();

        $this->assertSame('001/BAP-SENAT/X/2026', $bap->nomor_bap);
        $this->assertSame('2026-10-31', $bap->tanggal_bap->toDateString());
        $this->assertSame($dosen->id, $bap->user_id);
        $this->assertSame('APLIKASI PERANGKAT LUNAK SITUS', $bap->bidang_keilmuan);
        $this->assertSame('asisten_ahli_ke_lektor', $bap->kenaikan_jabatan);
        $this->assertSame(150, $bap->prosentase_penelitian);
        $this->assertSame('Ruang Rapat Senat Lt. 2', $bap->tempat_pelaksanaan);
        $this->assertSame('Pemberian pertimbangan kenaikan jabatan akademik dosen', $bap->materi_rapat);
        $this->assertSame([$ketua->user_id, $anggota->user_id], $bap->anggota_senat_ids);

        $this->actingAs($admin)
            ->put(route('surat.bap-senat.update', $bap), [
                'nomor_bap' => '002/BAP-SENAT/XI/2026',
                'tanggal_bap' => '01-11-2026',
                'dosen_id' => $dosen->id,
                'bidang_keilmuan' => 'TEKNIK JARINGAN KOMPUTER',
                'kenaikan_jabatan' => 'asisten_ahli_ke_lektor',
                'prosentase_penelitian' => 200,
                'tempat_pelaksanaan' => 'Aula Kampus',
                'materi_rapat' => 'Pembahasan usulan jabatan akademik',
                'anggota_senat_ids' => [$ketua->user_id],
            ])
            ->assertRedirect(route('surat.index').'#bap-senat')
            ->assertSessionHas('status', 'BAP senat berhasil diperbarui.');

        $bap->refresh();

        $this->assertSame('002/BAP-SENAT/XI/2026', $bap->nomor_bap);
        $this->assertSame('2026-11-01', $bap->tanggal_bap->toDateString());
        $this->assertSame('TEKNIK JARINGAN KOMPUTER', $bap->bidang_keilmuan);
        $this->assertSame('asisten_ahli_ke_lektor', $bap->kenaikan_jabatan);
        $this->assertSame(200, $bap->prosentase_penelitian);
        $this->assertSame('Aula Kampus', $bap->tempat_pelaksanaan);
        $this->assertSame('Pembahasan usulan jabatan akademik', $bap->materi_rapat);
        $this->assertSame([$ketua->user_id], $bap->anggota_senat_ids);
    }

    public function test_bap_senat_table_lists_nomor_dosen_tanggal_and_actions(): void
    {
        [$dosen, $ketua] = $this->makeSenat();

        $bap = SuratBapSenat::create([
            'nomor_bap' => '007/BAP-SENAT/X/2026',
            'tanggal_bap' => '2026-10-31',
            'user_id' => $dosen->id,
            'bidang_keilmuan' => 'Sistem Informasi',
            'prosentase_penelitian' => 150,
            'anggota_senat_ids' => [$ketua->user_id],
        ]);

        $this->actingAs($this->makeUser('admin'))
            ->get(route('surat.index'))
            ->assertOk()
            ->assertSee('007/BAP-SENAT/X/2026')
            ->assertSee('31-10-2026')
            ->assertSee('aria-label="Print"', false)
            ->assertSee('aria-label="Edit"', false)
            ->assertSee('aria-label="Hapus"', false)
            ->assertSee(route('surat.bap-senat.cetak', $bap), false)
            ->assertSee(route('surat.index', ['edit_bap' => $bap->id]), false)
            ->assertSee(route('surat.bap-senat.destroy', $bap), false);
    }

    public function test_edit_parameter_prefills_bap_senat_form(): void
    {
        [$dosen, $ketua] = $this->makeSenat();

        $bap = SuratBapSenat::create([
            'nomor_bap' => '009/BAP-SENAT/X/2026',
            'tanggal_bap' => '2026-10-31',
            'user_id' => $dosen->id,
            'bidang_keilmuan' => 'Sistem Informasi',
            'prosentase_penelitian' => 30,
            'tempat_pelaksanaan' => 'Ruang Rapat Senat Lt. 2',
            'materi_rapat' => 'Pemberian pertimbangan kenaikan jabatan akademik',
            'anggota_senat_ids' => [$ketua->user_id],
        ]);

        $this->actingAs($this->makeUser('admin'))
            ->get(route('surat.index', ['edit_bap' => $bap->id]))
            ->assertOk()
            ->assertSee('Perbarui')
            ->assertSee(route('surat.bap-senat.update', $bap), false)
            ->assertSee('value="009/BAP-SENAT/X/2026"', false)
            ->assertSee('value="31-10-2026"', false)
            ->assertSee('value="Ruang Rapat Senat Lt. 2"', false)
            ->assertSee('value="Pemberian pertimbangan kenaikan jabatan akademik"', false)
            // Bidang di luar daftar rumpun tetap tampil pada dropdown sebagai data lama.
            ->assertSee('<optgroup label="Lainnya (data lama)">', false)
            ->assertSee('value="Sistem Informasi" selected', false)
            // Prosentase lama di luar daftar tetap tetap tampil pada dropdown.
            ->assertSee('value="30" selected', false);
    }

    public function test_bap_senat_lama_tetap_dapat_diperbarui_tanpa_mengubah_bidang(): void
    {
        [$dosen, $ketua] = $this->makeSenat();

        // Bidang di luar daftar rumpun (data lama) tetap diterima saat BAP diubah,
        // sehingga perubahan isian lain tidak terhalang validasi.
        $bap = SuratBapSenat::create([
            'nomor_bap' => '008/BAP-SENAT/X/2026',
            'tanggal_bap' => '2026-10-31',
            'user_id' => $dosen->id,
            'bidang_keilmuan' => 'Sistem Informasi',
            'prosentase_penelitian' => 30,
            'tempat_pelaksanaan' => 'Ruang Rapat Senat Lt. 2',
            'materi_rapat' => 'Pemberian pertimbangan kenaikan jabatan akademik',
            'anggota_senat_ids' => [$ketua->user_id],
        ]);

        $this->actingAs($this->makeUser('admin'))
            ->put(route('surat.bap-senat.update', $bap), [
                'nomor_bap' => '008/BAP-SENAT/X/2026',
                'tanggal_bap' => '31-10-2026',
                'dosen_id' => $dosen->id,
                'bidang_keilmuan' => 'Sistem Informasi',
                'kenaikan_jabatan' => 'asisten_ahli_ke_lektor',
                'prosentase_penelitian' => 300,
                'tempat_pelaksanaan' => 'Ruang Rapat Senat Lt. 2',
                'materi_rapat' => 'Pemberian pertimbangan kenaikan jabatan akademik',
                'anggota_senat_ids' => [$ketua->user_id],
            ])
            ->assertRedirect(route('surat.index').'#bap-senat')
            ->assertSessionHasNoErrors();

        $bap->refresh();

        $this->assertSame('Sistem Informasi', $bap->bidang_keilmuan);
        $this->assertSame(300, $bap->prosentase_penelitian);
    }

    public function test_bap_senat_rejects_invalid_input(): void
    {
        [$dosen] = $this->makeSenat();
        $bukanSenat = $this->makeUser('dosen', '199501122015011003', 'Dosen Biasa, M.Pd.');
        $admin = $this->makeUser('admin');

        $this->actingAs($admin)
            ->post(route('surat.bap-senat.store'), [
                'nomor_bap' => '',
                'tanggal_bap' => '31/10/2026',
                'dosen_id' => $dosen->id,
                'bidang_keilmuan' => '',
                'prosentase_penelitian' => 120,
                'tempat_pelaksanaan' => '',
                'materi_rapat' => '',
                'anggota_senat_ids' => [$bukanSenat->id],
            ])
            ->assertSessionHasErrors(['nomor_bap', 'tanggal_bap', 'bidang_keilmuan', 'prosentase_penelitian', 'tempat_pelaksanaan', 'materi_rapat', 'anggota_senat_ids.0']);

        $this->actingAs($admin)
            ->post(route('surat.bap-senat.store'), [
                'nomor_bap' => '003/BAP-SENAT/X/2026',
                'tanggal_bap' => '31-10-2026',
                'dosen_id' => $dosen->id,
                'bidang_keilmuan' => 'APLIKASI PERANGKAT LUNAK SITUS',
                'prosentase_penelitian' => 150,
                'tempat_pelaksanaan' => 'Ruang Rapat Senat',
                'materi_rapat' => 'Pemberian pertimbangan kenaikan jabatan akademik',
                'anggota_senat_ids' => [],
            ])
            ->assertSessionHasErrors('anggota_senat_ids');

        // Bidang keilmuan di luar daftar rumpun tidak boleh disimpan.
        $this->actingAs($admin)
            ->post(route('surat.bap-senat.store'), [
                'nomor_bap' => '004/BAP-SENAT/X/2026',
                'tanggal_bap' => '31-10-2026',
                'dosen_id' => $dosen->id,
                'bidang_keilmuan' => 'Sistem Informasi',
                'prosentase_penelitian' => 150,
                'tempat_pelaksanaan' => 'Ruang Rapat Senat',
                'materi_rapat' => 'Pemberian pertimbangan kenaikan jabatan akademik',
                'anggota_senat_ids' => [$dosen->id],
            ])
            ->assertSessionHasErrors('bidang_keilmuan');

        // Prosentase di luar pilihan dropdown (150, 200, 300, 400, 550, 700, 850, 1050) ditolak.
        $this->actingAs($admin)
            ->post(route('surat.bap-senat.store'), [
                'nomor_bap' => '005/BAP-SENAT/X/2026',
                'tanggal_bap' => '31-10-2026',
                'dosen_id' => $dosen->id,
                'bidang_keilmuan' => 'APLIKASI PERANGKAT LUNAK SITUS',
                'prosentase_penelitian' => 250,
                'tempat_pelaksanaan' => 'Ruang Rapat Senat',
                'materi_rapat' => 'Pemberian pertimbangan kenaikan jabatan akademik',
                'anggota_senat_ids' => [$dosen->id],
            ])
            ->assertSessionHasErrors('prosentase_penelitian');

        $this->assertDatabaseCount('surat_bap_senat', 0);
    }

    public function test_bap_senat_belum_asisten_ahli_menyimpan_prosentase_kosong(): void
    {
        [$dosen, $ketua] = $this->makeSenat();
        $admin = $this->makeUser('admin');

        $this->actingAs($admin)
            ->post(route('surat.bap-senat.store'), [
                'nomor_bap' => '015/BAP-SENAT/X/2026',
                'tanggal_bap' => '31-10-2026',
                'dosen_id' => $dosen->id,
                'bidang_keilmuan' => 'APLIKASI PERANGKAT LUNAK SITUS',
                'kenaikan_jabatan' => 'belum_asisten_ahli',
                'tempat_pelaksanaan' => 'Ruang Rapat Senat Lt. 2',
                'materi_rapat' => 'Pemberian pertimbangan kenaikan jabatan akademik dosen',
                'anggota_senat_ids' => [$ketua->user_id],
            ])
            ->assertRedirect(route('surat.index').'#bap-senat')
            ->assertSessionHasNoErrors();

        $bap = SuratBapSenat::query()->firstOrFail();

        $this->assertSame('belum_asisten_ahli', $bap->kenaikan_jabatan);
        $this->assertNull($bap->prosentase_penelitian);
    }

    public function test_bap_senat_asisten_ahli_ke_lektor_wajib_prosentase(): void
    {
        [$dosen, $ketua] = $this->makeSenat();
        $admin = $this->makeUser('admin');

        $this->actingAs($admin)
            ->post(route('surat.bap-senat.store'), [
                'nomor_bap' => '016/BAP-SENAT/X/2026',
                'tanggal_bap' => '31-10-2026',
                'dosen_id' => $dosen->id,
                'bidang_keilmuan' => 'APLIKASI PERANGKAT LUNAK SITUS',
                'kenaikan_jabatan' => 'asisten_ahli_ke_lektor',
                'tempat_pelaksanaan' => 'Ruang Rapat Senat Lt. 2',
                'materi_rapat' => 'Pemberian pertimbangan kenaikan jabatan akademik dosen',
                'anggota_senat_ids' => [$ketua->user_id],
            ])
            ->assertSessionHasErrors('prosentase_penelitian');

        $this->assertDatabaseCount('surat_bap_senat', 0);
    }

    public function test_cetak_bap_senat_menyesuaikan_teks_kenaikan_dan_prosentase(): void
    {
        Instansi::create(['nama_instansi' => 'Universitas Athena', 'kota' => 'Bandung']);

        [$dosen, $ketua] = $this->makeSenat();
        $admin = $this->makeUser('admin');

        // Belum ke Asisten Ahli: tidak ada kalimat proporsi penelitian.
        $belum = SuratBapSenat::create([
            'nomor_bap' => '017/BAP-SENAT/X/2026',
            'tanggal_bap' => '2026-10-31',
            'user_id' => $dosen->id,
            'bidang_keilmuan' => 'Sistem Informasi',
            'kenaikan_jabatan' => 'belum_asisten_ahli',
            'prosentase_penelitian' => null,
            'anggota_senat_ids' => [$ketua->user_id],
        ]);

        $html = $this->actingAs($admin)
            ->get(route('surat.bap-senat.cetak', $belum))
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString('Untuk Kenaikan Jabatan Akademik Dosen ke Asisten Ahli', $html);
        $this->assertStringNotContainsString('proporsi penelitian', $html);

        // Asisten Ahli ke Lektor: kalimat proporsi penelitian ditampilkan.
        $lektor = SuratBapSenat::create([
            'nomor_bap' => '018/BAP-SENAT/X/2026',
            'tanggal_bap' => '2026-10-31',
            'user_id' => $dosen->id,
            'bidang_keilmuan' => 'Sistem Informasi',
            'kenaikan_jabatan' => 'asisten_ahli_ke_lektor',
            'prosentase_penelitian' => 150,
            'anggota_senat_ids' => [$ketua->user_id],
        ]);

        $html = $this->actingAs($admin)
            ->get(route('surat.bap-senat.cetak', $lektor))
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString('Untuk Kenaikan Jabatan Akademik Dosen ke Lektor', $html);
        $this->assertStringContainsString('proporsi penelitian 150%', $html);
    }

    public function test_admin_can_delete_bap_senat(): void
    {
        [$dosen, $ketua] = $this->makeSenat();

        $bap = SuratBapSenat::create([
            'nomor_bap' => '010/BAP-SENAT/X/2026',
            'tanggal_bap' => '2026-10-31',
            'user_id' => $dosen->id,
            'bidang_keilmuan' => 'Sistem Informasi',
            'prosentase_penelitian' => 150,
            'anggota_senat_ids' => [$ketua->user_id],
        ]);

        $this->actingAs($this->makeUser('admin'))
            ->delete(route('surat.bap-senat.destroy', $bap))
            ->assertRedirect(route('surat.index').'#bap-senat')
            ->assertSessionHas('status', 'BAP senat berhasil dihapus.');

        $this->assertDatabaseMissing('surat_bap_senat', ['id' => $bap->id]);
    }

    public function test_admin_can_open_bap_senat_print_preview_with_two_pages(): void
    {
        Instansi::create([
            'nama_instansi' => 'Universitas Athena',
            'kota' => 'Bandung',
            'alamat' => 'Jl. Jenderal Sudirman 1',
            'logo_base64' => 'data:image/png;base64,logo-instansi',
            'logo_senat_base64' => 'data:image/png;base64,logo-senat',
        ]);

        [$dosen, $ketua, $anggota] = $this->makeSenat();

        $bap = SuratBapSenat::create([
            'nomor_bap' => '011/BAP-SENAT/X/2026',
            'tanggal_bap' => '2026-10-31',
            'user_id' => $dosen->id,
            'bidang_keilmuan' => 'Sistem Informasi',
            'prosentase_penelitian' => 300,
            'tempat_pelaksanaan' => 'Ruang Rapat Senat Lt. 2',
            'materi_rapat' => 'Pemberian pertimbangan kenaikan jabatan akademik dosen',
            'anggota_senat_ids' => [$ketua->user_id, $anggota->user_id],
        ]);

        $html = $this->actingAs($this->makeUser('admin'))
            ->get(route('surat.bap-senat.cetak', $bap))
            ->assertOk()
            ->getContent();

        // Pilihan halaman sebelum cetak: halaman 1 (berita acara) & halaman 2 (daftar hadir).
        $this->assertStringContainsString('Halaman 1 — Berita Acara', $html);
        $this->assertStringContainsString('Halaman 2 — Daftar Hadir', $html);
        $this->assertSame(2, substr_count($html, 'class="cetak-halaman'));

        // Pilihan margin sebelum cetak, default 1,5 cm semua sisi.
        $this->assertStringContainsString('id="margin-atas"', $html);
        $this->assertStringContainsString('value="1.5"', $html);
        $this->assertStringContainsString('@page { size: A4 portrait; margin: 1.5cm; }', $html);
        // Margin tiap halaman ditulis JS supaya mengikuti pilihan pengguna.
        $this->assertStringContainsString('cetak-ukuran', $html);

        // Nama badan penyelenggara tampil di atas nama institusi pada kop (dua halaman).
        $this->assertSame(2, substr_count($html, 'Perkumpulan Penyelenggara Lembaga Pendidikan'));
        $this->assertSame(2, substr_count($html, 'Perguruan Tinggi PGRI Pacitan'));

        // Kop dokumen senat: kata "Senat Akademik" mendahului nama instansi (dua halaman).
        $this->assertSame(2, substr_count($html, 'Senat Akademik Universitas Athena'));

        // Kop dokumen senat memakai logo senat (bukan logo instansi).
        $this->assertSame(2, substr_count($html, 'data:image/png;base64,logo-senat'));
        $this->assertStringNotContainsString('data:image/png;base64,logo-instansi', $html);

        // Seluruh naskah BAP senat memakai Times New Roman 16px; kop 16px penyelenggara, 18px instansi, 10px alamat.
        $this->assertStringContainsString("font-family: 'Times New Roman', Times, serif;", $html);
        $this->assertStringContainsString('font-size: 16px;', $html);
        $this->assertSame(4, substr_count($html, '<p class="text-[16px] font-bold uppercase">'));
        $this->assertSame(2, substr_count($html, '<p class="text-[18px] font-bold uppercase">'));
        $this->assertSame(2, substr_count($html, 'class="text-[10px]"'));

        // Jarak baris dirapatkan (naskah 1,375 dan tabel identitas 1,25) supaya naskah 16px
        // tetap muat satu halaman A4/F4 pada margin 1,5 cm.
        $this->assertStringContainsString('<table class="mt-2 w-full border-collapse text-[16px] leading-tight">', $html);
        $this->assertStringContainsString('<p class="mt-2 text-justify text-[16px] leading-snug">', $html);

        // Ruang tanda tangan 2,5 cm antara jabatan dan nama, baik di halaman 1 maupun 2.
        $this->assertSame(2, substr_count($html, '<div class="h-[2.5cm]" aria-hidden="true"></div>'));
        // Font aplikasi hanya tersisa pada panel alat cetak di luar kertas.
        $this->assertSame(1, substr_count($html, 'font-sans'));

        // Berita acara (halaman 1).
        $this->assertStringContainsString('Persetujuan Senat Akademik', $html);
        $this->assertStringContainsString('NOMOR: 011/BAP-SENAT/X/2026', $html);
        $this->assertStringContainsString('Sistem Informasi', $html);
        // Tanda tangan hanya untuk Ketua Senat.
        $this->assertStringContainsString('Ketua Senat', $html);
        $this->assertStringContainsString('Dr. Budi Santoso, M.Pd.', $html);

        // Daftar hadir (halaman 2) memuat waktu, tempat, materi, dan tabel kehadiran.
        $this->assertStringContainsString('DAFTAR HADIR RAPAT', $html);
        // Format tanggal daftar hadir: "Hari, DD Bulan YYYY" (bukan DD-MM-YYYY).
        $this->assertStringContainsString('Sabtu, 31 Oktober 2026', $html);
        $this->assertStringNotContainsString('Sabtu, 31-10-2026', $html);
        $this->assertStringContainsString('Ruang Rapat Senat Lt. 2', $html);
        $this->assertStringContainsString('Pemberian pertimbangan kenaikan jabatan akademik dosen', $html);
        $this->assertStringContainsString('Rina Wijaya, M.Kom.', $html);

        // Tanggal penanda tangan: "Kota, DD Bulan YYYY".
        $this->assertStringContainsString('Bandung, 31 Oktober 2026', $html);

        // Nama penanda tangan apa adanya sesuai database (tidak dipaksa kapital).
        $this->assertStringNotContainsString('DR. BUDI SANTOSO', $html);

        // Baris tabel tidak terpotong di tengah halaman.
        $this->assertStringContainsString('.cetak-tabel tr { break-inside: avoid; }', $html);
    }

    public function test_kop_bap_senat_falls_back_to_instansi_logo_when_senat_logo_is_empty(): void
    {
        Instansi::create([
            'nama_instansi' => 'Universitas Athena',
            'kota' => 'Bandung',
            'logo_base64' => 'data:image/png;base64,logo-instansi',
        ]);

        [$dosen, $ketua] = $this->makeSenat();

        $bap = SuratBapSenat::create([
            'nomor_bap' => '014/BAP-SENAT/X/2026',
            'tanggal_bap' => '2026-10-31',
            'user_id' => $dosen->id,
            'bidang_keilmuan' => 'Sistem Informasi',
            'prosentase_penelitian' => 150,
            'anggota_senat_ids' => [$ketua->user_id],
        ]);

        $html = $this->actingAs($this->makeUser('admin'))
            ->get(route('surat.bap-senat.cetak', $bap))
            ->assertOk()
            ->getContent();

        // Logo senat belum diunggah: kop tetap memakai logo instansi agar tidak kosong.
        $this->assertSame(2, substr_count($html, 'data:image/png;base64,logo-instansi'));
        $this->assertStringNotContainsString('logo-senat', $html);
        // Huruf Times New Roman 16px tetap berlaku untuk seluruh naskah.
        $this->assertStringContainsString("font-family: 'Times New Roman', Times, serif;", $html);
        $this->assertStringContainsString('font-size: 16px;', $html);
    }

    public function test_daftar_hadir_bap_senat_mengikuti_urutan_pilihan_admin(): void
    {
        Instansi::create(['nama_instansi' => 'Universitas Athena', 'kota' => 'Bandung']);

        [$dosen, $ketua, $anggota] = $this->makeSenat();

        $sekretaris = $this->makeUser('dosen', '198505052010011003', 'Dra. Sari Puspita, M.Si.');
        Senat::create(['user_id' => $sekretaris->id, 'jabatan' => 'sekretaris']);

        // Urutan pilih admin: anggota, lalu sekretaris, terakhir ketua. Daftar hadir mengikuti
        // urutan tersebut, bukan urutan jabatan maupun abjad.
        $bap = SuratBapSenat::create([
            'nomor_bap' => '015/BAP-SENAT/X/2026',
            'tanggal_bap' => '2026-10-31',
            'user_id' => $dosen->id,
            'bidang_keilmuan' => 'Sistem Informasi',
            'prosentase_penelitian' => 150,
            'anggota_senat_ids' => [$anggota->user_id, $sekretaris->id, $ketua->user_id],
        ]);

        $this->assertSame(
            [$anggota->user_id, $sekretaris->id, $ketua->user_id],
            $bap->anggotaSenat()->pluck('user_id')->all(),
        );

        $html = $this->actingAs($this->makeUser('admin'))
            ->get(route('surat.bap-senat.cetak', $bap))
            ->assertOk()
            ->getContent();

        // Tabel daftar hadir (halaman 2) mengikuti urutan pilihan admin.
        $tabel = substr($html, (int) strpos($html, 'DAFTAR HADIR RAPAT'));

        $posisiAnggota = strpos($tabel, 'Rina Wijaya, M.Kom.');
        $posisiSekretaris = strpos($tabel, 'Dra. Sari Puspita, M.Si.');
        $posisiKetua = strpos($tabel, 'Dr. Budi Santoso, M.Pd.');

        $this->assertNotFalse($posisiAnggota);
        $this->assertNotFalse($posisiSekretaris);
        $this->assertNotFalse($posisiKetua);
        $this->assertTrue($posisiAnggota < $posisiSekretaris, 'Pilihan pertama harus di atas pilihan kedua.');
        $this->assertTrue($posisiSekretaris < $posisiKetua, 'Pilihan kedua harus di atas pilihan ketiga.');
    }

    public function test_form_bap_senat_menampilkan_anggota_terpilih_sesuai_urutan_pilihan(): void
    {
        [$dosen, $ketua, $anggota] = $this->makeSenat();

        // Saat disunting, urutan pilihan tersimpan tetap tampil lebih dulu pada dropdown
        // agar urutan yang tersimpan tidak berubah tanpa sepengetahuan admin.
        $bap = SuratBapSenat::create([
            'nomor_bap' => '018/BAP-SENAT/X/2026',
            'tanggal_bap' => '2026-10-31',
            'user_id' => $dosen->id,
            'bidang_keilmuan' => 'Sistem Informasi',
            'prosentase_penelitian' => 150,
            'anggota_senat_ids' => [$anggota->user_id, $ketua->user_id],
        ]);

        $html = $this->actingAs($this->makeUser('admin'))
            ->get(route('surat.index', ['edit_bap' => $bap->id]))
            ->assertOk()
            ->getContent();

        $daftar = substr($html, (int) strpos($html, 'name="anggota_senat_ids[]"'));

        $posisiAnggota = strpos($daftar, 'Rina Wijaya, M.Kom.');
        $posisiKetua = strpos($daftar, 'Dr. Budi Santoso, M.Pd.');

        $this->assertNotFalse($posisiAnggota);
        $this->assertNotFalse($posisiKetua);
        $this->assertTrue($posisiAnggota < $posisiKetua, 'Anggota yang dipilih lebih dulu harus tampil lebih atas.');
    }

    public function test_daftar_hadir_bap_senat_menampilkan_jabatan_rangkapan(): void
    {
        Instansi::create(['nama_instansi' => 'Universitas Athena', 'kota' => 'Bandung']);

        $dosen = $this->makeUser('dosen', '1987654321', 'Dr. Ayu Lestari, M.Kom.');
        $ketua = $this->makeUser('dosen', '198001012010011001', 'Dr. Budi Santoso, M.Pd.');
        Senat::create([
            'user_id' => $ketua->id,
            'jabatan' => 'ketua',
            'jabatan_tambahan' => 'anggota',
        ]);

        $bap = SuratBapSenat::create([
            'nomor_bap' => '016/BAP-SENAT/X/2026',
            'tanggal_bap' => '2026-10-31',
            'user_id' => $dosen->id,
            'bidang_keilmuan' => 'Sistem Informasi',
            'prosentase_penelitian' => 150,
            'anggota_senat_ids' => [$ketua->id],
        ]);

        // Ketua yang merangkap anggota tetap satu baris pada daftar hadir.
        $this->assertSame(['ketua'], $bap->anggotaSenat()->pluck('jabatan')->all());
        $this->assertSame('Ketua merangkap Anggota', $bap->anggotaSenat()->first()->jabatanLengkap());

        $html = $this->actingAs($this->makeUser('admin'))
            ->get(route('surat.bap-senat.cetak', $bap))
            ->assertOk()
            ->getContent();

        $tabel = substr($html, (int) strpos($html, 'DAFTAR HADIR RAPAT'));
        $this->assertStringContainsString('Ketua merangkap Anggota', $tabel);
    }

    public function test_dosen_cannot_manage_bap_senat(): void
    {
        Instansi::create(['nama_instansi' => 'Universitas Athena', 'kota' => 'Bandung']);

        [$dosen, $ketua] = $this->makeSenat();

        $bap = SuratBapSenat::create([
            'nomor_bap' => '012/BAP-SENAT/X/2026',
            'tanggal_bap' => '2026-10-31',
            'user_id' => $dosen->id,
            'bidang_keilmuan' => 'Sistem Informasi',
            'prosentase_penelitian' => 150,
            'anggota_senat_ids' => [$ketua->user_id],
        ]);

        $this->actingAs($dosen)
            ->post(route('surat.bap-senat.store'), [
                'nomor_bap' => '013/BAP-SENAT/X/2026',
                'tanggal_bap' => '31-10-2026',
                'dosen_id' => $dosen->id,
                'bidang_keilmuan' => 'Sistem Informasi',
                'prosentase_penelitian' => 150,
                'anggota_senat_ids' => [$ketua->user_id],
            ])
            ->assertForbidden();

        $this->actingAs($dosen)->get(route('surat.bap-senat.cetak', $bap))->assertForbidden();
        $this->actingAs($dosen)->delete(route('surat.bap-senat.destroy', $bap))->assertForbidden();

        $this->assertDatabaseCount('surat_bap_senat', 1);
    }
}
