<?php

namespace Tests\Feature;

use App\Models\JabatanFungsional;
use App\Models\JabatanStruktural;
use App\Models\Kepangkatan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class KepegawaianTest extends TestCase
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

    private function makeDosen(): User
    {
        return User::create([
            'nidn' => '1987654321',
            'name' => 'Dr. Ayu Lestari, M.Kom.',
            'email' => 'ayu@example.test',
            'program_studi' => 'Pendidikan Informatika',
            'jabatan_fungsional' => 'Lektor',
            'role' => 'dosen',
            'is_active' => true,
            'password' => Hash::make('password'),
        ]);
    }

    public function test_admin_can_open_each_kepegawaian_sub_menu(): void
    {
        $admin = $this->makeAdmin();

        $pages = [
            '/kepegawaian/jabatan-fungsional' => 'Jabatan Fungsional',
            '/kepegawaian/jabatan-struktural' => 'Jabatan Struktural',
            '/kepegawaian/kepangkatan' => 'Kepangkatan',
        ];

        foreach ($pages as $url => $label) {
            $this->actingAs($admin)
                ->get($url)
                ->assertOk()
                ->assertSee($label)
                ->assertSee('Tambah Data')
                ->assertSee('Kepegawaian');
        }
    }

    public function test_sidebar_shows_kepegawaian_menu_for_admin_only(): void
    {
        $admin = $this->makeAdmin();

        $this->actingAs($admin)
            ->get('/dashboard')
            ->assertOk()
            ->assertSee('Kepegawaian')
            ->assertSee('Jabatan Fungsional')
            ->assertSee('Jabatan Struktural')
            ->assertSee('Kepangkatan');

        $dosen = $this->makeDosen();

        $this->actingAs($dosen)
            ->get('/dashboard')
            ->assertOk()
            ->assertDontSee('Kepegawaian');
    }

    public function test_dosen_cannot_access_kepegawaian_pages(): void
    {
        $dosen = $this->makeDosen();

        $this->actingAs($dosen)
            ->get('/kepegawaian/jabatan-fungsional')
            ->assertForbidden();

        $this->actingAs($dosen)
            ->post('/kepegawaian/kepangkatan', ['nama' => 'Penata'])
            ->assertForbidden();
    }

    public function test_guest_is_redirected_to_login(): void
    {
        $this->get('/kepegawaian/jabatan-struktural')
            ->assertRedirect('/login');
    }

    public function test_admin_can_open_create_and_edit_forms(): void
    {
        $admin = $this->makeAdmin();
        $item = Kepangkatan::create(['nama' => 'Penata']);

        $this->actingAs($admin)
            ->get('/kepegawaian/kepangkatan/create')
            ->assertOk()
            ->assertSee('Tambah Kepangkatan')
            ->assertSee('Simpan Data');

        $this->actingAs($admin)
            ->get(route('kepegawaian.kepangkatan.edit', $item->id))
            ->assertOk()
            ->assertSee('Edit Kepangkatan')
            ->assertSee('Penata')
            ->assertSee('#'.$item->id)
            ->assertSee('Simpan Perubahan');
    }

    public function test_admin_can_create_update_and_delete_jabatan_fungsional(): void
    {
        $admin = $this->makeAdmin();

        $this->actingAs($admin)
            ->post('/kepegawaian/jabatan-fungsional', ['nama' => 'Lektor Kepala'])
            ->assertRedirect(route('kepegawaian.jabatan-fungsional.index'))
            ->assertSessionHasNoErrors();

        $item = JabatanFungsional::firstOrFail();

        $this->assertSame('Lektor Kepala', $item->nama);

        $this->actingAs($admin)
            ->put(route('kepegawaian.jabatan-fungsional.update', $item->id), ['nama' => 'Profesor'])
            ->assertRedirect(route('kepegawaian.jabatan-fungsional.index'))
            ->assertSessionHasNoErrors();

        $this->assertSame('Profesor', $item->fresh()->nama);

        $this->actingAs($admin)
            ->delete(route('kepegawaian.jabatan-fungsional.destroy', $item->id))
            ->assertRedirect(route('kepegawaian.jabatan-fungsional.index'));

        $this->assertDatabaseMissing('jabatan_fungsional', ['id' => $item->id]);
    }

    public function test_admin_can_manage_jabatan_struktural_and_kepangkatan(): void
    {
        $admin = $this->makeAdmin();

        $this->actingAs($admin)
            ->post('/kepegawaian/jabatan-struktural', ['nama' => 'Ketua Program Studi'])
            ->assertRedirect(route('kepegawaian.jabatan-struktural.index'))
            ->assertSessionHasNoErrors();

        $this->actingAs($admin)
            ->post('/kepegawaian/kepangkatan', ['nama' => 'Penata Tingkat I'])
            ->assertRedirect(route('kepegawaian.kepangkatan.index'))
            ->assertSessionHasNoErrors();

        $struktural = JabatanStruktural::firstOrFail();
        $kepangkatan = Kepangkatan::firstOrFail();

        $this->assertSame('Ketua Program Studi', $struktural->nama);
        $this->assertSame('Penata Tingkat I', $kepangkatan->nama);

        $this->actingAs($admin)
            ->put(route('kepegawaian.jabatan-struktural.update', $struktural->id), ['nama' => 'Dekan'])
            ->assertSessionHasNoErrors();

        $this->actingAs($admin)
            ->put(route('kepegawaian.kepangkatan.update', $kepangkatan->id), ['nama' => 'Pembina'])
            ->assertSessionHasNoErrors();

        $this->assertSame('Dekan', $struktural->fresh()->nama);
        $this->assertSame('Pembina', $kepangkatan->fresh()->nama);
    }

    public function test_index_displays_id_and_nama(): void
    {
        $admin = $this->makeAdmin();

        $item = JabatanFungsional::create(['nama' => 'Lektor']);

        $this->actingAs($admin)
            ->get('/kepegawaian/jabatan-fungsional')
            ->assertOk()
            ->assertSee('#'.$item->id)
            ->assertSee('Lektor');
    }

    public function test_nama_is_required_and_limited_to_100_characters(): void
    {
        $admin = $this->makeAdmin();

        $this->actingAs($admin)
            ->from('/kepegawaian/jabatan-fungsional/create')
            ->post('/kepegawaian/jabatan-fungsional', ['nama' => ''])
            ->assertRedirect('/kepegawaian/jabatan-fungsional/create')
            ->assertSessionHasErrors('nama');

        $this->actingAs($admin)
            ->from('/kepegawaian/jabatan-fungsional/create')
            ->post('/kepegawaian/jabatan-fungsional', ['nama' => str_repeat('a', 101)])
            ->assertSessionHasErrors('nama');
    }

    public function test_nama_is_unique_per_master_data(): void
    {
        $admin = $this->makeAdmin();

        JabatanFungsional::create(['nama' => 'Lektor']);

        $this->actingAs($admin)
            ->from('/kepegawaian/jabatan-fungsional/create')
            ->post('/kepegawaian/jabatan-fungsional', ['nama' => 'Lektor'])
            ->assertSessionHasErrors('nama');

        // Nama yang sama tetap boleh dipakai pada sub menu lain.
        $this->actingAs($admin)
            ->post('/kepegawaian/kepangkatan', ['nama' => 'Lektor'])
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('kepangkatan', ['nama' => 'Lektor']);
    }

    public function test_update_can_keep_the_same_nama(): void
    {
        $admin = $this->makeAdmin();

        $item = JabatanStruktural::create(['nama' => 'Dekan']);

        $this->actingAs($admin)
            ->put(route('kepegawaian.jabatan-struktural.update', $item->id), ['nama' => 'Dekan'])
            ->assertSessionHasNoErrors();

        $this->assertSame('Dekan', $item->fresh()->nama);
    }
}
