<?php

namespace Tests\Feature;

use App\Models\PengajuanDupak;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class RoleSelectionTest extends TestCase
{
    use RefreshDatabase;

    private function makeUser(array $roles, array $attributes = []): User
    {
        return User::create(array_merge([
            'nidn' => '1987654321',
            'name' => 'Dr. Ayu Lestari, M.Kom.',
            'email' => 'ayu@example.test',
            'program_studi' => 'Pendidikan Informatika',
            'jabatan_fungsional' => 'Lektor',
            'role' => $roles[0],
            'roles' => $roles,
            'is_active' => true,
            'password' => Hash::make('password'),
        ], $attributes));
    }

    private function login(User $user): void
    {
        $this->post('/login', [
            'nidn' => $user->nidn,
            'password' => 'password',
        ]);
    }

    public function test_multi_role_user_is_asked_to_pick_a_role_after_login(): void
    {
        $user = $this->makeUser(['dosen', 'admin']);

        $this->post('/login', [
            'nidn' => $user->nidn,
            'password' => 'password',
        ])->assertRedirect(route('role.select'));

        $this->get(route('role.select'))
            ->assertOk()
            ->assertSee('Masuk sebagai siapa?')
            ->assertSee('name="role" value="dosen"', false)
            ->assertSee('name="role" value="admin"', false)
            ->assertDontSee('type="radio"', false)
            ->assertSee('type="submit"', false);

        $this->get('/dashboard')->assertRedirect(route('role.select'));
        $this->get('/data-dosen')->assertRedirect(route('role.select'));
    }

    public function test_single_role_user_goes_straight_to_dashboard(): void
    {
        $user = $this->makeUser(['dosen']);

        $this->post('/login', [
            'nidn' => $user->nidn,
            'password' => 'password',
        ])->assertRedirect(route('dashboard'));

        $this->get('/dashboard')->assertOk()->assertSee('Ruang Dosen');
    }

    public function test_picking_dosen_role_grants_dosen_access_only(): void
    {
        $user = $this->makeUser(['dosen', 'admin']);

        $this->login($user);

        $this->post('/pilih-role', ['role' => 'dosen'])
            ->assertRedirect(route('dashboard'));

        $this->get('/dashboard')->assertOk()->assertSee('Ruang Dosen');
        $this->get('/profil')->assertOk();
        $this->get('/data-dosen')->assertForbidden();
    }

    public function test_picking_admin_role_grants_admin_access_only(): void
    {
        $user = $this->makeUser(['dosen', 'admin']);

        $this->login($user);

        $this->post('/pilih-role', ['role' => 'admin'])
            ->assertRedirect(route('dashboard'));

        $this->get('/dashboard')->assertOk()->assertSee('Ruang Admin');
        $this->get('/data-dosen')->assertOk();
        $this->get('/profil')->assertForbidden();
    }

    public function test_picking_tim_pak_role_reviews_all_submissions(): void
    {
        $timPak = $this->makeUser(['dosen', 'tim_pak']);

        $dosen = $this->makeUser(['dosen'], [
            'nidn' => '1990011220',
            'name' => 'Budi Santoso, M.Pd.',
            'email' => 'budi@example.test',
        ]);

        PengajuanDupak::create([
            'user_id' => $dosen->id,
            'tahun' => 2026,
            'semester' => 'ganjil',
            'status' => 'diajukan_dupak',
            'diajukan_at' => now(),
        ]);

        $this->login($timPak);

        $this->post('/pilih-role', ['role' => 'tim_pak'])
            ->assertRedirect(route('dashboard'));

        // Dashboard TIM PAK memakai tabel pengajuan DUPAK masuk.
        $this->get('/dashboard')
            ->assertOk()
            ->assertSee('Ruang TIM PAK')
            ->assertSee('Budi Santoso, M.Pd.')
            ->assertSee('2026/2027 · Ganjil');

        $this->get('/profil')->assertForbidden();
        $this->get('/data-dosen')->assertForbidden();
    }

    public function test_role_outside_user_roles_is_rejected(): void
    {
        $user = $this->makeUser(['dosen', 'admin']);

        $this->login($user);

        $this->post('/pilih-role', ['role' => 'tim_pak'])
            ->assertSessionHasErrors('role');

        $this->get('/dashboard')->assertRedirect(route('role.select'));
    }

    public function test_user_can_switch_role_from_selection_page(): void
    {
        $user = $this->makeUser(['dosen', 'admin']);

        $this->login($user);
        $this->post('/pilih-role', ['role' => 'admin']);

        $this->get('/data-dosen')->assertOk();

        $this->post('/pilih-role', ['role' => 'dosen']);

        $this->get('/data-dosen')->assertForbidden();
        $this->get('/profil')->assertOk();
    }
}
