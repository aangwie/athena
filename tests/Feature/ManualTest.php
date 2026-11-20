<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class ManualTest extends TestCase
{
    use RefreshDatabase;

    private function makeUser(string $role, string $suffix = '1'): User
    {
        return User::create([
            'nidn' => '9'.str_pad($suffix, 9, '0', STR_PAD_LEFT),
            'name' => 'Pengguna '.$role,
            'email' => $role.$suffix.'@example.test',
            'role' => $role,
            'is_active' => true,
            'password' => Hash::make('password'),
        ]);
    }

    public function test_semua_peran_dapat_membuka_buku_manual(): void
    {
        foreach (['dosen', 'tendik', 'tim_pak', 'admin'] as $index => $role) {
            $this->actingAs($this->makeUser($role, (string) ($index + 1)))
                ->get(route('manual.index'))
                ->assertOk()
                ->assertHeader('Content-Type', 'text/html; charset=UTF-8')
                ->assertSee('Kembali ke aplikasi')
                ->assertSee('Cetak / Simpan PDF')
                ->assertSee('Buku Manual Pengguna SIMJAD');
        }
    }

    public function test_gambar_manual_disajikan_lewat_rute_aplikasi(): void
    {
        $this->actingAs($this->makeUser('dosen'))
            ->get(route('manual.gambar', ['berkas' => '01-halaman-masuk.png']))
            ->assertOk()
            ->assertHeader('Content-Type', 'image/png');
    }

    public function test_tautan_gambar_pada_buku_manual_memakai_rute_aplikasi(): void
    {
        $isi = $this->actingAs($this->makeUser('dosen'))
            ->get(route('manual.index'))
            ->assertOk()
            ->getContent();

        $this->assertStringNotContainsString('src="images/', $isi);
        $this->assertStringContainsString(route('manual.gambar', ['berkas' => '01-halaman-masuk.png']), $isi);
        // Bilah navigasi dan gayanya disisipkan pada posisi yang benar.
        $this->assertStringContainsString('<body>'."\n".'<div class="athena-manual-bar">', $isi);
        $this->assertStringContainsString('</style>'."\n".'</head>', $isi);
    }

    public function test_gambar_di_luar_folder_manual_tidak_dapat_dibuka(): void
    {
        $dosen = $this->makeUser('dosen');

        $this->actingAs($dosen)->get('/manual/gambar/rahasia.png')->assertNotFound();
        $this->actingAs($dosen)->get('/manual/gambar/..%2F..%2F.env')->assertNotFound();
        $this->actingAs($dosen)->get('/manual/gambar/01-halaman-masuk.jpg')->assertNotFound();
    }

    public function test_tamu_diarahkan_ke_halaman_masuk(): void
    {
        $this->get(route('manual.index'))->assertRedirect(route('login'));
        $this->get(route('manual.gambar', ['berkas' => '01-halaman-masuk.png']))->assertRedirect(route('login'));
    }

    public function test_tautan_buku_manual_tampil_di_navbar_semua_peran(): void
    {
        $tendik = $this->makeUser('tendik', '2');

        $this->actingAs($tendik)
            ->get(route('profil.data-pribadi.edit'))
            ->assertOk()
            ->assertSee(route('manual.index'), false)
            ->assertSee('Buku Manual');
    }
}
