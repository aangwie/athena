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
            'tanggal_penilaian' => '05-11-2026',
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
            ->assertSee('Nilai Perilaku Kerja')
            ->assertDontSee('Rata-Rata');
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
        // Nilai perilaku kerja sama dengan rata-rata nilai.
        $this->assertEquals(85.5, $skp->nilaiPerilakuKerja());
        $this->assertSame('Sangat Baik', $skp->kategoriNilai($skp->nilai_disiplin));
        $this->assertSame('Baik', $skp->kategoriNilai($skp->nilai_kerjasama));
        // Tanggal penilaian tersimpan sebagai tanggal (dicetak di blok 9 SKP).
        $this->assertSame('2026-11-05', $skp->tanggal_penilaian->toDateString());

        $this->actingAs($pejabat)
            ->get(route('skp-usulan.index'))
            ->assertOk()
            ->assertSee('85,5')
            ->assertDontSee('Rata-Rata');
    }

    public function test_kategori_nilai_memakai_ambang_baru(): void
    {
        $skp = $this->makeSkp($this->makeUser('dosen'));

        // Ambang: ≥86 Sangat Baik · 71–85 Baik · 56–70 Cukup · ≤55 Kurang.
        $this->assertSame('Sangat Baik', $skp->kategoriNilai(100));
        $this->assertSame('Sangat Baik', $skp->kategoriNilai(86));
        $this->assertSame('Baik', $skp->kategoriNilai(85));
        $this->assertSame('Baik', $skp->kategoriNilai(71));
        $this->assertSame('Cukup', $skp->kategoriNilai(70));
        $this->assertSame('Cukup', $skp->kategoriNilai(56));
        $this->assertSame('Kurang', $skp->kategoriNilai(55));
        $this->assertSame('Kurang', $skp->kategoriNilai(0));
        $this->assertSame('—', $skp->kategoriNilai(null));
    }

    public function test_form_menampilkan_input_tanggal_penilaian(): void
    {
        $pejabat = $this->makeUser('dosen');
        $skp = $this->makeSkp($pejabat);
        $skp->update([
            'nilai_orientasi' => 90,
            'nilai_integritas' => 80,
            'nilai_komitmen' => 85,
            'nilai_disiplin' => 95,
            'nilai_kerjasama' => 75,
            'nilai_kepemimpinan' => 88,
            'tanggal_penilaian' => '2026-11-05',
        ]);

        $this->actingAs($pejabat)
            ->get(route('skp-usulan.index', ['edit' => $skp->id]))
            ->assertOk()
            ->assertSee('Tanggal Penilaian')
            ->assertSee('name="tanggal_penilaian"', false)
            // Date picker DD-MM-YYYY dengan nilai lama terisi otomatis.
            ->assertSee('data-date-picker', false)
            ->assertSee('data-date-mask', false)
            ->assertSee('value="05-11-2026"', false);
    }

    public function test_tanggal_penilaian_wajib_dan_harus_format_dd_mm_yyyy(): void
    {
        $pejabat = $this->makeUser('dosen');
        $skp = $this->makeSkp($pejabat);

        // Kosong ditolak.
        $payload = $this->nilaiPayload();
        unset($payload['tanggal_penilaian']);

        $this->actingAs($pejabat)
            ->from(route('skp-usulan.index'))
            ->put(route('skp-usulan.update', $skp), $payload)
            ->assertSessionHasErrors(['tanggal_penilaian' => 'Tanggal penilaian wajib diisi.']);

        // Format YYYY-MM-DD ditolak.
        $this->actingAs($pejabat)
            ->from(route('skp-usulan.index'))
            ->put(route('skp-usulan.update', $skp), $this->nilaiPayload(['tanggal_penilaian' => '2026-11-05']))
            ->assertSessionHasErrors(['tanggal_penilaian' => 'Tanggal penilaian harus berformat DD-MM-YYYY.']);

        $this->assertNull($skp->fresh()->tanggal_penilaian);
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

    public function test_tombol_tanggapan_keberatan_hanya_tampil_bila_ada_ajuan_keberatan(): void
    {
        $pejabat = $this->makeUser('dosen');
        $skpTanpa = $this->makeSkp($pejabat);
        $skpDengan = $this->makeSkp($pejabat);
        $skpDengan->update([
            'keberatan' => 'Nilai unsur kerjasama kurang tepat.',
            'tanggal_keberatan' => '2026-11-07',
        ]);

        // Tanpa ajuan keberatan: tombol Tanggapan Keberatan tidak dirender.
        $this->actingAs($pejabat)
            ->get(route('skp-usulan.index'))
            ->assertOk()
            ->assertDontSee(route('skp-usulan.tanggapan.edit', $skpTanpa), false)
            // Dengan ajuan keberatan: tombol tersedia.
            ->assertSee(route('skp-usulan.tanggapan.edit', $skpDengan), false)
            ->assertSee('Tanggapan Keberatan');

        // Halaman tanggapan untuk SKP tanpa keberatan tidak tersedia.
        $this->actingAs($pejabat)
            ->get(route('skp-usulan.tanggapan.edit', $skpTanpa))
            ->assertNotFound();
    }

    public function test_pejabat_dapat_mengisi_tanggapan_keberatan_dan_tanggal(): void
    {
        $pejabat = $this->makeUser('dosen');
        $skp = $this->makeSkp($pejabat);
        $skp->update([
            'keberatan' => 'Nilai unsur kerjasama kurang tepat.',
            'tanggal_keberatan' => '2026-11-07',
        ]);

        $this->actingAs($pejabat)
            ->get(route('skp-usulan.tanggapan.edit', $skp))
            ->assertOk()
            ->assertSee('Tanggapan Keberatan SKP')
            ->assertSee('Nilai unsur kerjasama kurang tepat.')
            ->assertSee('name="tanggapan_keberatan"', false)
            ->assertSee('name="tanggal_tanggapan"', false)
            ->assertSee('data-date-picker', false)
            ->assertSee('DD-MM-YYYY');

        $this->actingAs($pejabat)
            ->put(route('skp-usulan.tanggapan.update', $skp), [
                'tanggapan_keberatan' => 'Nilai sudah sesuai hasil verifikasi tim penilai.',
                'tanggal_tanggapan' => '10-11-2026',
            ])
            ->assertRedirect(route('skp-usulan.index'))
            ->assertSessionHas('status', 'Tanggapan keberatan berhasil disimpan.');

        $skp->refresh();

        $this->assertSame('Nilai sudah sesuai hasil verifikasi tim penilai.', $skp->tanggapan_keberatan);
        $this->assertSame('2026-11-10', $skp->tanggal_tanggapan->toDateString());
        $this->assertTrue($skp->sudahDitanggapi());

        // Tombol pada daftar berubah menjadi "Ubah Tanggapan".
        $this->actingAs($pejabat)
            ->get(route('skp-usulan.index'))
            ->assertOk()
            ->assertSee('Ubah Tanggapan');
    }

    public function test_tanggapan_wajib_diisi_dan_format_tanggal_dd_mm_yyyy(): void
    {
        $pejabat = $this->makeUser('dosen');
        $skp = $this->makeSkp($pejabat);
        $skp->update(['keberatan' => 'Nilai kurang tepat.', 'tanggal_keberatan' => '2026-11-07']);

        $this->actingAs($pejabat)
            ->from(route('skp-usulan.tanggapan.edit', $skp))
            ->put(route('skp-usulan.tanggapan.update', $skp), [])
            ->assertSessionHasErrors([
                'tanggapan_keberatan' => 'Tanggapan keberatan wajib diisi.',
                'tanggal_tanggapan' => 'Tanggal tanggapan wajib diisi.',
            ]);

        $this->actingAs($pejabat)
            ->from(route('skp-usulan.tanggapan.edit', $skp))
            ->put(route('skp-usulan.tanggapan.update', $skp), [
                'tanggapan_keberatan' => 'Sudah sesuai.',
                'tanggal_tanggapan' => '2026-11-10',
            ])
            ->assertSessionHasErrors([
                'tanggal_tanggapan' => 'Tanggal tanggapan harus berformat DD-MM-YYYY.',
            ]);

        $skp->refresh();

        $this->assertNull($skp->tanggapan_keberatan);
        $this->assertNull($skp->tanggal_tanggapan);
    }

    public function test_pejabat_lain_tidak_bisa_menanggapi_keberatan(): void
    {
        $pejabatA = $this->makeUser('dosen');
        $pejabatB = $this->makeUser('dosen');
        $skp = $this->makeSkp($pejabatA);
        $skp->update(['keberatan' => 'Nilai kurang tepat.', 'tanggal_keberatan' => '2026-11-07']);

        $this->actingAs($pejabatB)
            ->get(route('skp-usulan.tanggapan.edit', $skp))
            ->assertForbidden();

        $this->actingAs($pejabatB)
            ->put(route('skp-usulan.tanggapan.update', $skp), [
                'tanggapan_keberatan' => 'Mencoba menanggapi SKP pejabat lain.',
                'tanggal_tanggapan' => '10-11-2026',
            ])
            ->assertForbidden();

        $this->assertNull($skp->fresh()->tanggapan_keberatan);
    }

    public function test_tanggapan_tercetak_pada_blok_6_halaman_cetak_skp(): void
    {
        $admin = $this->makeUser('admin');
        $pejabat = $this->makeUser('dosen');
        $skp = $this->makeSkp($pejabat);
        $skp->update([
            'keberatan' => 'Nilai unsur kerjasama kurang tepat.',
            'tanggal_keberatan' => '2026-11-07',
            'tanggapan_keberatan' => 'Nilai sudah sesuai hasil verifikasi tim penilai.',
            'tanggal_tanggapan' => '2026-11-10',
        ]);

        $this->actingAs($admin)
            ->get(route('skp.cetak', $skp))
            ->assertOk()
            ->assertSee('6. TANGGAPAN PEJABAT PENILAI')
            ->assertSee('ATAS KEBERATAN')
            ->assertSee('Nilai sudah sesuai hasil verifikasi tim penilai.')
            ->assertSee('Tanggal, 10 November 2026');
    }

    public function test_blok_6_tetap_kosong_tanpa_tanggapan(): void
    {
        $pejabat = $this->makeUser('dosen');
        $skp = $this->makeSkp($pejabat);

        $this->actingAs($this->makeUser('admin'))
            ->get(route('skp.cetak', $skp))
            ->assertOk()
            ->assertSee('6. TANGGAPAN PEJABAT PENILAI')
            ->assertSee('Tanggal, ............................');
    }
}