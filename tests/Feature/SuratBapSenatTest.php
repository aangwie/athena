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
            ->assertSee('Prosentase Proporsi Penelitian')
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
                'prosentase_penelitian' => 25,
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
        $this->assertSame(25, $bap->prosentase_penelitian);
        $this->assertSame('Ruang Rapat Senat Lt. 2', $bap->tempat_pelaksanaan);
        $this->assertSame('Pemberian pertimbangan kenaikan jabatan akademik dosen', $bap->materi_rapat);
        $this->assertSame([$ketua->user_id, $anggota->user_id], $bap->anggota_senat_ids);

        $this->actingAs($admin)
            ->put(route('surat.bap-senat.update', $bap), [
                'nomor_bap' => '002/BAP-SENAT/XI/2026',
                'tanggal_bap' => '01-11-2026',
                'dosen_id' => $dosen->id,
                'bidang_keilmuan' => 'TEKNIK JARINGAN KOMPUTER',
                'prosentase_penelitian' => 40,
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
        $this->assertSame(40, $bap->prosentase_penelitian);
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
            'prosentase_penelitian' => 30,
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
            ->assertSee('value="Sistem Informasi" selected', false);
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
                'prosentase_penelitian' => 45,
                'tempat_pelaksanaan' => 'Ruang Rapat Senat Lt. 2',
                'materi_rapat' => 'Pemberian pertimbangan kenaikan jabatan akademik',
                'anggota_senat_ids' => [$ketua->user_id],
            ])
            ->assertRedirect(route('surat.index').'#bap-senat')
            ->assertSessionHasNoErrors();

        $bap->refresh();

        $this->assertSame('Sistem Informasi', $bap->bidang_keilmuan);
        $this->assertSame(45, $bap->prosentase_penelitian);
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
                'prosentase_penelitian' => 20,
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
                'prosentase_penelitian' => 20,
                'tempat_pelaksanaan' => 'Ruang Rapat Senat',
                'materi_rapat' => 'Pemberian pertimbangan kenaikan jabatan akademik',
                'anggota_senat_ids' => [$dosen->id],
            ])
            ->assertSessionHasErrors('bidang_keilmuan');

        $this->assertDatabaseCount('surat_bap_senat', 0);
    }

    public function test_admin_can_delete_bap_senat(): void
    {
        [$dosen, $ketua] = $this->makeSenat();

        $bap = SuratBapSenat::create([
            'nomor_bap' => '010/BAP-SENAT/X/2026',
            'tanggal_bap' => '2026-10-31',
            'user_id' => $dosen->id,
            'bidang_keilmuan' => 'Sistem Informasi',
            'prosentase_penelitian' => 30,
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
        ]);

        [$dosen, $ketua, $anggota] = $this->makeSenat();

        $bap = SuratBapSenat::create([
            'nomor_bap' => '011/BAP-SENAT/X/2026',
            'tanggal_bap' => '2026-10-31',
            'user_id' => $dosen->id,
            'bidang_keilmuan' => 'Sistem Informasi',
            'prosentase_penelitian' => 35,
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

        // Berita acara (halaman 1).
        $this->assertStringContainsString('Persetujuan Senat Akademik', $html);
        $this->assertStringContainsString('NOMOR: 011/BAP-SENAT/X/2026', $html);
        $this->assertStringContainsString('Sistem Informasi', $html);
        // Tanda tangan hanya untuk Ketua Senat.
        $this->assertStringContainsString('Ketua Senat', $html);
        $this->assertStringContainsString('Dr. Budi Santoso, M.Pd.', $html);

        // Daftar hadir (halaman 2) memuat waktu, tempat, materi, dan tabel kehadiran.
        $this->assertStringContainsString('DAFTAR HADIR RAPAT', $html);
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

    public function test_dosen_cannot_manage_bap_senat(): void
    {
        Instansi::create(['nama_instansi' => 'Universitas Athena', 'kota' => 'Bandung']);

        [$dosen, $ketua] = $this->makeSenat();

        $bap = SuratBapSenat::create([
            'nomor_bap' => '012/BAP-SENAT/X/2026',
            'tanggal_bap' => '2026-10-31',
            'user_id' => $dosen->id,
            'bidang_keilmuan' => 'Sistem Informasi',
            'prosentase_penelitian' => 30,
            'anggota_senat_ids' => [$ketua->user_id],
        ]);

        $this->actingAs($dosen)
            ->post(route('surat.bap-senat.store'), [
                'nomor_bap' => '013/BAP-SENAT/X/2026',
                'tanggal_bap' => '31-10-2026',
                'dosen_id' => $dosen->id,
                'bidang_keilmuan' => 'Sistem Informasi',
                'prosentase_penelitian' => 10,
                'anggota_senat_ids' => [$ketua->user_id],
            ])
            ->assertForbidden();

        $this->actingAs($dosen)->get(route('surat.bap-senat.cetak', $bap))->assertForbidden();
        $this->actingAs($dosen)->delete(route('surat.bap-senat.destroy', $bap))->assertForbidden();

        $this->assertDatabaseCount('surat_bap_senat', 1);
    }
}
