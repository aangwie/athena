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

class SkpTest extends TestCase
{
    use RefreshDatabase;

    /** Penghitung NIDN/kode prodi unik di antara user/prodi yang dibuat tiap test. */
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
        // nama jabatan stektural unik di DB; sertakan penghitung agar tidak berbenturan.
        return StrukturInstansi::create([
            'jabatan_struktural_id' => JabatanStruktural::create(['nama' => $jabatan.' '.(++self::$seq)])->id,
            'user_id' => $this->makeUser('dosen')->id,
        ]);
    }

    private function payload(array $overrides = []): array
    {
        return array_merge([
            'dosen_id' => $this->makeUser('dosen')->id,
            'pejabat_penilai_id' => $this->makePejabat('Rektor')->user_id,
            'atasan_penilai_id' => $this->makePejabat('Dekan')->user_id,
            'prodi_id' => $this->makeProdi()->id,
            'bulan_mulai' => 1,
            'tahun_mulai' => 2026,
            'bulan_akhir' => 6,
            'tahun_akhir' => 2026,
        ], $overrides);
    }

    public function test_admin_can_open_skp_page(): void
    {
        $this->actingAs($this->makeUser('admin'))
            ->get(route('skp.index'))
            ->assertOk()
            ->assertSee('SKP')
            ->assertSee('Dosen yang Dinilai')
            ->assertSee('Pejabat Penilai')
            ->assertSee('Atasan Penilai')
            ->assertSee('Jangka Waktu Penilaian')
            ->assertSee('Prodi Dosen yang Dinilai')
            ->assertSee('data-datatable', false);
    }

    public function test_admin_can_create_skp(): void
    {
        $this->actingAs($this->makeUser('admin'))
            ->post(route('skp.store'), $this->payload())
            ->assertRedirect(route('skp.index'))
            ->assertSessionHas('status', 'Data SKP berhasil ditambahkan.');

        $this->assertDatabaseCount('skp', 1);
        $skp = Skp::query()->first();
        $this->assertSame('2026-01-01', $skp->periode_mulai->toDateString());
        $this->assertSame('2026-06-01', $skp->periode_akhir->toDateString());
        // Tanggal simpan dicatat otomatis saat admin menekan Simpan.
        $this->assertSame(now()->toDateString(), $skp->tanggal_diterima->toDateString());
    }

    public function test_tanggal_simpan_skp_tercetak_pada_blok_10(): void
    {
        $admin = $this->makeUser('admin');

        $this->actingAs($admin)->post(route('skp.store'), $this->payload());
        $skp = Skp::query()->first();

        // Tanggal simpan tercetak pada blok 10 (diterima dosen yang dinilai).
        $this->actingAs($admin)
            ->get(route('skp.cetak', $skp))
            ->assertOk()
            ->assertSee('10. DITERIMA TANGGAL, '.now()->locale('id')->translatedFormat('j F Y'));

        // Mengubah data SKP tidak mengubah tanggal simpan.
        $this->travel(3)->days();

        $this->actingAs($admin)
            ->put(route('skp.update', $skp), $this->payload(['bulan_akhir' => 12]))
            ->assertRedirect(route('skp.index').'#form-skp');

        $this->assertSame(
            now()->subDays(3)->toDateString(),
            $skp->fresh()->tanggal_diterima->toDateString(),
        );
    }

    public function test_admin_can_update_and_delete_skp(): void
    {
        $admin = $this->makeUser('admin');

        $this->actingAs($admin)->post(route('skp.store'), $this->payload());
        $skp = Skp::query()->first();

        $this->actingAs($admin)
            ->put(route('skp.update', $skp), $this->payload(['bulan_akhir' => 12, 'tahun_akhir' => 2026]))
            ->assertRedirect(route('skp.index').'#form-skp')
            ->assertSessionHas('status', 'Data SKP berhasil diperbarui.');

        $this->assertSame('2026-12-01', $skp->fresh()->periode_akhir->toDateString());

        $this->actingAs($admin)
            ->delete(route('skp.destroy', $skp))
            ->assertRedirect(route('skp.index'));

        $this->assertDatabaseMissing('skp', ['id' => $skp->id]);
    }

    public function test_periode_akhir_tidak_boleh_mendahului_mulai(): void
    {
        $this->actingAs($this->makeUser('admin'))
            ->from(route('skp.index'))
            ->post(route('skp.store'), $this->payload(['bulan_akhir' => 12, 'tahun_akhir' => 2025]))
            ->assertSessionHasErrors('bulan_akhir');

        $this->assertDatabaseCount('skp', 0);
    }

    public function test_penilai_harus_memiliki_jabatan_struktural(): void
    {
        $this->actingAs($this->makeUser('admin'))
            ->from(route('skp.index'))
            ->post(route('skp.store'), $this->payload(['pejabat_penilai_id' => $this->makeUser('dosen')->id]))
            ->assertSessionHasErrors('pejabat_penilai_id');

        $this->assertDatabaseCount('skp', 0);
    }

    public function test_dosen_cannot_access_skp(): void
    {
        $this->actingAs($this->makeUser('dosen'))
            ->get(route('skp.index'))
            ->assertForbidden();
    }

    public function test_admin_can_open_halaman_cetak_skp(): void
    {
        $admin = $this->makeUser('admin');

        $this->actingAs($admin)->post(route('skp.store'), $this->payload());
        $skp = Skp::query()->first();
        $skp->update([
            'nilai_orientasi' => 82,
            'nilai_integritas' => 79,
            'nilai_komitmen' => 82,
            'nilai_disiplin' => 82,
            'nilai_kerjasama' => 83,
            'nilai_kepemimpinan' => 84,
            'tanggal_penilaian' => '2026-11-05',
        ]);

        $respon = $this->actingAs($admin)->get(route('skp.cetak', $skp));

        $respon->assertOk()
            ->assertSee('Penilaian Prestasi Kerja')
            ->assertSee($skp->dosen->name)
            ->assertSee($skp->pejabatPenilai->name)
            ->assertSee($skp->atasanPenilai->name)
            ->assertSee('Orientasi Pelayanan')
            ->assertSee('492')     // jumlah keenam unsur
            ->assertSee('82,00')   // rata-rata = Nilai Prestasi Kerja
            ->assertSee('(Baik)')  // predikat rata-rata 82
            ->assertDontSee('x 60%')
            ->assertDontSee('Nilai Perilaku Kerja')
            ->assertSee('cetak-ruang-ttd')   // ruang tanda tangan blok 9–11 tidak boleh kolaps
            // Blok 9 memakai Tanggal Penilaian dari halaman Usulan SKP.
            ->assertSee('9. DIBUAT TANGGAL, 5 November 2026')
            // Blok 10 memakai tanggal simpan SKP oleh admin (dicatat otomatis saat Simpan).
            ->assertSee('10. DITERIMA TANGGAL, '.now()->locale('id')->translatedFormat('j F Y'))
            // Blok 11 tetap diisi manual (titik-titik).
            ->assertSee('11. DITERIMA TANGGAL, ............................')
            ->assertSee('cetak-orientasi')   // pilihan portrait/landscape sebelum cetak
            ->assertSee('@page { size: A4')
            ->assertSee('margin: 0')         // margin halaman digambar sendiri lewat padding
            ->assertSee('.cetak-halaman { padding: 2cm');

        // Tiga section = tiga halaman A4; section ke-2 dan ke-3 dipaksa mulai halaman baru.
        $this->assertSame(3, substr_count($respon->getContent(), 'class="cetak-halaman"'));
        $respon->assertSee('.cetak-halaman + .cetak-halaman { break-before: page; }');
    }

    public function test_cetak_skp_tanpa_tanggal_penilaian_menampilkan_titik_titik(): void
    {
        $admin = $this->makeUser('admin');

        $this->actingAs($admin)->post(route('skp.store'), $this->payload());

        // SKP yang belum diisi tanggal penilaian tetap bisa dicetak (blok 9 berisi titik-titik).
        $this->actingAs($admin)
            ->get(route('skp.cetak', Skp::query()->first()))
            ->assertOk()
            ->assertSee('9. DIBUAT TANGGAL, ............................');
    }

    public function test_dosen_cannot_open_halaman_cetak_skp(): void
    {
        $this->actingAs($this->makeUser('admin'))->post(route('skp.store'), $this->payload());

        $this->actingAs($this->makeUser('dosen'))
            ->get(route('skp.cetak', Skp::query()->first()))
            ->assertForbidden();
    }
}