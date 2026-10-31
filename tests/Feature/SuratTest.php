<?php

namespace Tests\Feature;

use App\Models\Instansi;
use App\Models\JabatanStruktural;
use App\Models\KaryaTulis;
use App\Models\StrukturInstansi;
use App\Models\SuratPaktaIntegritas;
use App\Models\SuratPernyataanPimpinan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class SuratTest extends TestCase
{
    use RefreshDatabase;

    private function makeUser(string $role, ?string $nidn = null): User
    {
        $nidn ??= $role === 'admin' ? '0000000001' : '1987654321';

        return User::create([
            'nidn' => $nidn,
            'name' => $role === 'dosen' ? 'Dr. Ayu Lestari, M.Kom.' : 'Admin Kepegawaian',
            'email' => $nidn.'@example.test',
            'role' => $role,
            'is_active' => true,
            'password' => Hash::make('password'),
        ]);
    }

    private function makePimpinan(): StrukturInstansi
    {
        $jabatan = JabatanStruktural::create(['nama' => 'Rektor']);

        return StrukturInstansi::create([
            'jabatan_struktural_id' => $jabatan->id,
            'user_id' => $this->makeUser('dosen', '198001012010011001')->id,
        ]);
    }

    public function test_admin_can_open_surat_page_with_four_tabs(): void
    {
        $response = $this->actingAs($this->makeUser('admin'))
            ->get(route('surat.index'))
            ->assertOk()
            ->assertSee('Surat');

        foreach ([
            'pernyataan-pimpinan' => 'Pernyataan Pimpinan',
            'pakta-integritas' => 'Pakta Integritas',
            'bap-senat' => 'BAP Senat',
            'baki' => 'BAKI',
        ] as $key => $label) {
            $response->assertSee('data-tab-target="'.$key.'"', false)
                ->assertSee('data-tab-panel="'.$key.'"', false)
                ->assertSee($label);
        }
    }

    public function test_pernyataan_pimpinan_tab_has_two_searchable_selects_and_date_field(): void
    {
        $this->actingAs($this->makeUser('admin'))
            ->get(route('surat.index'))
            ->assertOk()
            ->assertSee('Nama Pimpinan')
            ->assertSee('Dosen Yang Di Usulkan')
            ->assertSee('Tanggal Surat')
            ->assertSee('DD-MM-YYYY')
            ->assertSee('data-select-search="Cari nama pimpinan…"', false)
            ->assertSee('data-select-search="Cari nama dosen…"', false)
            ->assertSee('data-date-mask', false)
            ->assertSee('data-datatable', false)
            ->assertSee('Simpan');
    }

    public function test_pernyataan_pimpinan_table_has_print_edit_and_delete_icons(): void
    {
        $pernyataan = SuratPernyataanPimpinan::query()->create([
            'struktur_instansi_id' => $this->makePimpinan()->id,
            'user_id' => $this->makeUser('dosen')->id,
            'tanggal_surat' => '2026-10-31',
        ]);

        $this->actingAs($this->makeUser('admin'))
            ->get(route('surat.index'))
            ->assertOk()
            ->assertSee('Aksi')
            ->assertSee('aria-label="Print"', false)
            ->assertSee('aria-label="Edit"', false)
            ->assertSee('aria-label="Hapus"', false)
            ->assertDontSee('>Print</a>', false)
            ->assertSee(route('surat.pernyataan-pimpinan.cetak', $pernyataan), false)
            ->assertSee(route('surat.index', ['edit' => $pernyataan->id]), false)
            ->assertSee(route('surat.pernyataan-pimpinan.destroy', $pernyataan), false);
    }

    public function test_surat_menu_sits_under_verifikasi_berkas_for_admin_only(): void
    {
        $this->actingAs($this->makeUser('admin'))
            ->get('/dashboard')
            ->assertOk()
            ->assertSee(route('surat.index'), false)
            ->assertSeeInOrder([route('verifikasi-berkas.index'), route('surat.index')], false);

        $this->actingAs($this->makeUser('dosen'))
            ->get('/dashboard')
            ->assertOk()
            ->assertDontSee(route('surat.index'), false);
    }

    public function test_surat_page_is_only_available_for_admin(): void
    {
        $dosen = $this->makeUser('dosen');

        $this->actingAs($dosen)
            ->get(route('surat.index'))
            ->assertForbidden();

        $this->actingAs($dosen)
            ->post(route('surat.pernyataan-pimpinan.store'), [])
            ->assertForbidden();
    }

    public function test_admin_can_save_pernyataan_pimpinan(): void
    {
        $admin = $this->makeUser('admin');
        $pimpinan = $this->makePimpinan();
        $dosen = $this->makeUser('dosen');

        $this->actingAs($admin)
            ->post(route('surat.pernyataan-pimpinan.store'), [
                'struktur_instansi_id' => $pimpinan->id,
                'user_id' => $dosen->id,
                'tanggal_surat' => '31-10-2026',
            ])
            ->assertRedirect(route('surat.index').'#pernyataan-pimpinan')
            ->assertSessionHas('status', 'Pernyataan pimpinan berhasil disimpan.');

        $this->assertSame(
            '2026-10-31',
            SuratPernyataanPimpinan::query()->sole()->tanggal_surat->toDateString(),
        );
    }

    public function test_admin_sees_saved_pernyataan_pimpinan_in_table(): void
    {
        SuratPernyataanPimpinan::query()->create([
            'struktur_instansi_id' => $this->makePimpinan()->id,
            'user_id' => $this->makeUser('dosen')->id,
            'tanggal_surat' => '2026-10-31',
        ]);

        $this->actingAs($this->makeUser('admin'))
            ->get(route('surat.index'))
            ->assertOk()
            ->assertSee('Rektor')
            ->assertSee('Dr. Ayu Lestari, M.Kom.')
            ->assertSee('31-10-2026');
    }

    public function test_pernyataan_pimpinan_rejects_invalid_input(): void
    {
        $admin = $this->makeUser('admin');
        $pimpinan = $this->makePimpinan();

        $this->actingAs($admin)
            ->post(route('surat.pernyataan-pimpinan.store'), [
                'struktur_instansi_id' => $pimpinan->id,
                'user_id' => $admin->id,
                'tanggal_surat' => '2026-10-31',
            ])
            ->assertSessionHasErrors(['user_id', 'tanggal_surat']);

        $this->assertDatabaseCount('surat_pernyataan_pimpinan', 0);
    }

    public function test_admin_can_update_pernyataan_pimpinan(): void
    {
        $pimpinan = $this->makePimpinan();
        $dosenBaru = $this->makeUser('dosen', '199001012015011001');

        $pernyataan = SuratPernyataanPimpinan::query()->create([
            'struktur_instansi_id' => $pimpinan->id,
            'user_id' => $this->makeUser('dosen')->id,
            'tanggal_surat' => '2026-10-31',
        ]);

        $this->actingAs($this->makeUser('admin'))
            ->put(route('surat.pernyataan-pimpinan.update', $pernyataan), [
                'struktur_instansi_id' => $pimpinan->id,
                'user_id' => $dosenBaru->id,
                'tanggal_surat' => '01-11-2026',
                'nomor_surat' => '001/SPP/PT/2026',
            ])
            ->assertRedirect(route('surat.index').'#pernyataan-pimpinan')
            ->assertSessionHas('status', 'Pernyataan pimpinan berhasil diperbarui.');

        $pernyataan->refresh();

        $this->assertSame('2026-11-01', $pernyataan->tanggal_surat->toDateString());
        $this->assertSame($dosenBaru->id, $pernyataan->user_id);
        $this->assertSame('001/SPP/PT/2026', $pernyataan->nomor_surat);
    }

    public function test_admin_can_delete_pernyataan_pimpinan(): void
    {
        $pernyataan = SuratPernyataanPimpinan::query()->create([
            'struktur_instansi_id' => $this->makePimpinan()->id,
            'user_id' => $this->makeUser('dosen')->id,
            'tanggal_surat' => '2026-10-31',
        ]);

        $this->actingAs($this->makeUser('admin'))
            ->delete(route('surat.pernyataan-pimpinan.destroy', $pernyataan))
            ->assertRedirect(route('surat.index').'#pernyataan-pimpinan')
            ->assertSessionHas('status', 'Pernyataan pimpinan berhasil dihapus.');

        $this->assertDatabaseCount('surat_pernyataan_pimpinan', 0);
    }

    public function test_admin_can_print_pernyataan_pimpinan_as_pdf_letter(): void
    {
        $pernyataan = SuratPernyataanPimpinan::query()->create([
            'struktur_instansi_id' => $this->makePimpinan()->id,
            'user_id' => $this->makeUser('dosen')->id,
            'tanggal_surat' => '2026-10-31',
            'nomor_surat' => '001/SPP/PT/2026',
        ]);

        $response = $this->actingAs($this->makeUser('admin'))
            ->get(route('surat.pernyataan-pimpinan.cetak', $pernyataan))
            ->assertOk()
            ->assertHeader('Content-Type', 'application/pdf');

        $isi = $response->getContent();

        $this->assertStringStartsWith('%PDF-', $isi);
        // A4 portrait (595.28 x 841.89 pt) sesuai dokumentasi FPDF.
        $this->assertStringContainsString('/MediaBox [0 0 595.28 841.89]', $isi);
        $this->assertStringContainsString('inline; filename="surat-pernyataan-pimpinan-dr-ayu-lestari-mkom.pdf"', (string) $response->headers->get('Content-Disposition'));
    }

    public function test_dosen_cannot_print_update_or_delete_pernyataan_pimpinan(): void
    {
        $pernyataan = SuratPernyataanPimpinan::query()->create([
            'struktur_instansi_id' => $this->makePimpinan()->id,
            'user_id' => $this->makeUser('dosen')->id,
            'tanggal_surat' => '2026-10-31',
        ]);

        $dosen = $this->makeUser('dosen', '199001012015011001');

        $this->actingAs($dosen)
            ->get(route('surat.pernyataan-pimpinan.cetak', $pernyataan))
            ->assertForbidden();

        $this->actingAs($dosen)
            ->put(route('surat.pernyataan-pimpinan.update', $pernyataan), [])
            ->assertForbidden();

        $this->actingAs($dosen)
            ->delete(route('surat.pernyataan-pimpinan.destroy', $pernyataan))
            ->assertForbidden();

        $this->assertDatabaseCount('surat_pernyataan_pimpinan', 1);
    }

    private function makePakta(User $dosen, string $tanggal = '2026-10-31'): SuratPaktaIntegritas
    {
        return SuratPaktaIntegritas::query()->create([
            'user_id' => $dosen->id,
            'tanggal_pakta' => $tanggal,
        ]);
    }

    public function test_pakta_integritas_tab_has_searchable_dosen_date_and_table_actions(): void
    {
        Instansi::create(['nama_instansi' => 'Universitas Athena', 'kota' => 'Bandung']);

        $pakta = $this->makePakta($this->makeUser('dosen'));

        $this->actingAs($this->makeUser('admin'))
            ->get(route('surat.index'))
            ->assertOk()
            ->assertSee('Nama Dosen')
            ->assertSee('Tanggal Pakta')
            ->assertSee('DD-MM-YYYY')
            ->assertSee('data-select-search="Cari nama dosen…"', false)
            ->assertSee('Tempat dan Tanggal Pakta')
            ->assertSee('Bandung, 31-10-2026')
            ->assertSee('aria-label="Print"', false)
            ->assertSee('aria-label="Edit"', false)
            ->assertSee('aria-label="Hapus"', false)
            ->assertSee(route('surat.pakta-integritas.cetak', $pakta), false)
            ->assertSee(route('surat.index', ['edit_pakta' => $pakta->id]), false)
            ->assertSee(route('surat.pakta-integritas.destroy', $pakta), false);
    }

    public function test_admin_can_save_and_update_pakta_integritas(): void
    {
        $admin = $this->makeUser('admin');
        $dosen = $this->makeUser('dosen');

        $this->actingAs($admin)
            ->post(route('surat.pakta-integritas.store'), [
                'dosen_id' => $dosen->id,
                'tanggal_pakta' => '31-10-2026',
            ])
            ->assertRedirect(route('surat.index').'#pakta-integritas')
            ->assertSessionHas('status', 'Pakta integritas berhasil disimpan.');

        $pakta = SuratPaktaIntegritas::query()->firstOrFail();

        $this->assertSame($dosen->id, $pakta->user_id);
        $this->assertSame('2026-10-31', $pakta->tanggal_pakta->toDateString());

        $this->actingAs($admin)
            ->put(route('surat.pakta-integritas.update', $pakta), [
                'dosen_id' => $dosen->id,
                'tanggal_pakta' => '01-11-2026',
            ])
            ->assertRedirect(route('surat.index').'#pakta-integritas')
            ->assertSessionHas('status', 'Pakta integritas berhasil diperbarui.');

        $this->assertSame('2026-11-01', $pakta->refresh()->tanggal_pakta->toDateString());
    }

    public function test_admin_can_print_pakta_integritas_as_pdf_letter(): void
    {
        Instansi::create(['nama_instansi' => 'Universitas Athena', 'kota' => 'Bandung']);

        $pakta = $this->makePakta($this->makeUser('dosen'));

        $pakta->dosen->karyaTulis()->createMany([
            [
                'karya_ilmiah' => 'Artikel Jurnal',
                'judul' => 'Sistem Informasi Akademik Terintegrasi untuk Perguruan Tinggi di Indonesia',
                'link_bukti' => 'https://drive.google.com/file/d/1abcdefghijklmnopqrstuvwxyz012345/view',
            ],
            [
                'karya_ilmiah' => 'Buku',
                'judul' => 'Rekayasa Perangkat Lunak',
                'link_bukti' => 'https://drive.google.com/file/d/1zyxwvutsrqponmlkjihgfedcba543210/view',
            ],
        ]);

        $response = $this->actingAs($this->makeUser('admin'))
            ->get(route('surat.pakta-integritas.cetak', $pakta))
            ->assertOk()
            ->assertHeader('Content-Type', 'application/pdf');

        $isi = $response->getContent();

        $this->assertStringStartsWith('%PDF-', $isi);
        // A4 portrait (595.28 x 841.89 pt) sesuai dokumentasi FPDF.
        $this->assertStringContainsString('/MediaBox [0 0 595.28 841.89]', $isi);
        $this->assertStringContainsString('inline; filename="surat-pakta-integritas-dr-ayu-lestari-mkom.pdf"', (string) $response->headers->get('Content-Disposition'));
    }

    private function makeKarya(User $dosen, string $judul, string $jenis = 'Buku'): KaryaTulis
    {
        return $dosen->karyaTulis()->create([
            'karya_ilmiah' => $jenis,
            'judul' => $judul,
            'link_bukti' => 'https://drive.google.com/file/d/1abcdefghijklmnopqrstuvwxyz012345/view',
        ]);
    }

    public function test_pakta_integritas_form_lists_karya_ilmiah_of_each_dosen(): void
    {
        Instansi::create(['nama_instansi' => 'Universitas Athena', 'kota' => 'Bandung']);

        $dosen = $this->makeUser('dosen');
        $this->makeKarya($dosen, 'Karya Ilmiah Pertama');
        $this->makeKarya($dosen, 'Karya Ilmiah Kedua', 'Artikel Jurnal');

        $this->actingAs($this->makeUser('admin'))
            ->get(route('surat.index'))
            ->assertOk()
            ->assertSee('data-karya-picker', false)
            ->assertSee('data-karya-data', false)
            ->assertSee('Karya Ilmiah Pertama')
            ->assertSee('Karya Ilmiah Kedua');
    }

    public function test_admin_can_choose_karya_ilmiah_printed_in_pakta_integritas(): void
    {
        $dosen = $this->makeUser('dosen');
        $dipilih = $this->makeKarya($dosen, 'Karya Ilmiah Pertama');
        $this->makeKarya($dosen, 'Karya Ilmiah Kedua');

        $this->actingAs($this->makeUser('admin'))
            ->post(route('surat.pakta-integritas.store'), [
                'dosen_id' => $dosen->id,
                'tanggal_pakta' => '31-10-2026',
                'karya_tulis_ids' => [$dipilih->id],
            ])
            ->assertRedirect(route('surat.index').'#pakta-integritas')
            ->assertSessionHasNoErrors();

        $pakta = SuratPaktaIntegritas::query()->sole();

        $this->assertSame([$dipilih->id], $pakta->karya_tulis_ids);
        $this->assertSame(['Karya Ilmiah Pertama'], $pakta->karyaTerpilih()->pluck('judul')->all());
    }

    public function test_pakta_integritas_rejects_karya_ilmiah_of_another_dosen(): void
    {
        $dosen = $this->makeUser('dosen');
        $dosenLain = $this->makeUser('dosen', '199001012015011002');
        $karyaDosenLain = $this->makeKarya($dosenLain, 'Karya Dosen Lain');

        $this->actingAs($this->makeUser('admin'))
            ->post(route('surat.pakta-integritas.store'), [
                'dosen_id' => $dosen->id,
                'tanggal_pakta' => '31-10-2026',
                'karya_tulis_ids' => [$karyaDosenLain->id],
            ])
            ->assertSessionHasErrors('karya_tulis_ids.0');

        $this->assertDatabaseCount('surat_pakta_integritas', 0);
    }

    public function test_cetak_pakta_integritas_only_prints_selected_karya_ilmiah(): void
    {
        Instansi::create(['nama_instansi' => 'Universitas Athena', 'kota' => 'Bandung']);

        $dosen = $this->makeUser('dosen');
        $dipilih = $this->makeKarya($dosen, 'Karya Dipilih');
        $this->makeKarya($dosen, 'Karya Dilewati');

        $pakta = SuratPaktaIntegritas::query()->create([
            'user_id' => $dosen->id,
            'tanggal_pakta' => '2026-10-31',
            'karya_tulis_ids' => [$dipilih->id],
        ]);

        $isi = $this->actingAs($this->makeUser('admin'))
            ->get(route('surat.pakta-integritas.cetak', $pakta))
            ->assertOk()
            ->getContent();

        // FPDF menekan stream dengan zlib; buka dulu agar teks surat bisa diperiksa.
        preg_match_all('/stream\r?\n(.*?)\r?\nendstream/s', $isi, $stream);

        $teks = collect($stream[1])
            ->map(fn ($data) => function_exists('gzuncompress') ? (@gzuncompress($data) ?: $data) : $data)
            ->implode("\n");

        $this->assertStringContainsString('Karya Dipilih', $teks);
        $this->assertStringNotContainsString('Karya Dilewati', $teks);
    }

    public function test_dosen_cannot_manage_pakta_integritas(): void
    {
        $dosen = $this->makeUser('dosen');
        $pakta = $this->makePakta($dosen);

        $this->actingAs($dosen)
            ->get(route('surat.pakta-integritas.cetak', $pakta))
            ->assertForbidden();

        $this->actingAs($dosen)
            ->post(route('surat.pakta-integritas.store'), [])
            ->assertForbidden();

        $this->actingAs($dosen)
            ->delete(route('surat.pakta-integritas.destroy', $pakta))
            ->assertForbidden();

        $this->assertDatabaseCount('surat_pakta_integritas', 1);
    }
}
