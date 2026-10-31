<?php

namespace Tests\Feature;

use App\Models\Instansi;
use App\Models\JabatanStruktural;
use App\Models\StrukturInstansi;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class InstansiTest extends TestCase
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

    private function payload(array $overrides = []): array
    {
        return array_merge([
            'nama_instansi' => 'Universitas Athena Nusantara',
            'nomor_sk_operasional' => 'SK-MENDIKBUD/001/2026',
            'alamat' => 'Jl. Pendidikan No. 1, Bandung',
            'kota' => 'Bandung',
            'no_telp' => '(022) 1234567',
            'website' => 'https://athena.ac.id',
        ], $overrides);
    }

    public function test_whatsapp_admin_link_converts_leading_zero_to_62(): void
    {
        $this->assertNull(Instansi::whatsappAdmin());

        $instansi = Instansi::create($this->payload(['no_telp' => '0812-3456-7890']));
        $this->assertSame('https://wa.me/6281234567890', Instansi::whatsappAdmin());

        $instansi->update(['no_telp' => '+62 812 3456 7890']);
        $this->assertSame('https://wa.me/6281234567890', Instansi::whatsappAdmin());

        $instansi->update(['no_telp' => '(022) 1234567']);
        $this->assertSame('https://wa.me/62221234567', Instansi::whatsappAdmin());
    }

    public function test_sidebar_help_link_uses_instansi_whatsapp_number_for_every_role(): void
    {
        Instansi::create($this->payload(['no_telp' => '0812-3456-7890']));

        foreach ([$this->makeAdmin(), $this->makeDosen()] as $user) {
            $this->actingAs($user)
                ->get('/dashboard')
                ->assertOk()
                ->assertSee('https://wa.me/6281234567890', false)
                ->assertSee('Hubungi Admin via WhatsApp')
                ->assertDontSee('mailto:admin@athena.test');
        }
    }

    public function test_admin_can_open_data_instansi_page(): void
    {
        $this->actingAs($this->makeAdmin())
            ->get('/instansi')
            ->assertOk()
            ->assertSee('Data Instansi')
            ->assertSee('Nama Instansi')
            ->assertSee('Nomor SK Operasional')
            ->assertSee('Alamat')
            ->assertSee('No. Telp')
            ->assertSee('Website')
            ->assertSee('Logo Instansi')
            ->assertSee('Pejabat Struktural');
    }

    public function test_admin_can_save_instansi_profile_with_logo(): void
    {
        $admin = $this->makeAdmin();
        $logo = UploadedFile::fake()->image('logo.png', 200, 200)->size(120);

        $this->actingAs($admin)
            ->put('/instansi', $this->payload(['logo' => $logo]))
            ->assertRedirect(route('instansi.profil.edit'))
            ->assertSessionHasNoErrors();

        $instansi = Instansi::firstOrFail();

        $this->assertSame('Universitas Athena Nusantara', $instansi->nama_instansi);
        $this->assertSame('SK-MENDIKBUD/001/2026', $instansi->nomor_sk_operasional);
        $this->assertSame('(022) 1234567', $instansi->no_telp);
        $this->assertStringStartsWith('data:image/png;base64,', $instansi->logo_base64);
        $this->assertNotFalse(base64_decode(substr($instansi->logo_base64, strlen('data:image/png;base64,')), true));
    }

    public function test_instansi_profile_is_stored_as_a_single_record(): void
    {
        $admin = $this->makeAdmin();

        $this->actingAs($admin)->put('/instansi', $this->payload());
        $this->actingAs($admin)->put('/instansi', $this->payload([
            'nama_instansi' => 'Institut Athena Nusantara',
        ]));

        $this->assertSame(1, Instansi::query()->count());
        $this->assertSame('Institut Athena Nusantara', Instansi::firstOrFail()->nama_instansi);
    }

    public function test_website_without_scheme_is_normalized(): void
    {
        $this->actingAs($this->makeAdmin())
            ->put('/instansi', $this->payload(['website' => 'athena.ac.id']))
            ->assertSessionHasNoErrors();

        $this->assertSame('https://athena.ac.id', Instansi::firstOrFail()->website);
    }

    public function test_logo_must_be_an_image_and_not_exceed_500_kb(): void
    {
        $admin = $this->makeAdmin();

        $this->actingAs($admin)
            ->from('/instansi')
            ->put('/instansi', $this->payload([
                'logo' => UploadedFile::fake()->create('logo.pdf', 100, 'application/pdf'),
            ]))
            ->assertSessionHasErrors('logo');

        $this->actingAs($admin)
            ->from('/instansi')
            ->put('/instansi', $this->payload([
                'logo' => UploadedFile::fake()->image('logo.png', 100, 100)->size(501),
            ]))
            ->assertSessionHasErrors('logo');
    }

    public function test_replacing_logo_updates_the_stored_base64(): void
    {
        $admin = $this->makeAdmin();

        $this->actingAs($admin)->put('/instansi', $this->payload([
            'logo' => UploadedFile::fake()->image('logo.png', 120, 120)->size(100),
        ]));

        $oldLogo = Instansi::firstOrFail()->logo_base64;

        $this->actingAs($admin)->put('/instansi', $this->payload([
            'logo' => UploadedFile::fake()->image('logo-baru.png', 80, 80)->size(80),
        ]));

        $newLogo = Instansi::firstOrFail()->logo_base64;

        $this->assertNotSame($oldLogo, $newLogo);
        $this->assertStringStartsWith('data:image/png;base64,', $newLogo);
    }

    public function test_logo_is_rendered_as_data_uri(): void
    {
        $admin = $this->makeAdmin();

        $this->actingAs($admin)->put('/instansi', $this->payload([
            'logo' => UploadedFile::fake()->image('logo.png')->size(100),
        ]));

        $logo = Instansi::firstOrFail()->logo_base64;

        $this->actingAs($admin)
            ->get('http://127.0.0.1:8000/instansi')
            ->assertOk()
            ->assertSee('src="'.$logo.'"', false);
    }

    public function test_instansi_logo_is_rendered_inline_without_external_url(): void
    {
        $admin = $this->makeAdmin();

        $this->actingAs($admin)->put('/instansi', $this->payload([
            'logo' => UploadedFile::fake()->image('logo.png', 120, 120)->size(80),
        ]));

        $this->actingAs($admin)
            ->get('/instansi')
            ->assertOk()
            ->assertSee('data:image/png;base64,', false)
            ->assertDontSee('/storage/instansi/logo', false);
    }

    public function test_dosen_cannot_access_instansi_pages(): void
    {
        $dosen = $this->makeDosen();

        $this->actingAs($dosen)->get('/instansi')->assertForbidden();
        $this->actingAs($dosen)->put('/instansi', $this->payload())->assertForbidden();
        $this->actingAs($dosen)->get('/instansi/struktur')->assertForbidden();
    }

    public function test_guest_is_redirected_to_login(): void
    {
        $this->get('/instansi')->assertRedirect('/login');
        $this->get('/instansi/struktur')->assertRedirect('/login');
    }

    public function test_struktur_instansi_displays_jabatan_and_pejabat_in_table(): void
    {
        $admin = $this->makeAdmin();
        $dosen = $this->makeDosen();

        $jabatan = JabatanStruktural::create(['nama' => 'Dekan']);

        StrukturInstansi::create([
            'jabatan_struktural_id' => $jabatan->id,
            'user_id' => $dosen->id,
        ]);

        $this->actingAs($admin)
            ->get('/instansi/struktur')
            ->assertOk()
            ->assertSee('Pejabat Struktural')
            ->assertSee('Daftar Pejabat Struktural')
            ->assertSee('Dekan')
            ->assertSee('Dr. Ayu Lestari, M.Kom.')
            ->assertSee($dosen->nidn)
            ->assertSee('Pendidikan Informatika');
    }

    public function test_admin_can_create_update_and_delete_struktur_instansi(): void
    {
        $admin = $this->makeAdmin();
        $dosen = $this->makeDosen();
        $lain = $this->makeDosen('1990011220', 'Budi Santoso, M.Pd.');
        $dekan = JabatanStruktural::create(['nama' => 'Dekan']);
        $rektor = JabatanStruktural::create(['nama' => 'Rektor']);

        $this->actingAs($admin)
            ->post('/instansi/struktur', [
                'jabatan_struktural_id' => $dekan->id,
                'user_id' => $dosen->id,
            ])
            ->assertRedirect(route('instansi.struktur.index'))
            ->assertSessionHasNoErrors();

        $struktur = StrukturInstansi::firstOrFail();
        $this->assertSame($dekan->id, $struktur->jabatan_struktural_id);
        $this->assertSame($dosen->id, $struktur->user_id);

        $this->actingAs($admin)
            ->put(route('instansi.struktur.update', $struktur), [
                'jabatan_struktural_id' => $rektor->id,
                'user_id' => $lain->id,
            ])
            ->assertRedirect(route('instansi.struktur.index'))
            ->assertSessionHasNoErrors();

        $struktur->refresh();
        $this->assertSame($rektor->id, $struktur->jabatan_struktural_id);
        $this->assertSame($lain->id, $struktur->user_id);

        $this->actingAs($admin)
            ->delete(route('instansi.struktur.destroy', $struktur))
            ->assertRedirect(route('instansi.struktur.index'));

        $this->assertDatabaseMissing('struktur_instansi', ['id' => $struktur->id]);
    }

    public function test_struktur_requires_valid_jabatan_and_active_dosen(): void
    {
        $admin = $this->makeAdmin();
        $tidakAktif = $this->makeDosen('1985041722', 'Dr. Citra Maharani, M.Si.', false);

        $this->actingAs($admin)
            ->from('/instansi/struktur/create')
            ->post('/instansi/struktur', [
                'jabatan_struktural_id' => 999,
                'user_id' => $admin->id,
            ])
            ->assertSessionHasErrors(['jabatan_struktural_id', 'user_id']);

        $jabatan = JabatanStruktural::create(['nama' => 'Dekan']);

        $this->actingAs($admin)
            ->from('/instansi/struktur/create')
            ->post('/instansi/struktur', [
                'jabatan_struktural_id' => $jabatan->id,
                'user_id' => $tidakAktif->id,
            ])
            ->assertSessionHasErrors('user_id');
    }

    public function test_pejabat_cannot_be_registered_twice_for_the_same_jabatan(): void
    {
        $admin = $this->makeAdmin();
        $dosen = $this->makeDosen();
        $jabatan = JabatanStruktural::create(['nama' => 'Dekan']);

        $payload = [
            'jabatan_struktural_id' => $jabatan->id,
            'user_id' => $dosen->id,
        ];

        $this->actingAs($admin)->post('/instansi/struktur', $payload)->assertSessionHasNoErrors();

        $this->actingAs($admin)
            ->from('/instansi/struktur/create')
            ->post('/instansi/struktur', $payload)
            ->assertSessionHasErrors('user_id');
    }

    public function test_admin_can_open_struktur_form_with_dosen_options(): void
    {
        $admin = $this->makeAdmin();
        $dosen = $this->makeDosen();
        JabatanStruktural::create(['nama' => 'Dekan']);

        $this->actingAs($admin)
            ->get('/instansi/struktur/create')
            ->assertOk()
            ->assertSee('Tambah Pejabat Struktural')
            ->assertSee('Jabatan Struktural')
            ->assertSee('Nama Pejabat')
            ->assertSee('Dekan')
            ->assertSee($dosen->name)
            ->assertSee($dosen->nidn);
    }
}
