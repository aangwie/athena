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
            'ijazah' => UploadedFile::fake()->create('ijazah.pdf', 100, 'application/pdf'),
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
            'transkrip' => UploadedFile::fake()->create('transkrip.pdf', 100, 'application/pdf'),
        ], $overrides));
    }

    public function test_dosen_can_add_sd_education_with_ijazah(): void
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
        $this->assertNull($riwayat->transkrip_path);
        $this->assertMatchesRegularExpression(
            '/^sd_1987654321_[A-Za-z0-9]{16}\.pdf$/',
            basename($riwayat->ijazah_path),
        );
        Storage::disk('local')->assertExists($riwayat->ijazah_path);
    }

    public function test_dosen_can_add_higher_education_with_ijazah_and_transkrip(): void
    {
        $dosen = $this->makeDosen();

        $this->actingAs($dosen)
            ->post('/profil/riwayat-pendidikan', $this->higherPayload())
            ->assertRedirect(route('profil.pendidikan.index'))
            ->assertSessionHasNoErrors();

        $riwayat = RiwayatPendidikan::firstOrFail();

        $this->assertSame('s1', $riwayat->jenjang);
        $this->assertMatchesRegularExpression(
            '/^s1_1987654321_[A-Za-z0-9]{16}\.pdf$/',
            basename($riwayat->ijazah_path),
        );
        $this->assertMatchesRegularExpression(
            '/^s1_1987654321_[A-Za-z0-9]{16}\.pdf$/',
            basename($riwayat->transkrip_path),
        );
        Storage::disk('local')->assertExists($riwayat->ijazah_path);
        Storage::disk('local')->assertExists($riwayat->transkrip_path);
    }

    public function test_higher_education_requires_ijazah_and_transkrip(): void
    {
        $dosen = $this->makeDosen();

        $payload = $this->higherPayload();
        unset($payload['ijazah'], $payload['transkrip']);

        $this->actingAs($dosen)
            ->from('/profil/riwayat-pendidikan/create')
            ->post('/profil/riwayat-pendidikan', $payload)
            ->assertRedirect('/profil/riwayat-pendidikan/create')
            ->assertSessionHasErrors(['ijazah', 'transkrip']);
    }

    public function test_ijazah_must_be_pdf_and_not_exceed_500_kb(): void
    {
        $dosen = $this->makeDosen();

        $this->actingAs($dosen)
            ->from('/profil/riwayat-pendidikan/create')
            ->post('/profil/riwayat-pendidikan', $this->payload([
                'ijazah' => UploadedFile::fake()->create('ijazah.jpg', 100, 'image/jpeg'),
            ]))
            ->assertSessionHasErrors('ijazah');

        $this->actingAs($dosen)
            ->from('/profil/riwayat-pendidikan/create')
            ->post('/profil/riwayat-pendidikan', $this->payload([
                'ijazah' => UploadedFile::fake()->create('ijazah.pdf', 501, 'application/pdf'),
            ]))
            ->assertSessionHasErrors('ijazah');
    }

    public function test_dosen_can_update_education_and_replace_berkas(): void
    {
        $dosen = $this->makeDosen();

        $this->actingAs($dosen)->post('/profil/riwayat-pendidikan', $this->payload());

        $riwayat = RiwayatPendidikan::firstOrFail();
        $oldPath = $riwayat->ijazah_path;

        $this->actingAs($dosen)
            ->put(route('profil.pendidikan.update', $riwayat), $this->payload([
                'nama_institusi' => 'SD Negeri 2 Bandung',
                'tahun_lulus' => 2000,
                'ijazah' => UploadedFile::fake()->create('ijazah-baru.pdf', 120, 'application/pdf'),
            ]))
            ->assertRedirect(route('profil.pendidikan.index'))
            ->assertSessionHasNoErrors();

        $riwayat->refresh();

        $this->assertSame('SD Negeri 2 Bandung', $riwayat->nama_institusi);
        $this->assertSame(2000, $riwayat->tahun_lulus);
        $this->assertNotSame($oldPath, $riwayat->ijazah_path);
        Storage::disk('local')->assertMissing($oldPath);
        Storage::disk('local')->assertExists($riwayat->ijazah_path);
    }

    public function test_switching_to_non_higher_education_removes_transkrip(): void
    {
        $dosen = $this->makeDosen();

        $this->actingAs($dosen)->post('/profil/riwayat-pendidikan', $this->higherPayload());

        $riwayat = RiwayatPendidikan::firstOrFail();
        $transkripPath = $riwayat->transkrip_path;

        $this->actingAs($dosen)
            ->put(route('profil.pendidikan.update', $riwayat), $this->payload([
                'jenjang' => 'sma',
                'nama_institusi' => 'SMA Negeri 3 Bandung',
            ]))
            ->assertSessionHasNoErrors();

        $riwayat->refresh();

        $this->assertSame('sma', $riwayat->jenjang);
        $this->assertNull($riwayat->transkrip_path);
        Storage::disk('local')->assertMissing($transkripPath);
    }

    public function test_dosen_can_delete_education_and_its_files(): void
    {
        $dosen = $this->makeDosen();

        $this->actingAs($dosen)->post('/profil/riwayat-pendidikan', $this->higherPayload());

        $riwayat = RiwayatPendidikan::firstOrFail();
        $ijazahPath = $riwayat->ijazah_path;
        $transkripPath = $riwayat->transkrip_path;

        $this->actingAs($dosen)
            ->delete(route('profil.pendidikan.destroy', $riwayat))
            ->assertRedirect(route('profil.pendidikan.index'));

        $this->assertDatabaseMissing('riwayat_pendidikan', ['id' => $riwayat->id]);
        Storage::disk('local')->assertMissing($ijazahPath);
        Storage::disk('local')->assertMissing($transkripPath);
    }

    public function test_dosen_can_download_own_berkas(): void
    {
        $dosen = $this->makeDosen();
        $path = 'riwayat-pendidikan/1987654321/ijazah.pdf';

        Storage::disk('local')->put($path, '%PDF-1.4');

        $riwayat = $dosen->riwayatPendidikan()->create([
            'jenjang' => 'sd',
            'nama_institusi' => 'SD Negeri 1 Bandung',
            'tahun_masuk' => 1993,
            'tahun_lulus' => 1999,
            'ijazah_path' => $path,
        ]);

        $this->actingAs($dosen)
            ->get(route('profil.pendidikan.berkas', [$riwayat, 'ijazah']))
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
            'ijazah_path' => 'riwayat-pendidikan/1111111111/ijazah.pdf',
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
            ->get(route('profil.pendidikan.berkas', [$riwayat, 'ijazah']))
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
            'ijazah_path' => 'riwayat-pendidikan/1987654321/sd.pdf',
        ]);

        $dosen->riwayatPendidikan()->create([
            'jenjang' => 's1',
            'nama_institusi' => 'Universitas Pendidikan Indonesia',
            'jurusan' => 'Pendidikan Informatika',
            'tahun_masuk' => 2005,
            'tahun_lulus' => 2009,
            'ijazah_path' => 'riwayat-pendidikan/1987654321/s1.pdf',
            'transkrip_path' => 'riwayat-pendidikan/1987654321/s1-transkrip.pdf',
        ]);

        $this->actingAs($dosen)
            ->get('/profil/riwayat-pendidikan')
            ->assertOk()
            ->assertSee('Daftar Riwayat Pendidikan')
            ->assertSee('2 data')
            ->assertSee('SD Negeri 1 Bandung')
            ->assertSee('Universitas Pendidikan Indonesia')
            ->assertSee('Pendidikan Informatika')
            ->assertSee('Ijazah')
            ->assertSee('Transkrip');
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
            'ijazah_path' => 'riwayat-pendidikan/1987654321/ijazah.pdf',
            'transkrip_path' => 'riwayat-pendidikan/1987654321/transkrip.pdf',
        ]);

        $this->actingAs($dosen)
            ->get('/profil')
            ->assertOk()
            ->assertSee('Riwayat Pendidikan')
            ->assertSee('Universitas Pendidikan Indonesia')
            ->assertSee('Pendidikan Informatika')
            ->assertSee('Ijazah')
            ->assertSee('Transkrip');
    }
}
