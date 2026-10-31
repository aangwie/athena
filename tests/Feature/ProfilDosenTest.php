<?php

namespace Tests\Feature;

use App\Models\ProfilDosen;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class ProfilDosenTest extends TestCase
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

    private function payload(array $overrides = []): array
    {
        return array_merge([
            'nama' => 'Dr. Ayu Lestari, M.Kom.',
            'tempat_lahir' => 'Bandung',
            'tanggal_lahir' => '1987-05-14',
            'alamat' => 'Jl. Cihampelas No. 112, Bandung',
            'no_hp' => '0812-3456-7890',
        ], $overrides);
    }

    public function test_dosen_can_open_data_pribadi_page(): void
    {
        $dosen = $this->makeDosen();

        $this->actingAs($dosen)
            ->get('/profil/data-pribadi')
            ->assertOk()
            ->assertSee('Data Pribadi')
            ->assertDontSee('Status Pernikahan')
            ->assertDontSee('Data Pasangan')
            ->assertDontSee('Data Anak');
    }

    public function test_dosen_can_save_personal_profile(): void
    {
        $dosen = $this->makeDosen();

        $this->actingAs($dosen)
            ->put('/profil/data-pribadi', $this->payload())
            ->assertRedirect(route('profil.show'));

        $this->assertDatabaseHas('users', [
            'id' => $dosen->id,
            'name' => 'Dr. Ayu Lestari, M.Kom.',
        ]);

        $this->assertDatabaseHas('profil_dosen', [
            'user_id' => $dosen->id,
            'tempat_lahir' => 'Bandung',
            'alamat' => 'Jl. Cihampelas No. 112, Bandung',
            'no_hp' => '0812-3456-7890',
        ]);
    }

    public function test_dosen_can_save_agama_on_data_pribadi(): void
    {
        $dosen = $this->makeDosen();

        $this->actingAs($dosen)
            ->put('/profil/data-pribadi', $this->payload(['agama' => 'islam']))
            ->assertRedirect(route('profil.show'));

        $this->assertDatabaseHas('profil_dosen', [
            'user_id' => $dosen->id,
            'agama' => 'islam',
        ]);
    }

    public function test_agama_must_be_one_of_the_available_options(): void
    {
        $this->actingAs($this->makeDosen())
            ->put('/profil/data-pribadi', $this->payload(['agama' => 'lainnya']))
            ->assertSessionHasErrors('agama');
    }

    public function test_portfolio_displays_personal_data(): void
    {
        $dosen = $this->makeDosen();

        ProfilDosen::create([
            'user_id' => $dosen->id,
            'tempat_lahir' => 'Bandung',
            'tanggal_lahir' => '1987-05-14',
            'alamat' => 'Jl. Cihampelas No. 112, Bandung',
            'no_hp' => '0812-3456-7890',
        ]);

        $this->actingAs($dosen)
            ->get('/profil')
            ->assertOk()
            ->assertSee('Portofolio Dosen')
            ->assertSee('Jl. Cihampelas No. 112, Bandung')
            ->assertDontSee('Data Keluarga')
            ->assertDontSee('Status Pernikahan');
    }

    public function test_riwayat_pendidikan_page_is_available_for_dosen(): void
    {
        $dosen = $this->makeDosen();

        $this->actingAs($dosen)
            ->get('/profil/riwayat-pendidikan')
            ->assertOk()
            ->assertSee('Riwayat Pendidikan')
            ->assertSee('Tambah Riwayat')
            ->assertSee('Belum ada riwayat pendidikan');
    }

    public function test_admin_cannot_access_profile_pages(): void
    {
        $admin = $this->makeAdmin();

        $this->actingAs($admin)->get('/profil')->assertForbidden();
        $this->actingAs($admin)->get('/profil/data-pribadi')->assertForbidden();
        $this->actingAs($admin)->get('/profil/riwayat-pendidikan')->assertForbidden();
    }
}
