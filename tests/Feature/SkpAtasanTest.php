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

class SkpAtasanTest extends TestCase
{
    use RefreshDatabase;

    private static int $seq = 0;

    private function makeUser(string $role, ?string $nama = null): User
    {
        $nidn = $role === 'admin'
            ? '0000000001'
            : '9'.str_pad((string) (++self::$seq), 9, '0', STR_PAD_LEFT);

        return User::create([
            'nidn' => $nidn,
            'name' => $nama ?? ($role === 'dosen' ? 'Dosen '.$nidn : 'Admin Kepegawaian'),
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

    private function makeStruktur(User $user, string $jabatan): StrukturInstansi
    {
        return StrukturInstansi::create([
            'jabatan_struktural_id' => JabatanStruktural::create(['nama' => $jabatan.' '.(++self::$seq)])->id,
            'user_id' => $user->id,
        ]);
    }

    /**
     * SKP dengan pejabat penilai & atasan penilai tertentu; $dinilai mengisi keenam unsur.
     */
    private function makeSkp(User $dosen, User $pejabat, User $atasan, bool $dinilai = true, array $overrides = []): Skp
    {
        $data = [
            'dosen_id' => $dosen->id,
            'pejabat_penilai_id' => $pejabat->id,
            'atasan_penilai_id' => $atasan->id,
            'prodi_id' => $this->makeProdi()->id,
            'periode_mulai' => '2026-01-01',
            'periode_akhir' => '2026-06-01',
        ];

        if ($dinilai) {
            $data += [
                'nilai_orientasi' => 90,
                'nilai_integritas' => 80,
                'nilai_komitmen' => 85,
                'nilai_disiplin' => 95,
                'nilai_kerjasama' => 75,
                'nilai_kepemimpinan' => 88,
                'tanggal_penilaian' => '2026-11-05',
            ];
        }

        return Skp::create(array_merge($data, $overrides));
    }

    public function test_atasan_penilai_melihat_menu_usulan_skp_dan_barisnya(): void
    {
        $pejabat = $this->makeUser('dosen', 'Pejabat Penilai');
        $atasan = $this->makeUser('dosen', 'Atasan Penilai');
        $dosen = $this->makeUser('dosen', 'Dosen A');
        $skp = $this->makeSkp($dosen, $pejabat, $atasan);

        // Menu Usulan SKP muncul di sidebar atasan penilai.
        $this->actingAs($atasan)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee(route('skp-usulan.index'), false);

        // Baris SKP tempat dia menjadi atasan penilai ikut tampil.
        $this->actingAs($atasan)
            ->get(route('skp-usulan.index'))
            ->assertOk()
            ->assertSee('Dosen A')
            ->assertSee($skp->periodeLabel())
            // Sebelum dosen menyetujui, aksi atasan masih menunggu.
            ->assertSee('Menunggu persetujuan dosen')
            ->assertDontSee(route('skp-usulan.keputusan.edit', $skp), false);
    }

    public function test_atasan_tidak_melihat_tombol_input_nilai_pejabat(): void
    {
        $pejabat = $this->makeUser('dosen', 'Pejabat Penilai');
        $atasan = $this->makeUser('dosen', 'Atasan Penilai');
        $skp = $this->makeSkp($this->makeUser('dosen', 'Dosen A'), $pejabat, $atasan);

        $this->actingAs($atasan)
            ->get(route('skp-usulan.index'))
            ->assertOk()
            ->assertDontSee('Input Nilai')
            // Form nilai juga tidak dibuka walau parameter edit dipaksa.
            ->assertDontSee('id="form-nilai"', false);

        $this->actingAs($atasan)
            ->get(route('skp-usulan.index', ['edit' => $skp->id]))
            ->assertOk()
            ->assertDontSee('id="form-nilai"', false);
    }

    public function test_atasan_menyetujui_dengan_tanggal_persetujuan(): void
    {
        $pejabat = $this->makeUser('dosen', 'Pejabat Penilai');
        $atasan = $this->makeUser('dosen', 'Atasan Penilai');
        $skp = $this->makeSkp($this->makeUser('dosen', 'Dosen A'), $pejabat, $atasan, true, [
            'disetujui_pada' => '2026-11-10',
        ]);

        // Setelah dosen menyetujui: tombol Setujui + input tanggal persetujuan tampil.
        $this->actingAs($atasan)
            ->get(route('skp-usulan.index'))
            ->assertOk()
            ->assertSee(route('skp-usulan.setujui-atasan', $skp), false)
            ->assertSee('name="tanggal_persetujuan"', false)
            ->assertSee('data-date-picker', false)
            ->assertSee('Setujui');

        $this->actingAs($atasan)
            ->post(route('skp-usulan.setujui-atasan', $skp), ['tanggal_persetujuan' => '12-11-2026'])
            ->assertRedirect(route('skp-usulan.index'))
            ->assertSessionHas('status', 'Penilaian SKP berhasil Anda setujui.');

        $skp->refresh();

        $this->assertSame('2026-11-12', $skp->disetujui_atasan_pada->toDateString());
        $this->assertTrue($skp->sudahDisetujuiAtasan());

        // Setelah disetujui: badge tampil dan form persetujuan hilang.
        $this->actingAs($atasan)
            ->get(route('skp-usulan.index'))
            ->assertOk()
            ->assertSee('Disetujui')
            ->assertDontSee('name="tanggal_persetujuan"', false);
    }

    public function test_setujui_atasan_wajib_tanggal_format_dd_mm_yyyy(): void
    {
        $pejabat = $this->makeUser('dosen', 'Pejabat Penilai');
        $atasan = $this->makeUser('dosen', 'Atasan Penilai');
        $skp = $this->makeSkp($this->makeUser('dosen', 'Dosen A'), $pejabat, $atasan, true, [
            'disetujui_pada' => '2026-11-10',
        ]);

        $this->actingAs($atasan)
            ->from(route('skp-usulan.index'))
            ->post(route('skp-usulan.setujui-atasan', $skp), [])
            ->assertSessionHasErrors(['tanggal_persetujuan' => 'Tanggal persetujuan wajib diisi.']);

        $this->actingAs($atasan)
            ->from(route('skp-usulan.index'))
            ->post(route('skp-usulan.setujui-atasan', $skp), ['tanggal_persetujuan' => '2026-11-12'])
            ->assertSessionHasErrors(['tanggal_persetujuan' => 'Tanggal persetujuan harus berformat DD-MM-YYYY.']);

        $this->assertNull($skp->fresh()->disetujui_atasan_pada);
    }

    public function test_keputusan_keberatan_hanya_setelah_ditinjau_penilai_dan_disetujui_dosen(): void
    {
        $pejabat = $this->makeUser('dosen', 'Pejabat Penilai');
        $atasan = $this->makeUser('dosen', 'Atasan Penilai');
        $skp = $this->makeSkp($this->makeUser('dosen', 'Dosen A'), $pejabat, $atasan, true, [
            'keberatan' => 'Nilai unsur kerjasama kurang tepat.',
            'tanggal_keberatan' => '2026-11-07',
        ]);

        // Keberatan belum ditinjau penilai & belum disetujui dosen: tombol keputusan tidak tampil.
        $this->actingAs($atasan)
            ->get(route('skp-usulan.index'))
            ->assertOk()
            ->assertDontSee(route('skp-usulan.keputusan.edit', $skp), false);

        $this->actingAs($atasan)
            ->get(route('skp-usulan.keputusan.edit', $skp))
            ->assertNotFound();

        // Peninjauan ulang pejabat penilai + persetujuan dosen terpenuhi.
        $skp->update([
            'tanggapan_keberatan' => 'Nilai sudah sesuai hasil verifikasi.',
            'tanggal_tanggapan' => '2026-11-08',
            'disetujui_pada' => '2026-11-10',
        ]);

        $this->actingAs($atasan)
            ->get(route('skp-usulan.index'))
            ->assertOk()
            ->assertSee(route('skp-usulan.keputusan.edit', $skp), false)
            ->assertSee('Keputusan atas Keberatan');

        $this->actingAs($atasan)
            ->get(route('skp-usulan.keputusan.edit', $skp))
            ->assertOk()
            ->assertSee('Keputusan atas Keberatan SKP')
            ->assertSee('Nilai unsur kerjasama kurang tepat.')
            ->assertSee('Nilai sudah sesuai hasil verifikasi.')
            ->assertSee('name="keputusan_keberatan"', false)
            ->assertSee('name="tanggal_keputusan"', false)
            ->assertSee('data-date-picker', false);

        $this->actingAs($atasan)
            ->put(route('skp-usulan.keputusan.update', $skp), [
                'keputusan_keberatan' => 'Keberatan ditolak, nilai dipertahankan.',
                'tanggal_keputusan' => '13-11-2026',
            ])
            ->assertRedirect(route('skp-usulan.index'))
            ->assertSessionHas('status', 'Keputusan atas keberatan berhasil disimpan.');

        $skp->refresh();

        $this->assertSame('Keberatan ditolak, nilai dipertahankan.', $skp->keputusan_keberatan);
        $this->assertSame('2026-11-13', $skp->tanggal_keputusan->toDateString());
        $this->assertTrue($skp->sudahDiputuskanAtasan());

        // Setelah diputuskan, tombol berubah menjadi "Ubah Keputusan".
        $this->actingAs($atasan)
            ->get(route('skp-usulan.index'))
            ->assertOk()
            ->assertSee('Ubah Keputusan');
    }

    public function test_keputusan_keberatan_wajib_diisi_dengan_format_tanggal(): void
    {
        $pejabat = $this->makeUser('dosen', 'Pejabat Penilai');
        $atasan = $this->makeUser('dosen', 'Atasan Penilai');
        $skp = $this->makeSkp($this->makeUser('dosen', 'Dosen A'), $pejabat, $atasan, true, [
            'keberatan' => 'Nilai kurang tepat.',
            'tanggal_keberatan' => '2026-11-07',
            'tanggapan_keberatan' => 'Nilai sudah sesuai.',
            'tanggal_tanggapan' => '2026-11-08',
            'disetujui_pada' => '2026-11-10',
        ]);

        $this->actingAs($atasan)
            ->from(route('skp-usulan.keputusan.edit', $skp))
            ->put(route('skp-usulan.keputusan.update', $skp), [])
            ->assertSessionHasErrors([
                'keputusan_keberatan' => 'Keputusan atas keberatan wajib diisi.',
                'tanggal_keputusan' => 'Tanggal keputusan wajib diisi.',
            ]);

        $this->actingAs($atasan)
            ->from(route('skp-usulan.keputusan.edit', $skp))
            ->put(route('skp-usulan.keputusan.update', $skp), [
                'keputusan_keberatan' => 'Ditolak.',
                'tanggal_keputusan' => '2026-11-13',
            ])
            ->assertSessionHasErrors(['tanggal_keputusan' => 'Tanggal keputusan harus berformat DD-MM-YYYY.']);

        $this->assertNull($skp->fresh()->keputusan_keberatan);
    }

    public function test_hanya_atasan_penilai_terkait_yang_bisa_memutuskan_dan_menyetujui(): void
    {
        $pejabat = $this->makeUser('dosen', 'Pejabat Penilai');
        $atasan = $this->makeUser('dosen', 'Atasan Penilai');
        $atasanLain = $this->makeUser('dosen', 'Atasan Lain');
        $skp = $this->makeSkp($this->makeUser('dosen', 'Dosen A'), $pejabat, $atasan, true, [
            'keberatan' => 'Nilai kurang tepat.',
            'tanggal_keberatan' => '2026-11-07',
            'tanggapan_keberatan' => 'Nilai sudah sesuai.',
            'tanggal_tanggapan' => '2026-11-08',
            'disetujui_pada' => '2026-11-10',
        ]);

        $this->actingAs($atasanLain)
            ->get(route('skp-usulan.keputusan.edit', $skp))
            ->assertForbidden();

        $this->actingAs($atasanLain)
            ->put(route('skp-usulan.keputusan.update', $skp), [
                'keputusan_keberatan' => 'Mencoba memutuskan SKP orang lain.',
                'tanggal_keputusan' => '13-11-2026',
            ])
            ->assertForbidden();

        $this->actingAs($atasanLain)
            ->post(route('skp-usulan.setujui-atasan', $skp), ['tanggal_persetujuan' => '12-11-2026'])
            ->assertForbidden();

        $skp->refresh();

        $this->assertNull($skp->keputusan_keberatan);
        $this->assertNull($skp->disetujui_atasan_pada);
    }

    public function test_setujui_atasan_ditolak_sebelum_dosen_menyetujui(): void
    {
        $pejabat = $this->makeUser('dosen', 'Pejabat Penilai');
        $atasan = $this->makeUser('dosen', 'Atasan Penilai');
        $skp = $this->makeSkp($this->makeUser('dosen', 'Dosen A'), $pejabat, $atasan);

        $this->actingAs($atasan)
            ->post(route('skp-usulan.setujui-atasan', $skp), ['tanggal_persetujuan' => '12-11-2026'])
            ->assertNotFound();

        $this->assertNull($skp->fresh()->disetujui_atasan_pada);
    }

    public function test_cetak_menampilkan_keputusan_dan_tanggal_persetujuan_atasan(): void
    {
        $pejabat = $this->makeUser('dosen', 'Pejabat Penilai');
        $atasan = $this->makeUser('dosen', 'Atasan Penilai');
        $skp = $this->makeSkp($this->makeUser('dosen', 'Dosen A'), $pejabat, $atasan, true, [
            'keberatan' => 'Nilai kurang tepat.',
            'tanggal_keberatan' => '2026-11-07',
            'tanggapan_keberatan' => 'Nilai sudah sesuai.',
            'tanggal_tanggapan' => '2026-11-08',
            'disetujui_pada' => '2026-11-10',
            'keputusan_keberatan' => 'Keberatan ditolak, nilai dipertahankan.',
            'tanggal_keputusan' => '2026-11-13',
            'disetujui_atasan_pada' => '2026-11-14',
        ]);

        $this->actingAs($this->makeUser('admin'))
            ->get(route('skp.cetak', $skp))
            ->assertOk()
            // Blok 7: keputusan atasan atas keberatan + tanggalnya.
            ->assertSee('7. KEPUTUSAN ATASAN PEJABAT')
            ->assertSee('Keberatan ditolak, nilai dipertahankan.')
            ->assertSee('Tanggal, 13 November 2026')
            // Blok 10: tanggal persetujuan dosen yang dinilai.
            ->assertSee('10. DITERIMA TANGGAL, 10 November 2026')
            // Blok 11: tanggal persetujuan atasan pejabat penilai.
            ->assertSee('11. DITERIMA TANGGAL, 14 November 2026');
    }
}
