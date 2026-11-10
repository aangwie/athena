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

class SkpDosenTest extends TestCase
{
    use RefreshDatabase;

    /** Penghitung NIDN/kode prodi unik di antara user/prodi yang dibuat tiap test. */
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

    private function makePejabat(string $jabatan): StrukturInstansi
    {
        return StrukturInstansi::create([
            'jabatan_struktural_id' => JabatanStruktural::create(['nama' => $jabatan.' '.(++self::$seq)])->id,
            'user_id' => $this->makeUser('dosen')->id,
        ]);
    }

    /**
     * SKP untuk dosen tertentu; $dinilai = true mengisi keenam unsur + tanggal penilaian.
     */
    private function makeSkp(User $dosen, User $pejabat, bool $dinilai = true, array $overrides = []): Skp
    {
        $data = [
            'dosen_id' => $dosen->id,
            'pejabat_penilai_id' => $pejabat->id,
            'atasan_penilai_id' => $this->makePejabat('Dekan')->user_id,
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

    public function test_dosen_hanya_melihat_penilaian_atas_namanya_sendiri(): void
    {
        $pejabat = $this->makeUser('dosen', 'Pejabat Penilai');
        $dosenA = $this->makeUser('dosen', 'Dosen A');
        $dosenB = $this->makeUser('dosen', 'Dosen B');
        $dosenC = $this->makeUser('dosen', 'Dosen C');

        $skpA = $this->makeSkp($dosenA, $pejabat);
        $skpB = $this->makeSkp($dosenB, $pejabat);
        $this->makeSkp($dosenC, $pejabat);

        // Dosen B login: hanya baris Dosen B yang tampil.
        $this->actingAs($dosenB)
            ->get(route('skp-dosen.index'))
            ->assertOk()
            ->assertSee('SKP-Dosen')
            ->assertSee('Dosen B')
            ->assertDontSee('Dosen A')
            ->assertDontSee('Dosen C')
            ->assertSee($skpB->periodeLabel())
            // Jumlah & nilai perilaku kerja penilaian Dosen B.
            ->assertSee('513')
            ->assertSee('85,5')
            // Tombol aksi tersedia karena SKP sudah dinilai.
            ->assertSee('Ajukan Keberatan')
            ->assertSee('Setujui')
            ->assertDontSee(route('skp-dosen.keberatan.edit', $skpA), false);
    }

    public function test_menu_skp_dosen_tampil_di_sidebar_dosen_dan_aktif(): void
    {
        $pejabat = $this->makeUser('dosen', 'Pejabat Penilai');
        $dosen = $this->makeUser('dosen', 'Dosen A');
        $this->makeSkp($dosen, $pejabat, false);

        $this->actingAs($dosen)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee(route('skp-dosen.index'), false);

        $this->actingAs($dosen)
            ->get(route('skp-dosen.index'))
            ->assertOk()
            ->assertSee('sidebar-link-active', false);

        // Admin tidak memakai menu dosen ini.
        $this->actingAs($this->makeUser('admin'))
            ->get(route('dashboard'))
            ->assertOk()
            ->assertDontSee(route('skp-dosen.index'), false);
    }

    public function test_menu_skp_dosen_tidak_tampil_bila_belum_diajukan_nilai_skp(): void
    {
        $pejabat = $this->makeUser('dosen', 'Pejabat Penilai');
        $dosenLain = $this->makeUser('dosen', 'Dosen Lain');
        $dosenTanpaSkp = $this->makeUser('dosen', 'Dosen Tanpa SKP');

        // SKP hanya untuk dosenLain: dosenTanpaSkp belum diajukan nilai SKP oleh admin.
        $this->makeSkp($dosenLain, $pejabat);

        $this->actingAs($dosenTanpaSkp)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertDontSee(route('skp-dosen.index'), false);

        // Dosenn yang diajukan nilai SKP tetap melihat menunya.
        $this->actingAs($dosenLain)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee(route('skp-dosen.index'), false);
    }

    public function test_skp_belum_dinilai_belum_punya_tombol_aksi(): void
    {
        $pejabat = $this->makeUser('dosen', 'Pejabat Penilai');
        $dosen = $this->makeUser('dosen', 'Dosen A');
        $skp = $this->makeSkp($dosen, $pejabat, false);

        $this->actingAs($dosen)
            ->get(route('skp-dosen.index'))
            ->assertOk()
            ->assertSee('Belum dinilai')
            ->assertSee('Menunggu penilaian')
            ->assertDontSee('Ajukan Keberatan')
            ->assertDontSee('Setujui');

        // Halaman keberatan tidak tersedia sebelum dinilai.
        $this->actingAs($dosen)
            ->get(route('skp-dosen.keberatan.edit', $skp))
            ->assertNotFound();
    }

    public function test_halaman_keberatan_menampilkan_form_dan_menyimpan_ajuan(): void
    {
        $pejabat = $this->makeUser('dosen', 'Pejabat Penilai');
        $dosen = $this->makeUser('dosen', 'Dosen A');
        $skp = $this->makeSkp($dosen, $pejabat);

        // Halaman baru (bukan tab) berisi uraian keberatan + tanggal ajuan.
        $this->actingAs($dosen)
            ->get(route('skp-dosen.keberatan.edit', $skp))
            ->assertOk()
            ->assertSee('Ajukan Keberatan SKP')
            ->assertSee('Uraian Keberatan')
            ->assertSee('Tanggal Ajuan Keberatan')
            ->assertSee('name="keberatan"', false)
            ->assertSee('name="tanggal_keberatan"', false)
            ->assertSee('data-date-picker', false)
            ->assertSee('DD-MM-YYYY');

        $this->actingAs($dosen)
            ->put(route('skp-dosen.keberatan.update', $skp), [
                'keberatan' => 'Nilai unsur kerjasama menurut saya kurang tepat.',
                'tanggal_keberatan' => '07-11-2026',
            ])
            ->assertRedirect(route('skp-dosen.index'))
            ->assertSessionHas('status', 'Keberatan atas nilai SKP berhasil diajukan.');

        $skp->refresh();

        $this->assertSame('Nilai unsur kerjasama menurut saya kurang tepat.', $skp->keberatan);
        $this->assertSame('2026-11-07', $skp->tanggal_keberatan->toDateString());
        $this->assertTrue($skp->mengajukanKeberatan());

        // Daftar menampilkan status keberatan dan tombol berubah menjadi "Ubah Keberatan".
        $this->actingAs($dosen)
            ->get(route('skp-dosen.index'))
            ->assertOk()
            ->assertSee('Ubah Keberatan')
            ->assertSee('Keberatan diajukan 7 November 2026');
    }

    public function test_keberatan_wajib_diisi_dengan_format_tanggal_dd_mm_yyyy(): void
    {
        $pejabat = $this->makeUser('dosen', 'Pejabat Penilai');
        $dosen = $this->makeUser('dosen', 'Dosen A');
        $skp = $this->makeSkp($dosen, $pejabat);

        $this->actingAs($dosen)
            ->from(route('skp-dosen.keberatan.edit', $skp))
            ->put(route('skp-dosen.keberatan.update', $skp), [])
            ->assertSessionHasErrors([
                'keberatan' => 'Uraian keberatan wajib diisi.',
                'tanggal_keberatan' => 'Tanggal ajuan keberatan wajib diisi.',
            ]);

        $this->actingAs($dosen)
            ->from(route('skp-dosen.keberatan.edit', $skp))
            ->put(route('skp-dosen.keberatan.update', $skp), [
                'keberatan' => 'Nilai terlalu rendah.',
                'tanggal_keberatan' => '2026-11-07',
            ])
            ->assertSessionHasErrors([
                'tanggal_keberatan' => 'Tanggal ajuan keberatan harus berformat DD-MM-YYYY.',
            ]);

        $skp->refresh();

        $this->assertNull($skp->keberatan);
        $this->assertNull($skp->tanggal_keberatan);
    }

    public function test_dosen_tidak_bisa_mengakses_skp_dosen_lain(): void
    {
        $pejabat = $this->makeUser('dosen', 'Pejabat Penilai');
        $dosenA = $this->makeUser('dosen', 'Dosen A');
        $dosenB = $this->makeUser('dosen', 'Dosen B');
        $skpA = $this->makeSkp($dosenA, $pejabat);

        $this->actingAs($dosenB)
            ->get(route('skp-dosen.keberatan.edit', $skpA))
            ->assertForbidden();

        $this->actingAs($dosenB)
            ->put(route('skp-dosen.keberatan.update', $skpA), [
                'keberatan' => 'Mengubah keberatan dosen lain.',
                'tanggal_keberatan' => '07-11-2026',
            ])
            ->assertForbidden();

        $this->actingAs($dosenB)
            ->post(route('skp-dosen.setujui', $skpA))
            ->assertForbidden();

        $this->assertNull($skpA->fresh()->keberatan);
        $this->assertNull($skpA->fresh()->disetujui_pada);
    }

    public function test_dosen_dapat_menyetujui_penilaian_skp(): void
    {
        $pejabat = $this->makeUser('dosen', 'Pejabat Penilai');
        $dosen = $this->makeUser('dosen', 'Dosen A');
        $skp = $this->makeSkp($dosen, $pejabat);

        $this->actingAs($dosen)
            ->post(route('skp-dosen.setujui', $skp))
            ->assertRedirect(route('skp-dosen.index'))
            ->assertSessionHas('status', 'Penilaian SKP berhasil disetujui.');

        $skp->refresh();

        $this->assertTrue($skp->sudahDisetujui());
        $this->assertSame(now()->toDateString(), $skp->disetujui_pada->toDateString());

        // Setelah disetujui: badge "Disetujui" tampil dan aksi tidak tersedia lagi.
        $this->actingAs($dosen)
            ->get(route('skp-dosen.index'))
            ->assertOk()
            ->assertSee('Disetujui')
            ->assertDontSee('Ajukan Keberatan');

        // Halaman keberatan ditolak setelah disetujui.
        $this->actingAs($dosen)
            ->get(route('skp-dosen.keberatan.edit', $skp))
            ->assertForbidden();
    }

    public function test_keberatan_tercetak_pada_blok_5_halaman_cetak_skp(): void
    {
        $pejabat = $this->makeUser('dosen', 'Pejabat Penilai');
        $dosen = $this->makeUser('dosen', 'Dosen A');
        $skp = $this->makeSkp($dosen, $pejabat, true, [
            'keberatan' => 'Nilai unsur disiplin menurut saya sudah tepat waktu.',
            'tanggal_keberatan' => '2026-11-07',
        ]);

        $this->actingAs($this->makeUser('admin'))
            ->get(route('skp.cetak', $skp))
            ->assertOk()
            ->assertSee('5. KEBERATAN DARI DOSEN')
            ->assertSee('YANG DINILAI (APABILA ADA)')
            // Isi ajuan keberatan + tanggal ajuannya tampil pada blok 5.
            ->assertSee('Nilai unsur disiplin menurut saya sudah tepat waktu.')
            ->assertSee('Tanggal, 7 November 2026');
    }

    public function test_dosen_dapat_membatalkan_persetujuan_dan_nilai_terbuka_kembali(): void
    {
        $pejabat = $this->makeUser('dosen', 'Pejabat Penilai');
        $dosen = $this->makeUser('dosen', 'Dosen A');
        $skp = $this->makeSkp($dosen, $pejabat, true, ['disetujui_pada' => '2026-11-10']);

        // Sudah disetujui: tombol pembatalan tampil, aksi keberatan/persetujuan hilang.
        $this->actingAs($dosen)
            ->get(route('skp-dosen.index'))
            ->assertOk()
            ->assertSee('Batalkan Persetujuan')
            ->assertSee(route('skp-dosen.batal-setujui', $skp), false)
            ->assertDontSee('Ajukan Keberatan');

        // Pejabat penilai: nilai terkunci (tombol Input Nilai hilang).
        $this->actingAs($pejabat)
            ->get(route('skp-usulan.index'))
            ->assertOk()
            ->assertSee('Disetujui dosen')
            ->assertDontSee(route('skp-usulan.index', ['edit' => $skp->id]), false)
            ->assertDontSee('Input Nilai');

        // Form nilai juga tidak bisa dibuka walau parameter edit dipaksa.
        $this->actingAs($pejabat)
            ->get(route('skp-usulan.index', ['edit' => $skp->id]))
            ->assertOk()
            ->assertDontSee('id="form-nilai"', false);

        $this->actingAs($dosen)
            ->post(route('skp-dosen.batal-setujui', $skp))
            ->assertRedirect(route('skp-dosen.index'))
            ->assertSessionHas('status', 'Persetujuan penilaian SKP berhasil dibatalkan.');

        $skp->refresh();

        $this->assertNull($skp->disetujui_pada);
        $this->assertFalse($skp->sudahDisetujui());

        // Setelah dibatalkan: tombol Setujui/Ajukan Keberatan kembali, pembatalan hilang.
        $this->actingAs($dosen)
            ->get(route('skp-dosen.index'))
            ->assertOk()
            ->assertSee('Setujui')
            ->assertSee('Ajukan Keberatan')
            ->assertDontSee('Batalkan Persetujuan');

        // Pejabat penilai bisa mengubah nilai lagi.
        $this->actingAs($pejabat)
            ->get(route('skp-usulan.index'))
            ->assertOk()
            ->assertSee('Input Nilai')
            ->assertDontSee('Disetujui dosen');

        $this->actingAs($pejabat)
            ->put(route('skp-usulan.update', $skp), $this->nilaiPayload(['nilai_kerjasama' => 80]))
            ->assertRedirect(route('skp-usulan.index'));

        $this->assertSame(80, $skp->fresh()->nilai_kerjasama);
    }

    public function test_pembatalan_persetujuan_hilang_setelah_disetujui_atasan(): void
    {
        $pejabat = $this->makeUser('dosen', 'Pejabat Penilai');
        $dosen = $this->makeUser('dosen', 'Dosen A');
        $skp = $this->makeSkp($dosen, $pejabat, true, [
            'disetujui_pada' => '2026-11-10',
            'disetujui_atasan_pada' => '2026-11-12',
        ]);

        // Atasan sudah menyetujui: tombol pembatalan tidak tampil, hanya keterangan.
        $this->actingAs($dosen)
            ->get(route('skp-dosen.index'))
            ->assertOk()
            ->assertSee('Disetujui')
            ->assertSee('Sudah disetujui atasan penilai')
            ->assertDontSee('Batalkan Persetujuan');

        // Endpoint pembatalan ditolak (403) dan persetujuan tetap tersimpan.
        $this->actingAs($dosen)
            ->post(route('skp-dosen.batal-setujui', $skp))
            ->assertForbidden();

        $this->assertSame('2026-11-10', $skp->fresh()->disetujui_pada->toDateString());
    }

    public function test_pejabat_tidak_bisa_mengubah_nilai_yang_sudah_disetujui_dosen(): void
    {
        $pejabat = $this->makeUser('dosen', 'Pejabat Penilai');
        $skp = $this->makeSkp($this->makeUser('dosen', 'Dosen A'), $pejabat, true, ['disetujui_pada' => '2026-11-10']);

        $this->actingAs($pejabat)
            ->put(route('skp-usulan.update', $skp), $this->nilaiPayload(['nilai_kerjasama' => 60]))
            ->assertForbidden();

        $this->assertSame(75, $skp->fresh()->nilai_kerjasama);
    }

    public function test_dosen_lain_tidak_bisa_membatalkan_persetujuan(): void
    {
        $pejabat = $this->makeUser('dosen', 'Pejabat Penilai');
        $dosenA = $this->makeUser('dosen', 'Dosen A');
        $dosenB = $this->makeUser('dosen', 'Dosen B');
        $skp = $this->makeSkp($dosenA, $pejabat, true, ['disetujui_pada' => '2026-11-10']);

        $this->actingAs($dosenB)
            ->post(route('skp-dosen.batal-setujui', $skp))
            ->assertForbidden();

        $this->assertNotNull($skp->fresh()->disetujui_pada);
    }

    public function test_blok_5_tetap_kosong_bila_tidak_ada_keberatan(): void
    {
        $pejabat = $this->makeUser('dosen', 'Pejabat Penilai');
        $dosen = $this->makeUser('dosen', 'Dosen A');
        $skp = $this->makeSkp($dosen, $pejabat);

        $this->actingAs($this->makeUser('admin'))
            ->get(route('skp.cetak', $skp))
            ->assertOk()
            ->assertSee('5. KEBERATAN DARI DOSEN')
            ->assertSee('Tanggal, ............................');
    }
}
