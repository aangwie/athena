<?php

namespace Tests\Feature;

use App\Models\KaryaTulis;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class KaryaTulisTest extends TestCase
{
    use RefreshDatabase;

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
            'karya_ilmiah' => 'Jurnal Nasional Terakreditasi Kemendiktisaintek (1,2,3,4,5,6)',
            'judul' => 'Penerapan Augmented Reality pada Pembelajaran Pemrograman Dasar',
            'link_bukti' => 'https://drive.google.com/file/d/1AbCdEfGhIjKlMnOpQrStUvWxYz/view?usp=sharing',
            'nama_jurnal' => 'Jurnal Teknologi Pendidikan',
            'url_jurnal' => 'https://jurnal.example.org/index.php/jtp',
        ], $overrides);
    }

    public function test_dosen_page_shows_form_and_karya_tulis_table(): void
    {
        $dosen = $this->makeDosen();

        KaryaTulis::create($this->payload(['user_id' => $dosen->id]));

        $this->actingAs($dosen)
            ->get('/profil/riwayat-karya-tulis')
            ->assertOk()
            ->assertSee('Riwayat Karya Tulis')
            ->assertSee('Tambah Karya Tulis')
            ->assertSee('Karya Ilmiah')
            ->assertSee('Link Bukti Google Drive')
            ->assertSee('data-datatable', false)
            ->assertSee('data-link-input', false)
            ->assertSee('Penerapan Augmented Reality pada Pembelajaran Pemrograman Dasar')
            ->assertSee('https://drive.google.com/file/d/1AbCdEfGhIjKlMnOpQrStUvWxYz/preview', false);
    }

    public function test_dosen_can_add_update_and_delete_karya_tulis(): void
    {
        $dosen = $this->makeDosen();

        $this->actingAs($dosen)
            ->post('/profil/riwayat-karya-tulis', $this->payload())
            ->assertRedirect(route('profil.karya-tulis.index'))
            ->assertSessionHasNoErrors();

        $karya = KaryaTulis::firstOrFail();
        $this->assertSame($dosen->id, $karya->user_id);

        $this->actingAs($dosen)
            ->put(route('profil.karya-tulis.update', $karya), $this->payload([
                'judul' => 'Judul Setelah Diperbarui',
                'link_bukti' => 'https://drive.google.com/file/d/9ZzYyXxWwVvUuTtSsRrQqPpOoNn/preview',
            ]))
            ->assertRedirect(route('profil.karya-tulis.index'))
            ->assertSessionHasNoErrors();

        $this->assertSame('Judul Setelah Diperbarui', $karya->refresh()->judul);

        $this->actingAs($dosen)
            ->delete(route('profil.karya-tulis.destroy', $karya))
            ->assertRedirect(route('profil.karya-tulis.index'));

        $this->assertDatabaseMissing('karya_tulis', ['id' => $karya->id]);
    }

    public function test_karya_tulis_requires_karya_judul_and_valid_url(): void
    {
        $dosen = $this->makeDosen();

        $this->actingAs($dosen)
            ->from('/profil/riwayat-karya-tulis')
            ->post('/profil/riwayat-karya-tulis', ['karya_ilmiah' => '', 'judul' => '', 'link_bukti' => 'bukan-url'])
            ->assertRedirect('/profil/riwayat-karya-tulis')
            ->assertSessionHasErrors(['karya_ilmiah', 'judul', 'link_bukti']);

        $this->assertDatabaseCount('karya_tulis', 0);
    }

    public function test_karya_ilmiah_is_chosen_from_select_options(): void
    {
        $dosen = $this->makeDosen();

        $response = $this->actingAs($dosen)
            ->get('/profil/riwayat-karya-tulis')
            ->assertOk()
            ->assertSee('Pilih jenis karya ilmiah');

        foreach (KaryaTulis::JENIS as $jenis) {
            $response->assertSee('<option value="'.$jenis.'"', false);
        }

        // Teks bebas di luar daftar pilihan ditolak.
        $this->actingAs($dosen)
            ->from('/profil/riwayat-karya-tulis')
            ->post('/profil/riwayat-karya-tulis', $this->payload(['karya_ilmiah' => 'Artikel Jurnal']))
            ->assertSessionHasErrors('karya_ilmiah');

        $this->assertDatabaseCount('karya_tulis', 0);
    }

    public function test_jurnal_fields_are_required_and_saved_for_jurnal_types(): void
    {
        $dosen = $this->makeDosen();

        // Bidang jurnal (nama jurnal, alamat web, & ISSN opsional) tampil pada formulir.
        $this->actingAs($dosen)
            ->get('/profil/riwayat-karya-tulis')
            ->assertOk()
            ->assertSee('Nama Jurnal')
            ->assertSee('Alamat Web Jurnal')
            ->assertSee('id="issn_jurnal"', false)
            // Grup bidang toggle (nama atribut grup beda dari select agar tidak bentrok).
            ->assertSee('data-grup-jurnal', false)
            ->assertSee('data-grup-prosiding', false)
            ->assertSee('data-karya-jurnal', false)
            ->assertSee('data-karya-prosiding', false)
            ->assertSee('data-karya-jurnal-input', false);

        // Ketiga jenis jurnal menyertakan nama jurnal & alamat web jurnal.
        foreach (KaryaTulis::JENIS_JURNAL as $jenis) {
            $this->actingAs($dosen)
                ->post('/profil/riwayat-karya-tulis', $this->payload(['karya_ilmiah' => $jenis]))
                ->assertSessionHasNoErrors();
        }

        $this->assertSame(3, KaryaTulis::query()->count());

        // Menghilangkan nama jurnal / alamat web jurnal ditolak untuk jenis jurnal.
        $this->actingAs($dosen)
            ->from('/profil/riwayat-karya-tulis')
            ->post('/profil/riwayat-karya-tulis', $this->payload([
                'nama_jurnal' => '',
                'url_jurnal' => 'bukan-url',
            ]))
            ->assertSessionHasErrors(['nama_jurnal', 'url_jurnal']);
    }

    public function test_prosiding_fields_are_required_and_saved_for_prosiding_type(): void
    {
        $dosen = $this->makeDosen();

        $this->actingAs($dosen)
            ->get('/profil/riwayat-karya-tulis')
            ->assertOk()
            ->assertSee('Nama Prosiding')
            ->assertSee('ISSN')
            ->assertSee('URL Prosiding')
            ->assertSee('URL Plagiasi');

        $this->actingAs($dosen)
            ->post('/profil/riwayat-karya-tulis', $this->payload([
                'karya_ilmiah' => KaryaTulis::JENIS_PROSIDING,
                'nama_prosiding' => 'Prosiding Seminar Nasional Informatika',
                'issn' => '2686-1234',
                'url_prosiding' => 'https://prosiding.example.org/sni',
                'url_plagiasi' => 'https://drive.google.com/file/d/PlagiasiId/view?usp=sharing',
            ]))
            ->assertRedirect(route('profil.karya-tulis.index'))
            ->assertSessionHasNoErrors();

        $karya = KaryaTulis::firstOrFail();
        $this->assertTrue($karya->isProsiding());
        $this->assertFalse($karya->isJurnal());
        $this->assertSame('Prosiding Seminar Nasional Informatika', $karya->nama_prosiding);
        $this->assertSame('2686-1234', $karya->issn);
        $this->assertSame('https://prosiding.example.org/sni', $karya->url_prosiding);
        // Bidang jurnal dikosongkan karena jenisnya prosiding.
        $this->assertNull($karya->nama_jurnal);
        $this->assertNull($karya->url_jurnal);

        // Bidang prosiding wajib saat jenis prosiding dipilih — kecuali ISSN (opsional).
        $this->actingAs($dosen)
            ->from('/profil/riwayat-karya-tulis')
            ->post('/profil/riwayat-karya-tulis', $this->payload([
                'karya_ilmiah' => KaryaTulis::JENIS_PROSIDING,
                'nama_prosiding' => '',
                'issn' => '',
                'url_prosiding' => '',
                'url_plagiasi' => '',
            ]))
            ->assertSessionHasErrors(['nama_prosiding', 'url_prosiding', 'url_plagiasi'])
            ->assertSessionDoesntHaveErrors('issn');
    }

    public function test_issn_is_optional_for_jurnal_and_prosiding(): void
    {
        $dosen = $this->makeDosen();

        // Jurnal: ISSN opsional (dikosongkan tetap tersimpan).
        $this->actingAs($dosen)
            ->post('/profil/riwayat-karya-tulis', $this->payload(['issn' => '']))
            ->assertSessionHasNoErrors();

        $jurnal = KaryaTulis::firstOrFail();
        $this->assertTrue($jurnal->isJurnal());
        $this->assertNull($jurnal->issn);

        // Jurnal: ISSN boleh diisi dan tersimpan.
        $this->actingAs($dosen)
            ->put(route('profil.karya-tulis.update', $jurnal), $this->payload(['issn' => '2302-9999']))
            ->assertSessionHasNoErrors();

        $this->assertSame('2302-9999', $jurnal->refresh()->issn);

        // Bidang ISSN tampil di grup jurnal maupun prosiding.
        $this->actingAs($dosen)
            ->get('/profil/riwayat-karya-tulis')
            ->assertOk()
            ->assertSee('id="issn_jurnal"', false)
            ->assertSee('id="issn"', false);

        // Prosiding: ISSN opsional (dikosongkan tetap tersimpan dengan bidang prosiding lain).
        $this->actingAs($dosen)
            ->post('/profil/riwayat-karya-tulis', $this->payload([
                'karya_ilmiah' => KaryaTulis::JENIS_PROSIDING,
                'nama_prosiding' => 'Prosiding Nasional',
                'issn' => '',
                'url_prosiding' => 'https://prosiding.example.org/x',
                'url_plagiasi' => 'https://drive.google.com/file/d/PlagId/view?usp=sharing',
            ]))
            ->assertSessionHasNoErrors();

        $prosiding = KaryaTulis::query()->where('karya_ilmiah', KaryaTulis::JENIS_PROSIDING)->firstOrFail();
        $this->assertNull($prosiding->issn);
    }

    public function test_dosen_cannot_touch_other_dosen_karya_tulis(): void
    {
        $pemilik = $this->makeDosen();
        $lain = $this->makeDosen('1990011220', 'Budi Santoso, M.Pd.');

        $karya = KaryaTulis::create($this->payload(['user_id' => $pemilik->id]));

        $this->actingAs($lain)->get(route('profil.karya-tulis.index', ['edit' => $karya->id]))
            ->assertOk()
            ->assertDontSee(route('profil.karya-tulis.update', $karya), false);

        $this->actingAs($lain)->put(route('profil.karya-tulis.update', $karya), $this->payload())->assertNotFound();
        $this->actingAs($lain)->delete(route('profil.karya-tulis.destroy', $karya))->assertNotFound();

        $this->assertDatabaseHas('karya_tulis', ['id' => $karya->id, 'judul' => $this->payload()['judul']]);
    }

    public function test_dosen_sidebar_shows_profil_riwayat_dupak_and_karya_tulis(): void
    {
        $this->actingAs($this->makeDosen())
            ->get('/profil/riwayat-karya-tulis')
            ->assertOk()
            ->assertSeeInOrder(['Profil', 'Riwayat DUPAK', 'Riwayat Karya Tulis'])
            ->assertDontSee('Riwayat IKD')
            ->assertSee(route('profil.karya-tulis.index'), false);
    }

    public function test_non_dosen_cannot_open_karya_tulis_page(): void
    {
        $admin = User::create([
            'nidn' => '0000000001',
            'name' => 'Admin Kepegawaian',
            'email' => 'admin@example.test',
            'role' => 'admin',
            'is_active' => true,
            'password' => Hash::make('password'),
        ]);

        $this->actingAs($admin)->get('/profil/riwayat-karya-tulis')->assertForbidden();
    }

    public function test_guest_is_redirected_to_login_from_karya_tulis_page(): void
    {
        $this->get('/profil/riwayat-karya-tulis')->assertRedirect('/login');
    }
}
