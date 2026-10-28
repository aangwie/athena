<?php

namespace Tests\Feature;

use App\Http\Controllers\PengaturanController;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class PengaturanTest extends TestCase
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

    public function test_admin_can_open_pengaturan_page(): void
    {
        $this->actingAs($this->makeAdmin())
            ->get('/pengaturan')
            ->assertOk()
            ->assertSee('Pengaturan')
            ->assertSee('Token GitHub')
            ->assertSee('Bersihkan Cache')
            ->assertSee('Migrasi Tabel')
            ->assertSee('Buat Symlink')
            ->assertSee('terminal');
    }

    public function test_dosen_cannot_open_pengaturan_page(): void
    {
        $dosen = User::create([
            'nidn' => '1987654321',
            'name' => 'Dr. Ayu Lestari, M.Kom.',
            'email' => 'ayu@example.test',
            'role' => 'dosen',
            'is_active' => true,
            'password' => Hash::make('password'),
        ]);

        $this->actingAs($dosen)->get('/pengaturan')->assertForbidden();
    }

    public function test_token_must_follow_github_pat_format(): void
    {
        $this->actingAs($this->makeAdmin())
            ->post('/pengaturan/token', ['token_github' => 'token-salah'])
            ->assertSessionHasErrors('token_github');
    }

    public function test_admin_can_clear_terminal_log(): void
    {
        $this->actingAs($this->makeAdmin())
            ->withSession(['log-pengaturan' => [
                ['waktu' => '2026-10-28 19:30:00', 'judul' => 'Bersihkan cache', 'sukses' => true, 'baris' => ['$ ok']],
            ]])
            ->delete('/pengaturan/log')
            ->assertRedirect(route('pengaturan.index'))
            ->assertSessionMissing('log-pengaturan');
    }

    public function test_runner_masks_secret_and_reports_exit_code(): void
    {
        $controller = new PengaturanController;

        $sukses = $controller->jalankan([PHP_BINARY, '-r', 'echo "rahasia-123";'], 'rahasia-123');

        $this->assertTrue($sukses['sukses']);
        $this->assertStringNotContainsString('rahasia-123', implode("\n", $sukses['baris']));
        $this->assertStringContainsString('***', implode("\n", $sukses['baris']));

        $gagal = $controller->jalankan([PHP_BINARY, '-r', 'exit(3);']);

        $this->assertFalse($gagal['sukses']);
        $this->assertStringContainsString('Exit code: 3', implode("\n", $gagal['baris']));
    }
}
