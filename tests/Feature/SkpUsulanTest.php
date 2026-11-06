<?php

namespace Tests\Feature;

use App\Models\JabatanStruktural;
use App\Models\Prodi;
use App\Models\Skp;
use App\Models\StrukturInstansi;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class SkpUsulanTest extends TestCase
{
    use RefreshDatabase;

    private static int $seq = 0;

    private function makeUser(string $role): User
    {
        $nidn = $role === 'admin'
            ? '0000000001'
            : '9'.str_pad((string) (++self::$seq), 9, '0', STR_PAD_LEFT);

        return User::create([
            'nidn' => $nidn,
            'name' => $role === 'dosen' ? 'Dosen '.$nidn : 'Admin Kepegawaian',
            'email' => $nidn.'@example.test',
            'role' => $role,
            'is_active' => true,
            'password' => Hash::make('password'),
        ]);
    }

    private function makeProdi(): Prodi
    {
        return Prodi::create([
            'nama_prodi' => 'Pendidikan Informatika',
            'kode_prodi' => 'PI-'.str_pad((string) (++self::$seq), 2, '0', STR_PAD_LEFT),
        ]);
    }

    private function makePejabat(string $jabatan): StrukturInstansi
    {
        return StrukturInstansi::create([
            'jabatan_struktural_id' => JabatanStruktural::create(['nama' => $jabatan.' '.(++self::$seq)])->id,
            'user_id' => $this->makeUser('dosen')->id,
        ]);
    }

    private function makeSkp(User $pejabat): Skp
    {
        return Skp::create([
            'dosen_id' => $this->makeUser('dosen')->id,
            'pejabat_penilai_id' => $pejabat->id,
            'atasan_penilai_id' => $this->makePejabat('Dekan')->user_id,
            'prodi_id' => $this->makeProdi()->id,
            'periode_mulai' => '2026-01-01',
            'periode_akhir' => '2026-06-01',
        ]);
    }

    private function nilaiPayload(array $overrides = []): array
    {
        return array_merge([
            'nilai_orientasi' => 90,
            'nilai_integritas' => 80,
            'nilai_komitmen' => 85,
            'nilai_disiplin' => 95,
            'nilai_kerjasama' => 75,
            'nilai_kepemimpinan' => 88,
        ], $overrides);
    }

    public function test_pejabat_penilai_sees_menu_and_can_open_page(): void
    {
        $pejabat = $this->makeUser('dosen');
        $this->makeSkp($pejabat);

        $this->actingAs($pejabat)
            ->get('/dashboard')
            ->assertOk()
            ->assertSee(route('skp-usulan.index'), false);

        $this->actingAs($pejabat)
            ->get(route('skp-usulan.index'))
            ->assertOk()
            ->assertSee('Usulan SKP')
            ->assertSee('Edit Nilai')
            ->assertSee('Nilai Perilaku Kerja');
    }

    public function test_dosen_non_pejabat_tidak_melihat_menu(): void
    {
        $this->actingAs($this->makeUser('dosen'))
            ->get('/dashboard')
            ->assertOk()
            ->assertDontSee(route('skp-usulan.index'), false);
    }

    public function test_pejabat_dapat_mengisi_dan_menghitung_nilai(): void
    {
        $pejabat = $this->makeUser('dosen');
        $skp = $this->makeSkp($pejabat);

        $this->actingAs($pejabat)
            ->put(route('skp-usulan.update', $skp), $this->nilaiPayload())
            ->assertRedirect(route('skp-usulan.index'))
            ->assertSessionHas('status', 'Nilai perilaku kerja berhasil disimpan.');

        $skp = $skp->fresh();
        $this->assertSame(513, $skp->jumlahNilai());
        $this->assertEquals(85.5, $skp->rataRataNilai());
        $this->assertEquals(34.2, $skp->nilaiPerilakuKerja());
        $this->assertSame('Sangat Baik', $skp->kategoriNilai($skp->nilai_disiplin));
        $this->assertSame('Cukup', $skp->kategoriNilai($skp->nilai_kerjasama));
    }

    public function test_nilai_wajib_diantara_0_sampai_100(): void
    {
        $pejabat = $this->makeUser('dosen');
        $skp = $this->makeSkp($pejabat);

        $this->actingAs($pejabat)
            ->from(route('skp-usulan.index'))
            ->put(route('skp-usulan.update', $skp), $this->nilaiPayload(['nilai_orientasi' => 150]))
            ->assertSessionHasErrors('nilai_orientasi');

        $this->assertNull($skp->fresh()->nilai_orientasi);
    }

    public function test_pejabat_tidak_bisa_mengisi_skp_pejabat_lain(): void
    {
        $pejabatA = $this->makeUser('dosen');
        $pejabatB = $this->makeUser('dosen');
        $skp = $this->makeSkp($pejabatA);

        $this->actingAs($pejabatB)
            ->put(route('skp-usulan.update', $skp), $this->nilaiPayload())
            ->assertForbidden();
    }
}