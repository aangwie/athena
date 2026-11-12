<?php

namespace Tests\Feature;

use App\Models\RiwayatPendidikan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class RiwayatDosenTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('local');
    }

    private function makeUser(string $role, string $nidn, string $name): User
    {
        return User::create([
            'nidn' => $nidn,
            'name' => $name,
            'email' => $nidn.'@example.test',
            'program_studi' => 'Pendidikan Informatika',
            'jabatan_fungsional' => 'Lektor',
            'role' => $role,
            'roles' => [$role],
            'is_active' => true,
            'password' => Hash::make('password'),
        ]);
    }

    private function makeDosen(string $nidn = '1987654321'): User
    {
        return $this->makeUser('dosen', $nidn, 'Dr. Ayu Lestari, M.Kom.');
    }

    private function makeAdmin(): User
    {
        return $this->makeUser('admin', '0000000001', 'Administrator');
    }

    public function test_dosen_can_view_own_riwayat(): void
    {
        $dosen = $this->makeDosen();
        $dosen->profilDosen()->create(['tempat_lahir' => 'Bandung', 'bidang_ilmu' => 'Rekayasa Perangkat Lunak']);
        $dosen->riwayatPendidikan()->create([
            'jenjang' => 's1',
            'nama_institusi' => 'Universitas Pendidikan Indonesia',
            'tahun_masuk' => 2005,
            'tahun_lulus' => 2009,
        ]);

        $this->actingAs($dosen)
            ->get(route('profil.riwayat'))
            ->assertOk()
            ->assertSee($dosen->name)
            ->assertSee('Profil')
            ->assertSee('Riwayat Pendidikan')
            ->assertSee('Universitas Pendidikan Indonesia')
            ->assertSee('Riwayat Pekerjaan');
    }

    public function test_dosen_cannot_access_admin_riwayat_list(): void
    {
        $dosen = $this->makeDosen();

        $this->actingAs($dosen)
            ->get(route('riwayat-dosen.index'))
            ->assertForbidden();
    }

    public function test_admin_can_view_dosen_riwayat_and_download_berkas(): void
    {
        $admin = $this->makeAdmin();
        $dosen = $this->makeDosen();

        $path = 'riwayat-pendidikan/1987654321/dokumen.pdf';
        Storage::disk('local')->put($path, '%PDF-1.4');

        $riwayat = $dosen->riwayatPendidikan()->create([
            'jenjang' => 's1',
            'nama_institusi' => 'Universitas Pendidikan Indonesia',
            'tahun_masuk' => 2005,
            'tahun_lulus' => 2009,
            'ijazah_transkrip_path' => $path,
        ]);

        $dosen->profilDosen()->create([
            'sk_dosen_tetap_path' => 'riwayat-pekerjaan/1987654321/sk.pdf',
        ]);
        Storage::disk('local')->put('riwayat-pekerjaan/1987654321/sk.pdf', '%PDF-1.4');

        $this->actingAs($admin)
            ->get(route('riwayat-dosen.index'))
            ->assertOk()
            ->assertSee($dosen->name)
            ->assertSee('data-datatable', false)
            ->assertSee('data-table-search', false);

        $this->actingAs($admin)
            ->get(route('riwayat-dosen.show', $dosen))
            ->assertOk()
            ->assertSee('Riwayat Pendidikan')
            ->assertSee('Universitas Pendidikan Indonesia');

        $this->actingAs($admin)
            ->get(route('riwayat-dosen.berkas', [$dosen, $riwayat, 'ijazah_transkrip']))
            ->assertOk()
            ->assertHeader('content-type', 'application/pdf');

        $this->actingAs($admin)
            ->get(route('riwayat-dosen.sk', $dosen))
            ->assertOk()
            ->assertHeader('content-type', 'application/pdf');
    }

    public function test_dosen_can_upload_and_download_sk_dosen_tetap(): void
    {
        $dosen = $this->makeDosen();

        $this->actingAs($dosen)
            ->put(route('profil.pekerjaan.update'), [
                'sk_dosen_tetap' => UploadedFile::fake()->create('sk.pdf', 100, 'application/pdf'),
            ])
            ->assertRedirect(route('profil.pekerjaan.index'))
            ->assertSessionHasNoErrors();

        $profil = $dosen->profilDosen()->firstOrFail();
        $this->assertNotNull($profil->sk_dosen_tetap_path);
        Storage::disk('local')->assertExists($profil->sk_dosen_tetap_path);

        $this->actingAs($dosen)
            ->get(route('profil.pekerjaan.sk'))
            ->assertOk()
            ->assertHeader('content-type', 'application/pdf');
    }
}