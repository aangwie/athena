<?php

namespace Tests\Feature;

use App\Models\TimKomite;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class TimKomiteTest extends TestCase
{
    use RefreshDatabase;

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

    private function makeDosen(string $nidn = '1987654321', string $name = 'Dr. Ayu Lestari, M.Kom.', bool $active = true): User
    {
        return User::create([
            'nidn' => $nidn,
            'name' => $name,
            'email' => "{$nidn}@example.test",
            'program_studi' => 'Pendidikan Informatika',
            'jabatan_fungsional' => 'Lektor',
            'role' => 'dosen',
            'is_active' => $active,
            'password' => Hash::make('password'),
        ]);
    }

    public function test_tim_komite_page_shows_dosen_options_and_member_table(): void
    {
        $admin = $this->makeAdmin();
        $ketua = $this->makeDosen('1987654321', 'Dr. Ayu Lestari, M.Kom.');
        $this->makeDosen('1990011220', 'Budi Santoso, M.Pd.');

        TimKomite::create(['user_id' => $ketua->id, 'jabatan' => 'ketua']);

        $this->actingAs($admin)
            ->get('/instansi/tim-komite')
            ->assertOk()
            ->assertSee('Tim Komite')
            ->assertSee('Nama Dosen')
            ->assertSee('Jabatan')
            ->assertSee('Daftar Anggota Tim Komite')
            ->assertSee('data-datatable', false)
            ->assertSee('Dr. Ayu Lestari, M.Kom.')
            ->assertSee('Budi Santoso, M.Pd.')
            ->assertSee('Ketua')
            ->assertSee('Anggota')
            ->assertDontSee('Sekretaris');
    }

    public function test_member_table_follows_admin_input_order(): void
    {
        $admin = $this->makeAdmin();
        // Nama dosen sengaja tidak berurutan abjad supaya urutan tabel mudah diperiksa.
        $anggota = $this->makeDosen('1990011220', 'Budi Santoso, M.Pd.');
        $ketua = $this->makeDosen('1987654321', 'Dr. Ayu Lestari, M.Kom.');
        $anggotaLain = $this->makeDosen('1995123120', 'Ahmad Fauzi, S.Kom.');

        // Urutan input admin: anggota, ketua, lalu anggota lain.
        TimKomite::create(['user_id' => $anggota->id, 'jabatan' => 'anggota']);
        TimKomite::create(['user_id' => $ketua->id, 'jabatan' => 'ketua']);
        TimKomite::create(['user_id' => $anggotaLain->id, 'jabatan' => 'anggota']);

        $response = $this->actingAs($admin)
            ->get('/instansi/tim-komite')
            ->assertOk();

        // Tabel mengikuti urutan input (id menaik), bukan abjad nama maupun jabatan.
        $this->assertSame(
            ['Budi Santoso, M.Pd.', 'Dr. Ayu Lestari, M.Kom.', 'Ahmad Fauzi, S.Kom.'],
            $response->viewData('items')->pluck('user.name')->all(),
        );

        $html = $response->getContent();

        // Hanya periksa isi tabel: daftar opsi dosen pada formulir tetap berurut abjad.
        $this->assertSame(1, preg_match('/<table data-datatable.*?<\/table>/s', $html, $cocok));
        $tabel = $cocok[0];

        $this->assertLessThan(
            strpos($tabel, 'Dr. Ayu Lestari, M.Kom.'),
            strpos($tabel, 'Budi Santoso, M.Pd.'),
        );
        $this->assertLessThan(
            strpos($tabel, 'Ahmad Fauzi, S.Kom.'),
            strpos($tabel, 'Dr. Ayu Lestari, M.Kom.'),
        );

        // Kolom No mengikuti urutan baris tabel: 1, 2, 3.
        $this->assertSame(3, preg_match_all('/font-mono text-slate-500">(\d+)</', $tabel, $nomor));
        $this->assertSame(['1', '2', '3'], $nomor[1]);
    }

    public function test_tim_komite_menu_is_listed_on_admin_sidebar(): void
    {
        $this->actingAs($this->makeAdmin())
            ->get('/instansi/tim-komite')
            ->assertOk()
            ->assertSee(route('instansi.tim-komite.index'), false);
    }

    public function test_admin_can_add_update_and_delete_anggota_tim_komite(): void
    {
        $admin = $this->makeAdmin();
        $dosen = $this->makeDosen();
        $lain = $this->makeDosen('1990011220', 'Budi Santoso, M.Pd.');

        $this->actingAs($admin)
            ->post('/instansi/tim-komite', ['user_id' => $dosen->id, 'jabatan' => 'ketua'])
            ->assertRedirect(route('instansi.tim-komite.index'))
            ->assertSessionHasNoErrors();

        $timKomite = TimKomite::firstOrFail();
        $this->assertSame($dosen->id, $timKomite->user_id);
        $this->assertSame('ketua', $timKomite->jabatan);

        $this->actingAs($admin)
            ->put(route('instansi.tim-komite.update', $timKomite), ['user_id' => $lain->id, 'jabatan' => 'anggota'])
            ->assertRedirect(route('instansi.tim-komite.index'))
            ->assertSessionHasNoErrors();

        $timKomite->refresh();
        $this->assertSame($lain->id, $timKomite->user_id);
        $this->assertSame('anggota', $timKomite->jabatan);

        $this->actingAs($admin)
            ->delete(route('instansi.tim-komite.destroy', $timKomite))
            ->assertRedirect(route('instansi.tim-komite.index'));

        $this->assertDatabaseMissing('tim_komite', ['id' => $timKomite->id]);
    }

    public function test_admin_can_bulk_delete_selected_anggota_tim_komite(): void
    {
        $admin = $this->makeAdmin();
        $ketua = $this->makeDosen('1987654321', 'Dr. Ayu Lestari, M.Kom.');
        $anggota = $this->makeDosen('1990011220', 'Budi Santoso, M.Pd.');
        $tetap = $this->makeDosen('1995123120', 'Ahmad Fauzi, S.Kom.');

        $barisKetua = TimKomite::create(['user_id' => $ketua->id, 'jabatan' => 'ketua']);
        $barisAnggota = TimKomite::create(['user_id' => $anggota->id, 'jabatan' => 'anggota']);
        $barisTetap = TimKomite::create(['user_id' => $tetap->id, 'jabatan' => 'anggota']);

        // Halaman menyediakan formulir hapus massal dan kotak centang per baris.
        $this->actingAs($admin)
            ->get('/instansi/tim-komite')
            ->assertOk()
            ->assertSee(route('instansi.tim-komite.bulk-destroy'), false)
            ->assertSee('data-bulk-all', false)
            ->assertSee('data-bulk-item', false)
            ->assertSee('name="ids[]"', false);

        $this->actingAs($admin)
            ->delete('/instansi/tim-komite/hapus-terpilih', [
                'ids' => [$barisKetua->id, $barisAnggota->id],
            ])
            ->assertRedirect(route('instansi.tim-komite.index'))
            ->assertSessionHas('status', '2 anggota tim komite berhasil dihapus.');

        $this->assertDatabaseMissing('tim_komite', ['id' => $barisKetua->id]);
        $this->assertDatabaseMissing('tim_komite', ['id' => $barisAnggota->id]);
        // Baris yang tidak dicentang tetap tersimpan.
        $this->assertDatabaseHas('tim_komite', ['id' => $barisTetap->id]);
        $this->assertDatabaseCount('tim_komite', 1);
    }

    public function test_bulk_delete_requires_at_least_one_selected_member(): void
    {
        $admin = $this->makeAdmin();
        $dosen = $this->makeDosen();

        $timKomite = TimKomite::create(['user_id' => $dosen->id, 'jabatan' => 'anggota']);

        $this->actingAs($admin)
            ->from('/instansi/tim-komite')
            ->delete('/instansi/tim-komite/hapus-terpilih', ['ids' => []])
            ->assertRedirect('/instansi/tim-komite')
            ->assertSessionHasErrors([
                'ids' => 'Pilih minimal satu anggota tim komite yang akan dihapus.',
            ]);

        $this->assertDatabaseHas('tim_komite', ['id' => $timKomite->id]);
    }

    public function test_bulk_delete_rejects_unknown_member_id(): void
    {
        $admin = $this->makeAdmin();
        $dosen = $this->makeDosen();

        $timKomite = TimKomite::create(['user_id' => $dosen->id, 'jabatan' => 'anggota']);

        $this->actingAs($admin)
            ->from('/instansi/tim-komite')
            ->delete('/instansi/tim-komite/hapus-terpilih', [
                'ids' => [$timKomite->id, 999999],
            ])
            ->assertSessionHasErrors('ids.1');

        $this->assertDatabaseHas('tim_komite', ['id' => $timKomite->id]);
    }

    public function test_non_admin_cannot_bulk_delete_tim_komite(): void
    {
        $dosen = $this->makeDosen();

        $timKomite = TimKomite::create(['user_id' => $dosen->id, 'jabatan' => 'anggota']);

        $this->actingAs($dosen)
            ->delete('/instansi/tim-komite/hapus-terpilih', ['ids' => [$timKomite->id]])
            ->assertForbidden();

        $this->assertDatabaseHas('tim_komite', ['id' => $timKomite->id]);
    }

    public function test_tim_komite_rejects_inactive_dosen_invalid_jabatan_and_duplicate_member(): void
    {
        $admin = $this->makeAdmin();
        $dosen = $this->makeDosen();
        $nonaktif = $this->makeDosen('1990011220', 'Budi Santoso, M.Pd.', false);

        $this->actingAs($admin)
            ->from('/instansi/tim-komite')
            ->post('/instansi/tim-komite', ['user_id' => $nonaktif->id, 'jabatan' => 'ketua'])
            ->assertRedirect('/instansi/tim-komite')
            ->assertSessionHasErrors('user_id');

        $this->actingAs($admin)
            ->from('/instansi/tim-komite')
            ->post('/instansi/tim-komite', ['user_id' => $dosen->id, 'jabatan' => 'sekretaris'])
            ->assertSessionHasErrors('jabatan');

        $this->assertDatabaseCount('tim_komite', 0);

        TimKomite::create(['user_id' => $dosen->id, 'jabatan' => 'ketua']);

        $this->actingAs($admin)
            ->from('/instansi/tim-komite')
            ->post('/instansi/tim-komite', ['user_id' => $dosen->id, 'jabatan' => 'anggota'])
            ->assertSessionHasErrors('user_id');

        $this->assertDatabaseCount('tim_komite', 1);
    }

    public function test_edit_parameter_prefills_tim_komite_form(): void
    {
        $admin = $this->makeAdmin();
        $dosen = $this->makeDosen();

        $timKomite = TimKomite::create(['user_id' => $dosen->id, 'jabatan' => 'anggota']);

        $this->actingAs($admin)
            ->get(route('instansi.tim-komite.index', ['edit' => $timKomite->id]))
            ->assertOk()
            ->assertSee('Edit Anggota Tim Komite')
            ->assertSee('Simpan Perubahan')
            ->assertSee(route('instansi.tim-komite.update', $timKomite), false);
    }

    public function test_ketua_only_one_and_cannot_be_saved_twice(): void
    {
        $admin = $this->makeAdmin();
        $ketua = $this->makeDosen('1987654321', 'Dr. Ayu Lestari, M.Kom.');
        $lain = $this->makeDosen('1990011220', 'Budi Santoso, M.Pd.');

        TimKomite::create(['user_id' => $ketua->id, 'jabatan' => 'ketua']);

        $this->actingAs($admin)
            ->from('/instansi/tim-komite')
            ->post('/instansi/tim-komite', ['user_id' => $lain->id, 'jabatan' => 'ketua'])
            ->assertRedirect('/instansi/tim-komite')
            ->assertSessionHasErrors([
                'jabatan' => 'Ketua tim komite sudah ada. Hapus atau ubah jabatan ketua sebelumnya terlebih dahulu.',
            ]);

        $this->assertDatabaseCount('tim_komite', 1);

        // Opsi ketua dikunci di formulir dan statusnya dijelaskan.
        $this->actingAs($admin)
            ->get('/instansi/tim-komite')
            ->assertOk()
            ->assertSee('sudah terisi')
            ->assertSee('ubah atau hapus dulu ketua atas nama Dr. Ayu Lestari, M.Kom.');

        // Baris ketua yang sama tetap boleh disimpan ulang.
        $this->actingAs($admin)
            ->put(route('instansi.tim-komite.update', TimKomite::firstOrFail()), [
                'user_id' => $ketua->id,
                'jabatan' => 'ketua',
            ])
            ->assertSessionHasNoErrors();

        // Setelah ketua dihapus, jabatan ketua bisa dipakai lagi.
        $this->actingAs($admin)->delete(route('instansi.tim-komite.destroy', TimKomite::firstOrFail()));

        $this->actingAs($admin)
            ->post('/instansi/tim-komite', ['user_id' => $lain->id, 'jabatan' => 'ketua'])
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('tim_komite', ['user_id' => $lain->id, 'jabatan' => 'ketua']);
    }

    public function test_non_admin_cannot_manage_tim_komite(): void
    {
        $dosen = $this->makeDosen();

        $this->actingAs($dosen)->get('/instansi/tim-komite')->assertForbidden();
        $this->actingAs($dosen)
            ->post('/instansi/tim-komite', ['user_id' => $dosen->id, 'jabatan' => 'ketua'])
            ->assertForbidden();

        $this->assertDatabaseCount('tim_komite', 0);
    }

    public function test_guest_is_redirected_to_login_from_tim_komite_page(): void
    {
        $this->get('/instansi/tim-komite')->assertRedirect('/login');
    }
}
