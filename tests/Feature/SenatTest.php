<?php

namespace Tests\Feature;

use App\Models\Instansi;
use App\Models\Senat;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class SenatTest extends TestCase
{
    use RefreshDatabase;

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

    private function makeDosen(string $nidn = '1987654321', string $name = 'Dr. Ayu Lestari, M.Kom.', bool $active = true): User
    {
        return User::create([
            'nidn' => $nidn,
            'name' => $name,
            'email' => "{$nidn}@example.test",
            'program_studi' => 'Pendidikan Informatika',
            'jabatan_fungsional' => 'Lektor',
            'role' => 'dosen',
            'is_active' => $active,
            'password' => Hash::make('password'),
        ]);
    }

    public function test_senat_page_shows_dosen_options_and_member_table(): void
    {
        $admin = $this->makeAdmin();
        $ketua = $this->makeDosen('1987654321', 'Dr. Ayu Lestari, M.Kom.');
        $this->makeDosen('1990011220', 'Budi Santoso, M.Pd.');

        Senat::create(['user_id' => $ketua->id, 'jabatan' => 'ketua']);

        $this->actingAs($admin)
            ->get('/instansi/senat')
            ->assertOk()
            ->assertSee('Senat')
            ->assertSee('Nama Dosen')
            ->assertSee('Jabatan')
            ->assertSee('Daftar Anggota Senat')
            ->assertSee('data-datatable', false)
            ->assertSee('Dr. Ayu Lestari, M.Kom.')
            ->assertSee('Budi Santoso, M.Pd.')
            ->assertSee('Ketua')
            ->assertSee('Sekretaris')
            ->assertSee('Anggota')
            ->assertSee('Anggota/Ex Officio')
            ->assertSee('Merangkap Jabatan')
            ->assertSee('data-dependent-select="#jabatan_tambahan"', false);
    }

    public function test_sekretaris_is_a_valid_jabatan_and_sorted_after_ketua(): void
    {
        $admin = $this->makeAdmin();
        $ketua = $this->makeDosen('1987654321', 'Dr. Ayu Lestari, M.Kom.');
        $sekretaris = $this->makeDosen('1990011220', 'Budi Santoso, M.Pd.');
        $anggota = $this->makeDosen('1995050520', 'Ahmad Fauzi, M.T.');

        Senat::create(['user_id' => $anggota->id, 'jabatan' => 'anggota']);

        $this->actingAs($admin)
            ->post('/instansi/senat', ['user_id' => $sekretaris->id, 'jabatan' => 'sekretaris'])
            ->assertRedirect(route('instansi.senat.index'))
            ->assertSessionHasNoErrors();

        $this->assertSame('sekretaris', Senat::where('user_id', $sekretaris->id)->value('jabatan'));

        Senat::create(['user_id' => $ketua->id, 'jabatan' => 'ketua']);

        $urutan = $this->actingAs($admin)
            ->get('/instansi/senat')
            ->assertOk()
            ->viewData('items')
            ->pluck('jabatan')
            ->all();

        $this->assertSame(['ketua', 'sekretaris', 'anggota'], $urutan);
    }

    public function test_anggota_ex_officio_is_a_valid_jabatan_and_sorted_after_anggota(): void
    {
        $admin = $this->makeAdmin();
        $anggota = $this->makeDosen('1995050520', 'Ahmad Fauzi, M.T.');
        $exOfficio = $this->makeDosen('1990011220', 'Budi Santoso, M.Pd.');
        $exOfficioLain = $this->makeDosen('1993030320', 'Citra Dewi, M.Kom.');

        // Jabatan baru dapat disimpan, dan lebih dari satu dosen boleh ex officio
        // (batasan satu orang hanya berlaku untuk jabatan ketua).
        foreach ([$exOfficio, $exOfficioLain] as $dosen) {
            $this->actingAs($admin)
                ->post('/instansi/senat', ['user_id' => $dosen->id, 'jabatan' => 'anggota_ex_officio'])
                ->assertRedirect(route('instansi.senat.index'))
                ->assertSessionHasNoErrors();
        }

        $this->assertSame(
            ['anggota_ex_officio', 'anggota_ex_officio'],
            Senat::whereIn('user_id', [$exOfficio->id, $exOfficioLain->id])->pluck('jabatan')->all(),
        );

        Senat::create(['user_id' => $anggota->id, 'jabatan' => 'anggota']);

        $items = $this->actingAs($admin)
            ->get('/instansi/senat')
            ->assertOk()
            ->assertSee('Anggota/Ex Officio')
            ->viewData('items');

        // Anggota/Ex Officio tampil setelah anggota biasa.
        $this->assertSame(
            ['anggota', 'anggota_ex_officio', 'anggota_ex_officio'],
            $items->pluck('jabatan')->all(),
        );

        // Dalam kelompok jabatan yang sama, urutan mengikuti abjad nama dosen.
        $this->assertSame(
            ['Ahmad Fauzi, M.T.', 'Budi Santoso, M.Pd.', 'Citra Dewi, M.Kom.'],
            $items->pluck('user.name')->all(),
        );
    }

    public function test_ketua_boleh_merangkap_anggota_dalam_satu_baris_senat(): void
    {
        $admin = $this->makeAdmin();
        $ketua = $this->makeDosen('1987654321', 'Dr. Ayu Lestari, M.Kom.');

        $this->actingAs($admin)
            ->post('/instansi/senat', [
                'user_id' => $ketua->id,
                'jabatan' => 'ketua',
                'jabatan_tambahan' => 'anggota',
            ])
            ->assertRedirect(route('instansi.senat.index'))
            ->assertSessionHasNoErrors();

        // Rangkapan disimpan pada baris yang sama, bukan baris senat baru.
        $senat = Senat::firstOrFail();
        $this->assertSame('ketua', $senat->jabatan);
        $this->assertSame('anggota', $senat->jabatan_tambahan);
        $this->assertSame('Ketua merangkap Anggota', $senat->jabatanLengkap());
        $this->assertDatabaseCount('senat', 1);

        $this->actingAs($admin)
            ->get('/instansi/senat')
            ->assertOk()
            ->assertSee('Ketua merangkap Anggota');
    }

    public function test_sekretaris_boleh_merangkap_anggota_dan_rangkapan_dapat_dikosongkan(): void
    {
        $admin = $this->makeAdmin();
        $sekretaris = $this->makeDosen('1990011220', 'Budi Santoso, M.Pd.');

        $this->actingAs($admin)
            ->post('/instansi/senat', [
                'user_id' => $sekretaris->id,
                'jabatan' => 'sekretaris',
                'jabatan_tambahan' => 'anggota',
            ])
            ->assertSessionHasNoErrors();

        $senat = Senat::firstOrFail();
        $this->assertSame('Sekretaris merangkap Anggota', $senat->jabatanLengkap());

        // Pilihan "Tidak merangkap" mengosongkan kembali jabatan rangkapan.
        $this->actingAs($admin)
            ->put(route('instansi.senat.update', $senat), [
                'user_id' => $sekretaris->id,
                'jabatan' => 'sekretaris',
                'jabatan_tambahan' => '',
            ])
            ->assertRedirect(route('instansi.senat.index'))
            ->assertSessionHasNoErrors();

        $senat->refresh();
        $this->assertNull($senat->jabatan_tambahan);
        $this->assertSame('Sekretaris', $senat->jabatanLengkap());
    }

    public function test_jabatan_rangkapan_menolak_ketua_jabatan_yang_sama_dan_nilai_tidak_dikenal(): void
    {
        $admin = $this->makeAdmin();
        $dosen = $this->makeDosen();

        // Ketua tidak dapat dirangkap: hanya boleh sebagai jabatan utama.
        $this->actingAs($admin)
            ->from('/instansi/senat')
            ->post('/instansi/senat', [
                'user_id' => $dosen->id,
                'jabatan' => 'sekretaris',
                'jabatan_tambahan' => 'ketua',
            ])
            ->assertSessionHasErrors([
                'jabatan_tambahan' => 'Jabatan rangkapan tidak valid. Ketua tidak dapat dirangkap.',
            ]);

        // Rangkapan tidak boleh sama dengan jabatan utama.
        $this->actingAs($admin)
            ->from('/instansi/senat')
            ->post('/instansi/senat', [
                'user_id' => $dosen->id,
                'jabatan' => 'anggota',
                'jabatan_tambahan' => 'anggota',
            ])
            ->assertSessionHasErrors([
                'jabatan_tambahan' => 'Jabatan rangkapan tidak boleh sama dengan jabatan utama.',
            ]);

        // Jabatan di luar daftar senat tetap ditolak.
        $this->actingAs($admin)
            ->from('/instansi/senat')
            ->post('/instansi/senat', [
                'user_id' => $dosen->id,
                'jabatan' => 'anggota',
                'jabatan_tambahan' => 'bendahara',
            ])
            ->assertSessionHasErrors('jabatan_tambahan');

        $this->assertDatabaseCount('senat', 0);
    }

    public function test_opsi_jabatan_rangkapan_mengikuti_jabatan_utama(): void
    {
        // Semua jabatan boleh dirangkap kecuali Ketua.
        $this->assertSame(
            ['sekretaris', 'anggota', 'anggota_ex_officio'],
            array_keys(Senat::rangkapanOptions()),
        );

        $peta = Senat::rangkapanPerJabatan();

        // Jabatan utama tidak ditawarkan sebagai rangkapannya sendiri.
        $this->assertSame(['sekretaris', 'anggota', 'anggota_ex_officio'], array_keys($peta['ketua']));
        $this->assertSame(['anggota', 'anggota_ex_officio'], array_keys($peta['sekretaris']));
        $this->assertSame(['sekretaris', 'anggota_ex_officio'], array_keys($peta['anggota']));
        $this->assertSame(['sekretaris', 'anggota'], array_keys($peta['anggota_ex_officio']));
    }

    public function test_dosen_merangkap_diurutkan_memakai_jabatan_tertinggi(): void
    {
        $admin = $this->makeAdmin();
        $ketua = $this->makeDosen('1987654321', 'Dr. Ayu Lestari, M.Kom.');
        $anggota = $this->makeDosen('1995050520', 'Ahmad Fauzi, M.T.');
        $merangkap = $this->makeDosen('1993030320', 'Citra Dewi, M.Kom.');

        Senat::create(['user_id' => $ketua->id, 'jabatan' => 'ketua']);
        Senat::create(['user_id' => $anggota->id, 'jabatan' => 'anggota']);
        // Anggota yang merangkap sekretaris tampil bersama kelompok sekretaris.
        Senat::create([
            'user_id' => $merangkap->id,
            'jabatan' => 'anggota',
            'jabatan_tambahan' => 'sekretaris',
        ]);

        $items = $this->actingAs($admin)
            ->get('/instansi/senat')
            ->assertOk()
            ->assertSee('Anggota merangkap Sekretaris')
            ->viewData('items');

        $this->assertSame(
            ['Dr. Ayu Lestari, M.Kom.', 'Citra Dewi, M.Kom.', 'Ahmad Fauzi, M.T.'],
            $items->pluck('user.name')->all(),
        );
    }

    public function test_senat_menu_is_listed_on_admin_sidebar(): void
    {
        $this->actingAs($this->makeAdmin())
            ->get('/instansi/senat')
            ->assertOk()
            ->assertSee(route('instansi.senat.index'), false);
    }

    public function test_admin_can_add_update_and_delete_anggota_senat(): void
    {
        $admin = $this->makeAdmin();
        $dosen = $this->makeDosen();
        $lain = $this->makeDosen('1990011220', 'Budi Santoso, M.Pd.');

        $this->actingAs($admin)
            ->post('/instansi/senat', ['user_id' => $dosen->id, 'jabatan' => 'ketua'])
            ->assertRedirect(route('instansi.senat.index'))
            ->assertSessionHasNoErrors();

        $senat = Senat::firstOrFail();
        $this->assertSame($dosen->id, $senat->user_id);
        $this->assertSame('ketua', $senat->jabatan);

        $this->actingAs($admin)
            ->put(route('instansi.senat.update', $senat), ['user_id' => $lain->id, 'jabatan' => 'anggota'])
            ->assertRedirect(route('instansi.senat.index'))
            ->assertSessionHasNoErrors();

        $senat->refresh();
        $this->assertSame($lain->id, $senat->user_id);
        $this->assertSame('anggota', $senat->jabatan);

        $this->actingAs($admin)
            ->delete(route('instansi.senat.destroy', $senat))
            ->assertRedirect(route('instansi.senat.index'));

        $this->assertDatabaseMissing('senat', ['id' => $senat->id]);
    }

    public function test_senat_rejects_inactive_dosen_invalid_jabatan_and_duplicate_member(): void
    {
        $admin = $this->makeAdmin();
        $dosen = $this->makeDosen();
        $nonaktif = $this->makeDosen('1990011220', 'Budi Santoso, M.Pd.', false);

        $this->actingAs($admin)
            ->from('/instansi/senat')
            ->post('/instansi/senat', ['user_id' => $nonaktif->id, 'jabatan' => 'ketua'])
            ->assertRedirect('/instansi/senat')
            ->assertSessionHasErrors('user_id');

        $this->actingAs($admin)
            ->from('/instansi/senat')
            ->post('/instansi/senat', ['user_id' => $dosen->id, 'jabatan' => 'bendahara'])
            ->assertSessionHasErrors('jabatan');

        $this->assertDatabaseCount('senat', 0);

        Senat::create(['user_id' => $dosen->id, 'jabatan' => 'ketua']);

        $this->actingAs($admin)
            ->from('/instansi/senat')
            ->post('/instansi/senat', ['user_id' => $dosen->id, 'jabatan' => 'anggota'])
            ->assertSessionHasErrors('user_id');

        $this->assertDatabaseCount('senat', 1);
    }

    public function test_edit_parameter_prefills_senat_form(): void
    {
        $admin = $this->makeAdmin();
        $dosen = $this->makeDosen();

        $senat = Senat::create(['user_id' => $dosen->id, 'jabatan' => 'anggota']);

        $this->actingAs($admin)
            ->get(route('instansi.senat.index', ['edit' => $senat->id]))
            ->assertOk()
            ->assertSee('Edit Anggota Senat')
            ->assertSee('Simpan Perubahan')
            ->assertSee(route('instansi.senat.update', $senat), false);
    }

    public function test_ketua_only_one_and_cannot_be_saved_twice(): void
    {
        $admin = $this->makeAdmin();
        $ketua = $this->makeDosen('1987654321', 'Dr. Ayu Lestari, M.Kom.');
        $lain = $this->makeDosen('1990011220', 'Budi Santoso, M.Pd.');

        Senat::create(['user_id' => $ketua->id, 'jabatan' => 'ketua']);

        $this->actingAs($admin)
            ->from('/instansi/senat')
            ->post('/instansi/senat', ['user_id' => $lain->id, 'jabatan' => 'ketua'])
            ->assertRedirect('/instansi/senat')
            ->assertSessionHasErrors([
                'jabatan' => 'Ketua senat sudah ada. Hapus atau ubah jabatan ketua sebelumnya terlebih dahulu.',
            ]);

        $this->assertDatabaseCount('senat', 1);

        // Opsi ketua dikunci di formulir dan statusnya dijelaskan.
        $this->actingAs($admin)
            ->get('/instansi/senat')
            ->assertOk()
            ->assertSee('sudah terisi')
            ->assertSee('ubah atau hapus dulu ketua atas nama Dr. Ayu Lestari, M.Kom.');

        // Baris ketua yang sama tetap boleh disimpan ulang.
        $this->actingAs($admin)
            ->put(route('instansi.senat.update', Senat::firstOrFail()), [
                'user_id' => $ketua->id,
                'jabatan' => 'ketua',
            ])
            ->assertSessionHasNoErrors();

        // Setelah ketua dihapus, jabatan ketua bisa dipakai lagi.
        $this->actingAs($admin)->delete(route('instansi.senat.destroy', Senat::firstOrFail()));

        $this->actingAs($admin)
            ->post('/instansi/senat', ['user_id' => $lain->id, 'jabatan' => 'ketua'])
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('senat', ['user_id' => $lain->id, 'jabatan' => 'ketua']);
    }

    public function test_senat_page_shows_logo_upload_form(): void
    {
        $this->actingAs($this->makeAdmin())
            ->get('/instansi/senat')
            ->assertOk()
            ->assertSee('Logo Senat')
            ->assertSee('Berkas Logo Senat')
            ->assertSee('Simpan Logo Senat')
            ->assertSee(route('instansi.senat.logo'), false)
            ->assertSee('enctype="multipart/form-data"', false);
    }

    public function test_admin_can_upload_logo_senat_as_base64(): void
    {
        $admin = $this->makeAdmin();
        $logo = UploadedFile::fake()->image('logo-senat.png', 240, 240)->size(120);

        $this->actingAs($admin)
            ->post(route('instansi.senat.logo'), ['logo_senat' => $logo])
            ->assertRedirect(route('instansi.senat.index'))
            ->assertSessionHasNoErrors()
            ->assertSessionHas('status', 'Logo senat berhasil disimpan.');

        $instansi = Instansi::firstOrFail();

        $this->assertStringStartsWith('data:image/png;base64,', $instansi->logo_senat_base64);
        $this->assertNotFalse(base64_decode(substr($instansi->logo_senat_base64, strlen('data:image/png;base64,')), true));

        // Logo yang tersimpan tampil sebagai pratinjau pada halaman senat.
        $this->actingAs($admin)
            ->get('/instansi/senat')
            ->assertOk()
            ->assertSee('src="'.$instansi->logoSenatDataUri().'"', false);
    }

    public function test_replacing_logo_senat_updates_the_stored_base64(): void
    {
        $admin = $this->makeAdmin();

        Instansi::create([
            'nama_instansi' => 'Universitas Athena Nusantara',
            'logo_base64' => 'data:image/png;base64,logo-instansi',
        ]);

        $this->actingAs($admin)->post(route('instansi.senat.logo'), [
            'logo_senat' => UploadedFile::fake()->image('logo-senat.png', 120, 120)->size(100),
        ])->assertSessionHasNoErrors();

        $oldLogo = Instansi::firstOrFail()->logo_senat_base64;

        $this->actingAs($admin)->post(route('instansi.senat.logo'), [
            'logo_senat' => UploadedFile::fake()->image('logo-senat-baru.png', 80, 80)->size(80),
        ])->assertSessionHasNoErrors();

        $instansi = Instansi::firstOrFail();

        $this->assertNotSame($oldLogo, $instansi->logo_senat_base64);
        $this->assertStringStartsWith('data:image/png;base64,', $instansi->logo_senat_base64);

        // Logo instansi dan identitas instansi tidak ikut berubah.
        $this->assertSame('Universitas Athena Nusantara', $instansi->nama_instansi);
        $this->assertSame('data:image/png;base64,logo-instansi', $instansi->logo_base64);
    }

    public function test_logo_senat_must_be_an_image_and_not_exceed_500_kb(): void
    {
        $admin = $this->makeAdmin();

        $this->actingAs($admin)
            ->from('/instansi/senat')
            ->post(route('instansi.senat.logo'), [
                'logo_senat' => UploadedFile::fake()->create('logo-senat.pdf', 100, 'application/pdf'),
            ])
            ->assertSessionHasErrors('logo_senat');

        $this->actingAs($admin)
            ->from('/instansi/senat')
            ->post(route('instansi.senat.logo'), [
                'logo_senat' => UploadedFile::fake()->image('logo-senat.png', 100, 100)->size(501),
            ])
            ->assertSessionHasErrors('logo_senat');

        $this->actingAs($admin)
            ->from('/instansi/senat')
            ->post(route('instansi.senat.logo'), [])
            ->assertSessionHasErrors([
                'logo_senat' => 'Logo senat wajib diunggah.',
            ]);

        $this->assertDatabaseCount('instansi', 0);
    }

    public function test_non_admin_cannot_upload_logo_senat(): void
    {
        $dosen = $this->makeDosen();

        $this->actingAs($dosen)
            ->post(route('instansi.senat.logo'), [
                'logo_senat' => UploadedFile::fake()->image('logo-senat.png'),
            ])
            ->assertForbidden();

        $this->assertDatabaseCount('instansi', 0);
    }

    public function test_non_admin_cannot_manage_senat(): void
    {
        $dosen = $this->makeDosen();

        $this->actingAs($dosen)->get('/instansi/senat')->assertForbidden();
        $this->actingAs($dosen)
            ->post('/instansi/senat', ['user_id' => $dosen->id, 'jabatan' => 'ketua'])
            ->assertForbidden();

        $this->assertDatabaseCount('senat', 0);
    }

    public function test_guest_is_redirected_to_login_from_senat_page(): void
    {
        $this->get('/instansi/senat')->assertRedirect('/login');
    }
}
