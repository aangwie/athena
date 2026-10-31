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
            ->assertSeeInOrder(['Profil', 'Riwayat DUPAK', 'Riwayat IKD', 'Riwayat Karya Tulis'])
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
