<?php

namespace Tests\Feature;

use App\Models\PanduanPenilaian;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

class PanduanPenilaianTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('local');
    }

    private function makeUser(string $role, string $suffix = '1'): User
    {
        return User::create([
            'nidn' => '9'.str_pad($suffix, 9, '0', STR_PAD_LEFT),
            'name' => match ($role) {
                'admin' => 'Admin Kepegawaian',
                'tim_pak' => 'Prof. Budi Santoso, M.T.',
                default => 'Dr. Ayu Lestari, M.Kom.',
            },
            'email' => $role.$suffix.'@example.test',
            'role' => $role,
            'is_active' => true,
            'password' => Hash::make('password'),
        ]);
    }

    private function unggahBerkas(User $admin, string $nama = 'panduan-penilaian.pdf'): TestResponse
    {
        return $this->actingAs($admin)
            ->post(route('panduan.penilaian.simpan'), [
                'tipe' => 'file',
                'berkas' => UploadedFile::fake()->create($nama, 512, 'application/pdf'),
            ]);
    }

    public function test_admin_dapat_membuka_halaman_panduan_penilaian(): void
    {
        $this->actingAs($this->makeUser('admin'))
            ->get(route('panduan.penilaian.index'))
            ->assertOk()
            ->assertSee('Panduan Penilaian')
            ->assertSee('Upload File')
            ->assertSee('Kirim Link File')
            ->assertSee('Simpan Panduan')
            ->assertSee('Panduan penilaian belum tersedia');
    }

    public function test_tim_pak_hanya_dapat_melihat_isi_panduan(): void
    {
        $this->unggahBerkas($this->makeUser('admin'));

        $this->actingAs($this->makeUser('tim_pak', '2'))
            ->get(route('panduan.penilaian.index'))
            ->assertOk()
            ->assertSee('Isi Panduan')
            ->assertSee('panduan-penilaian.pdf')
            ->assertSee(route('panduan.penilaian.berkas'), false)
            ->assertDontSee('Upload File')
            ->assertDontSee('Kirim Link File')
            ->assertDontSee('Simpan Panduan')
            ->assertDontSee('name="berkas"', false)
            ->assertDontSee('name="tautan"', false);
    }

    public function test_dosen_dan_tendik_tidak_dapat_membuka_panduan_penilaian(): void
    {
        foreach ([$this->makeUser('dosen', '3'), $this->makeUser('tendik', '4')] as $user) {
            $this->actingAs($user)
                ->get(route('panduan.penilaian.index'))
                ->assertForbidden();
        }
    }

    public function test_admin_mengunggah_berkas_panduan(): void
    {
        $admin = $this->makeUser('admin');

        $this->unggahBerkas($admin)
            ->assertRedirect(route('panduan.penilaian.index'))
            ->assertSessionHas('status');

        $panduan = PanduanPenilaian::saatIni();

        $this->assertNotNull($panduan);
        $this->assertSame('file', $panduan->tipe);
        $this->assertSame('panduan-penilaian.pdf', $panduan->nama_asli);
        $this->assertNotNull($panduan->path);
        $this->assertNull($panduan->url);
        $this->assertSame($admin->id, $panduan->user_id);
        Storage::disk('local')->assertExists($panduan->path);
    }

    public function test_admin_mengirim_link_panduan(): void
    {
        $this->actingAs($this->makeUser('admin'))
            ->post(route('panduan.penilaian.simpan'), [
                'tipe' => 'link',
                'tautan' => 'https://drive.google.com/file/d/abc123/view?usp=sharing',
            ])
            ->assertRedirect(route('panduan.penilaian.index'))
            ->assertSessionHas('status');

        $panduan = PanduanPenilaian::saatIni();

        $this->assertSame('link', $panduan->tipe);
        $this->assertNull($panduan->path);
        $this->assertNull($panduan->nama_asli);
        // Tautan Google Drive diubah ke bentuk pratinjau agar dapat ditampilkan di iframe.
        $this->assertSame('https://drive.google.com/file/d/abc123/preview', $panduan->tautanPratinjau());

        $this->get(route('panduan.penilaian.index'))
            ->assertOk()
            ->assertSee('https://drive.google.com/file/d/abc123/preview', false);
    }

    public function test_berkas_lama_dihapus_saat_admin_beralih_ke_link(): void
    {
        $admin = $this->makeUser('admin');
        $this->unggahBerkas($admin);

        $path = PanduanPenilaian::saatIni()->path;
        Storage::disk('local')->assertExists($path);

        $this->actingAs($admin)->post(route('panduan.penilaian.simpan'), [
            'tipe' => 'link',
            'tautan' => 'https://example.test/panduan-penilaian.pdf',
        ])->assertRedirect(route('panduan.penilaian.index'));

        Storage::disk('local')->assertMissing($path);
        $this->assertSame('link', PanduanPenilaian::saatIni()->tipe);
    }

    public function test_sumber_panduan_divalidasi(): void
    {
        $admin = $this->makeUser('admin');

        $this->actingAs($admin)
            ->post(route('panduan.penilaian.simpan'), ['tipe' => 'file'])
            ->assertSessionHasErrors('berkas');

        $this->actingAs($admin)
            ->post(route('panduan.penilaian.simpan'), [
                'tipe' => 'file',
                'berkas' => UploadedFile::fake()->create('panduan.txt', 10, 'text/plain'),
            ])
            ->assertSessionHasErrors('berkas');

        $this->actingAs($admin)
            ->post(route('panduan.penilaian.simpan'), ['tipe' => 'link'])
            ->assertSessionHasErrors('tautan');

        $this->actingAs($admin)
            ->post(route('panduan.penilaian.simpan'), ['tipe' => 'link', 'tautan' => 'bukan-url'])
            ->assertSessionHasErrors('tautan');

        $this->assertNull(PanduanPenilaian::saatIni());
    }

    public function test_berkas_panduan_hanya_dapat_dibuka_admin_dan_tim_pak(): void
    {
        $this->unggahBerkas($this->makeUser('admin'));

        $this->actingAs($this->makeUser('tim_pak', '2'))
            ->get(route('panduan.penilaian.berkas'))
            ->assertOk()
            ->assertHeader('content-type', 'application/pdf');

        $this->actingAs($this->makeUser('dosen', '3'))
            ->get(route('panduan.penilaian.berkas'))
            ->assertForbidden();
    }

    public function test_berkas_panduan_kosong_mengembalikan_404(): void
    {
        $this->actingAs($this->makeUser('tim_pak'))
            ->get(route('panduan.penilaian.berkas'))
            ->assertNotFound();
    }

    public function test_admin_dapat_menghapus_panduan(): void
    {
        $admin = $this->makeUser('admin');
        $this->unggahBerkas($admin);

        $path = PanduanPenilaian::saatIni()->path;

        $this->actingAs($admin)
            ->delete(route('panduan.penilaian.hapus'))
            ->assertRedirect(route('panduan.penilaian.index'))
            ->assertSessionHas('status');

        $this->assertNull(PanduanPenilaian::saatIni());
        Storage::disk('local')->assertMissing($path);
    }

    public function test_tim_pak_tidak_dapat_mengubah_panduan(): void
    {
        $timPak = $this->makeUser('tim_pak');

        $this->actingAs($timPak)
            ->post(route('panduan.penilaian.simpan'), [
                'tipe' => 'link',
                'tautan' => 'https://example.test/panduan.pdf',
            ])
            ->assertForbidden();

        $this->actingAs($timPak)
            ->delete(route('panduan.penilaian.hapus'))
            ->assertForbidden();

        $this->assertNull(PanduanPenilaian::saatIni());
    }

    public function test_menu_panduan_penilaian_tampil_untuk_admin_dan_tim_pak(): void
    {
        foreach ([$this->makeUser('admin'), $this->makeUser('tim_pak', '2')] as $user) {
            $this->actingAs($user)
                ->get(route('dashboard'))
                ->assertOk()
                ->assertSee('Panduan Penilaian')
                ->assertSee(route('panduan.penilaian.index'), false);
        }

        $this->actingAs($this->makeUser('dosen', '3'))
            ->get(route('dashboard'))
            ->assertOk()
            ->assertDontSee(route('panduan.penilaian.index'), false);
    }
}

