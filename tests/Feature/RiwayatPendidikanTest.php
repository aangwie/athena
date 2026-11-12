<?php

namespace Tests\Feature;

use App\Models\RiwayatPendidikan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class RiwayatPendidikanTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('local');
    }

    private function makeDosen(string $nidn = '1987654321', string $name = 'Dr. Ayu Lestari, M.Kom.'): User
    {
        return User::create([
            'nidn' => $nidn,
            'name' => $name,
            'email' => "{$nidn}@example.test",
            'program_studi' => 'Pendidikan Informatika',
            'jabatan_fungsional' => 'Lektor',
            'role' => 'dosen',
            'is_active' => true,
            'password' => Hash::make('password'),
        ]);
    }

    private function payload(array $overrides = []): array
    {
        return array_merge([
            'jenjang' => 'sd',
            'nama_institusi' => 'SD Negeri 1 Bandung',
            'jurusan' => null,
            'tahun_masuk' => 1993,
            'tahun_lulus' => 1999,
            'dokumen' => UploadedFile::fake()->create('dokumen.pdf', 100, 'application/pdf'),
        ], $overrides);
    }

    private function higherPayload(array $overrides = []): array
    {
        return $this->payload(array_merge([
            'jenjang' => 's1',
            'nama_institusi' => 'Universitas Pendidikan Indonesia',
            'jurusan' => 'Pendidikan Informatika',
            'tahun_masuk' => 2005,
            'tahun_lulus' => 2009,
            'akreditasi_prodi' => UploadedFile::fake()->create('akreditasi_prodi.pdf', 100, 'application/pdf'),
            'disertasi_tesis' => UploadedFile::fake()->create('disertasi_tesis.pdf', 100, 'application/pdf'),
        ], $overrides));
    }

    public function test_dosen_can_add_sd_education_with_dokumen(): void
    {
        $dosen = $this->makeDosen();

        $this->actingAs($dosen)
            ->post('/profil/riwayat-pendidikan', $this->payload())
            ->assertRedirect(route('profil.pendidikan.index'))
            ->assertSessionHasNoErrors();

        $riwayat = RiwayatPendidikan::firstOrFail();

        $this->assertSame($dosen->id, $riwayat->user_id);
        $this->assertSame('sd', $riwayat->jenjang);
        $this->assertSame('SD Negeri 1 Bandung', $riwayat->nama_institusi);
        $this->assertNull($riwayat->akreditasi_prodi_path);
        $this->assertNull($riwayat->disertasi_tesis_path);
        $this->assertMatchesRegularExpression(
            '/^ijazah_transkrip_sd_1987654321_[A-Za-z0-9]{16}\.pdf$/',
            basename($riwayat->ijazah_transkrip_path),
        );
        Storage::disk('local')->assertExists($riwayat->ijazah_transkrip_path);
    }

    public function test_dosen_can_add_higher_education_with_optional_berkas(): void
    {
        $dosen = $this->makeDosen();

        $this->actingAs($dosen)
            ->post('/profil/riwayat-pendidikan', $this->higherPayload())
            ->assertRedirect(route('profil.pendidikan.index'))
            ->assertSessionHasNoErrors();

        $riwayat = RiwayatPendidikan::firstOrFail();

        $this->assertSame('s1', $riwayat->jenjang);
        $this->assertMatchesRegularExpression(
            '/^ijazah_transkrip_s1_1987654321_[A-Za-z0-9]{16}\.pdf$/',
            basename($riwayat->ijazah_transkrip_path),
        );
        $this->assertMatchesRegularExpression(
            '/^akreditasi_prodi_s1_1987654321_[A-Za-z0-9]{16}\.pdf$/',
            basename($riwayat->akreditasi_prodi_path),
        );
        $this->assertMatchesRegularExpression(
            '/^disertasi_tesis_s1_1987654321_[A-Za-z0-9]{16}\.pdf$/',
            basename($riwayat->disertasi_tesis_path),
        );
        Storage::disk('local')->assertExists($riwayat->ijazah_transkrip_path);
        Storage::disk('local')->assertExists($riwayat->akreditasi_prodi_path);
        Storage::disk('local')->assertExists($riwayat->disertasi_tesis_path);
    }

    public function test_education_requires_dokumen(): void
    {
        $dosen = $this->makeDosen();

        $payload = $this->payload();
        unset($payload['dokumen']);

        $this->actingAs($dosen)
            ->from('/profil/riwayat-pendidikan/create')
            ->post('/profil/riwayat-pendidikan', $payload)
            ->assertRedirect('/profil/riwayat-pendidikan/create')
            ->assertSessionHasErrors(['dokumen']);
    }

    public function test_dokumen_must_be_pdf_and_not_exceed_limits(): void
    {
        $dosen = $this->makeDosen();

        $this->actingAs($dosen)
            ->from('/profil/riwayat-pendidikan/create')
            ->post('/profil/riwayat-pendidikan', $this->payload([
                'dokumen' => UploadedFile::fake()->create('dokumen.jpg', 100, 'image/jpeg'),
            ]))
            ->assertSessionHasErrors('dokumen');

        $this->actingAs($dosen)
            ->from('/profil/riwayat-pendidikan/create')
            ->post('/profil/riwayat-pendidikan', $this->payload([
                'dokumen' => UploadedFile::fake()->create('dokumen.pdf', 1025, 'application/pdf'),
            ]))
            ->assertSessionHasErrors('dokumen');

        $this->actingAs($dosen)
            ->from('/profil/riwayat-pendidikan/create')
            ->post('/profil/riwayat-pendidikan', $this->payload([
                'disertasi_tesis' => UploadedFile::fake()->create('disertasi.pdf', 4097, 'application/pdf'),
            ]))
            ->assertSessionHasErrors('disertasi_tesis');
    }

    public function test_dosen_can_update_education_and_replace_berkas(): void
    {
        $dosen = $this->makeDosen();

        $this->actingAs($dosen)->post('/profil/riwayat-pendidikan', $this->payload());

        $riwayat = RiwayatPendidikan::firstOrFail();
        $oldPath = $riwayat->ijazah_transkrip_path;

        $this->actingAs($dosen)
            ->put(route('profil.pendidikan.update', $riwayat), $this->payload([
                'nama_institusi' => 'SD Negeri 2 Bandung',
                'tahun_lulus' => 2000,
                'dokumen' => UploadedFile::fake()->create('dokumen-baru.pdf', 120, 'application/pdf'),
            ]))
            ->assertRedirect(route('profil.pendidikan.index'))
            ->assertSessionHasNoErrors();

        $riwayat->refresh();

        $this->assertSame('SD Negeri 2 Bandung', $riwayat->nama_institusi);
        $this->assertSame(2000, $riwayat->tahun_lulus);
        $this->assertNotSame($oldPath, $riwayat->ijazah_transkrip_path);
        Storage::disk('local')->assertMissing($oldPath);
        Storage::disk('local')->assertExists($riwayat->ijazah_transkrip_path);
    }

    public function test_dosen_can_add_optional_berkas_on_update(): void
    {
        $dosen = $this->makeDosen();

        $this->actingAs($dosen)->post('/profil/riwayat-pendidikan', $this->payload());

        $riwayat = RiwayatPendidikan::firstOrFail();

        $this->assertNull($riwayat->akreditasi_prodi_path);
        $this->assertNull($riwayat->disertasi_tesis_path);

        $this->actingAs($dosen)
            ->put(route('profil.pendidikan.update', $riwayat), $this->payload([
                'akreditasi_prodi' => UploadedFile::fake()->create('akreditasi.pdf', 100, 'application/pdf'),
                'disertasi_tesis' => UploadedFile::fake()->create('disertasi.pdf', 100, 'application/pdf'),
            ]))
            ->assertSessionHasNoErrors();

        $riwayat->refresh();

        $this->assertNotNull($riwayat->akreditasi_prodi_path);
        $this->assertNotNull($riwayat->disertasi_tesis_path);
    }

    public function test_dosen_can_delete_education_and_its_files(): void
    {
        $dosen = $this->makeDosen();

        $this->actingAs($dosen)->post('/profil/riwayat-pendidikan', $this->higherPayload());

        $riwayat = RiwayatPendidikan::firstOrFail();
        $dokumenPath = $riwayat->ijazah_transkrip_path;
        $akreditasiPath = $riwayat->akreditasi_prodi_path;
        $disertasiPath = $riwayat->disertasi_tesis_path;

        $this->actingAs($dosen)
            ->delete(route('profil.pendidikan.destroy', $riwayat))
            ->assertRedirect(route('profil.pendidikan.index'));

        $this->assertDatabaseMissing('riwayat_pendidikan', ['id' => $riwayat->id]);
        Storage::disk('local')->assertMissing($dokumenPath);
        Storage::disk('local')->assertMissing($akreditasiPath);
        Storage::disk('local')->assertMissing($disertasiPath);
    }

    public function test_dosen_can_download_own_berkas(): void
    {
        $dosen = $this->makeDosen();
        $path = 'riwayat-pendidikan/1987654321/ijazah_transkrip.pdf';

        Storage::disk('local')->put($path, '%PDF-1.4');

        $riwayat = $dosen->riwayatPendidikan()->create([
            'jenjang' => 'sd',
            'nama_institusi' => 'SD Negeri 1 Bandung',
            'tahun_masuk' => 1993,
            'tahun_lulus' => 1999,
            'ijazah_transkrip_path' => $path,
        ]);

        $this->actingAs($dosen)
            ->get(route('profil.pendidikan.berkas', [$riwayat, 'ijazah_transkrip']))
            ->assertOk()
            ->assertHeader('content-type', 'application/pdf');
    }

    public function test_dosen_cannot_access_another_dosen_education(): void
    {
        $dosen = $this->makeDosen();
        $other = $this->makeDosen('1111111111', 'Budi Santoso, M.Pd.');

        $riwayat = $other->riwayatPendidikan()->create([
            'jenjang' => 'sd',
            'nama_institusi' => 'SD Negeri 1 Sleman',
            'tahun_masuk' => 1996,
            'tahun_lulus' => 2002,
            'ijazah_transkrip_path' => 'riwayat-pendidikan/1111111111/dokumen.pdf',
        ]);

        $this->actingAs($dosen)
            ->get(route('profil.pendidikan.edit', $riwayat))
            ->assertNotFound();

        $this->actingAs($dosen)
            ->put(route('profil.pendidikan.update', $riwayat), $this->payload())
            ->assertNotFound();

        $this->actingAs($dosen)
            ->delete(route('profil.pendidikan.destroy', $riwayat))
            ->assertNotFound();

        $this->actingAs($dosen)
            ->get(route('profil.pendidikan.berkas', [$riwayat, 'ijazah_transkrip']))
            ->assertNotFound();
    }

    public function test_index_lists_education_and_highest_jenjang(): void
    {
        $dosen = $this->makeDosen();

        $dosen->riwayatPendidikan()->create([
            'jenjang' => 'sd',
            'nama_institusi' => 'SD Negeri 1 Bandung',
            'tahun_masuk' => 1993,
            'tahun_lulus' => 1999,
            'ijazah_transkrip_path' => 'riwayat-pendidikan/1987654321/sd.pdf',
        ]);

        $dosen->riwayatPendidikan()->create([
            'jenjang' => 's1',
            'nama_institusi' => 'Universitas Pendidikan Indonesia',
            'jurusan' => 'Pendidikan Informatika',
            'tahun_masuk' => 2005,
            'tahun_lulus' => 2009,
            'ijazah_transkrip_path' => 'riwayat-pendidikan/1987654321/s1.pdf',
            'akreditasi_prodi_path' => 'riwayat-pendidikan/1987654321/akreditasi.pdf',
            'disertasi_tesis_path' => 'riwayat-pendidikan/1987654321/disertasi.pdf',
        ]);

        $this->actingAs($dosen)
            ->get('/profil/riwayat-pendidikan')
            ->assertOk()
            ->assertSee('Daftar Riwayat Pendidikan')
            ->assertSee('2 data')
            ->assertSee('SD Negeri 1 Bandung')
            ->assertSee('Universitas Pendidikan Indonesia')
            ->assertSee('Pendidikan Informatika')
            ->assertSee('Ijazah & Transkrip', false)
            ->assertSee('Akreditasi Prodi')
            ->assertSee('Disertasi / Tesis');
    }

    public function test_portfolio_displays_education_history_and_berkas_links(): void
    {
        $dosen = $this->makeDosen();

        $dosen->riwayatPendidikan()->create([
            'jenjang' => 's1',
            'nama_institusi' => 'Universitas Pendidikan Indonesia',
            'jurusan' => 'Pendidikan Informatika',
            'tahun_masuk' => 2005,
            'tahun_lulus' => 2009,
            'ijazah_transkrip_path' => 'riwayat-pendidikan/1987654321/dokumen.pdf',
            'akreditasi_prodi_path' => 'riwayat-pendidikan/1987654321/akreditasi.pdf',
            'disertasi_tesis_path' => 'riwayat-pendidikan/1987654321/disertasi.pdf',
        ]);

        $this->actingAs($dosen)
            ->get('/profil')
            ->assertOk()
            ->assertSee('Riwayat Pendidikan')
            ->assertSee('Universitas Pendidikan Indonesia')
            ->assertSee('Pendidikan Informatika')
            ->assertSee('Ijazah & Transkrip', false)
            ->assertSee('Akreditasi Prodi')
            ->assertSee('Disertasi / Tesis');
    }
}
