<?php

namespace Tests\Feature;

use App\Models\Instansi;
use App\Models\JabatanFungsional;
use App\Models\JabatanStruktural;
use App\Models\StrukturInstansi;
use App\Models\SuratPengantar;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class SuratPengantarTest extends TestCase
{
    use RefreshDatabase;

    private function makeUser(string $role, string $nidn, string $name, ?string $jabatan = null): User
    {
        return User::create([
            'nidn' => $nidn,
            'name' => $name,
            'email' => $nidn.'@example.test',
            'program_studi' => 'Pendidikan Informatika',
            'jabatan_fungsional' => $jabatan ?? 'Lektor',
            'role' => $role,
            'is_active' => true,
            'password' => Hash::make('password'),
        ]);
    }

    private function makeAdmin(): User
    {
        return $this->makeUser('admin', '0000000001', 'Admin Kepegawaian', 'Administrator');
    }

    private function makeKetua(): User
    {
        $ketua = $this->makeUser('dosen', '0708098001', 'Bakti Sutopo, S.S., M.A.', 'Lektor Kepala');

        $jabatan = JabatanStruktural::create(['nama' => 'Ketua']);

        StrukturInstansi::create(['jabatan_struktural_id' => $jabatan->id, 'user_id' => $ketua->id]);

        return $ketua;
    }

    private function payload(array $overrides = []): array
    {
        return array_merge([
            'nomor_surat' => '613/STKIP PGRI/KP/III/2026',
            'tanggal_surat' => '10-03-2026',
            'lampiran' => '1 berkas',
            'perihal' => 'Usulan Jabatan Akademik',
            'tujuan' => 'Kepala LLDIKTI Wilayah VII Jawa Timur',
            'kota_tujuan' => 'Surabaya',
            'jabatan_diusulkan' => 'Lektor Kepala',
        ], $overrides);
    }

    public function test_surat_pengantar_tab_shows_form_and_table_columns(): void
    {
        $dosen = $this->makeUser('dosen', '0722059901', 'Esti Nur Qorimah, M.Pd.', 'Tenaga Pengajar');
        JabatanFungsional::create(['nama' => 'Lektor Kepala']);

        $this->actingAs($this->makeAdmin())
            ->get(route('surat.index'))
            ->assertOk()
            ->assertSee('data-tab-target="surat-pengantar"', false)
            ->assertSee('Surat Pengantar')
            ->assertSee('Nomor Surat')
            ->assertSee('Lampiran')
            ->assertSee('Perihal')
            ->assertSee('Tanggal Surat')
            ->assertSee('data-date-picker', false)
            // Kota tujuan surat: baris ketiga blok tujuan.
            ->assertSee('Kota Tujuan Surat')
            ->assertSee('name="kota_tujuan"', false)
            // Bentuk surat: radio Perorangan/Kolektif.
            ->assertSee('Perorangan')
            ->assertSee('Kolektif')
            ->assertSee('data-pengantar-jenis', false)
            ->assertSee('name="kolektif" value="0"', false)
            ->assertSee('name="kolektif" value="1"', false)
            // Perorangan: dropdown satu dosen + jabatan akademik yang diusulkan.
            ->assertSee('id="pengantar_dosen_perorangan" name="dosen_ids[]"', false)
            ->assertSee('Diusulkan ke')
            ->assertSee('name="jabatan_diusulkan"', false)
            ->assertSee('Lektor Kepala')
            // Kolektif: multi select.
            ->assertSee('name="dosen_ids[]" multiple', false)
            ->assertSee('Daftar Surat Pengantar')
            ->assertSee('No Surat')
            ->assertSee('data-datatable', false)
            ->assertSee($dosen->name);
    }

    public function test_admin_can_save_update_and_delete_pribadi_pengantar(): void
    {
        $dosen = $this->makeUser('dosen', '0722059901', 'Esti Nur Qorimah, M.Pd.', 'Tenaga Pengajar');
        $admin = $this->makeAdmin();

        $this->actingAs($admin)
            ->post(route('surat.pengantar.store'), $this->payload(['dosen_ids' => [$dosen->id]]))
            ->assertRedirect(route('surat.index').'#surat-pengantar')
            ->assertSessionHas('status', 'Surat pengantar berhasil disimpan.');

        $pengantar = SuratPengantar::query()->firstOrFail();

        $this->assertSame('613/STKIP PGRI/KP/III/2026', $pengantar->nomor_surat);
        $this->assertSame('2026-03-10', $pengantar->tanggal_surat->toDateString());
        $this->assertSame('1 berkas', $pengantar->lampiran);
        $this->assertSame('Usulan Jabatan Akademik', $pengantar->perihal);
        $this->assertSame('Kepala LLDIKTI Wilayah VII Jawa Timur', $pengantar->tujuan);
        $this->assertSame('Surabaya', $pengantar->kota_tujuan);
        $this->assertSame('Lektor Kepala', $pengantar->jabatan_diusulkan);
        $this->assertFalse($pengantar->kolektif);
        $this->assertSame([$dosen->id], $pengantar->dosen_ids);

        $this->actingAs($admin)
            ->put(route('surat.pengantar.update', $pengantar), $this->payload([
                'nomor_surat' => '614/STKIP PGRI/KP/IV/2026',
                'dosen_ids' => [$dosen->id],
            ]))
            ->assertRedirect(route('surat.index').'#surat-pengantar')
            ->assertSessionHas('status', 'Surat pengantar berhasil diperbarui.');

        $this->assertSame('614/STKIP PGRI/KP/IV/2026', $pengantar->fresh()->nomor_surat);

        // Tabel daftar surat menampilkan nama dosen, nomor, dan tanggal surat.
        $this->actingAs($admin)
            ->get(route('surat.index'))
            ->assertOk()
            ->assertSee('614/STKIP PGRI/KP/IV/2026')
            ->assertSee('10-03-2026')
            ->assertSee('Esti Nur Qorimah, M.Pd.')
            ->assertSee(route('surat.pengantar.cetak', $pengantar), false);

        $this->actingAs($admin)
            ->delete(route('surat.pengantar.destroy', $pengantar))
            ->assertRedirect(route('surat.index').'#surat-pengantar')
            ->assertSessionHas('status', 'Surat pengantar berhasil dihapus.');

        $this->assertDatabaseCount('surat_pengantar', 0);
    }

    public function test_admin_can_save_kolektif_pengantar_with_many_dosen(): void
    {
        $admin = $this->makeAdmin();
        $satu = $this->makeUser('dosen', '0727119102', 'Welly Novitasari, M.Pd.', 'Tenaga Pengajar');
        $dua = $this->makeUser('dosen', '0703058901', 'Wira Dimuksa, M.Kom.', 'Tenaga Pengajar');

        $this->actingAs($admin)
            ->post(route('surat.pengantar.store'), $this->payload([
                'kolektif' => '1',
                'perihal' => 'Daftar Dosen Belum Masuk Aplikasi SIAP Jafa',
                'dosen_ids' => [$satu->id, $dua->id],
            ]))
            ->assertRedirect(route('surat.index').'#surat-pengantar')
            ->assertSessionHasNoErrors();

        $pengantar = SuratPengantar::query()->firstOrFail();

        $this->assertTrue($pengantar->kolektif);
        $this->assertNull($pengantar->jabatan_diusulkan);
        $this->assertSame([$satu->id, $dua->id], $pengantar->dosen_ids);
        $this->assertSame(2, $pengantar->dosenList()->count());

        // Baris tabel merangkum jumlah dosen untuk surat kolektif.
        $this->actingAs($admin)
            ->get(route('surat.index'))
            ->assertOk()
            ->assertSee('2 dosen')
            ->assertSee('Kolektif');
    }

    public function test_pribadi_pengantar_only_accepts_one_dosen(): void
    {
        $satu = $this->makeUser('dosen', '0727119102', 'Welly Novitasari, M.Pd.');
        $dua = $this->makeUser('dosen', '0703058901', 'Wira Dimuksa, M.Kom.');

        $this->actingAs($this->makeAdmin())
            ->post(route('surat.pengantar.store'), $this->payload(['dosen_ids' => [$satu->id, $dua->id]]))
            ->assertSessionHasErrors('dosen_ids');

        $this->assertDatabaseCount('surat_pengantar', 0);
    }

    public function test_pengantar_rejects_invalid_input(): void
    {
        $admin = $this->makeAdmin();

        $this->actingAs($admin)
            ->post(route('surat.pengantar.store'), [
                'nomor_surat' => '',
                'tanggal_surat' => '10/03/2026',
                'perihal' => '',
                'dosen_ids' => [],
            ])
            ->assertSessionHasErrors(['nomor_surat', 'tanggal_surat', 'perihal', 'dosen_ids']);

        $this->assertDatabaseCount('surat_pengantar', 0);
    }

    public function test_pribadi_pengantar_is_printed_with_identity_block(): void
    {
        Instansi::create([
            'nama_instansi' => 'STKIP PGRI Pacitan',
            'kota' => 'Pacitan',
            'alamat' => 'Jalan Cut Nyak Dien 4A Pacitan',
        ]);

        $ketua = $this->makeKetua();
        $dosen = $this->makeUser('dosen', '0722059901', 'Esti Nur Qorimah, M.Pd.', 'Tenaga Pengajar');

        $pengantar = SuratPengantar::create([
            'nomor_surat' => '613/STKIP PGRI/KP/III/2026',
            'lampiran' => '1 berkas',
            'perihal' => 'Usulan Jabatan Akademik',
            'tujuan' => 'Kepala LLDIKTI Wilayah VII Jawa Timur',
            'kota_tujuan' => 'Surabaya',
            'jabatan_diusulkan' => 'Lektor Kepala',
            'tanggal_surat' => '2026-03-10',
            'kolektif' => false,
            'dosen_ids' => [$dosen->id],
        ]);

        $response = $this->actingAs($this->makeAdmin())
            ->get(route('surat.pengantar.cetak', $pengantar))
            ->assertOk();

        $html = $response->getContent();

        // Kop surat sama seperti surat lain.
        $this->assertStringContainsString('Perkumpulan Penyelenggara Lembaga Pendidikan', $html);
        $this->assertStringContainsString('Perguruan Tinggi PGRI Pacitan', $html);
        // Kepala surat & tujuan.
        $this->assertStringContainsString('613/STKIP PGRI/KP/III/2026', $html);
        $this->assertStringContainsString('1 berkas', $html);
        $this->assertStringContainsString('Usulan Jabatan Akademik', $html);
        // Blok tujuan: "Kepada. Yth. <nama>" / "di" / "<kota tujuan>".
        $this->assertStringContainsString('Kepada. Yth. Kepala LLDIKTI Wilayah VII Jawa Timur', $html);
        $this->assertStringContainsString('Surabaya', $html);
        // Badan surat pribadi: blok identitas, bukan tabel.
        $this->assertStringContainsString('Bersama ini kami kirimkan berkas usulan Jabatan Akademik Dosen', $html);
        $this->assertStringContainsString('NIDN / NUPTK', $html);
        $this->assertStringContainsString('Jabatan Akademik', $html);
        $this->assertStringContainsString('Esti Nur Qorimah, M.Pd.', $html);
        $this->assertStringContainsString('0722059901', $html);
        $this->assertStringContainsString('Tenaga Pengajar', $html);
        // Baris jabatan yang diusulkan: di bawah Jabatan Akademik.
        $this->assertStringContainsString('Diusulkan', $html);
        $this->assertStringContainsString('Lektor Kepala', $html);
        $this->assertStringNotContainsString('Adapun kelengkapan data dosen di atas', $html);
        // Tanda tangan ketua instansi.
        $this->assertStringContainsString('Pacitan, 10 Maret 2026', $html);
        $this->assertStringContainsString($ketua->name, $html);
        $this->assertStringContainsString('NIDN. 0708098001', $html);

        // Pilihan margin sebelum cetak, default 1,5 cm semua sisi.
        $this->assertStringContainsString('id="margin-atas"', $html);
        $this->assertStringContainsString('value="1.5"', $html);
        $this->assertStringContainsString('@page { size: A4 portrait; margin: 1.5cm; }', $html);
        // Margin tiap halaman ditulis JS supaya mengikuti pilihan pengguna.
        $this->assertStringContainsString('cetak-ukuran', $html);
    }

    public function test_kolektif_pengantar_is_printed_with_dosen_table(): void
    {
        Instansi::create([
            'nama_instansi' => 'STKIP PGRI Pacitan',
            'kota' => 'Pacitan',
            'alamat' => 'Jalan Cut Nyak Dien 4A Pacitan',
        ]);

        $this->makeKetua();
        $satu = $this->makeUser('dosen', '0727119102', 'Welly Novitasari, M.Pd.', 'Tenaga Pengajar');
        $dua = $this->makeUser('dosen', '0703058901', 'Wira Dimuksa, M.Kom.', 'Tenaga Pengajar');

        $pengantar = SuratPengantar::create([
            'nomor_surat' => '613/STKIP PGRI/KP/III/2026',
            'lampiran' => '1 berkas',
            'perihal' => 'Daftar Dosen Belum Masuk Aplikasi SIAP Jafa',
            'tanggal_surat' => '2026-02-24',
            'kolektif' => true,
            'dosen_ids' => [$satu->id, $dua->id],
        ]);

        $response = $this->actingAs($this->makeAdmin())
            ->get(route('surat.pengantar.cetak', $pengantar))
            ->assertOk();

        $html = $response->getContent();

        // Badan surat kolektif: tabel dosen.
        $this->assertStringContainsString('Bersama ini kami kirimkan data dosen', $html);
        $this->assertStringContainsString('NUPTK', $html);
        $this->assertStringContainsString('Adapun kelengkapan data dosen di atas, sebagaimana terlampir.', $html);
        $this->assertStringContainsString('Welly Novitasari, M.Pd.', $html);
        $this->assertStringContainsString('Wira Dimuksa, M.Kom.', $html);
        $this->assertStringContainsString('0727119102', $html);
        $this->assertStringContainsString('0703058901', $html);
        // Bukan bentuk pribadi.
        $this->assertStringNotContainsString('Bersama ini kami kirimkan berkas usulan Jabatan Akademik Dosen', $html);

        // Pilihan margin sebelum cetak, default 1,5 cm semua sisi.
        $this->assertStringContainsString('id="margin-atas"', $html);
        $this->assertStringContainsString('value="1.5"', $html);
        $this->assertStringContainsString('@page { size: A4 portrait; margin: 1.5cm; }', $html);
        $this->assertStringContainsString('cetak-ukuran', $html);
    }

    public function test_dosen_cannot_manage_pengantar(): void
    {
        $dosen = $this->makeUser('dosen', '0722059901', 'Esti Nur Qorimah, M.Pd.');

        $pengantar = SuratPengantar::create([
            'nomor_surat' => '613/STKIP PGRI/KP/III/2026',
            'perihal' => 'Usulan Jabatan Akademik',
            'tanggal_surat' => '2026-03-10',
            'kolektif' => false,
            'dosen_ids' => [$dosen->id],
        ]);

        $this->actingAs($dosen)->get(route('surat.pengantar.cetak', $pengantar))->assertForbidden();
        $this->actingAs($dosen)->post(route('surat.pengantar.store'), $this->payload(['dosen_ids' => [$dosen->id]]))->assertForbidden();
        $this->actingAs($dosen)->put(route('surat.pengantar.update', $pengantar), $this->payload(['dosen_ids' => [$dosen->id]]))->assertForbidden();
        $this->actingAs($dosen)->delete(route('surat.pengantar.destroy', $pengantar))->assertForbidden();
    }
}
