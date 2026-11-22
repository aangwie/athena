<?php

namespace Tests\Feature;

use App\Models\KaryaTulis;
use App\Models\PendukungDupak;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PendukungDupakTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('local');
    }

    private function makeDosen(string $nidn = '1987654321', string $name = 'Dr. Ayu Lestari, M.Kom.', ?string $jabatan = 'Lektor'): User
    {
        return User::create([
            'nidn' => $nidn,
            'name' => $name,
            'email' => "{$nidn}@example.test",
            'program_studi' => 'Pendidikan Informatika',
            'jabatan_fungsional' => $jabatan,
            'role' => 'dosen',
            'is_active' => true,
            'password' => Hash::make('password'),
        ]);
    }

    private function makeAdmin(): User
    {
        return User::create([
            'nidn' => '0000000001',
            'name' => 'Admin Kepegawaian',
            'email' => 'admin@example.test',
            'role' => 'admin',
            'is_active' => true,
            'password' => Hash::make('password'),
        ]);
    }

    private function pdf(string $nama = 'dokumen.pdf', int $kb = 200): UploadedFile
    {
        return UploadedFile::fake()->create($nama, $kb, 'application/pdf');
    }

    /**
     * Payload lengkap formulir syarat khusus: seluruh isian dan enam berkas bukti.
     *
     * @return array<string, mixed>
     */
    private function payload(array $overrides = []): array
    {
        $payload = [
            'judul_publikasi' => 'Optimization of Nutrition Science Learning',
            'kategori_kegiatan' => KaryaTulis::KATEGORI_KEGIATAN[0],
            'jenis_publikasi' => KaryaTulis::JENIS[0],
            'nama_jurnal' => 'Jurnal EduSains : Kajian Ilmu Pendidikan 8 (1)',
            'volume' => '8',
            'nomor' => '1',
            'tahun_terbit' => '2026-11-23',
            'halaman' => '385-401',
            'issn' => '2548-78',
            'penerbit' => 'Fakultas Ilmu Ekonomi dan Bisnis Universitas',
            'doi' => 'https://doi.org/10.25217/jiv8i1.1234556',
            'tautan_jurnal' => 'https://journal.example.ac.id/index.php/jiv',
            'tautan_artikel' => 'https://sinta.kemdikbud.go.id/authors/profile/123456',
            'penulis' => 'Pertama',
            'tautan_sinta' => 'https://sinta.kemdikbud.go.id/authors/profile/123456',
        ];

        foreach (array_keys(PendukungDupak::BERKAS) as $kunci) {
            $payload[$kunci] = $this->pdf($kunci.'.pdf');
        }

        return array_merge($payload, $overrides);
    }

    public function test_dosen_page_shows_syarat_khusus_form(): void
    {
        $dosen = $this->makeDosen();

        $response = $this->actingAs($dosen)
            ->get('/profil/pendukung-dupak')
            ->assertOk()
            ->assertSee('Syarat Khusus DUPAK')
            ->assertSee('Penilaian untuk Syarat Khusus')
            ->assertSee('Simpan Syarat Khusus')
            ->assertSee('name="judul_publikasi"', false)
            ->assertSee('name="tahun_terbit"', false)
            ->assertSee('name="penulis"', false)
            ->assertSee('data-pdf-input', false)
            ->assertSee('data-max-kb="1024"', false)
            ->assertSee('data-max-kb="2048"', false)
            ->assertSee('data-max-kb="4096"', false);

        // Seluruh 21 baris lampiran syarat khusus tampil.
        foreach (PendukungDupak::BARIS as $baris) {
            $response->assertSee($baris['label']);
        }

        // Kategori kegiatan sama dengan halaman Riwayat Karya Tulis.
        foreach (KaryaTulis::KATEGORI_KEGIATAN as $kategori) {
            $response->assertSee('<option value="'.$kategori.'"', false);
        }

        // Jenis publikasi memakai daftar yang sama dengan dropdown Judul Publikasi.
        foreach (KaryaTulis::JENIS as $jenis) {
            $response->assertSee('<option value="'.$jenis.'"', false);
        }

        foreach (PendukungDupak::PENULIS as $penulis) {
            $response->assertSee('<option value="'.$penulis.'"', false);
        }

        // Keterangan bukti korespondensi tampil pada baris 19.
        $response
            ->assertSee('Unggah Bukti Korespondensi dalam satu file pdf, antara lain: bukti submit artikel, bukti catatan koreksi dari para reviewer berkaitan substansi, bukti respon penulis terkait catatan dari para reviewer, bukti diterima, dan bukti publikasi.')
            ->assertSee('Unggah Bukti')
            ->assertSee('Unggah Tautan');
    }

    public function test_dosen_page_shows_saved_entry_values(): void
    {
        $dosen = $this->makeDosen();

        $item = PendukungDupak::create([
            'user_id' => $dosen->id,
            'judul_publikasi' => 'Optimization of Nutrition Science Learning',
            'kategori_kegiatan' => KaryaTulis::KATEGORI_KEGIATAN[1],
            'jenis_publikasi' => KaryaTulis::JENIS[2],
            'penulis' => 'Utama',
            'tahun_terbit' => '2026-11-23',
            'volume' => '8',
            'berkas_artikel_path' => 'pendukung-dupak/1987654321/artikel.pdf',
            'berkas_artikel_nama' => 'artikel.pdf',
        ]);

        $this->actingAs($dosen)
            ->get('/profil/pendukung-dupak')
            ->assertOk()
            ->assertSee('Simpan Perubahan')
            ->assertSee('Optimization of Nutrition Science Learning')
            ->assertSee('<option value="'.KaryaTulis::KATEGORI_KEGIATAN[1].'" selected>', false)
            ->assertSee('<option value="'.KaryaTulis::JENIS[2].'" selected>', false)
            ->assertSee('<option value="Utama" selected>', false)
            ->assertSee('value="2026-11-23"', false)
            ->assertSee('Berkas saat ini')
            ->assertSee('Lihat Berkas')
            ->assertSee(route('pendukung-dupak.berkas', ['pendukungDupak' => $item, 'field' => 'artikel']), false);
    }

    public function test_dosen_can_save_syarat_khusus_entry(): void
    {
        $dosen = $this->makeDosen();

        $this->actingAs($dosen)
            ->post('/profil/pendukung-dupak', $this->payload())
            ->assertRedirect(route('profil.pendukung-dupak.index'))
            ->assertSessionHasNoErrors();

        $this->assertDatabaseCount('pendukung_dupak', 1);

        $item = PendukungDupak::firstOrFail();

        $this->assertSame($dosen->id, $item->user_id);
        $this->assertSame('Optimization of Nutrition Science Learning', $item->judul_publikasi);
        $this->assertSame(KaryaTulis::KATEGORI_KEGIATAN[0], $item->kategori_kegiatan);
        $this->assertSame(KaryaTulis::JENIS[0], $item->jenis_publikasi);
        $this->assertSame('Pertama', $item->penulis);
        $this->assertSame('2026-11-23', $item->tahun_terbit->format('Y-m-d'));
        $this->assertSame('https://doi.org/10.25217/jiv8i1.1234556', $item->doi);

        // Enam berkas bukti tersimpan pada disk privat dengan nama aslinya.
        foreach (array_keys(PendukungDupak::BERKAS) as $kunci) {
            $path = $item->pathBerkas($kunci);

            $this->assertNotNull($path);
            $this->assertSame($kunci.'.pdf', $item->namaBerkas($kunci));
            $this->assertMatchesRegularExpression(
                '/^'.$kunci.'_1987654321_[A-Za-z0-9]{16}\.pdf$/',
                basename($path),
            );
            Storage::disk('local')->assertExists($path);
        }
    }

    public function test_syarat_khusus_fields_are_validated(): void
    {
        $dosen = $this->makeDosen();

        // Isian wajib dan pilihan dropdown divalidasi.
        $this->actingAs($dosen)
            ->from('/profil/pendukung-dupak')
            ->post('/profil/pendukung-dupak', $this->payload([
                'judul_publikasi' => '',
                'kategori_kegiatan' => 'Kategori karangan sendiri',
                'jenis_publikasi' => 'Jenis karangan sendiri',
                'penulis' => 'Kedua',
                'tahun_terbit' => 'bukan-tanggal',
            ]))
            ->assertRedirect('/profil/pendukung-dupak')
            ->assertSessionHasErrors(['judul_publikasi', 'kategori_kegiatan', 'jenis_publikasi', 'penulis', 'tahun_terbit']);

        // Tautan harus berupa URL yang valid.
        $this->actingAs($dosen)
            ->from('/profil/pendukung-dupak')
            ->post('/profil/pendukung-dupak', $this->payload(['doi' => 'bukan-url']))
            ->assertSessionHasErrors('doi');

        // Berkas selain PDF ditolak.
        $this->actingAs($dosen)
            ->from('/profil/pendukung-dupak')
            ->post('/profil/pendukung-dupak', $this->payload([
                'artikel' => UploadedFile::fake()->create('artikel.docx', 100),
            ]))
            ->assertSessionHasErrors('artikel');

        // Ukuran berkas mengikuti batas tiap baris (pakta integritas maksimal 1 MB).
        $this->actingAs($dosen)
            ->from('/profil/pendukung-dupak')
            ->post('/profil/pendukung-dupak', $this->payload([
                'pakta_integritas' => $this->pdf('pakta.pdf', 2048),
            ]))
            ->assertSessionHasErrors('pakta_integritas');

        // Berkas wajib diunggah saat entri belum pernah disimpan.
        $this->actingAs($dosen)
            ->from('/profil/pendukung-dupak')
            ->post('/profil/pendukung-dupak', $this->payload([
                'korespondensi' => null,
            ]))
            ->assertSessionHasErrors('korespondensi');

        $this->assertDatabaseCount('pendukung_dupak', 0);
    }

    public function test_dosen_can_resubmit_and_keep_or_replace_berkas(): void
    {
        $dosen = $this->makeDosen();

        $this->actingAs($dosen)
            ->post('/profil/pendukung-dupak', $this->payload())
            ->assertSessionHasNoErrors();

        $item = PendukungDupak::firstOrFail();
        $pathLama = $item->pathBerkas('artikel');

        // Kirim ulang tanpa berkas baru: berkas lama dipertahankan, isian diperbarui.
        $tanpaBerkas = $this->payload(['volume' => '9']);

        foreach (array_keys(PendukungDupak::BERKAS) as $kunci) {
            $tanpaBerkas[$kunci] = null;
        }

        $this->actingAs($dosen)
            ->post('/profil/pendukung-dupak', $tanpaBerkas)
            ->assertRedirect(route('profil.pendukung-dupak.index'))
            ->assertSessionHasNoErrors();

        $this->assertDatabaseCount('pendukung_dupak', 1);
        $this->assertSame($pathLama, $item->refresh()->pathBerkas('artikel'));
        $this->assertSame('9', $item->volume);
        Storage::disk('local')->assertExists($pathLama);

        // Berkas baru menggantikan berkas lama.
        $this->actingAs($dosen)
            ->post('/profil/pendukung-dupak', $this->payload([
                'artikel' => $this->pdf('artikel-baru.pdf'),
            ]))
            ->assertSessionHasNoErrors();

        $item->refresh();

        $this->assertNotSame($pathLama, $item->pathBerkas('artikel'));
        $this->assertSame('artikel-baru.pdf', $item->namaBerkas('artikel'));
        Storage::disk('local')->assertMissing($pathLama);
        Storage::disk('local')->assertExists($item->pathBerkas('artikel'));
    }

    public function test_dosen_cannot_open_other_dosen_berkas(): void
    {
        $dosen = $this->makeDosen();
        $lain = $this->makeDosen('1990011220', 'Budi Santoso, M.Pd.');
        $admin = $this->makeAdmin();

        $this->actingAs($dosen)
            ->post('/profil/pendukung-dupak', $this->payload())
            ->assertSessionHasNoErrors();

        $item = PendukungDupak::firstOrFail();

        // Pemilik dan admin boleh membuka berkas; dosen lain tidak.
        $this->actingAs($dosen)
            ->get(route('pendukung-dupak.berkas', ['pendukungDupak' => $item, 'field' => 'artikel']))
            ->assertOk()
            ->assertHeader('content-type', 'application/pdf');

        $this->actingAs($admin)
            ->get(route('pendukung-dupak.berkas', ['pendukungDupak' => $item, 'field' => 'artikel']))
            ->assertOk();

        $this->actingAs($lain)
            ->get(route('pendukung-dupak.berkas', ['pendukungDupak' => $item, 'field' => 'artikel']))
            ->assertNotFound();

        // Kunci berkas di luar daftar syarat khusus ditolak.
        $this->actingAs($dosen)
            ->get(route('pendukung-dupak.berkas', ['pendukungDupak' => $item, 'field' => 'lainnya']))
            ->assertNotFound();

        $this->actingAs($dosen)->get('/pendukung-dupak')->assertForbidden();
    }

    public function test_admin_sees_read_only_syarat_khusus_of_selected_dosen(): void
    {
        $dosen = $this->makeDosen();
        $lain = $this->makeDosen('1990011220', 'Budi Santoso, M.Pd.');
        $admin = $this->makeAdmin();

        $item = PendukungDupak::create([
            'user_id' => $dosen->id,
            'judul_publikasi' => 'Optimization of Nutrition Science Learning',
            'kategori_kegiatan' => KaryaTulis::KATEGORI_KEGIATAN[0],
            'jenis_publikasi' => KaryaTulis::JENIS[0],
            'penulis' => 'Utama',
            'tahun_terbit' => '2026-11-23',
            'tautan_sinta' => 'https://sinta.kemdikbud.go.id/authors/profile/123456',
            'berkas_artikel_path' => 'pendukung-dupak/1987654321/artikel.pdf',
            'berkas_artikel_nama' => 'artikel.pdf',
        ]);

        // Tanpa parameter dosen: hanya pemilihan dosen yang tampil.
        $this->actingAs($admin)
            ->get('/pendukung-dupak')
            ->assertOk()
            ->assertSee('Pilih dosen terlebih dahulu')
            ->assertSee('name="dosen"', false)
            ->assertSee('Dr. Ayu Lestari, M.Kom. — 1987654321 (sudah mengisi)')
            ->assertDontSee('Budi Santoso, M.Pd.')
            ->assertDontSee('name="judul_publikasi"', false);

        // Dosen terpilih: hasil isian tampil tanpa formulir.
        $this->actingAs($admin)
            ->get(route('pendukung-dupak.index', ['dosen' => $dosen->id]))
            ->assertOk()
            ->assertSee('Penilaian untuk Syarat Khusus')
            ->assertSee('Optimization of Nutrition Science Learning')
            ->assertSee(KaryaTulis::KATEGORI_KEGIATAN[0])
            ->assertSee(KaryaTulis::JENIS[0])
            ->assertSee('Utama')
            ->assertSee('23/11/2026')
            ->assertSee('Lihat Berkas')
            ->assertSee(route('pendukung-dupak.berkas', ['pendukungDupak' => $item, 'field' => 'artikel']), false)
            ->assertDontSee('name="judul_publikasi"', false)
            ->assertDontSee('data-pdf-input', false)
            ->assertDontSee('Simpan Syarat Khusus')
            ->assertDontSee('Simpan Perubahan');

        // Dosen yang belum mengirim syarat khusus tidak ditawarkan.
        $this->makeDosen('1992022030', 'Citra Dewi, S.Kom.');

        $this->actingAs($admin)
            ->get('/pendukung-dupak')
            ->assertOk()
            ->assertDontSee('Citra Dewi, S.Kom.');
    }

    public function test_admin_ignores_unknown_dosen_parameter(): void
    {
        $admin = $this->makeAdmin();
        $dosen = $this->makeDosen();

        PendukungDupak::create([
            'user_id' => $dosen->id,
            'judul_publikasi' => 'Optimization of Nutrition Science Learning',
        ]);

        $this->actingAs($admin)
            ->get(route('pendukung-dupak.index', ['dosen' => 999999]))
            ->assertOk()
            ->assertSee('Pilih dosen terlebih dahulu')
            ->assertDontSee('Optimization of Nutrition Science Learning');
    }

    public function test_sidebar_menu_only_appears_for_dosen_with_jabatan_akademik(): void
    {
        // Dosen dengan jabatan akademik: menu tampil tepat di bawah Riwayat DUPAK.
        $this->actingAs($this->makeDosen())
            ->get('/profil/riwayat-karya-tulis')
            ->assertOk()
            ->assertSeeInOrder(['Riwayat DUPAK', 'Syarat Khusus DUPAK', 'Riwayat IKD'])
            ->assertSee(route('profil.pendukung-dupak.index'), false);

        // Dosen tanpa jabatan akademik: menu tidak tampil.
        $this->actingAs($this->makeDosen('1990011220', 'Budi Santoso, M.Pd.', null))
            ->get('/profil/riwayat-karya-tulis')
            ->assertOk()
            ->assertDontSee('Syarat Khusus DUPAK')
            ->assertDontSee(route('profil.pendukung-dupak.index'), false);

        // Admin juga melihat menu ini untuk membaca hasil isian seluruh dosen, tepat di bawah Riwayat DUPAK.
        $this->actingAs($this->makeAdmin())
            ->get('/pendukung-dupak')
            ->assertOk()
            ->assertSeeInOrder(['Riwayat DUPAK', 'Syarat Khusus DUPAK', 'Ajuan PAK'])
            ->assertSee(route('pendukung-dupak.index'), false);
    }

    public function test_guest_is_redirected_to_login_from_syarat_khusus_page(): void
    {
        $this->get('/profil/pendukung-dupak')->assertRedirect('/login');
    }
}
