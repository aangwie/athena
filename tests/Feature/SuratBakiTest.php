<?php

namespace Tests\Feature;

use App\Models\Instansi;
use App\Models\Senat;
use App\Models\SuratBaki;
use App\Models\SuratBapSenat;
use App\Models\TimKomite;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class SuratBakiTest extends TestCase
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

    private function makeBapSenat(User $dosen): SuratBapSenat
    {
        $ketua = $this->makeUser('dosen', '198001012010011001', 'Dr. Budi Santoso, M.Pd.');
        $anggota = $this->makeUser('dosen', '199001122010012002', 'Rina Wijaya, M.Kom.');

        Senat::create(['user_id' => $ketua->id, 'jabatan' => 'ketua']);
        Senat::create(['user_id' => $anggota->id, 'jabatan' => 'anggota']);

        return SuratBapSenat::create([
            'nomor_bap' => '007/BAP-SENAT/X/2026',
            'tanggal_bap' => '2026-10-31',
            'user_id' => $dosen->id,
            'bidang_keilmuan' => 'Sistem Informasi',
            'prosentase_penelitian' => 30,
            'anggota_senat_ids' => [$ketua->id, $anggota->id],
        ]);
    }

    private function makeBaki(SuratBapSenat $bap, string $nomor = '001/BAKI/X/2026'): SuratBaki
    {
        return SuratBaki::create([
            'nomor_surat' => $nomor,
            'surat_bap_senat_id' => $bap->id,
            'tanggal_surat' => '2026-11-01',
            'tempat_pelaksanaan' => 'Ruang Rapat Tim Komite Lt. 2',
        ]);
    }

    public function test_baki_tab_shows_form_fields_and_usulan_senat_options(): void
    {
        $dosen = $this->makeUser('dosen');
        $bap = $this->makeBapSenat($dosen);

        $this->actingAs($this->makeUser('admin'))
            ->get(route('surat.index'))
            ->assertOk()
            ->assertSee('Data Berita Acara Komite Integritas Akademik')
            ->assertSee('Nomor Surat')
            ->assertSee('Nama Dosen Yang Diusulkan Senat')
            ->assertSee('Tanggal Surat')
            ->assertSee('DD-MM-YYYY')
            ->assertSee('data-select-search="Cari nama dosen…"', false)
            ->assertSee('data-date-mask', false)
            ->assertSee('data-date-picker', false)
            ->assertSee('Daftar BAKI')
            ->assertSee('data-datatable', false)
            ->assertSee('Dr. Ayu Lestari, M.Kom.')
            ->assertSee($bap->nomor_bap)
            ->assertSee('31-10-2026');
    }

    public function test_admin_can_save_and_update_baki(): void
    {
        $dosen = $this->makeUser('dosen');
        $bap = $this->makeBapSenat($dosen);
        $admin = $this->makeUser('admin');

        $this->actingAs($admin)
            ->post(route('surat.baki.store'), [
                'nomor_surat' => '001/BAKI/X/2026',
                'surat_bap_senat_id' => $bap->id,
                'tanggal_surat' => '01-11-2026',
                'tempat_pelaksanaan' => 'Ruang Rapat Tim Komite Lt. 2',
            ])
            ->assertRedirect(route('surat.index').'#baki')
            ->assertSessionHas('status', 'BAKI berhasil disimpan.');

        $baki = SuratBaki::query()->firstOrFail();

        $this->assertSame('001/BAKI/X/2026', $baki->nomor_surat);
        $this->assertSame($bap->id, $baki->surat_bap_senat_id);
        $this->assertSame('2026-11-01', $baki->tanggal_surat->toDateString());
        $this->assertSame('Ruang Rapat Tim Komite Lt. 2', $baki->tempat_pelaksanaan);
        $this->assertSame('Dr. Ayu Lestari, M.Kom.', $baki->bapSenat?->dosen?->name);

        $this->actingAs($admin)
            ->put(route('surat.baki.update', $baki), [
                'nomor_surat' => '002/BAKI/XI/2026',
                'surat_bap_senat_id' => $bap->id,
                'tanggal_surat' => '02-11-2026',
                'tempat_pelaksanaan' => 'Ruang Sidang Utama',
            ])
            ->assertRedirect(route('surat.index').'#baki')
            ->assertSessionHas('status', 'BAKI berhasil diperbarui.');

        $baki->refresh();

        $this->assertSame('002/BAKI/XI/2026', $baki->nomor_surat);
        $this->assertSame('2026-11-02', $baki->tanggal_surat->toDateString());
        $this->assertSame('Ruang Sidang Utama', $baki->tempat_pelaksanaan);
    }

    public function test_baki_table_lists_no_nama_dosen_tanggal_and_actions(): void
    {
        $dosen = $this->makeUser('dosen');
        $baki = $this->makeBaki($this->makeBapSenat($dosen));

        $this->actingAs($this->makeUser('admin'))
            ->get(route('surat.index'))
            ->assertOk()
            ->assertSeeInOrder(['No', 'Nama Dosen', 'Tanggal', 'Aksi'], false)
            ->assertSee('Dr. Ayu Lestari, M.Kom.')
            ->assertSee('01-11-2026')
            ->assertSee('aria-label="Print"', false)
            ->assertSee('aria-label="Edit"', false)
            ->assertSee('aria-label="Hapus"', false)
            ->assertSee(route('surat.baki.cetak', $baki), false)
            ->assertSee(route('surat.index', ['edit_baki' => $baki->id]), false)
            ->assertSee(route('surat.baki.destroy', $baki), false);
    }

    public function test_edit_parameter_prefills_baki_form(): void
    {
        $baki = $this->makeBaki($this->makeBapSenat($this->makeUser('dosen')), '009/BAKI/X/2026');

        $this->actingAs($this->makeUser('admin'))
            ->get(route('surat.index', ['edit_baki' => $baki->id]))
            ->assertOk()
            ->assertSee('Perbarui')
            ->assertSee(route('surat.baki.update', $baki), false)
            ->assertSee('value="009/BAKI/X/2026"', false)
            ->assertSee('value="01-11-2026"', false)
            ->assertSee('value="Ruang Rapat Tim Komite Lt. 2"', false)
            ->assertSee('Ubah BAKI nomor');
    }

    public function test_baki_rejects_invalid_input(): void
    {
        $this->makeBapSenat($this->makeUser('dosen'));

        $this->actingAs($this->makeUser('admin'))
            ->post(route('surat.baki.store'), [
                'nomor_surat' => '',
                'surat_bap_senat_id' => 9999,
                'tanggal_surat' => '01/11/2026',
                'tempat_pelaksanaan' => '',
            ])
            ->assertSessionHasErrors(['nomor_surat', 'surat_bap_senat_id', 'tanggal_surat', 'tempat_pelaksanaan']);

        $this->assertDatabaseCount('surat_baki', 0);
    }

    public function test_admin_can_delete_baki(): void
    {
        $baki = $this->makeBaki($this->makeBapSenat($this->makeUser('dosen')));

        $this->actingAs($this->makeUser('admin'))
            ->delete(route('surat.baki.destroy', $baki))
            ->assertRedirect(route('surat.index').'#baki')
            ->assertSessionHas('status', 'BAKI berhasil dihapus.');

        $this->assertDatabaseMissing('surat_baki', ['id' => $baki->id]);
    }

    public function test_admin_can_open_baki_print_preview_with_two_pages(): void
    {
        Instansi::create([
            'nama_instansi' => 'Universitas Athena',
            'kota' => 'Bandung',
            'alamat' => 'Jl. Jenderal Sudirman 1',
            'logo_base64' => 'data:image/png;base64,logo-instansi',
            'logo_senat_base64' => 'data:image/png;base64,logo-senat',
        ]);

        $dosen = $this->makeUser('dosen');
        $bap = $this->makeBapSenat($dosen);
        $baki = $this->makeBaki($bap);

        $ketua = $this->makeUser('dosen', '197501012000031001', 'Dr. Hendra Wijaya, M.Pd.');
        TimKomite::create(['user_id' => $ketua->id, 'jabatan' => 'ketua']);
        TimKomite::create([
            'user_id' => $this->makeUser('dosen', '198801012012011002', 'Sari Puspita, M.Kom.')->id,
            'jabatan' => 'anggota',
        ]);
        TimKomite::create([
            'user_id' => $this->makeUser('dosen', '198701012013011003', 'RINA WIJAYA, M.KOM.')->id,
            'jabatan' => 'anggota',
        ]);

        $html = $this->actingAs($this->makeUser('admin'))
            ->get(route('surat.baki.cetak', $baki))
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

        // Kop surat (nama badan penyelenggara) tampil di kedua halaman.
        $this->assertSame(2, substr_count($html, 'Perkumpulan Penyelenggara Lembaga Pendidikan'));
        $this->assertSame(2, substr_count($html, 'Perguruan Tinggi PGRI Pacitan'));

        // Kop BAKI tidak memakai awalan "Senat Akademik" (khusus dokumen BAP Senat).
        $this->assertStringNotContainsString('Senat Akademik', $html);

        // Kop BAKI memakai logo instansi (bukan logo senat) dengan huruf naskah Times New Roman.
        $this->assertSame(2, substr_count($html, 'data:image/png;base64,logo-instansi'));
        $this->assertStringNotContainsString('logo-senat', $html);
        $this->assertStringContainsString("font-family: 'Times New Roman', Times, serif;", $html);
        // Ukuran huruf kop: badan penyelenggara 16px, nama instansi 18px, alamat instansi 10px.
        $this->assertSame(4, substr_count($html, '<p class="text-[16px] font-bold uppercase">'));
        $this->assertSame(2, substr_count($html, '<p class="text-[18px] font-bold uppercase">'));
        $this->assertSame(2, substr_count($html, 'class="text-[10px]"'));

        // Jarak baris dirapatkan (naskah 1,375 dan tabel identitas 1,25) supaya naskah 16px
        // tetap muat satu halaman A4/F4 pada margin 1,5 cm.
        $this->assertStringContainsString('<table class="mt-2 w-full border-collapse text-[16px] leading-tight">', $html);
        $this->assertStringContainsString('<p class="mt-2 text-justify text-[16px] leading-snug">', $html);

        // Ruang tanda tangan 2,5 cm antara jabatan dan nama, baik di halaman 1 maupun 2.
        $this->assertSame(2, substr_count($html, '<div class="h-[2.5cm]" aria-hidden="true"></div>'));

        // Berita acara (halaman 1).
        $this->assertStringContainsString('Berita Acara Persetujuan', $html);
        $this->assertStringContainsString('Tim Komite Integritas Akademik', $html);
        $this->assertStringContainsString('NOMOR: 001/BAKI/X/2026', $html);
        $this->assertStringContainsString('007/BAP-SENAT/X/2026', $html);
        $this->assertStringContainsString('31 Oktober 2026', $html);
        $this->assertStringContainsString('Sistem Informasi', $html);
        $this->assertStringContainsString('Ketua Tim', $html);
        $this->assertStringContainsString('Dr. Hendra Wijaya, M.Pd.', $html);

        // Daftar hadir (halaman 2) memuat waktu, tempat, materi, dan tabel kehadiran.
        $this->assertStringContainsString('DAFTAR HADIR RAPAT TIM KOMITE INTEGRITAS AKADEMIK', $html);
        $this->assertStringContainsString('Tempat pelaksanaan', $html);
        // Format tanggal daftar hadir: "Hari, DD Bulan YYYY" (bukan DD-MM-YYYY).
        $this->assertStringContainsString('Minggu, 01 November 2026', $html);
        $this->assertStringNotContainsString('Minggu, 01-11-2026', $html);
        // Tempat pelaksanaan dari isian form tampil di halaman 2 (menggantikan titik-titik).
        $this->assertStringContainsString('Ruang Rapat Tim Komite Lt. 2', $html);
        $this->assertStringNotContainsString(str_repeat('.', 55), $html);
        $this->assertStringContainsString('Pemberian pertimbangan/persetujuan kenaikan jabatan akademik dosen', $html);
        $this->assertStringContainsString('Sari Puspita, M.Kom.', $html);

        // Tanggal penanda tangan: "Kota, DD Bulan YYYY".
        $this->assertStringContainsString('Bandung, 1 November 2026', $html);

        // Nama yang sudah kapital di database tetap kapital, penanda tangan tidak dipaksa kapital.
        $this->assertStringContainsString('RINA WIJAYA, M.KOM.', $html);
        $this->assertStringNotContainsString('DR. HENDRA WIJAYA', $html);

        // Baris tabel tidak terpotong di tengah halaman.
        $this->assertStringContainsString('.cetak-tabel tr { break-inside: avoid; }', $html);
    }

    public function test_daftar_hadir_baki_only_prints_rows_with_member_names(): void
    {
        $baki = $this->makeBaki($this->makeBapSenat($this->makeUser('dosen')));

        TimKomite::create([
            'user_id' => $this->makeUser('dosen', '197501012000031001', 'Dr. Hendra Wijaya, M.Pd.')->id,
            'jabatan' => 'ketua',
        ]);
        TimKomite::create([
            'user_id' => $this->makeUser('dosen', '198801012012011002', 'Sari Puspita, M.Kom.')->id,
            'jabatan' => 'anggota',
        ]);

        $html = $this->actingAs($this->makeUser('admin'))
            ->get(route('surat.baki.cetak', $baki))
            ->assertOk()
            ->getContent();

        // Tabel daftar hadir hanya berisi baris anggota (tidak ada baris kosong pengisi).
        $this->assertSame(1, preg_match('/<table class="cetak-tabel.*?<\/table>/s', $html, $cocok));
        // 1 baris header + 2 baris anggota bernama.
        $this->assertSame(3, substr_count($cocok[0], '<tr>'));

        // Jumlah peserta pada narasi berita acara mengikuti daftar hadir yang dicetak.
        $this->assertStringContainsString('sejumlah 2 orang tim', $html);
    }

    public function test_daftar_hadir_baki_follows_admin_input_order(): void
    {
        $baki = $this->makeBaki($this->makeBapSenat($this->makeUser('dosen')));

        // Nama sengaja tidak berurutan abjad dan ketua diinput terakhir, supaya
        // urutan daftar hadir mudah dibedakan dari urutan abjad/jabatan.
        TimKomite::create([
            'user_id' => $this->makeUser('dosen', '198801012012011002', 'Sari Puspita, M.Kom.')->id,
            'jabatan' => 'anggota',
        ]);
        TimKomite::create([
            'user_id' => $this->makeUser('dosen', '199512312020121003', 'Ahmad Fauzi, S.Kom.')->id,
            'jabatan' => 'anggota',
        ]);
        TimKomite::create([
            'user_id' => $this->makeUser('dosen', '197501012000031001', 'Dr. Hendra Wijaya, M.Pd.')->id,
            'jabatan' => 'ketua',
        ]);

        $html = $this->actingAs($this->makeUser('admin'))
            ->get(route('surat.baki.cetak', $baki))
            ->assertOk()
            ->getContent();

        // Daftar hadir (halaman 2) mengikuti urutan input admin (id menaik),
        // bukan abjad nama maupun pengelompokan jabatan.
        $tabel = substr($html, (int) strpos($html, 'DAFTAR HADIR RAPAT TIM KOMITE INTEGRITAS AKADEMIK'));
        $this->assertSame(1, preg_match('/<table class="cetak-tabel.*?<\/table>/s', $tabel, $cocok));
        $tabel = $cocok[0];

        $this->assertLessThan(
            strpos($tabel, 'Ahmad Fauzi, S.Kom.'),
            strpos($tabel, 'Sari Puspita, M.Kom.'),
        );
        $this->assertLessThan(
            strpos($tabel, 'Dr. Hendra Wijaya, M.Pd.'),
            strpos($tabel, 'Ahmad Fauzi, S.Kom.'),
        );

        // Kolom NO tetap berurutan 1, 2, 3 mengikuti urutan baris.
        $this->assertSame(3, preg_match_all('/text-center align-top">(\d+)</', $tabel, $nomor));
        $this->assertSame(['1', '2', '3'], $nomor[1]);
    }

    public function test_dosen_cannot_manage_baki(): void
    {
        $dosen = $this->makeUser('dosen');
        $baki = $this->makeBaki($this->makeBapSenat($dosen));

        $this->actingAs($dosen)
            ->post(route('surat.baki.store'), [])
            ->assertForbidden();

        $this->actingAs($dosen)
            ->get(route('surat.baki.cetak', $baki))
            ->assertForbidden();

        $this->actingAs($dosen)
            ->delete(route('surat.baki.destroy', $baki))
            ->assertForbidden();

        $this->assertDatabaseCount('surat_baki', 1);
    }

    public function test_cetak_baki_menulis_status_kepegawaian_non_pns(): void
    {
        $baki = $this->makeBaki($this->makeBapSenat($this->makeUser('dosen')));

        $this->actingAs($this->makeUser('admin'))
            ->get(route('surat.baki.cetak', $baki))
            ->assertOk()
            ->assertSee('Status dosen tetap')
            ->assertSee('Non PNS')
            ->assertDontSee('Dosen Tetap');
    }
}
