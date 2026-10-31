<?php

namespace Tests\Feature;

use App\Models\JabatanFungsional;
use App\Models\JabatanStruktural;
use App\Models\Kepangkatan;
use App\Models\Prodi;
use App\Models\RiwayatAngkaKredit;
use App\Models\RiwayatJabatanStruktural;
use App\Models\RiwayatKepangkatan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class RiwayatPekerjaanTest extends TestCase
{
    use RefreshDatabase;

    private function makeDosen(array $attributes = []): User
    {
        return User::create(array_merge([
            'nidn' => '1987654321',
            'name' => 'Dr. Ayu Lestari, M.Kom.',
            'email' => 'ayu@example.test',
            'program_studi' => 'Pendidikan Informatika',
            'jabatan_fungsional' => 'Lektor',
            'role' => 'dosen',
            'is_active' => true,
            'password' => Hash::make('password'),
        ], $attributes));
    }

    private function makeAdmin(): User
    {
        return User::create([
            'nidn' => '0000000001',
            'name' => 'Admin Kepegawaian',
            'email' => 'admin@example.test',
            'role' => 'admin',
            'is_active' => true,
            'password' => Hash::make('password'),
        ]);
    }

    /**
     * Data master kepegawaian yang dipakai sebagai pilihan select.
     *
     * @return array{kepangkatan: Kepangkatan, struktural: JabatanStruktural, fungsional: JabatanFungsional}
     */
    private function master(): array
    {
        return [
            'kepangkatan' => Kepangkatan::create(['nama' => 'Penata Muda (III/a)']),
            'struktural' => JabatanStruktural::create(['nama' => 'Kepala Lembaga Penjaminan Mutu']),
            'fungsional' => JabatanFungsional::create(['nama' => 'Lektor']),
        ];
    }

    public function test_dosen_can_open_riwayat_pekerjaan_page(): void
    {
        $master = $this->master();

        $response = $this->actingAs($this->makeDosen())->get(route('profil.pekerjaan.index'));

        $response->assertOk();
        $response->assertSee('Riwayat Pekerjaan');
        $response->assertSee('Masa Kerja');
        $response->assertSee('Riwayat Kepangkatan');
        $response->assertSee('Riwayat Jabatan Struktural');
        $response->assertSee($master['kepangkatan']->nama);
        $response->assertSee($master['struktural']->nama);
        $response->assertSee($master['fungsional']->nama);
    }

    public function test_dosen_can_save_identitas_and_masa_kerja(): void
    {
        $user = $this->makeDosen();

        $response = $this->actingAs($user)->put(route('profil.pekerjaan.update'), [
            'niy_npp' => '046/1234/NPP',
            'nuptk' => '1234567890123456',
            'sk_pplp_tanggal' => '2019-08-02',
            'sk_pplp_tahun' => 11,
            'sk_pplp_bulan' => 2,
            'sk_jafa_tanggal' => '2020-01-15',
            'sk_jafa_tahun' => 8,
            'sk_jafa_bulan' => 0,
        ]);

        $response->assertRedirect(route('profil.pekerjaan.index'));

        $this->assertDatabaseHas('profil_dosen', [
            'user_id' => $user->id,
            'niy_npp' => '046/1234/NPP',
            'nuptk' => '1234567890123456',
            'sk_pplp_tahun' => 11,
            'sk_pplp_bulan' => 2,
        ]);

        $this->actingAs($user)
            ->get(route('profil.pekerjaan.index'))
            ->assertSee('11 Tahun 02 Bulan')
            ->assertSee('8 Tahun 00 Bulan');
    }

    public function test_masa_kerja_tahun_dan_bulan_are_validated(): void
    {
        $response = $this->actingAs($this->makeDosen())->put(route('profil.pekerjaan.update'), [
            'sk_pplp_tahun' => 99,
            'sk_pplp_bulan' => 13,
        ]);

        $response->assertSessionHasErrors(['sk_pplp_tahun', 'sk_pplp_bulan']);
    }

    public function test_dosen_can_choose_prodi_tempat_mengajar_from_master_data(): void
    {
        $user = $this->makeDosen(['program_studi' => null]);
        $prodi = Prodi::create(['nama_prodi' => 'Pendidikan Informatika', 'kode_prodi' => 'PIF']);

        $this->actingAs($user)
            ->get(route('profil.pekerjaan.index'))
            ->assertOk()
            ->assertSee('Prodi Tempat Mengajar')
            ->assertSee($prodi->nama_prodi);

        $this->actingAs($user)
            ->put(route('profil.pekerjaan.update'), ['program_studi' => $prodi->nama_prodi])
            ->assertRedirect(route('profil.pekerjaan.index'))
            ->assertSessionHasNoErrors();

        $this->assertSame($prodi->nama_prodi, $user->fresh()->program_studi);
    }

    public function test_prodi_tempat_mengajar_must_exist_on_master_data(): void
    {
        $user = $this->makeDosen(['program_studi' => 'Pendidikan Informatika']);

        $response = $this->actingAs($user)->put(route('profil.pekerjaan.update'), [
            'program_studi' => 'Program Studi Karangan Sendiri',
        ]);

        $response->assertSessionHasErrors('program_studi');
        $this->assertSame('Pendidikan Informatika', $user->fresh()->program_studi);
    }

    public function test_dosen_can_add_riwayat_kepangkatan(): void
    {
        $user = $this->makeDosen();
        $master = $this->master();

        $response = $this->actingAs($user)->post(route('profil.pekerjaan.kepangkatan.store'), [
            'kepangkatan_id' => $master['kepangkatan']->id,
            'tmt' => '2019-08-02',
        ]);

        $response->assertRedirect(route('profil.pekerjaan.index'));

        $this->assertDatabaseHas('riwayat_kepangkatan', [
            'user_id' => $user->id,
            'kepangkatan_id' => $master['kepangkatan']->id,
        ]);

        $this->actingAs($user)
            ->get(route('profil.pekerjaan.index'))
            ->assertSee('Penata Muda (III/a)')
            ->assertSee('02 Agustus 2019');
    }

    public function test_dosen_can_update_riwayat_kepangkatan(): void
    {
        $user = $this->makeDosen();
        $master = $this->master();
        $lain = Kepangkatan::create(['nama' => 'Penata (III/c)']);

        $riwayat = RiwayatKepangkatan::create([
            'user_id' => $user->id,
            'kepangkatan_id' => $master['kepangkatan']->id,
            'tmt' => '2019-08-02',
        ]);

        $this->actingAs($user)
            ->put(route('profil.pekerjaan.kepangkatan.update', $riwayat), [
                'kepangkatan_id' => $lain->id,
                'tmt' => '2021-04-01',
            ])
            ->assertRedirect(route('profil.pekerjaan.index'));

        $this->assertDatabaseHas('riwayat_kepangkatan', [
            'id' => $riwayat->id,
            'kepangkatan_id' => $lain->id,
        ]);

        $this->assertSame('2021-04-01', $riwayat->fresh()->tmt->format('Y-m-d'));
    }

    public function test_dosen_can_delete_riwayat_kepangkatan(): void
    {
        $user = $this->makeDosen();
        $master = $this->master();

        $riwayat = RiwayatKepangkatan::create([
            'user_id' => $user->id,
            'kepangkatan_id' => $master['kepangkatan']->id,
            'tmt' => '2019-08-02',
        ]);

        $this->actingAs($user)
            ->delete(route('profil.pekerjaan.kepangkatan.destroy', $riwayat))
            ->assertRedirect(route('profil.pekerjaan.index'));

        $this->assertDatabaseMissing('riwayat_kepangkatan', ['id' => $riwayat->id]);
    }

    public function test_dosen_can_manage_riwayat_jabatan_struktural(): void
    {
        $user = $this->makeDosen();
        $master = $this->master();

        $this->actingAs($user)
            ->post(route('profil.pekerjaan.jabatan.store'), [
                'jabatan_struktural_id' => $master['struktural']->id,
                'jabatan_fungsional_id' => $master['fungsional']->id,
                'tmt' => '2019-08-02',
            ])
            ->assertRedirect(route('profil.pekerjaan.index'));

        $this->assertDatabaseHas('riwayat_jabatan_struktural', [
            'user_id' => $user->id,
            'jabatan_struktural_id' => $master['struktural']->id,
            'jabatan_fungsional_id' => $master['fungsional']->id,
        ]);

        $this->actingAs($user)
            ->get(route('profil.pekerjaan.index'))
            ->assertSee('Kepala Lembaga Penjaminan Mutu')
            ->assertSee('02 Agustus 2019');

        $riwayat = RiwayatJabatanStruktural::query()->firstOrFail();

        $this->actingAs($user)
            ->delete(route('profil.pekerjaan.jabatan.destroy', $riwayat))
            ->assertRedirect(route('profil.pekerjaan.index'));

        $this->assertDatabaseMissing('riwayat_jabatan_struktural', ['id' => $riwayat->id]);
    }

    public function test_riwayat_pekerjaan_fields_are_validated(): void
    {
        $response = $this->actingAs($this->makeDosen())
            ->post(route('profil.pekerjaan.kepangkatan.store'), []);

        $response->assertSessionHasErrors(['kepangkatan_id', 'tmt']);
    }

    public function test_dosen_cannot_change_another_dosen_riwayat(): void
    {
        $pemilik = $this->makeDosen();
        $lain = $this->makeDosen(['nidn' => '1987654322', 'email' => 'lain@example.test']);
        $master = $this->master();

        $riwayat = RiwayatKepangkatan::create([
            'user_id' => $pemilik->id,
            'kepangkatan_id' => $master['kepangkatan']->id,
            'tmt' => '2019-08-02',
        ]);

        $this->actingAs($lain)
            ->put(route('profil.pekerjaan.kepangkatan.update', $riwayat), [
                'kepangkatan_id' => $master['kepangkatan']->id,
                'tmt' => '2022-01-01',
            ])
            ->assertNotFound();

        $this->actingAs($lain)
            ->delete(route('profil.pekerjaan.kepangkatan.destroy', $riwayat))
            ->assertNotFound();

        $this->assertDatabaseHas('riwayat_kepangkatan', [
            'id' => $riwayat->id,
            'user_id' => $pemilik->id,
        ]);
    }

    public function test_dosen_can_save_masa_kerja_golongan(): void
    {
        $user = $this->makeDosen();

        $this->actingAs($user)
            ->put(route('profil.pekerjaan.update'), [
                'masa_kerja_golongan_lama_tahun' => 11,
                'masa_kerja_golongan_lama_bulan' => 2,
                'masa_kerja_golongan_baru_tahun' => 12,
                'masa_kerja_golongan_baru_bulan' => 4,
            ])
            ->assertRedirect(route('profil.pekerjaan.index'));

        $this->assertDatabaseHas('profil_dosen', [
            'user_id' => $user->id,
            'masa_kerja_golongan_lama_tahun' => 11,
            'masa_kerja_golongan_lama_bulan' => 2,
            'masa_kerja_golongan_baru_tahun' => 12,
            'masa_kerja_golongan_baru_bulan' => 4,
        ]);

        $this->actingAs($user)
            ->get(route('profil.pekerjaan.index'))
            ->assertSee('Masa Kerja Golongan Lama')
            ->assertSee('Masa Kerja Golongan Baru')
            ->assertSee('11 Tahun 02 Bulan')
            ->assertSee('12 Tahun 04 Bulan');
    }

    public function test_dosen_can_manage_riwayat_angka_kredit(): void
    {
        $user = $this->makeDosen();

        $this->actingAs($user)
            ->post(route('profil.pekerjaan.angka-kredit.store'), [
                'angka_kredit' => '152.50',
                'tahun' => 2026,
            ])
            ->assertRedirect(route('profil.pekerjaan.index'));

        $this->assertDatabaseHas('riwayat_angka_kredit', [
            'user_id' => $user->id,
            'tahun' => 2026,
            'angka_kredit' => 152.50,
        ]);

        $this->actingAs($user)
            ->get(route('profil.pekerjaan.index'))
            ->assertSee('Riwayat Angka Kredit')
            ->assertSee('Tahun Pengajuan')
            ->assertSee('2026')
            ->assertSee('152,50');

        $riwayat = RiwayatAngkaKredit::query()->firstOrFail();

        $this->actingAs($user)
            ->put(route('profil.pekerjaan.angka-kredit.update', $riwayat), [
                'angka_kredit' => '175.25',
                'tahun' => 2027,
            ])
            ->assertRedirect(route('profil.pekerjaan.index'));

        $this->assertDatabaseHas('riwayat_angka_kredit', [
            'id' => $riwayat->id,
            'tahun' => 2027,
            'angka_kredit' => 175.25,
        ]);

        $this->actingAs($user)
            ->delete(route('profil.pekerjaan.angka-kredit.destroy', $riwayat))
            ->assertRedirect(route('profil.pekerjaan.index'));

        $this->assertDatabaseMissing('riwayat_angka_kredit', ['id' => $riwayat->id]);
    }

    public function test_riwayat_angka_kredit_fields_are_validated(): void
    {
        $this->actingAs($this->makeDosen())
            ->post(route('profil.pekerjaan.angka-kredit.store'), [
                'angka_kredit' => 'bukan angka',
                'tahun' => 1900,
            ])
            ->assertSessionHasErrors(['angka_kredit', 'tahun']);
    }

    public function test_dosen_cannot_change_another_dosen_angka_kredit(): void
    {
        $pemilik = $this->makeDosen();
        $lain = $this->makeDosen(['nidn' => '1987654322', 'email' => 'lain@example.test']);

        $riwayat = RiwayatAngkaKredit::create([
            'user_id' => $pemilik->id,
            'tahun' => 2026,
            'angka_kredit' => 152.50,
        ]);

        $this->actingAs($lain)
            ->delete(route('profil.pekerjaan.angka-kredit.destroy', $riwayat))
            ->assertNotFound();

        $this->assertDatabaseHas('riwayat_angka_kredit', [
            'id' => $riwayat->id,
            'user_id' => $pemilik->id,
        ]);
    }

    public function test_admin_cannot_open_riwayat_pekerjaan_page(): void
    {
        $this->actingAs($this->makeAdmin())
            ->get(route('profil.pekerjaan.index'))
            ->assertForbidden();
    }
}
