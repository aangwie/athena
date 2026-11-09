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

    public function test_admin_can_open_surat_page_with_five_tabs(): void
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
            'surat-pengantar' => 'Surat Pengantar',
        ] as $key => $label) {
            $response->assertSee('data-tab-target="'.$key.'"', false)
                ->assertSee('data-tab-panel="'.$key.'"', false)
                ->assertSee($label);
        }
    }

    public function test_tab_panels_are_not_nested_inside_each_other(): void
    {
        // @continue di dalam loop tab pernah membuat </div> panel tidak dirender,
        // sehingga tab berikutnya bersarang dan ikut tersembunyi.
        $html = $this->actingAs($this->makeUser('admin'))
            ->get(route('surat.index'))
            ->assertOk()
            ->getContent();

        libxml_use_internal_errors(true);

        $dom = new \DOMDocument;
        $dom->loadHTML($html);

        $panels = (new \DOMXPath($dom))->query('//*[@data-tab-panel]');

        $this->assertCount(5, $panels);

        foreach ($panels as $panel) {
            $this->assertSame(
                'section',
                $panel->parentNode->nodeName,
                'Panel '.$panel->getAttribute('data-tab-panel').' bersarang di dalam panel lain.',
            );
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

    public function test_admin_can_open_pernyataan_pimpinan_print_preview(): void
    {
        $pernyataan = SuratPernyataanPimpinan::query()->create([
            'struktur_instansi_id' => $this->makePimpinan()->id,
            'user_id' => $this->makeUser('dosen')->id,
            'tanggal_surat' => '2026-10-31',
            'nomor_surat' => '001/SPP/PT/2026',
        ]);

        $this->actingAs($this->makeUser('admin'))
            ->get(route('surat.pernyataan-pimpinan.cetak', $pernyataan))
            ->assertOk()
            ->assertSee('Surat Pernyataan Pimpinan Perguruan Tinggi')
            ->assertSee('Nomor: 001/SPP/PT/2026')
            // Kop surat: nama badan penyelenggara di atas nama institusi.
            ->assertSee('Perkumpulan Penyelenggara Lembaga Pendidikan')
            ->assertSee('Perguruan Tinggi PGRI Pacitan')
            // Tanggal penanda tangan: "Kota, DD Bulan YYYY".
            ->assertSee('31 Oktober 2026')
            // Nama pimpinan apa adanya sesuai database (tidak dipaksa kapital).
            ->assertSee('Dr. Ayu Lestari, M.Kom.')
            ->assertDontSee('DR. AYU LESTARI')
            ->assertSee('Dosen Tetap')
            // Pilihan margin sebelum cetak, default 1,5 cm semua sisi.
            ->assertSee('id="margin-atas"', false)
            ->assertSee('value="1.5"', false)
            ->assertSee('@page { size: A4 portrait; margin: 1.5cm; }')
            // Margin tiap halaman ditulis JS supaya mengikuti pilihan pengguna.
            ->assertSee('cetak-ukuran')
            // Blok tanda tangan tidak boleh terpotong di tengah halaman.
            ->assertSee('.cetak-tanda-tangan { break-inside: avoid; }');
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

    private function makePakta(User $dosen, string $tanggal = '2026-10-31', ?string $nomorValidasi = null): SuratPaktaIntegritas
    {
        return SuratPaktaIntegritas::query()->create([
            'user_id' => $dosen->id,
            'tanggal_pakta' => $tanggal,
            'nomor_validasi' => $nomorValidasi,
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
            // Input nomor validasi surat (dipakai pada lembar validasi).
            ->assertSee('Nomor Validasi Surat')
            ->assertSee('name="nomor_validasi"', false)
            // Tombol cetak memicu pilihan Pernyataan/Validasi (data-* untuk SweetAlert2).
            ->assertSee('aria-label="Cetak"', false)
            ->assertSee('data-cetak-pakta', false)
            ->assertSee('data-cetak-pernyataan="'.route('surat.pakta-integritas.cetak', $pakta).'"', false)
            ->assertSee('data-cetak-validasi="'.route('surat.pakta-integritas.validasi', $pakta).'"', false)
            ->assertSee('aria-label="Edit"', false)
            ->assertSee('aria-label="Hapus"', false)
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
                'nomor_validasi' => '750/STKIP PGRI PACITAN/KL/TX/2025',
            ])
            ->assertRedirect(route('surat.index').'#pakta-integritas')
            ->assertSessionHas('status', 'Pakta integritas berhasil disimpan.');

        $pakta = SuratPaktaIntegritas::query()->firstOrFail();

        $this->assertSame($dosen->id, $pakta->user_id);
        $this->assertSame('2026-10-31', $pakta->tanggal_pakta->toDateString());
        $this->assertSame('750/STKIP PGRI PACITAN/KL/TX/2025', $pakta->nomor_validasi);

        $this->actingAs($admin)
            ->put(route('surat.pakta-integritas.update', $pakta), [
                'dosen_id' => $dosen->id,
                'tanggal_pakta' => '01-11-2026',
                'nomor_validasi' => '751/STKIP PGRI PACITAN/KL/TX/2025',
            ])
            ->assertRedirect(route('surat.index').'#pakta-integritas')
            ->assertSessionHas('status', 'Pakta integritas berhasil diperbarui.');

        $pakta->refresh();

        $this->assertSame('2026-11-01', $pakta->tanggal_pakta->toDateString());
        $this->assertSame('751/STKIP PGRI PACITAN/KL/TX/2025', $pakta->nomor_validasi);
    }

    public function test_admin_can_open_pakta_integritas_print_preview(): void
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

        $this->actingAs($this->makeUser('admin'))
            ->get(route('surat.pakta-integritas.cetak', $pakta))
            ->assertOk()
            ->assertSee('Surat Pernyataan Pakta Integritas')
            ->assertSee('Dr. Ayu Lestari, M.Kom.')
            ->assertSee('Rekayasa Perangkat Lunak')
            // Tanggal penanda tangan: "Kota, DD Bulan YYYY".
            ->assertSee('Bandung, 31 Oktober 2026')
            // Pilihan margin sebelum cetak, default 1,5 cm semua sisi.
            ->assertSee('id="margin-atas"', false)
            ->assertSee('value="1.5"', false)
            ->assertSee('@page { size: A4 portrait; margin: 1.5cm; }')
            // Margin tiap halaman ditulis JS supaya mengikuti pilihan pengguna.
            ->assertSee('cetak-ukuran')
            // Baris tabel & blok tanda tangan tidak boleh terpotong di tengah halaman.
            ->assertSee('.cetak-tabel tr { break-inside: avoid; }')
            ->assertSee('.cetak-tanda-tangan { break-inside: avoid; }');
    }

    public function test_admin_can_open_pakta_integritas_validasi_preview(): void
    {
        Instansi::create(['nama_instansi' => 'Universitas Athena', 'kota' => 'Bandung']);

        $dosen = $this->makeUser('dosen');
        // Ketua instansi sebagai penanda tangan lembar validasi.
        $this->makePimpinan();
        $pakta = $this->makePakta($dosen, '2026-10-31', '750/STKIP PGRI PACITAN/KL/TX/2025');

        // Karya berupa jurnal: identitas memuat nama jurnal, web jurnal, dan URL dokumen.
        $dosen->karyaTulis()->create([
            'karya_ilmiah' => 'Jurnal Nasional Terakreditasi Kemendiktisaintek (1,2,3,4,5,6)',
            'judul' => 'Pemanfaatan Teknologi Informasi dalam Pembelajaran',
            'link_bukti' => 'https://drive.google.com/file/d/1abcdefghijklmnopqrstuvwxyz012345/view?usp=sharing',
            'nama_jurnal' => 'Jurnal Pendidikan Indonesia',
            'issn' => '2302-1234',
            'url_jurnal' => 'https://jurnal.example.org/index.php/jpi',
        ]);

        // Karya berupa prosiding: identitas memuat nama prosiding, URN prosiding, dan URL plagiasi.
        $dosen->karyaTulis()->create([
            'karya_ilmiah' => 'Prosiding Internasional Bereputasi',
            'judul' => 'The Implementation of Students Task',
            'link_bukti' => 'https://drive.google.com/file/d/2zyxwvutsrqponmlkjihgfedcba543210/view?usp=sharing',
            'nama_prosiding' => 'Proceedings of the 1st Hybrid ICELT',
            'issn' => '2962-1445',
            'url_prosiding' => 'https://prosiding.example.org/article/48718/20341',
            'url_plagiasi' => 'https://repository.example.ac.id/id/eprint/1617/THE%20INFLUENCE.pdf',
        ]);

        $this->actingAs($this->makeUser('admin'))
            ->get(route('surat.pakta-integritas.validasi', $pakta))
            ->assertOk()
            ->assertSee('Surat Pernyataan Pakta Integritas')
            ->assertSee('Pengesahan Hasil Validasi Karya Ilmiah')
            // Nomor validasi dari isian form tampil di bawah judul.
            ->assertSee('Nomor : 750/STKIP PGRI PACITAN/KL/TX/2025')
            // Blok identitas ketua & dosen.
            ->assertSee('Yang bertandatangan di bawah ini, Ketua Universitas Athena')
            ->assertSee('NIDN / NUPTK')
            ->assertSee('Status Ikatan Kerja')
            ->assertSee('Dosen Tetap')
            ->assertSee('Dr. Ayu Lestari, M.Kom.')
            // Identitas jurnal.
            ->assertSee('Nama Jurnal')
            ->assertSee('Jurnal Pendidikan Indonesia')
            ->assertSee('Web Jurnal')
            ->assertSee('https://jurnal.example.org/index.php/jpi')
            ->assertSee('URL Dokumen')
            // Identitas prosiding.
            ->assertSee('Nama Prosiding')
            ->assertSee('Proceedings of the 1st Hybrid ICELT')
            ->assertSee('URL Plagiasi')
            ->assertSee('https://repository.example.ac.id/id/eprint/1617/THE%20INFLUENCE.pdf')
            // ISSN karya tampil.
            ->assertSee('2302-1234')
            ->assertSee('2962-1445')
            // Paragraf penutup & tautan berwarna saat dicetak.
            ->assertSee('memenuhi syarat kaidah ilmiah, norma akademik, dan norma hukum')
            ->assertSee('cetak-tautan', false)
            // Tanggal penanda tangan: "Kota, DD Bulan YYYY" + tanda tangan Ketua.
            ->assertSee('Bandung, 31 Oktober 2026')
            ->assertSee('Ketua,')
            // Pilihan margin sebelum cetak, default 1,5 cm semua sisi.
            ->assertSee('id="margin-atas"', false)
            ->assertSee('value="1.5"', false)
            ->assertSee('@page { size: A4 portrait; margin: 1.5cm; }')
            ->assertSee('cetak-ukuran');
    }

    public function test_dosen_cannot_open_pakta_integritas_validasi_preview(): void
    {
        Instansi::create(['nama_instansi' => 'Universitas Athena', 'kota' => 'Bandung']);

        $pakta = $this->makePakta($this->makeUser('dosen'));

        $this->actingAs($this->makeUser('dosen', '199001012015011001'))
            ->get(route('surat.pakta-integritas.validasi', $pakta))
            ->assertForbidden();
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

        $html = $this->actingAs($this->makeUser('admin'))
            ->get(route('surat.pakta-integritas.cetak', $pakta))
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString('Karya Dipilih', $html);
        $this->assertStringNotContainsString('Karya Dilewati', $html);
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
