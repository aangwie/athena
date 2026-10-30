<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    private function makeUser(array $attributes = []): User
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

    public function test_guest_is_redirected_to_login(): void
    {
        $this->get('/dashboard')->assertRedirect('/login');
    }

    public function test_login_page_can_be_rendered(): void
    {
        $this->get('/login')
            ->assertOk()
            ->assertSee('NIDN')
            ->assertSee('Selamat datang kembali');
    }

    public function test_user_can_login_using_nidn(): void
    {
        $user = $this->makeUser();

        $response = $this->post('/login', [
            'nidn' => $user->nidn,
            'password' => 'password',
        ]);

        $response->assertRedirect(route('dashboard'));
        $this->assertAuthenticatedAs($user);
    }

    public function test_user_cannot_login_with_invalid_password(): void
    {
        $user = $this->makeUser();

        $this->from('/login')
            ->post('/login', [
                'nidn' => $user->nidn,
                'password' => 'password-salah',
            ])
            ->assertRedirect('/login')
            ->assertSessionHasErrors('nidn');

        $this->assertGuest();
    }

    public function test_inactive_user_cannot_login(): void
    {
        $user = $this->makeUser(['is_active' => false]);

        $this->from('/login')
            ->post('/login', [
                'nidn' => $user->nidn,
                'password' => 'password',
            ])
            ->assertRedirect('/login')
            ->assertSessionHasErrors('nidn');

        $this->assertGuest();
    }

    public function test_user_can_logout(): void
    {
        $user = $this->makeUser();

        $this->actingAs($user)
            ->post('/logout')
            ->assertRedirect(route('login'));

        $this->assertGuest();
    }

    public function test_dosen_only_sees_their_own_submissions(): void
    {
        $dosen = $this->makeUser();
        $otherDosen = $this->makeUser([
            'nidn' => '1990011220',
            'name' => 'Budi Santoso, M.Pd.',
            'email' => 'budi@example.test',
        ]);

        // Dashboard dosen menampilkan riwayat ajuan DUPAK, bukan pengajuan jabatan.
        $dosen->pengajuanDupak()->create([
            'tahun' => 2026,
            'semester' => 'ganjil',
            'status' => 'diajukan_dupak',
            'diajukan_at' => now(),
        ]);

        $otherDosen->pengajuanDupak()->create([
            'tahun' => 2025,
            'semester' => 'genap',
            'status' => 'diajukan_dupak',
            'diajukan_at' => now(),
        ]);

        $this->actingAs($dosen)
            ->get('/dashboard')
            ->assertOk()
            ->assertSee('2026/2027 · Ganjil')
            ->assertDontSee('2025/2026 · Genap');
    }

    public function test_admin_sees_all_dupak_submissions(): void
    {
        $admin = $this->makeUser([
            'nidn' => '0000000001',
            'name' => 'Admin Kepegawaian',
            'email' => 'admin@example.test',
            'role' => 'admin',
        ]);

        $dosen = $this->makeUser();

        $dosen->pengajuanDupak()->create([
            'tahun' => 2026,
            'semester' => 'ganjil',
            'status' => 'diajukan_dupak',
            'diajukan_at' => now(),
        ]);

        $this->actingAs($admin)
            ->get('/dashboard')
            ->assertOk()
            // Tabel pengajuan jabatan lama sudah tidak dipakai admin.
            ->assertDontSee('No. Pengajuan')
            ->assertSee('Ajuan DUPAK Masuk')
            ->assertSee($dosen->name)
            ->assertSee('2026/2027 · Ganjil');
    }
}
