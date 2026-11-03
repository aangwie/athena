<?php

namespace Tests\Feature;

use App\Models\PengajuanDupak;
use App\Models\ProfilDosen;
use App\Models\RiwayatPendidikan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class TendikRoleTest extends TestCase
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
            'role' => $role,
            'roles' => [$role],
            'is_active' => true,
            'password' => Hash::make('password'),
        ]);
    }

    private function makeTendik(): User
    {
        return $this->makeUser('tendik', '199001012020011001', 'Siti Aminah, A.Md.');
    }

    private function makeDosen(): User
    {
        return $this->makeUser('dosen', '1987654321', 'Dr. Ayu Lestari, M.Kom.');
    }

    public function test_tendik_can_save_data_pribadi(): void
    {
        $tendik = $this->makeTendik();

        $this->actingAs($tendik)
            ->get('/profil/data-pribadi')
            ->assertOk()
            ->assertSee('Data Pribadi')
            ->assertSee('Profil Tendik')
            // Portofolio dosen tidak berlaku untuk tendik.
            ->assertDontSee('Lihat Portofolio');

        $this->actingAs($tendik)
            ->put('/profil/data-pribadi', [
                'nama' => 'Siti Aminah, A.Md.',
                'tempat_lahir' => 'Pacitan',
                'tanggal_lahir' => '1990-01-01',
                'alamat' => 'Jl. Ahmad Yani No. 5, Pacitan',
                'no_hp' => '0812-3456-7890',
            ])
            ->assertRedirect(route('profil.data-pribadi.edit'))
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('profil_dosen', [
            'user_id' => $tendik->id,
            'tempat_lahir' => 'Pacitan',
        ]);

        $this->assertSame('Siti Aminah, A.Md.', ProfilDosen::firstOrFail()->user->name);
    }

    public function test_tendik_can_manage_riwayat_pendidikan(): void
    {
        $tendik = $this->makeTendik();

        $this->actingAs($tendik)
            ->get('/profil/riwayat-pendidikan')
            ->assertOk()
            ->assertSee('Riwayat Pendidikan');

        $this->actingAs($tendik)
            ->post('/profil/riwayat-pendidikan', [
                'jenjang' => 'sd',
                'nama_institusi' => 'SD Negeri 1 Pacitan',
                'jurusan' => null,
                'tahun_masuk' => 1996,
                'tahun_lulus' => 2002,
                'ijazah' => UploadedFile::fake()->create('ijazah.pdf', 100, 'application/pdf'),
            ])
            ->assertRedirect(route('profil.pendidikan.index'))
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('riwayat_pendidikan', [
            'user_id' => $tendik->id,
            'nama_institusi' => 'SD Negeri 1 Pacitan',
        ]);

        $this->assertSame(1, RiwayatPendidikan::count());
    }

    public function test_tendik_menu_shows_riwayat_tendik_and_can_open_it(): void
    {
        $tendik = $this->makeTendik();

        $this->actingAs($tendik)
            ->get('/dashboard')
            ->assertOk()
            ->assertSee('Riwayat Tendik')
            ->assertSee(route('profil.tendik.index'), false)
            ->assertSee('Profil Tendik')
            // Menu dosen tidak muncul untuk tendik.
            ->assertDontSee('Riwayat Pekerjaan')
            ->assertDontSee('Riwayat DUPAK');

        $this->actingAs($tendik)
            ->get(route('profil.tendik.index'))
            ->assertOk()
            ->assertSee('Riwayat Tendik');
    }

    public function test_dosen_and_admin_cannot_open_riwayat_tendik(): void
    {
        $dosen = $this->makeDosen();
        $admin = $this->makeUser('admin', '0000000001', 'Admin Kepegawaian');

        $this->actingAs($dosen)->get(route('profil.tendik.index'))->assertForbidden();
        $this->actingAs($admin)->get(route('profil.tendik.index'))->assertForbidden();

        $this->actingAs($dosen)
            ->get('/dashboard')
            ->assertOk()
            ->assertDontSee('Riwayat Tendik');
    }

    public function test_tendik_cannot_open_dosen_pages(): void
    {
        $tendik = $this->makeTendik();

        foreach ([
            '/profil',
            '/profil/pengajuan',
            '/profil/pengajuan/riwayat',
            '/profil/riwayat-pekerjaan',
            '/profil/riwayat-karya-tulis',
            '/panduan-pengajuan',
        ] as $url) {
            $this->actingAs($tendik)->get($url)->assertForbidden();
        }
    }

    public function test_dashboard_tendik_does_not_list_dosen_submissions(): void
    {
        $tendik = $this->makeTendik();
        $dosen = $this->makeDosen();

        PengajuanDupak::create([
            'user_id' => $dosen->id,
            'tahun' => 2026,
            'semester' => 'ganjil',
            'status' => 'diajukan_dupak',
            'diajukan_at' => now(),
        ]);

        $this->actingAs($tendik)
            ->get('/dashboard')
            ->assertOk()
            ->assertDontSee('Riwayat Pengajuan')
            ->assertDontSee('Total Pengajuan')
            ->assertDontSee($dosen->name);
    }
}
