<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class DataDosenTest extends TestCase
{
    use RefreshDatabase;

    private function makeAdmin(): User
    {
        return User::create([
            'nidn' => '0000000001',
            'name' => 'Admin Kepegawaian',
            'email' => 'admin@example.test',
            'role' => 'admin',
            'roles' => ['admin'],
            'is_active' => true,
            'password' => Hash::make('password'),
        ]);
    }

    private function payload(array $overrides = []): array
    {
        return array_merge([
            'name' => 'Dr. Citra Maharani, M.Si.',
            'nidn' => '1985041722',
            'memiliki_jabatan' => 'ya',
            'pangkat' => 'Lektor Kepala',
            'roles' => ['dosen', 'tim_pak'],
            'password' => 'password',
            'password_confirmation' => 'password',
        ], $overrides);
    }

    public function test_admin_can_create_user_with_multiple_roles(): void
    {
        $this->actingAs($this->makeAdmin())
            ->post('/data-dosen', $this->payload())
            ->assertRedirect(route('data-dosen.index'));

        $user = User::query()->where('nidn', '1985041722')->firstOrFail();

        $this->assertSame(['dosen', 'tim_pak'], $user->roleList());
        $this->assertSame('dosen', $user->role);
        $this->assertTrue($user->hasRole('dosen'));
        $this->assertTrue($user->hasRole('tim_pak'));
        $this->assertFalse($user->hasRole('admin'));
        $this->assertSame('dosen', $user->activeRole());
    }

    public function test_pangkat_is_required_when_dosen_has_jabatan_akademik(): void
    {
        $this->actingAs($this->makeAdmin())
            ->post('/data-dosen', $this->payload(['pangkat' => '']))
            ->assertSessionHasErrors('pangkat');

        $this->assertDatabaseMissing('users', ['nidn' => '1985041722']);
    }

    public function test_admin_can_create_dosen_without_jabatan_akademik(): void
    {
        $this->actingAs($this->makeAdmin())
            ->post('/data-dosen', $this->payload([
                'memiliki_jabatan' => 'tidak',
                'pangkat' => '',
            ]))
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('data-dosen.index'));

        $this->assertNull(User::query()->where('nidn', '1985041722')->firstOrFail()->jabatan_fungsional);
    }

    public function test_form_shows_jabatan_akademik_choice_before_pangkat(): void
    {
        $this->actingAs($this->makeAdmin())
            ->get('/data-dosen/create')
            ->assertOk()
            ->assertSee('Memiliki Jabatan Akademik?')
            ->assertSee('name="memiliki_jabatan"', false);
    }

    public function test_role_is_required_when_saving_user(): void
    {
        $this->actingAs($this->makeAdmin())
            ->post('/data-dosen', $this->payload(['roles' => []]))
            ->assertSessionHasErrors('roles');

        $this->assertDatabaseMissing('users', ['nidn' => '1985041722']);
    }

    public function test_admin_can_update_roles_and_roles_are_listed_in_table(): void
    {
        $admin = $this->makeAdmin();

        $dosen = User::create([
            'nidn' => '1987654321',
            'name' => 'Dr. Ayu Lestari, M.Kom.',
            'jabatan_fungsional' => 'Lektor',
            'role' => 'dosen',
            'roles' => ['dosen'],
            'is_active' => true,
            'password' => Hash::make('password'),
        ]);

        $this->actingAs($admin)
            ->put("/data-dosen/{$dosen->id}", $this->payload([
                'name' => $dosen->name,
                'nidn' => $dosen->nidn,
                'roles' => ['dosen', 'tim_pak'],
                'password' => '',
                'password_confirmation' => '',
            ]))
            ->assertRedirect(route('data-dosen.index'));

        $this->assertSame(['dosen', 'tim_pak'], $dosen->fresh()->roleList());

        $this->actingAs($admin)
            ->get('/data-dosen')
            ->assertOk()
            ->assertSee('TIM PAK')
            ->assertSee('Dosen');
    }

    public function test_user_with_tim_pak_role_can_login(): void
    {
        $user = User::create([
            'nidn' => '1985041722',
            'name' => 'Dr. Citra Maharani, M.Si.',
            'jabatan_fungsional' => 'Lektor Kepala',
            'role' => 'dosen',
            'roles' => ['dosen', 'tim_pak'],
            'is_active' => true,
            'password' => Hash::make('password'),
        ]);

        $this->post('/login', [
            'nidn' => $user->nidn,
            'password' => 'password',
        ])->assertRedirect(route('role.select'));

        $this->assertAuthenticatedAs($user);

        $this->post('/pilih-role', ['role' => 'tim_pak'])
            ->assertRedirect(route('dashboard'));

        $this->get('/dashboard')->assertOk();
    }

    public function test_form_shows_role_checkboxes_with_current_roles(): void
    {
        $admin = $this->makeAdmin();

        $dosen = User::create([
            'nidn' => '1985041722',
            'name' => 'Dr. Citra Maharani, M.Si.',
            'jabatan_fungsional' => 'Lektor Kepala',
            'role' => 'dosen',
            'roles' => ['dosen', 'tim_pak'],
            'is_active' => true,
            'password' => Hash::make('password'),
        ]);

        $this->actingAs($admin)
            ->get("/data-dosen/{$dosen->id}/edit")
            ->assertOk()
            ->assertSee('name="roles[]"', false)
            ->assertSee('value="tim_pak"', false)
            ->assertSee('TIM PAK');
    }

    public function test_admin_cannot_delete_own_account(): void
    {
        $admin = $this->makeAdmin();

        $this->actingAs($admin)
            ->delete("/data-dosen/{$admin->id}")
            ->assertForbidden();

        $this->assertDatabaseHas('users', ['id' => $admin->id]);
    }
}
