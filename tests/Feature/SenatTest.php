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
            ->assertSee('Anggota');
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
