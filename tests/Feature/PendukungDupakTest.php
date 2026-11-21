<?php

namespace Tests\Feature;

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

    private function pdf(string $nama = 'dokumen.pdf'): UploadedFile
    {
        return UploadedFile::fake()->create($nama, 200, 'application/pdf');
    }

    private function berkasPayload(array $overrides = []): array
    {
        return array_merge([
            'jenis' => PendukungDupak::JENIS_CHECK_SIMILARITY,
            'berkas' => $this->pdf('check-similarity.pdf'),
        ], $overrides);
    }

    public function test_dosen_page_shows_form_options_and_tooltip(): void
    {
        $dosen = $this->makeDosen();

        PendukungDupak::create([
            'user_id' => $dosen->id,
            'jenis' => PendukungDupak::JENIS_PROFIL_SINTA,
            'tautan' => 'https://sinta.kemdiktisaintek.go.id/authors/profile/123456',
        ]);

        $response = $this->actingAs($dosen)
            ->get('/profil/pendukung-dupak')
            ->assertOk()
            ->assertSee('Pendukung DUPAK')
            ->assertSee('Tambah Dokumen Pendukung')
            ->assertSee('Jenis Dokumen')
            ->assertSee('Pilih jenis dokumen')
            ->assertSee('data-datatable', false)
            ->assertSee('data-pendukung-grup="check_similarity"', false)
            ->assertSee('data-pendukung-grup="bukti_korespondensi"', false)
            ->assertSee('data-pendukung-grup="profil_sinta"', false)
            ->assertSee('data-pendukung-grup="artikel"', false)
            ->assertSee('name="berkas"', false)
            ->assertSee('data-pdf-input', false)
            ->assertSee('https://sinta.kemdiktisaintek.go.id/authors/profile/123456');

        foreach (PendukungDupak::JENIS as $nilai => $label) {
            $response->assertSee('<option value="'.$nilai.'"', false);
        }

        // Keterangan bukti korespondensi tampil sebagai popup saat ikon disorot.
        $response
            ->assertSee('Keterangan Bukti Korespondensi')
            ->assertSee('role="tooltip"', false)
            ->assertSee('Unggah Bukti Korespondensi dalam satu file pdf, antara lain: bukti submit artikel, bukti catatan koreksi dari para reviewer berkaitan substansi, bukti respon penulis terkait catatan dari para reviewer, bukti diterima, dan bukti publikasi.');
    }

    public function test_dosen_can_upload_check_similarity_document(): void
    {
        $dosen = $this->makeDosen();

        $this->actingAs($dosen)
            ->post('/profil/pendukung-dupak', $this->berkasPayload())
            ->assertRedirect(route('profil.pendukung-dupak.index'))
            ->assertSessionHasNoErrors();

        $item = PendukungDupak::firstOrFail();

        $this->assertSame($dosen->id, $item->user_id);
        $this->assertSame(PendukungDupak::JENIS_CHECK_SIMILARITY, $item->jenis);
        $this->assertSame('check-similarity.pdf', $item->berkas_nama);
        $this->assertNull($item->tautan);
        $this->assertMatchesRegularExpression(
            '/^check_similarity_1987654321_[A-Za-z0-9]{16}\.pdf$/',
            basename($item->berkas_path),
        );
        Storage::disk('local')->assertExists($item->berkas_path);
    }

    public function test_dosen_can_save_sinta_profile_link(): void
    {
        $dosen = $this->makeDosen();

        $this->actingAs($dosen)
            ->post('/profil/pendukung-dupak', [
                'jenis' => PendukungDupak::JENIS_PROFIL_SINTA,
                'tautan' => 'https://sinta.kemdiktisaintek.go.id/authors/profile/654321',
            ])
            ->assertRedirect(route('profil.pendukung-dupak.index'))
            ->assertSessionHasNoErrors();

        $item = PendukungDupak::firstOrFail();

        $this->assertSame(PendukungDupak::JENIS_PROFIL_SINTA, $item->jenis);
        $this->assertSame('https://sinta.kemdiktisaintek.go.id/authors/profile/654321', $item->tautan);
        $this->assertNull($item->berkas_path);
        $this->assertSame('sinta.kemdiktisaintek.go.id', $item->hostTautan());
    }

    public function test_jenis_berkas_and_tautan_are_validated(): void
    {
        $dosen = $this->makeDosen();

        // Jenis wajib dipilih dari daftar.
        $this->actingAs($dosen)
            ->from('/profil/pendukung-dupak')
            ->post('/profil/pendukung-dupak', ['jenis' => ''])
            ->assertRedirect('/profil/pendukung-dupak')
            ->assertSessionHasErrors('jenis');

        $this->actingAs($dosen)
            ->from('/profil/pendukung-dupak')
            ->post('/profil/pendukung-dupak', ['jenis' => 'lainnya'])
            ->assertSessionHasErrors('jenis');

        // Jenis berkas wajib disertai PDF.
        $this->actingAs($dosen)
            ->from('/profil/pendukung-dupak')
            ->post('/profil/pendukung-dupak', ['jenis' => PendukungDupak::JENIS_ARTIKEL])
            ->assertSessionHasErrors('berkas');

        // Berkas selain PDF ditolak.
        $this->actingAs($dosen)
            ->from('/profil/pendukung-dupak')
            ->post('/profil/pendukung-dupak', [
                'jenis' => PendukungDupak::JENIS_ARTIKEL,
                'berkas' => UploadedFile::fake()->create('artikel.docx', 100),
            ])
            ->assertSessionHasErrors('berkas');

        // Tautan SINTA wajib dan harus berupa URL.
        $this->actingAs($dosen)
            ->from('/profil/pendukung-dupak')
            ->post('/profil/pendukung-dupak', ['jenis' => PendukungDupak::JENIS_PROFIL_SINTA, 'tautan' => ''])
            ->assertSessionHasErrors('tautan');

        $this->actingAs($dosen)
            ->from('/profil/pendukung-dupak')
            ->post('/profil/pendukung-dupak', ['jenis' => PendukungDupak::JENIS_PROFIL_SINTA, 'tautan' => 'bukan-url'])
            ->assertSessionHasErrors('tautan');

        $this->assertDatabaseCount('pendukung_dupak', 0);
    }

    public function test_dosen_can_update_and_keep_or_replace_berkas(): void
    {
        $dosen = $this->makeDosen();

        $this->actingAs($dosen)
            ->post('/profil/pendukung-dupak', $this->berkasPayload())
            ->assertSessionHasNoErrors();

        $item = PendukungDupak::firstOrFail();
        $pathLama = $item->berkas_path;

        // Menyunting jenis yang sama tanpa berkas baru: berkas lama dipertahankan.
        $this->actingAs($dosen)
            ->put(route('profil.pendukung-dupak.update', $item), ['jenis' => PendukungDupak::JENIS_CHECK_SIMILARITY])
            ->assertRedirect(route('profil.pendukung-dupak.index'))
            ->assertSessionHasNoErrors();

        $this->assertSame($pathLama, $item->refresh()->berkas_path);
        Storage::disk('local')->assertExists($pathLama);

        // Unggah berkas baru: berkas lama dihapus dan diganti.
        $this->actingAs($dosen)
            ->put(route('profil.pendukung-dupak.update', $item), [
                'jenis' => PendukungDupak::JENIS_CHECK_SIMILARITY,
                'berkas' => $this->pdf('check-similarity-baru.pdf'),
            ])
            ->assertSessionHasNoErrors();

        $item->refresh();

        $this->assertNotSame($pathLama, $item->berkas_path);
        $this->assertSame('check-similarity-baru.pdf', $item->berkas_nama);
        Storage::disk('local')->assertMissing($pathLama);
        Storage::disk('local')->assertExists($item->berkas_path);

        // Pindah ke tautan SINTA: berkas lama dibersihkan.
        $pathTerakhir = $item->berkas_path;

        $this->actingAs($dosen)
            ->put(route('profil.pendukung-dupak.update', $item), [
                'jenis' => PendukungDupak::JENIS_PROFIL_SINTA,
                'tautan' => 'https://sinta.kemdiktisaintek.go.id/authors/profile/999',
            ])
            ->assertSessionHasNoErrors();

        $item->refresh();

        $this->assertSame(PendukungDupak::JENIS_PROFIL_SINTA, $item->jenis);
        $this->assertNull($item->berkas_path);
        $this->assertNull($item->berkas_nama);
        Storage::disk('local')->assertMissing($pathTerakhir);
    }

    public function test_dosen_can_delete_document_and_its_file(): void
    {
        $dosen = $this->makeDosen();

        $this->actingAs($dosen)
            ->post('/profil/pendukung-dupak', $this->berkasPayload())
            ->assertSessionHasNoErrors();

        $item = PendukungDupak::firstOrFail();
        $path = $item->berkas_path;

        $this->actingAs($dosen)
            ->delete(route('profil.pendukung-dupak.destroy', $item))
            ->assertRedirect(route('profil.pendukung-dupak.index'));

        $this->assertDatabaseMissing('pendukung_dupak', ['id' => $item->id]);
        Storage::disk('local')->assertMissing($path);
    }

    public function test_dosen_cannot_touch_or_open_other_dosen_document(): void
    {
        $pemilik = $this->makeDosen();
        $lain = $this->makeDosen('1990011220', 'Budi Santoso, M.Pd.');

        $this->actingAs($pemilik)
            ->post('/profil/pendukung-dupak', $this->berkasPayload())
            ->assertSessionHasNoErrors();

        $item = PendukungDupak::firstOrFail();

        $this->actingAs($lain)
            ->get('/profil/pendukung-dupak')
            ->assertOk()
            ->assertDontSee(route('profil.pendukung-dupak.update', $item), false);

        $this->actingAs($lain)
            ->put(route('profil.pendukung-dupak.update', $item), ['jenis' => PendukungDupak::JENIS_CHECK_SIMILARITY])
            ->assertNotFound();

        $this->actingAs($lain)
            ->delete(route('profil.pendukung-dupak.destroy', $item))
            ->assertNotFound();

        $this->actingAs($lain)
            ->get(route('pendukung-dupak.berkas', $item))
            ->assertNotFound();

        $this->assertDatabaseHas('pendukung_dupak', ['id' => $item->id]);

        // Pemilik dapat membuka berkasnya sendiri.
        $this->actingAs($pemilik)
            ->get(route('pendukung-dupak.berkas', $item))
            ->assertOk()
            ->assertHeader('content-type', 'application/pdf');
    }

    public function test_admin_must_pick_dosen_before_table_is_shown(): void
    {
        $dosen = $this->makeDosen();
        $admin = $this->makeAdmin();

        PendukungDupak::create([
            'user_id' => $dosen->id,
            'jenis' => PendukungDupak::JENIS_ARTIKEL,
            'berkas_path' => 'pendukung-dupak/1987654321/artikel.pdf',
            'berkas_nama' => 'artikel.pdf',
        ]);

        // Tanpa memilih dosen: hanya dropdown pilihan, tabel belum tampil.
        $this->actingAs($admin)
            ->get('/pendukung-dupak')
            ->assertOk()
            ->assertSee('Pilih Dosen')
            ->assertSee('Pilih dosen terlebih dahulu')
            ->assertSee('name="dosen"', false)
            ->assertSee('Dr. Ayu Lestari, M.Kom. — 1987654321 (1 dokumen)')
            ->assertDontSee('Dokumen Artikel (PDF)')
            ->assertDontSee('data-datatable', false);
    }

    public function test_admin_sees_read_only_table_of_selected_dosen(): void
    {
        $dosen = $this->makeDosen();
        $lain = $this->makeDosen('1990011220', 'Budi Santoso, M.Pd.');
        $admin = $this->makeAdmin();

        PendukungDupak::create([
            'user_id' => $dosen->id,
            'jenis' => PendukungDupak::JENIS_ARTIKEL,
            'berkas_path' => 'pendukung-dupak/1987654321/artikel.pdf',
            'berkas_nama' => 'artikel.pdf',
        ]);

        PendukungDupak::create([
            'user_id' => $lain->id,
            'jenis' => PendukungDupak::JENIS_PROFIL_SINTA,
            'tautan' => 'https://sinta.kemdiktisaintek.go.id/authors/profile/777',
        ]);

        $item = PendukungDupak::query()->where('user_id', $dosen->id)->firstOrFail();

        $this->actingAs($admin)
            ->get(route('pendukung-dupak.index', ['dosen' => $dosen->id]))
            ->assertOk()
            ->assertSee('Dokumen Pendukung')
            ->assertSee('Dr. Ayu Lestari, M.Kom.')
            ->assertSee('NIDN 1987654321')
            ->assertSee('Dokumen Artikel (PDF)')
            ->assertSee('data-datatable', false)
            // Hanya dokumen dosen terpilih yang tampil.
            ->assertDontSee('https://sinta.kemdiktisaintek.go.id/authors/profile/777')
            // Tanpa formulir input data dan tanpa aksi edit/hapus.
            ->assertDontSee('name="jenis"', false)
            ->assertDontSee('Tambah Dokumen Pendukung')
            ->assertDontSee(route('profil.pendukung-dupak.update', $item), false)
            ->assertDontSee(route('profil.pendukung-dupak.destroy', $item), false);

        // Dosen tanpa dokumen tidak ditawarkan pada pilihan.
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
            'jenis' => PendukungDupak::JENIS_ARTIKEL,
            'berkas_path' => 'pendukung-dupak/1987654321/artikel.pdf',
            'berkas_nama' => 'artikel.pdf',
        ]);

        // Dosen tanpa dokumen (atau id yang tidak ada) tidak membuka tabel.
        $this->actingAs($admin)
            ->get(route('pendukung-dupak.index', ['dosen' => 999999]))
            ->assertOk()
            ->assertSee('Pilih dosen terlebih dahulu')
            ->assertDontSee('data-datatable', false);
    }

    public function test_admin_can_open_dosen_document_but_dosen_cannot_open_admin_page(): void
    {
        $dosen = $this->makeDosen();
        $admin = $this->makeAdmin();

        $path = 'pendukung-dupak/1987654321/artikel.pdf';
        Storage::disk('local')->put($path, '%PDF-1.4');

        $item = PendukungDupak::create([
            'user_id' => $dosen->id,
            'jenis' => PendukungDupak::JENIS_ARTIKEL,
            'berkas_path' => $path,
            'berkas_nama' => 'artikel.pdf',
        ]);

        $this->actingAs($admin)
            ->get(route('pendukung-dupak.berkas', $item))
            ->assertOk()
            ->assertHeader('content-type', 'application/pdf');

        $this->actingAs($dosen)->get('/pendukung-dupak')->assertForbidden();
    }

    public function test_sidebar_menu_only_appears_for_dosen_with_jabatan_akademik(): void
    {
        // Dosen dengan jabatan akademik: menu tampil tepat di bawah Riwayat DUPAK.
        $this->actingAs($this->makeDosen())
            ->get('/profil/riwayat-karya-tulis')
            ->assertOk()
            ->assertSeeInOrder(['Riwayat DUPAK', 'Pendukung DUPAK', 'Riwayat IKD'])
            ->assertSee(route('profil.pendukung-dupak.index'), false);

        // Dosen tanpa jabatan akademik: menu tidak tampil.
        $this->actingAs($this->makeDosen('1990011220', 'Budi Santoso, M.Pd.', null))
            ->get('/profil/riwayat-karya-tulis')
            ->assertOk()
            ->assertDontSee('Pendukung DUPAK')
            ->assertDontSee(route('profil.pendukung-dupak.index'), false);

        // Admin juga melihat menu ini untuk membaca data seluruh dosen.
        $this->actingAs($this->makeAdmin())
            ->get('/pendukung-dupak')
            ->assertOk()
            ->assertSee('Pendukung DUPAK')
            ->assertSee(route('pendukung-dupak.index'), false);
    }

    public function test_form_only_enables_the_input_for_the_selected_jenis(): void
    {
        $dosen = $this->makeDosen();

        $sinta = PendukungDupak::create([
            'user_id' => $dosen->id,
            'jenis' => PendukungDupak::JENIS_PROFIL_SINTA,
            'tautan' => 'https://sinta.kemdiktisaintek.go.id/authors/profile/123456',
        ]);

        $artikel = PendukungDupak::create([
            'user_id' => $dosen->id,
            'jenis' => PendukungDupak::JENIS_ARTIKEL,
            'berkas_path' => 'pendukung-dupak/1987654321/artikel.pdf',
            'berkas_nama' => 'artikel.pdf',
        ]);

        // Belum ada jenis terpilih: semua isian dinonaktifkan.
        $html = $this->actingAs($dosen)->get('/profil/pendukung-dupak')->getContent();

        foreach ([PendukungDupak::JENIS_CHECK_SIMILARITY, PendukungDupak::JENIS_BUKTI_KORESPONDENSI, PendukungDupak::JENIS_ARTIKEL] as $jenis) {
            $this->assertMatchesRegularExpression('/id="berkas_'.$jenis.'"[^>]*disabled/', $html);
        }

        $this->assertMatchesRegularExpression('/id="tautan"[^>]*disabled/', $html);

        // Menyunting tautan SINTA: hanya isian tautan yang aktif.
        $html = $this->actingAs($dosen)
            ->get(route('profil.pendukung-dupak.index', ['edit' => $sinta->id]))
            ->getContent();

        $this->assertDoesNotMatchRegularExpression('/id="tautan"[^>]*disabled/', $html);
        $this->assertMatchesRegularExpression('/id="berkas_'.PendukungDupak::JENIS_ARTIKEL.'"[^>]*disabled/', $html);

        // Menyunting artikel: grup artikel tampil dan berkas lama tidak wajib diunggah ulang.
        $html = $this->actingAs($dosen)
            ->get(route('profil.pendukung-dupak.index', ['edit' => $artikel->id]))
            ->getContent();

        $this->assertMatchesRegularExpression('/data-pendukung-grup="artikel"(?![^>]*hidden)/', $html);
        $this->assertDoesNotMatchRegularExpression('/id="berkas_'.PendukungDupak::JENIS_ARTIKEL.'"[^>]*disabled/', $html);
        $this->assertDoesNotMatchRegularExpression('/id="berkas_'.PendukungDupak::JENIS_ARTIKEL.'"[^>]*required/', $html);
        $this->assertMatchesRegularExpression('/id="tautan"[^>]*disabled/', $html);
    }

    public function test_guest_is_redirected_to_login_from_pendukung_dupak_page(): void
    {
        $this->get('/profil/pendukung-dupak')->assertRedirect('/login');
    }
}
