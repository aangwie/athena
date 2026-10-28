<?php

namespace Tests\Feature;

use App\Models\Prodi;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class ProdiTest extends TestCase
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
            'email' => '1987654321@example.test',
            'program_studi' => 'Pendidikan Informatika',
            'role' => 'dosen',
            'is_active' => true,
            'password' => Hash::make('password'),
        ]);
    }

    public function test_admin_can_open_prodi_page(): void
    {
        $this->actingAs($this->makeAdmin())
            ->get('/instansi/prodi')
            ->assertOk()
            ->assertSee('Prodi')
            ->assertSee('Nama Prodi')
            ->assertSee('Kode Prodi');
    }

    public function test_admin_can_create_prodi_with_uppercase_code(): void
    {
        $this->actingAs($this->makeAdmin())
            ->post('/instansi/prodi', [
                'nama_prodi' => 'Bahasa Indonesia',
                'kode_prodi' => ' pbi ',
            ])
            ->assertRedirect(route('instansi.prodi.index'))
            ->assertSessionHasNoErrors();

        $prodi = Prodi::firstOrFail();

        $this->assertSame('Bahasa Indonesia', $prodi->nama_prodi);
        $this->assertSame('PBI', $prodi->kode_prodi);
    }

    public function test_kode_prodi_must_be_unique(): void
    {
        Prodi::query()->create([
            'nama_prodi' => 'Bahasa Indonesia',
            'kode_prodi' => 'PBI',
        ]);

        $this->actingAs($this->makeAdmin())
            ->from('/instansi/prodi/create')
            ->post('/instansi/prodi', [
                'nama_prodi' => 'Pendidikan Bahasa Indonesia',
                'kode_prodi' => 'pbi',
            ])
            ->assertSessionHasErrors('kode_prodi');
    }

    public function test_admin_can_update_prodi(): void
    {
        $prodi = Prodi::query()->create([
            'nama_prodi' => 'Bahasa Indonesia',
            'kode_prodi' => 'PBI',
        ]);

        $this->actingAs($this->makeAdmin())
            ->put("/instansi/prodi/{$prodi->id}", [
                'nama_prodi' => 'Pendidikan Bahasa Indonesia',
                'kode_prodi' => 'PBI',
            ])
            ->assertRedirect(route('instansi.prodi.index'))
            ->assertSessionHasNoErrors();

        $this->assertSame('Pendidikan Bahasa Indonesia', $prodi->fresh()->nama_prodi);
    }

    public function test_admin_can_delete_prodi(): void
    {
        $prodi = Prodi::query()->create([
            'nama_prodi' => 'Bahasa Indonesia',
            'kode_prodi' => 'PBI',
        ]);

        $this->actingAs($this->makeAdmin())
            ->delete("/instansi/prodi/{$prodi->id}")
            ->assertRedirect(route('instansi.prodi.index'));

        $this->assertDatabaseMissing('prodi', ['id' => $prodi->id]);
    }

    public function test_dosen_cannot_access_prodi_pages(): void
    {
        $this->actingAs($this->makeDosen())->get('/instansi/prodi')->assertForbidden();
    }

    public function test_guest_is_redirected_to_login(): void
    {
        $this->get('/instansi/prodi')->assertRedirect('/login');
    }
}
