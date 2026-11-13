<?php

namespace Tests\Feature;

use App\Models\Instansi;
use App\Models\ProfilDosen;
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
            ->assertSee('<optgroup label="E. Ekonomi">', false)
            ->assertSee('APLIKASI PERANGKAT LUNAK SITUS')
            ->assertSee('PENDIDIKAN BAHASA INGGRIS')
            ->assertSee('PENDIDIKAN MATEMATIKA')
            ->assertSee('KEAGAMAAN')
            ->assertSee('EKONOMI')
            ->assertSee('PENDIDIKAN EKONOMI')
            ->assertSee('EKONOMI SUMBERDAYA')
            ->assertSee('MANAJEMEN SDM')
            // Kode angka bidang keilmuan tidak lagi ditampilkan pada dropdown.
            ->assertDontSee('20.101.1003')
            ->assertDontSee('50.132.1020')
            ->assertDontSee('60.101.1001')
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

    public function test_bidang_keilmuan_pendidikan_sejarah_tampil_tanpa_kode_pada_rumpun_pendidikan_dan_keguruan(): void
    {
        $this->makeSenat();

        $html = $this->actingAs($this->makeUser('admin'))
            ->get(route('surat.index'))
            ->assertOk()
            ->getContent();

        // Label pilihan tidak lagi memuat kode angka di belakang nama bidang.
        $this->assertStringNotContainsString('PENDIDIKAN MATEMATIKA (', $html);
        $this->assertStringNotContainsString('APLIKASI PERANGKAT LUNAK SITUS (', $html);

        // "PENDIDIKAN SEJARAH" tersedia pada rumpun C. Pendidikan & Keguruan.
        $this->assertSame(1, preg_match(
            '/<optgroup label="C\. Pendidikan &amp; Keguruan">(.*?)<\/optgroup>/s',
            $html,
            $rumpun
        ), 'Rumpun C. Pendidikan & Keguruan tidak ditemukan pada dropdown bidang keilmuan.');

        $this->assertStringContainsString('<option value="PENDIDIKAN SEJARAH"', $rumpun[1]);
    }

    public function test_bidang_keilmuan_ekonomi_tersedia_pada_rumpun_ekonomi(): void
    {
        $this->makeSenat();

        $html = $this->actingAs($this->makeUser('admin'))
            ->get(route('surat.index'))
            ->assertOk()
            ->getContent();

        // Rumpun E memuat empat bidang ilmu ekonomi.
        $this->assertSame(1, preg_match(
            '/<optgroup label="E\. Ekonomi">(.*?)<\/optgroup>/s',
            $html,
            $rumpun
        ), 'Rumpun E. Ekonomi tidak ditemukan pada dropdown bidang keilmuan.');

        $this->assertStringContainsString('<option value="EKONOMI"', $rumpun[1]);
        $this->assertStringContainsString('<option value="PENDIDIKAN EKONOMI"', $rumpun[1]);
        $this->assertStringContainsString('<option value="EKONOMI SUMBERDAYA"', $rumpun[1]);
        $this->assertStringContainsString('<option value="MANAJEMEN SDM"', $rumpun[1]);

        // Keempat bidang tersebut tidak muncul pada rumpun lain.
        $this->assertSame(1, substr_count($html, '<option value="EKONOMI SUMBERDAYA"'));
        $this->assertSame(1, substr_count($html, '<option value="MANAJEMEN SDM"'));
    }

    public function test_admin_can_save_bap_senat_with_bidang_ekonomi(): void
    {
        [$dosen, $ketua] = $this->makeSenat();

        $this->actingAs($this->makeUser('admin'))
            ->post(route('surat.bap-senat.store'), [
                'nomor_bap' => '021/BAP-SENAT/XI/2026',
                'tanggal_bap' => '12-11-2026',
                'dosen_id' => $dosen->id,
                'bidang_keilmuan' => 'EKONOMI SUMBERDAYA',
                'kenaikan_jabatan' => 'asisten_ahli_ke_lektor',
                'prosentase_penelitian' => 150,
                'tempat_pelaksanaan' => 'Ruang Rapat Senat Lt. 2',
                'materi_rapat' => 'Pemberian pertimbangan kenaikan jabatan akademik dosen',
                'anggota_senat_ids' => [$ketua->user_id],
            ])
            ->assertRedirect(route('surat.index').'#bap-senat')
            ->assertSessionHasNoErrors();

        $this->assertSame('EKONOMI SUMBERDAYA', SuratBapSenat::query()->firstOrFail()->bidang_keilmuan);
    }

    public function test_admin_can_save_bap_senat_with_pendidikan_sejarah(): void
    {
        [$dosen, $ketua] = $this->makeSenat();

        $this->actingAs($this->makeUser('admin'))
            ->post(route('surat.bap-senat.store'), [
                'nomor_bap' => '020/BAP-SENAT/XI/2026',
                'tanggal_bap' => '12-11-2026',
                'dosen_id' => $dosen->id,
                'bidang_keilmuan' => 'PENDIDIKAN SEJARAH',
                'kenaikan_jabatan' => 'asisten_ahli_ke_lektor',
                'prosentase_penelitian' => 150,
                'tempat_pelaksanaan' => 'Ruang Rapat Senat Lt. 2',
                'materi_rapat' => 'Pemberian pertimbangan kenaikan jabatan akademik dosen',
                'anggota_senat_ids' => [$ketua->user_id],
            ])
            ->assertRedirect(route('surat.index').'#bap-senat')
            ->assertSessionHasNoErrors();

        $this->assertSame('PENDIDIKAN SEJARAH', SuratBapSenat::query()->firstOrFail()->bidang_keilmuan);
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

        // Pilihan format cetak V1 (model saat ini) / V2 (format lampiran).
        $this->assertStringContainsString('Format cetak', $html);
        $this->assertStringContainsString('versi=v1', $html);
        $this->assertStringContainsString('versi=v2', $html);

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

    public function test_cetak_bap_senat_menulis_status_kepegawaian_non_pns(): void
    {
        Instansi::create(['nama_instansi' => 'Universitas Athena', 'kota' => 'Bandung']);

        [$dosen, $ketua, $anggota] = $this->makeSenat();

        $bap = SuratBapSenat::create([
            'nomor_bap' => '016/BAP-SENAT/X/2026',
            'tanggal_bap' => '2026-10-31',
            'user_id' => $dosen->id,
            'bidang_keilmuan' => 'Sistem Informasi',
            'prosentase_penelitian' => 150,
            'anggota_senat_ids' => [$ketua->user_id, $anggota->user_id],
        ]);

        $this->actingAs($this->makeUser('admin'))
            ->get(route('surat.bap-senat.cetak', $bap))
            ->assertOk()
            ->assertSee('Status dosen tetap')
            ->assertSee('Non PNS')
            ->assertDontSee('Dosen Tetap');
    }

    public function test_cetak_bap_senat_format_v2_menampilkan_anggota_sesuai_urutan_pilihan(): void
    {
        Instansi::create(['nama_instansi' => 'Universitas Athena', 'kota' => 'Bandung']);

        [$dosen, $ketua, $anggota] = $this->makeSenat();

        // NUPTK tersimpan pada profil dosen; naskah V2 menuliskannya satu baris dengan NIDN.
        ProfilDosen::create(['user_id' => $dosen->id, 'nuptk' => '1234567890123456']);

        $sekretaris = $this->makeUser('dosen', '198505052010011003', 'Dra. Sari Puspita, M.Si.');
        Senat::create(['user_id' => $sekretaris->id, 'jabatan' => 'sekretaris']);

        // Urutan pilih admin: anggota, sekretaris, lalu ketua. Format V2 menomori
        // anggota senat mengikuti urutan tersebut (pilihan pertama = nomor 1).
        $bap = SuratBapSenat::create([
            'nomor_bap' => '019/BAP-SENAT/X/2026',
            'tanggal_bap' => '2026-10-31',
            'user_id' => $dosen->id,
            'bidang_keilmuan' => 'Sistem Informasi',
            'prosentase_penelitian' => 150,
            'anggota_senat_ids' => [$anggota->user_id, $sekretaris->id, $ketua->user_id],
        ]);

        $html = $this->actingAs($this->makeUser('admin'))
            ->get(route('surat.bap-senat.cetak', ['bap' => $bap, 'versi' => 'v2']))
            ->assertOk()
            ->getContent();

        // Pilihan format tetap tampil pada panel alat cetak.
        $this->assertStringContainsString('Format cetak', $html);
        $this->assertStringContainsString('versi=v1', $html);
        $this->assertStringContainsString('versi=v2', $html);

        // V2 dua halaman: berita acara + daftar anggota senat, lalu daftar hadir.
        // Pilihan halaman hanya ada pada V1, sehingga tidak tampil di sini.
        $this->assertStringNotContainsString('Halaman dicetak', $html);
        $this->assertStringContainsString('DAFTAR HADIR RAPAT', $html);
        $this->assertSame(2, substr_count($html, 'class="cetak-halaman'));

        // Judul lampiran, nomor BAP, dan identitas dosen yang diusulkan.
        $this->assertStringContainsString('Berita Acara Persetujuan Senat', $html);
        $this->assertStringContainsString('NOMOR: 019/BAP-SENAT/X/2026', $html);
        $this->assertStringContainsString('Status dosen tetap', $html);
        $this->assertStringContainsString('Non PNS', $html);

        // NIDN dan NUPTK ditulis pada satu baris seperti format V1 ("nidn / nuptk"),
        // bukan dua baris terpisah seperti format V2 sebelumnya.
        $this->assertMatchesRegularExpression(
            '/NIDN\/NUPTK\s*<\/td>\s*<td[^>]*>\s*:\s*<\/td>\s*<td[^>]*>\s*198001012010011001\s*\/\s*1234567890123456\s*<\/td>/',
            $html,
        );
        $this->assertStringNotContainsString('>NUPTK</td>', $html);

        // Tanda tangan sekretaris (kiri) dan ketua senat (kanan).
        $this->assertStringContainsString('Sekretaris,', $html);
        $this->assertStringContainsString('Ketua Senat,', $html);
        $this->assertStringContainsString('Dra. Sari Puspita, M.Si.', $html);
        $this->assertStringContainsString('Dr. Budi Santoso, M.Pd.', $html);
        $this->assertStringContainsString('NIDN. 198505052010011003', $html);

        // Daftar anggota senat: dua kolom seperti lampiran — kolom kiri memuat
        // separuh anggota pertama, kolom kanan melanjutkan nomornya — dan tiap nama
        // diberi garis tanda tangan pada kolom yang sama. Ketua & sekretaris tidak
        // diulang di sini karena tanda tangannya sudah ada pada berita acara di atas,
        // sehingga tinggal anggota lain (Rina Wijaya) yang bernomor 1.
        $awalDaftar = (int) strpos($html, '>Anggota Senat</p>');
        $daftar = substr($html, $awalDaftar, (int) strpos($html, 'DAFTAR HADIR RAPAT') - $awalDaftar);

        $this->assertMatchesRegularExpression(
            '/1\.\s*<\/td>\s*<td[^>]*>\s*Rina Wijaya, M\.Kom\./',
            $daftar,
            'Anggota selain ketua & sekretaris harus bernomor 1.',
        );
        $this->assertStringNotContainsString('Dra. Sari Puspita, M.Si.', $daftar);
        $this->assertStringNotContainsString('Dr. Budi Santoso, M.Pd.', $daftar);

        // Satu garis tanda tangan untuk satu anggota senat (kolom kiri). Kolom kanan
        // tetap disediakan selebar kolom kiri walau belum terpakai.
        $this->assertSame(1, substr_count($daftar, 'border-b border-slate-900'));

        // Tata letak dua kolom: kolom kiri (nomor, nama, garis) dan kolom kanan
        // (nomor, nama, garis), sehingga garis tanda tangan menempel pada namanya.
        $this->assertSame(2, substr_count($daftar, '<col style="width: 13%">'));
        $this->assertMatchesRegularExpression(
            '/Rina Wijaya, M\.Kom\.\s*<\/td>\s*<td[^>]*>\s*<div class="[^"]*border-b/',
            $daftar,
            'Garis tanda tangan kolom kiri harus mengikuti nama anggota.',
        );

        // Ukuran huruf V2 sama dengan V1 (16px, Times New Roman) — tidak ada lagi
        // pengecilan huruf khusus V2.
        $this->assertStringNotContainsString('text-[11px]', $html);
        $this->assertStringNotContainsString('cetak-kertas-v2', $html);
        $this->assertStringContainsString('font-size: 16px;', $html);

        // Naskah yang melebihi satu halaman A4 berlanjut otomatis: daftar anggota senat
        // tidak dipindah utuh ke halaman berikutnya (baris yang masih muat tetap dicetak
        // di halaman berjalan), tetapi tiap barisnya tidak boleh terpotong dan judulnya
        // tidak boleh menggantung tanpa isi.
        $this->assertStringNotContainsString('.cetak-anggota { break-inside: avoid; }', $html);
        $this->assertStringContainsString('.cetak-anggota tr { break-inside: avoid; }', $html);
        $this->assertStringContainsString('.cetak-anggota-judul { break-after: avoid; }', $html);
    }

    public function test_cetak_bap_senat_format_v2_menampilkan_daftar_hadir_dua_bagian_tanda_tangan(): void
    {
        Instansi::create(['nama_instansi' => 'Universitas Athena', 'kota' => 'Bandung']);

        [$dosen, $ketua, $anggota] = $this->makeSenat();

        $sekretarisUser = $this->makeUser('dosen', '198505052010011003', 'Dra. Sari Puspita, M.Si.');
        $sekretaris = Senat::create(['user_id' => $sekretarisUser->id, 'jabatan' => 'sekretaris']);

        $bap = SuratBapSenat::create([
            'nomor_bap' => '020/BAP-SENAT/X/2026',
            'tanggal_bap' => '2026-10-31',
            'user_id' => $dosen->id,
            'bidang_keilmuan' => 'Sistem Informasi',
            'prosentase_penelitian' => 150,
            'tempat_pelaksanaan' => 'Ruang Rapat Senat Lt. 2',
            'materi_rapat' => 'Pemberian pertimbangan kenaikan jabatan akademik dosen',
            'anggota_senat_ids' => [$anggota->user_id, $sekretaris->user_id, $ketua->user_id],
        ]);

        $html = $this->actingAs($this->makeUser('admin'))
            ->get(route('surat.bap-senat.cetak', ['bap' => $bap, 'versi' => 'v2']))
            ->assertOk()
            ->getContent();

        // Halaman 2 (daftar hadir) dimulai di lembar baru seperti format V1.
        $this->assertStringContainsString(
            '<section class="cetak-halaman cetak-pisah text-[16px] leading-snug" data-halaman="2">',
            $html,
        );

        $tabel = substr($html, (int) strpos($html, 'DAFTAR HADIR RAPAT'));

        $this->assertStringContainsString('SENAT AKADEMIK UNIVERSITAS ATHENA', $tabel);
        $this->assertStringContainsString('Sabtu, 31 Oktober 2026', $tabel);
        $this->assertStringContainsString('Ruang Rapat Senat Lt. 2', $tabel);
        $this->assertStringContainsString('Pemberian pertimbangan kenaikan jabatan akademik dosen', $tabel);

        // Kolom tanda tangan dipecah dua bagian bergaris seperti lampiran: satu judul
        // kolom (colspan 2) dan garis pemisah yang baru tampak pada baris isi.
        $this->assertStringContainsString(
            '<th colspan="2" class="border border-slate-900 px-2 py-1.5 text-center font-bold">TANDA TANGAN</th>',
            $tabel,
        );
        $this->assertSame(2, substr_count($tabel, '<col style="width: 17%">'));
        $this->assertStringContainsString('.cetak-ttd {', $html);
        $this->assertStringContainsString('height: 0.85cm;', $html);

        // Ketua & sekretaris tetap tercatat di daftar hadir walau tanda tangannya
        // sudah ada pada berita acara; urutannya mengikuti pilihan admin seperti V1.
        $this->assertMatchesRegularExpression(
            '/1<\/td>\s*<td[^>]*>\s*Rina Wijaya, M\.Kom\.\s*<\/td>\s*<td[^>]*>\s*Anggota\s*<\/td>/',
            $tabel,
        );
        $this->assertMatchesRegularExpression(
            '/2<\/td>\s*<td[^>]*>\s*Dra\. Sari Puspita, M\.Si\.\s*<\/td>\s*<td[^>]*>\s*Sekretaris\s*<\/td>/',
            $tabel,
        );
        $this->assertMatchesRegularExpression(
            '/3<\/td>\s*<td[^>]*>\s*Dr\. Budi Santoso, M\.Pd\.\s*<\/td>\s*<td[^>]*>\s*Ketua\s*<\/td>/',
            $tabel,
        );

        // Minimal 12 baris seperti V1, masing-masing dengan dua bagian tanda tangan.
        $this->assertSame(24, substr_count($tabel, 'class="cetak-ttd border border-slate-900"'));
        $this->assertSame(12, substr_count($tabel, 'px-2 py-1 text-center align-middle'));

        // Nomor baris ditulis pada bagian yang harus ditandatangani: baris ganjil di
        // bagian kiri, baris genap di bagian kanan (lihat lampiran).
        $this->assertMatchesRegularExpression(
            '/<td class="cetak-ttd border border-slate-900">1<\/td>\s*<td class="cetak-ttd border border-slate-900"><\/td>/',
            $tabel,
            'Baris ganjil harus menandatangani bagian kiri.',
        );
        $this->assertMatchesRegularExpression(
            '/<td class="cetak-ttd border border-slate-900"><\/td>\s*<td class="cetak-ttd border border-slate-900">2<\/td>/',
            $tabel,
            'Baris genap harus menandatangani bagian kanan.',
        );

        // Tanda tangan ketua tidak diulang di bawah daftar hadir karena sudah ada
        // pada halaman 1 (berita acara).
        $this->assertStringNotContainsString('Pemimpin rapat', $html);
    }

    public function test_cetak_bap_senat_format_v2_tidak_mengulang_ketua_dan_sekretaris_pada_daftar_anggota(): void
    {
        Instansi::create(['nama_instansi' => 'Universitas Athena', 'kota' => 'Bandung']);

        [$dosen, $ketua] = $this->makeSenat();

        $sekretarisUser = $this->makeUser('dosen', '198505052010011003', 'Dra. Sari Puspita, M.Si.');
        $sekretaris = Senat::create(['user_id' => $sekretarisUser->id, 'jabatan' => 'sekretaris']);

        // Hanya ketua & sekretaris yang dipilih. Keduanya sudah menandatangani berita
        // acara, sehingga daftar anggota senat tidak ditampilkan sama sekali.
        $bap = SuratBapSenat::create([
            'nomor_bap' => '021/BAP-SENAT/X/2026',
            'tanggal_bap' => '2026-10-31',
            'user_id' => $dosen->id,
            'bidang_keilmuan' => 'Sistem Informasi',
            'prosentase_penelitian' => 150,
            'anggota_senat_ids' => [$sekretaris->user_id, $ketua->user_id],
        ]);

        $html = $this->actingAs($this->makeUser('admin'))
            ->get(route('surat.bap-senat.cetak', ['bap' => $bap, 'versi' => 'v2']))
            ->assertOk()
            ->getContent();

        $this->assertStringNotContainsString('Anggota Senat</p>', $html);
        $this->assertStringNotContainsString('Belum ada anggota senat yang dipilih.', $html);

        // Daftar hadir tetap memuat keduanya beserta jabatannya.
        $tabel = substr($html, (int) strpos($html, 'DAFTAR HADIR RAPAT'));
        $this->assertStringContainsString('Dra. Sari Puspita, M.Si.', $tabel);
        $this->assertStringContainsString('Dr. Budi Santoso, M.Pd.', $tabel);
        $this->assertStringContainsString('Sekretaris', $tabel);
        $this->assertStringContainsString('Ketua', $tabel);
    }

    public function test_form_bap_senat_menampilkan_toggle_kuorum_otomatis_dan_manual(): void
    {
        $this->makeSenat();

        $html = $this->actingAs($this->makeUser('admin'))
            ->get(route('surat.index'))
            ->assertOk()
            ->assertSee('Jumlah Kuorum')
            ->assertSee('Otomatis (jumlah anggota senat)')
            ->assertSee('Manual (isi sendiri)')
            ->assertSee('name="kuorum_mode"', false)
            ->assertSee('value="otomatis"', false)
            ->assertSee('value="manual"', false)
            ->assertSee('data-kuorum-mode', false)
            ->assertSee('name="jumlah_kuorum"', false)
            ->getContent();

        // Toggle kuorum tampil di atas dropdown anggota senat.
        $posisiToggle = strpos($html, 'name="kuorum_mode"');
        $posisiAnggota = strpos($html, 'name="anggota_senat_ids[]"');

        $this->assertNotFalse($posisiToggle);
        $this->assertNotFalse($posisiAnggota);
        $this->assertTrue($posisiToggle < $posisiAnggota, 'Toggle kuorum harus berada di atas dropdown anggota senat.');

        // Default mode otomatis: isian angka disembunyikan dan tidak wajib diisi.
        $this->assertStringContainsString('data-grup-kuorum-manual class="mt-3 hidden"', $html);
        $this->assertStringContainsString('name="jumlah_kuorum" min="1" max="999" step="1" inputmode="numeric"', $html);
        $this->assertMatchesRegularExpression('/name="jumlah_kuorum"[^>]*\sdisabled/', $html);
    }

    public function test_form_bap_senat_menampilkan_isian_kuorum_manual_saat_disunting(): void
    {
        [$dosen, $ketua] = $this->makeSenat();

        $bap = SuratBapSenat::create([
            'nomor_bap' => '019/BAP-SENAT/X/2026',
            'tanggal_bap' => '2026-10-31',
            'user_id' => $dosen->id,
            'bidang_keilmuan' => 'Sistem Informasi',
            'prosentase_penelitian' => 150,
            'anggota_senat_ids' => [$ketua->user_id],
            'kuorum_mode' => SuratBapSenat::KUORUM_MANUAL,
            'jumlah_kuorum' => 13,
        ]);

        $html = $this->actingAs($this->makeUser('admin'))
            ->get(route('surat.index', ['edit_bap' => $bap->id]))
            ->assertOk()
            ->assertSee('value="13"', false)
            ->getContent();

        // Radio "Manual" terpilih dan isian angka tampil (tanpa kelas hidden).
        $this->assertMatchesRegularExpression('/value="manual"[^>]*\schecked/', $html);
        $this->assertStringContainsString('data-grup-kuorum-manual class="mt-3"', $html);
        $this->assertStringNotContainsString('data-grup-kuorum-manual class="mt-3 hidden"', $html);
        $this->assertDoesNotMatchRegularExpression('/name="jumlah_kuorum"[^>]*\sdisabled/', $html);
    }

    public function test_cetak_bap_senat_kuorum_otomatis_mengikuti_jumlah_anggota_senat(): void
    {
        Instansi::create(['nama_instansi' => 'Universitas Athena', 'kota' => 'Bandung']);

        [$dosen, $ketua, $anggota] = $this->makeSenat();
        $admin = $this->makeUser('admin');

        $this->actingAs($admin)
            ->post(route('surat.bap-senat.store'), [
                'nomor_bap' => '022/BAP-SENAT/XI/2026',
                'tanggal_bap' => '13-11-2026',
                'dosen_id' => $dosen->id,
                'bidang_keilmuan' => 'APLIKASI PERANGKAT LUNAK SITUS',
                'kenaikan_jabatan' => 'belum_asisten_ahli',
                'tempat_pelaksanaan' => 'Ruang Rapat Senat Lt. 2',
                'materi_rapat' => 'Pemberian pertimbangan kenaikan jabatan akademik dosen',
                'anggota_senat_ids' => [$ketua->user_id, $anggota->user_id],
                'kuorum_mode' => SuratBapSenat::KUORUM_OTOMATIS,
                // Angka yang ikut terkirim pada mode otomatis harus diabaikan.
                'jumlah_kuorum' => 40,
            ])
            ->assertRedirect(route('surat.index').'#bap-senat')
            ->assertSessionHasNoErrors();

        $bap = SuratBapSenat::query()->firstOrFail();

        $this->assertSame(SuratBapSenat::KUORUM_OTOMATIS, $bap->kuorum_mode);
        $this->assertNull($bap->jumlah_kuorum);
        $this->assertSame(2, $bap->jumlahKuorum(count($bap->anggota_senat_ids)));

        foreach ([null, 'v2'] as $versi) {
            $html = $this->actingAs($admin)
                ->get(route('surat.bap-senat.cetak', array_filter(['bap' => $bap, 'versi' => $versi])))
                ->assertOk()
                ->getContent();

            $this->assertMatchesRegularExpression(
                '/sejumlah\s+2\s+orang anggota senat/',
                $html,
                'Kuorum otomatis harus mencetak jumlah anggota senat (versi '.($versi ?? 'v1').').',
            );
        }
    }

    public function test_cetak_bap_senat_kuorum_manual_tercetak_di_v1_dan_v2(): void
    {
        Instansi::create(['nama_instansi' => 'Universitas Athena', 'kota' => 'Bandung']);

        [$dosen, $ketua, $anggota] = $this->makeSenat();
        $admin = $this->makeUser('admin');

        $this->actingAs($admin)
            ->post(route('surat.bap-senat.store'), [
                'nomor_bap' => '023/BAP-SENAT/XI/2026',
                'tanggal_bap' => '13-11-2026',
                'dosen_id' => $dosen->id,
                'bidang_keilmuan' => 'TEKNIK JARINGAN KOMPUTER',
                'kenaikan_jabatan' => 'belum_asisten_ahli',
                'tempat_pelaksanaan' => 'Ruang Rapat Senat Lt. 2',
                'materi_rapat' => 'Pemberian pertimbangan kenaikan jabatan akademik dosen',
                'anggota_senat_ids' => [$ketua->user_id, $anggota->user_id],
                'kuorum_mode' => SuratBapSenat::KUORUM_MANUAL,
                'jumlah_kuorum' => 13,
            ])
            ->assertRedirect(route('surat.index').'#bap-senat')
            ->assertSessionHasNoErrors();

        $bap = SuratBapSenat::query()->firstOrFail();

        $this->assertSame(SuratBapSenat::KUORUM_MANUAL, $bap->kuorum_mode);
        $this->assertSame(13, $bap->jumlah_kuorum);

        // Halaman cetak V1 dan V2 memakai angka manual, bukan jumlah anggota senat.
        foreach ([null, 'v2'] as $versi) {
            $html = $this->actingAs($admin)
                ->get(route('surat.bap-senat.cetak', array_filter(['bap' => $bap, 'versi' => $versi])))
                ->assertOk()
                ->getContent();

            $this->assertMatchesRegularExpression(
                '/sejumlah\s+13\s+orang anggota senat/',
                $html,
                'Kuorum manual harus tercetak pada versi '.($versi ?? 'v1').'.',
            );
        }
    }

    public function test_bap_senat_kuorum_manual_wajib_diisi_angkanya(): void
    {
        [$dosen, $ketua] = $this->makeSenat();

        $this->actingAs($this->makeUser('admin'))
            ->post(route('surat.bap-senat.store'), [
                'nomor_bap' => '024/BAP-SENAT/XI/2026',
                'tanggal_bap' => '13-11-2026',
                'dosen_id' => $dosen->id,
                'bidang_keilmuan' => 'EKONOMI',
                'kenaikan_jabatan' => 'belum_asisten_ahli',
                'tempat_pelaksanaan' => 'Ruang Rapat Senat Lt. 2',
                'materi_rapat' => 'Pemberian pertimbangan kenaikan jabatan akademik dosen',
                'anggota_senat_ids' => [$ketua->user_id],
                'kuorum_mode' => SuratBapSenat::KUORUM_MANUAL,
                'jumlah_kuorum' => '',
            ])
            ->assertSessionHasErrors(['jumlah_kuorum']);

        $this->assertDatabaseCount('surat_bap_senat', 0);
    }

    public function test_bap_senat_kembali_ke_kuorum_otomatis_mengosongkan_angka_manual(): void
    {
        [$dosen, $ketua] = $this->makeSenat();
        $admin = $this->makeUser('admin');

        $bap = SuratBapSenat::create([
            'nomor_bap' => '025/BAP-SENAT/XI/2026',
            'tanggal_bap' => '2026-11-13',
            'user_id' => $dosen->id,
            'bidang_keilmuan' => 'EKONOMI',
            'kenaikan_jabatan' => 'belum_asisten_ahli',
            'prosentase_penelitian' => null,
            'tempat_pelaksanaan' => 'Ruang Rapat Senat Lt. 2',
            'materi_rapat' => 'Pemberian pertimbangan kenaikan jabatan akademik dosen',
            'anggota_senat_ids' => [$ketua->user_id],
            'kuorum_mode' => SuratBapSenat::KUORUM_MANUAL,
            'jumlah_kuorum' => 13,
        ]);

        $this->actingAs($admin)
            ->put(route('surat.bap-senat.update', $bap), [
                'nomor_bap' => '025/BAP-SENAT/XI/2026',
                'tanggal_bap' => '13-11-2026',
                'dosen_id' => $dosen->id,
                'bidang_keilmuan' => 'EKONOMI',
                'kenaikan_jabatan' => 'belum_asisten_ahli',
                'tempat_pelaksanaan' => 'Ruang Rapat Senat Lt. 2',
                'materi_rapat' => 'Pemberian pertimbangan kenaikan jabatan akademik dosen',
                'anggota_senat_ids' => [$ketua->user_id],
                'kuorum_mode' => SuratBapSenat::KUORUM_OTOMATIS,
            ])
            ->assertRedirect(route('surat.index').'#bap-senat')
            ->assertSessionHasNoErrors();

        $bap->refresh();

        $this->assertSame(SuratBapSenat::KUORUM_OTOMATIS, $bap->kuorum_mode);
        $this->assertNull($bap->jumlah_kuorum);
        $this->assertSame(1, $bap->jumlahKuorum(count($bap->anggota_senat_ids)));
    }
}
