<?php

namespace Tests\Feature;

use App\Http\Controllers\PengaturanController;
use App\Models\Pengaturan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
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
            // URL progres langkah harus ikut ter-render; JS membacanya dari form.
            ->assertSee(route('pengaturan.db.langkah'), false)
            ->assertSee('data-db-langkah', false)
            ->assertSee('Hapus Semua Data')
            ->assertSee(route('pengaturan.db.kosongkan'), false)
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

        $this->actingAs($dosen)->delete('/pengaturan/database/kosongkan')->assertForbidden();
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

    public function test_admin_can_rename_web_and_name_shows_in_sidebar(): void
    {
        $this->actingAs($this->makeAdmin())
            ->post('/pengaturan/nama-web', ['nama_web' => 'SIGAP'])
            ->assertRedirect(route('pengaturan.index'))
            ->assertSessionHas('status');

        $this->assertDatabaseHas('pengaturan', ['kunci' => 'nama_web', 'nilai' => 'SIGAP']);
        $this->assertSame('SIGAP', Pengaturan::namaWeb());

        $this->get('/dashboard')->assertOk()->assertSee('SIGAP');
        $this->get('/pengaturan')->assertOk()->assertSee('value="SIGAP"', false);

        auth()->logout();
        $this->get('/login')->assertOk()->assertSee('SIGAP');
    }

    public function test_web_name_is_limited_to_ten_characters(): void
    {
        $this->actingAs($this->makeAdmin())
            ->post('/pengaturan/nama-web', ['nama_web' => 'SEBELASHRF1'])
            ->assertSessionHasErrors('nama_web');

        $this->assertDatabaseMissing('pengaturan', ['kunci' => 'nama_web']);
    }

    public function test_admin_can_backup_database_and_download_it(): void
    {
        $this->actingAs($this->makeAdmin())
            ->postJson('/pengaturan/database/mulai', ['mode' => 'backup'])
            ->assertOk()
            ->assertJsonPath('persen', 0);

        $percobaan = 0;

        do {
            $response = $this->postJson('/pengaturan/database/langkah')->assertOk();
            $this->assertLessThan(100, $percobaan++, 'Backup tidak selesai dalam 100 langkah.');
        } while ($response->json('selesai') !== true);

        $this->assertSame(100, $response->json('persen'));

        $unduh = (string) $response->json('unduh');
        $berkas = storage_path('app/backups/'.basename($unduh));

        $this->assertFileExists($berkas);

        $isi = (string) file_get_contents($berkas);
        $this->assertStringContainsString('CREATE TABLE', $isi);
        $this->assertStringContainsString('INSERT INTO', $isi);

        $this->get($unduh)->assertOk();

        @unlink($berkas);
    }

    public function test_admin_can_import_sql_file_in_progress_steps(): void
    {
        $berkas = sys_get_temp_dir().DIRECTORY_SEPARATOR.'athena-import-'.uniqid().'.sql';
        file_put_contents($berkas, "-- contoh dump\nCREATE TABLE uji_import (id INTEGER PRIMARY KEY, nama TEXT);\nINSERT INTO uji_import (id, nama) VALUES (1, 'a;b');\n");

        $this->actingAs($this->makeAdmin())
            ->post('/pengaturan/database/mulai', [
                'mode' => 'import',
                'berkas' => new UploadedFile($berkas, 'dump.sql', 'text/plain', null, true),
            ])
            ->assertOk();

        $percobaan = 0;

        do {
            $response = $this->postJson('/pengaturan/database/langkah')->assertOk();
            $this->assertLessThan(100, $percobaan++, 'Import tidak selesai dalam 100 langkah.');
        } while ($response->json('selesai') !== true);

        // Titik koma di dalam string tidak boleh memotong pernyataan.
        $this->assertDatabaseHas('uji_import', ['id' => 1, 'nama' => 'a;b']);
    }

    public function test_import_rejects_file_that_is_not_sql(): void
    {
        $berkas = sys_get_temp_dir().DIRECTORY_SEPARATOR.'athena-import-'.uniqid().'.txt';
        file_put_contents($berkas, 'bukan sql');

        $this->actingAs($this->makeAdmin())
            ->postJson('/pengaturan/database/mulai', [
                'mode' => 'import',
                'berkas' => new UploadedFile($berkas, 'dump.txt', 'text/plain', null, true),
            ])
            ->assertStatus(422)
            ->assertJsonPath('pesan', 'Berkas backup harus berformat .sql.');

        @unlink($berkas);
    }

    public function test_admin_can_wipe_dosen_data_but_keep_users_and_master(): void
    {
        $admin = $this->makeAdmin();

        $dosen = User::create([
            'nidn' => '1987654321',
            'name' => 'Dr. Ayu Lestari, M.Kom.',
            'email' => 'ayu@example.test',
            'role' => 'dosen',
            'is_active' => true,
            'password' => Hash::make('password'),
        ]);

        DB::table('syarat_khusus')->insert([
            'user_id' => $dosen->id,
            'tahun' => 2026,
            'semester' => 'ganjil',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        Pengaturan::simpanNamaWeb('SIGAP');

        DB::table('kepangkatan')->insert([
            'nama' => 'Penata Muda / III a',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->actingAs($admin)
            ->delete('/pengaturan/database/kosongkan')
            ->assertRedirect(route('pengaturan.index'))
            ->assertSessionHas('status')
            ->assertSessionHas('log-pengaturan');

        $this->assertDatabaseCount('syarat_khusus', 0);
        $this->assertDatabaseHas('users', ['nidn' => $dosen->nidn]);
        $this->assertDatabaseHas('users', ['nidn' => $admin->nidn]);
        $this->assertDatabaseHas('kepangkatan', ['nama' => 'Penata Muda / III a']);
        $this->assertSame('SIGAP', Pengaturan::namaWeb());
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
