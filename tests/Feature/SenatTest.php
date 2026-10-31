<?php

namespace Tests\Feature;

use App\Models\Senat;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
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
            ->assertSee('Anggota');
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
            ->post('/instansi/senat', ['user_id' => $dosen->id, 'jabatan' => 'sekretaris'])
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
