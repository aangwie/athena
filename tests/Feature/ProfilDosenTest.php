<?php

namespace Tests\Feature;

use App\Models\ProfilDosen;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class ProfilDosenTest extends TestCase
{
    use RefreshDatabase;

    private function makeDosen(array $attributes = []): User
    {
        return User::create(array_merge([
            'nidn' => '1987654321',
            'name' => 'Dr. Ayu Lestari, M.Kom.',
            'email' => 'ayu@example.test',
            'program_studi' => 'Pendidikan Informatika',
            'jabatan_fungsional' => 'Lektor',
            'role' => 'dosen',
            'is_active' => true,
            'password' => Hash::make('password'),
        ], $attributes));
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

    private function payload(array $overrides = []): array
    {
        return array_merge([
            'nama' => 'Dr. Ayu Lestari, M.Kom.',
            'tempat_lahir' => 'Bandung',
            'tanggal_lahir' => '1987-05-14',
            'alamat' => 'Jl. Cihampelas No. 112, Bandung',
            'no_hp' => '0812-3456-7890',
            'status_pernikahan' => 'menikah',
            'nama_pasangan' => 'Rizky Pratama, S.T.',
            'pekerjaan_pasangan' => 'asn',
            'nip_pasangan' => '198503122010011002',
            'tempat_lahir_pasangan' => 'Jakarta',
            'tanggal_lahir_pasangan' => '1985-03-12',
            'tanggal_nikah' => '2012-06-30',
            'children' => [
                [
                    'nama' => 'Nadia Lestari',
                    'tempat_lahir' => 'Bandung',
                    'tanggal_lahir' => '2014-02-11',
                    'jenis_kelamin' => 'P',
                    'pekerjaan' => 'Pelajar',
                ],
                [
                    'nama' => 'Fajar Pratama',
                    'tempat_lahir' => 'Bandung',
                    'tanggal_lahir' => '2018-09-05',
                    'jenis_kelamin' => 'L',
                    'pekerjaan' => 'Pelajar',
                ],
            ],
        ], $overrides);
    }

    public function test_dosen_can_open_data_pribadi_page(): void
    {
        $dosen = $this->makeDosen();

        $this->actingAs($dosen)
            ->get('/profil/data-pribadi')
            ->assertOk()
            ->assertSee('Data Pribadi')
            ->assertSee('Data Pasangan')
            ->assertSee('Tambah Anak');
    }

    public function test_dosen_can_save_married_profile_with_multiple_children(): void
    {
        $dosen = $this->makeDosen();

        $this->actingAs($dosen)
            ->put('/profil/data-pribadi', $this->payload())
            ->assertRedirect(route('profil.show'));

        $this->assertDatabaseHas('users', [
            'id' => $dosen->id,
            'name' => 'Dr. Ayu Lestari, M.Kom.',
        ]);

        $this->assertDatabaseHas('profil_dosen', [
            'user_id' => $dosen->id,
            'status_pernikahan' => 'menikah',
            'nama_pasangan' => 'Rizky Pratama, S.T.',
            'pekerjaan_pasangan' => 'asn',
            'nip_pasangan' => '198503122010011002',
        ]);

        $this->assertDatabaseCount('anak_dosen', 2);
        $this->assertDatabaseHas('anak_dosen', ['nama' => 'Nadia Lestari', 'jenis_kelamin' => 'P']);
        $this->assertDatabaseHas('anak_dosen', ['nama' => 'Fajar Pratama', 'jenis_kelamin' => 'L']);
    }

    public function test_dosen_can_save_agama_on_data_pribadi(): void
    {
        $dosen = $this->makeDosen();

        $this->actingAs($dosen)
            ->put('/profil/data-pribadi', $this->payload(['agama' => 'islam']))
            ->assertRedirect(route('profil.show'));

        $this->assertDatabaseHas('profil_dosen', [
            'user_id' => $dosen->id,
            'agama' => 'islam',
        ]);
    }

    public function test_agama_must_be_one_of_the_available_options(): void
    {
        $this->actingAs($this->makeDosen())
            ->put('/profil/data-pribadi', $this->payload(['agama' => 'lainnya']))
            ->assertSessionHasErrors('agama');
    }

    public function test_profile_switch_to_lajang_clears_spouse_and_children(): void
    {
        $dosen = $this->makeDosen();

        $this->actingAs($dosen)->put('/profil/data-pribadi', $this->payload());

        $this->actingAs($dosen)
            ->put('/profil/data-pribadi', $this->payload([
                'status_pernikahan' => 'lajang',
                'nama_pasangan' => 'Masih ada',
                'pekerjaan_pasangan' => 'asn',
                'nip_pasangan' => '198503122010011002',
                'children' => [
                    [
                        'nama' => 'Anak Lama',
                        'tempat_lahir' => 'Bandung',
                        'tanggal_lahir' => '2014-02-11',
                        'jenis_kelamin' => 'P',
                    ],
                ],
            ]))
            ->assertRedirect(route('profil.show'));

        $this->assertDatabaseHas('profil_dosen', [
            'user_id' => $dosen->id,
            'status_pernikahan' => 'lajang',
            'nama_pasangan' => null,
            'nip_pasangan' => null,
        ]);

        $this->assertDatabaseCount('anak_dosen', 0);
    }

    public function test_spouse_name_is_required_when_married(): void
    {
        $dosen = $this->makeDosen();

        $this->actingAs($dosen)
            ->from('/profil/data-pribadi')
            ->put('/profil/data-pribadi', $this->payload(['nama_pasangan' => '', 'children' => []]))
            ->assertRedirect('/profil/data-pribadi')
            ->assertSessionHasErrors('nama_pasangan');
    }

    public function test_nip_is_required_when_spouse_works_as_asn(): void
    {
        $dosen = $this->makeDosen();

        $this->actingAs($dosen)
            ->from('/profil/data-pribadi')
            ->put('/profil/data-pribadi', $this->payload(['nip_pasangan' => '', 'children' => []]))
            ->assertRedirect('/profil/data-pribadi')
            ->assertSessionHasErrors('nip_pasangan');
    }

    public function test_children_fields_are_validated(): void
    {
        $dosen = $this->makeDosen();

        $this->actingAs($dosen)
            ->from('/profil/data-pribadi')
            ->put('/profil/data-pribadi', $this->payload([
                'children' => [
                    ['nama' => 'Anak Baru', 'tempat_lahir' => '', 'tanggal_lahir' => '', 'jenis_kelamin' => ''],
                ],
            ]))
            ->assertRedirect('/profil/data-pribadi')
            ->assertSessionHasErrors([
                'children.0.tempat_lahir',
                'children.0.tanggal_lahir',
                'children.0.jenis_kelamin',
            ]);
    }

    public function test_portfolio_displays_personal_and_family_data(): void
    {
        $dosen = $this->makeDosen();

        $profil = ProfilDosen::create([
            'user_id' => $dosen->id,
            'tempat_lahir' => 'Bandung',
            'tanggal_lahir' => '1987-05-14',
            'alamat' => 'Jl. Cihampelas No. 112, Bandung',
            'no_hp' => '0812-3456-7890',
            'status_pernikahan' => 'menikah',
            'nama_pasangan' => 'Rizky Pratama, S.T.',
            'pekerjaan_pasangan' => 'asn',
            'nip_pasangan' => '198503122010011002',
            'tempat_lahir_pasangan' => 'Jakarta',
            'tanggal_lahir_pasangan' => '1985-03-12',
            'tanggal_nikah' => '2012-06-30',
        ]);

        $profil->anak()->create([
            'nama' => 'Nadia Lestari',
            'tempat_lahir' => 'Bandung',
            'tanggal_lahir' => '2014-02-11',
            'jenis_kelamin' => 'P',
            'pekerjaan' => 'Pelajar',
        ]);

        $this->actingAs($dosen)
            ->get('/profil')
            ->assertOk()
            ->assertSee('Portofolio Dosen')
            ->assertSee('Jl. Cihampelas No. 112, Bandung')
            ->assertSee('Rizky Pratama, S.T.')
            ->assertSee('Nadia Lestari')
            ->assertSee('NIP 198503122010011002');
    }

    public function test_riwayat_pendidikan_page_is_available_for_dosen(): void
    {
        $dosen = $this->makeDosen();

        $this->actingAs($dosen)
            ->get('/profil/riwayat-pendidikan')
            ->assertOk()
            ->assertSee('Riwayat Pendidikan')
            ->assertSee('Tambah Riwayat')
            ->assertSee('Belum ada riwayat pendidikan');
    }

    public function test_admin_cannot_access_profile_pages(): void
    {
        $admin = $this->makeAdmin();

        $this->actingAs($admin)->get('/profil')->assertForbidden();
        $this->actingAs($admin)->get('/profil/data-pribadi')->assertForbidden();
        $this->actingAs($admin)->get('/profil/riwayat-pendidikan')->assertForbidden();
    }
}
