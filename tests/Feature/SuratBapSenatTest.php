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
            ->assertSee('Nama Dosen')
            ->assertSee('Bidang Keilmuan')
            ->assertSee('Prosentase Proporsi Penelitian')
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
                'bidang_keilmuan' => 'Sistem Informasi',
                'prosentase_penelitian' => 25,
                'anggota_senat_ids' => [$ketua->user_id, $anggota->user_id],
            ])
            ->assertRedirect(route('surat.index').'#bap-senat')
            ->assertSessionHas('status', 'BAP senat berhasil disimpan.');

        $bap = SuratBapSenat::query()->firstOrFail();

        $this->assertSame('001/BAP-SENAT/X/2026', $bap->nomor_bap);
        $this->assertSame('2026-10-31', $bap->tanggal_bap->toDateString());
        $this->assertSame($dosen->id, $bap->user_id);
        $this->assertSame(25, $bap->prosentase_penelitian);
        $this->assertSame([$ketua->user_id, $anggota->user_id], $bap->anggota_senat_ids);

        $this->actingAs($admin)
            ->put(route('surat.bap-senat.update', $bap), [
                'nomor_bap' => '002/BAP-SENAT/XI/2026',
                'tanggal_bap' => '01-11-2026',
                'dosen_id' => $dosen->id,
                'bidang_keilmuan' => 'Rekayasa Perangkat Lunak',
                'prosentase_penelitian' => 40,
                'anggota_senat_ids' => [$ketua->user_id],
            ])
            ->assertRedirect(route('surat.index').'#bap-senat')
            ->assertSessionHas('status', 'BAP senat berhasil diperbarui.');

        $bap->refresh();

        $this->assertSame('002/BAP-SENAT/XI/2026', $bap->nomor_bap);
        $this->assertSame('2026-11-01', $bap->tanggal_bap->toDateString());
        $this->assertSame(40, $bap->prosentase_penelitian);
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
            'anggota_senat_ids' => [$ketua->user_id],
        ]);

        $this->actingAs($this->makeUser('admin'))
            ->get(route('surat.index', ['edit_bap' => $bap->id]))
            ->assertOk()
            ->assertSee('Perbarui')
            ->assertSee(route('surat.bap-senat.update', $bap), false)
            ->assertSee('value="009/BAP-SENAT/X/2026"', false)
            ->assertSee('value="31-10-2026"', false);
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
                'anggota_senat_ids' => [$bukanSenat->id],
            ])
            ->assertSessionHasErrors(['nomor_bap', 'tanggal_bap', 'bidang_keilmuan', 'prosentase_penelitian', 'anggota_senat_ids.0']);

        $this->actingAs($admin)
            ->post(route('surat.bap-senat.store'), [
                'nomor_bap' => '003/BAP-SENAT/X/2026',
                'tanggal_bap' => '31-10-2026',
                'dosen_id' => $dosen->id,
                'bidang_keilmuan' => 'Sistem Informasi',
                'prosentase_penelitian' => 20,
                'anggota_senat_ids' => [],
            ])
            ->assertSessionHasErrors('anggota_senat_ids');

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

    public function test_admin_can_print_bap_senat_as_two_page_a4_pdf(): void
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
            'anggota_senat_ids' => [$ketua->user_id, $anggota->user_id],
        ]);

        $response = $this->actingAs($this->makeUser('admin'))
            ->get(route('surat.bap-senat.cetak', $bap))
            ->assertOk()
            ->assertHeader('Content-Type', 'application/pdf');

        $isi = $response->getContent();

        $this->assertStringStartsWith('%PDF-', $isi);
        // A4 portrait (595.28 x 841.89 pt) sesuai dokumentasi FPDF.
        $this->assertStringContainsString('/MediaBox [0 0 595.28 841.89]', $isi);
        // Berita acara + daftar hadir rapat = dua halaman.
        $this->assertStringContainsString('/Count 2', $isi);
        $this->assertStringContainsString('inline; filename="bap-senat-dr-budi-santoso-mpd.pdf"', (string) $response->headers->get('Content-Disposition'));

        // Nama badan penyelenggara tampil di atas nama institusi pada kop.
        $teks = $this->teksPdf($isi);

        $this->assertStringContainsString('PERKUMPULAN PENYELENGGARA LEMBAGA PENDIDIKAN', $teks);
        $this->assertStringContainsString('PERGURUAN TINGGI PGRI PACITAN', $teks);
        // Tanggal penanda tangan: "Kota, DD Bulan YYYY".
        $this->assertStringContainsString('Bandung, 31 Oktober 2026', $teks);
        // Nama penanda tangan dicetak apa adanya sesuai database (tidak dipaksa kapital).
        $this->assertStringContainsString('Dr. Budi Santoso, M.Pd.', $teks);
        $this->assertStringNotContainsString('DR. BUDI SANTOSO', $teks);
        $this->assertLessThan(
            mb_strpos($teks, 'UNIVERSITAS ATHENA'),
            mb_strpos($teks, 'PERGURUAN TINGGI PGRI PACITAN'),
        );
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
