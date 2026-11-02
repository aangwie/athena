<?php

namespace Tests\Feature;

use App\Models\Senat;
use App\Models\SuratBaki;
use App\Models\SuratBapSenat;
use App\Models\SyaratKhusus;
use App\Models\TimKomite;
use App\Models\User;
use App\Support\Excel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class DataDosenTest extends TestCase
{
    use RefreshDatabase;

    private function makeAdmin(): User
    {
        return User::create([
            'nidn' => '0000000001',
            'name' => 'Admin Kepegawaian',
            'email' => 'admin@example.test',
            'role' => 'admin',
            'roles' => ['admin'],
            'is_active' => true,
            'password' => Hash::make('password'),
        ]);
    }

    private function makeDosen(string $nidn, string $name): User
    {
        return User::create([
            'nidn' => $nidn,
            'name' => $name,
            'email' => "{$nidn}@example.test",
            'role' => 'dosen',
            'roles' => ['dosen'],
            'is_active' => true,
            'password' => Hash::make('password'),
        ]);
    }

    private function payload(array $overrides = []): array
    {
        return array_merge([
            'name' => 'Dr. Citra Maharani, M.Si.',
            'nidn' => '1985041722',
            'memiliki_jabatan' => 'ya',
            'jabatan_fungsional' => 'Lektor Kepala',
            'pangkat' => 'Penata Tingkat I',
            'roles' => ['dosen', 'tim_pak'],
            'password' => 'password',
            'password_confirmation' => 'password',
        ], $overrides);
    }

    public function test_admin_can_create_user_with_multiple_roles(): void
    {
        $this->actingAs($this->makeAdmin())
            ->post('/data-dosen', $this->payload())
            ->assertRedirect(route('data-dosen.index'));

        $user = User::query()->where('nidn', '1985041722')->firstOrFail();

        $this->assertSame(['dosen', 'tim_pak'], $user->roleList());
        $this->assertSame('dosen', $user->role);
        $this->assertTrue($user->hasRole('dosen'));
        $this->assertTrue($user->hasRole('tim_pak'));
        $this->assertFalse($user->hasRole('admin'));
        $this->assertSame('dosen', $user->activeRole());
    }

    public function test_pangkat_is_required_when_dosen_has_jabatan_akademik(): void
    {
        $this->actingAs($this->makeAdmin())
            ->post('/data-dosen', $this->payload(['pangkat' => '']))
            ->assertSessionHasErrors('pangkat');

        $this->assertDatabaseMissing('users', ['nidn' => '1985041722']);
    }

    public function test_admin_can_create_dosen_without_jabatan_akademik(): void
    {
        $this->actingAs($this->makeAdmin())
            ->post('/data-dosen', $this->payload([
                'memiliki_jabatan' => 'tidak',
                'jabatan_fungsional' => '',
                'pangkat' => '',
            ]))
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('data-dosen.index'));

        $this->assertNull(User::query()->where('nidn', '1985041722')->firstOrFail()->jabatan_fungsional);
    }

    public function test_form_shows_jabatan_akademik_choice_before_pangkat(): void
    {
        $this->actingAs($this->makeAdmin())
            ->get('/data-dosen/create')
            ->assertOk()
            ->assertSee('Memiliki Jabatan Akademik?')
            ->assertSee('name="memiliki_jabatan"', false);
    }

    public function test_form_shows_jabatan_fungsional_select_above_pangkat(): void
    {
        $this->actingAs($this->makeAdmin())
            ->get('/data-dosen/create')
            ->assertOk()
            ->assertSeeInOrder(['name="jabatan_fungsional"', 'name="pangkat"'], false);
    }

    public function test_admin_can_import_dosen_from_excel_and_uploaded_file_is_deleted(): void
    {
        $jalur = sys_get_temp_dir().DIRECTORY_SEPARATOR.'import-data-dosen-uji-'.uniqid().'.xlsx';

        Excel::tulis($jalur, [
            ['Nama Dosen', 'NIDN', 'Memiliki Jabatan Akademik', 'Jabatan Akademik', 'Pangkat', 'Role', 'Password'],
            ['Dr. Citra Maharani, M.Si.', '1985041722', 'Ya', 'Lektor Kepala', 'Penata Tingkat I', 'dosen,tim_pak', 'rahasia123'],
            ['Budi Santoso, M.Pd.', '1990011220', 'Tidak', '', '', 'dosen', 'rahasia123'],
        ]);

        $admin = $this->makeAdmin();

        $this->actingAs($admin)
            ->post(route('data-dosen.import.mulai'),
                ['berkas' => new UploadedFile($jalur, 'data-dosen.xlsx', null, null, true)],
                ['Accept' => 'application/json'])
            ->assertOk()
            ->assertJsonPath('persen', 0);

        $this->actingAs($admin)
            ->post(route('data-dosen.import.langkah'), [], ['Accept' => 'application/json'])
            ->assertOk()
            ->assertJsonPath('persen', 100)
            ->assertJsonPath('redirect', route('data-dosen.index'));

        $citra = User::query()->where('nidn', '1985041722')->firstOrFail();
        $this->assertSame('Lektor Kepala', $citra->jabatan_fungsional);
        $this->assertSame('Penata Tingkat I', $citra->pangkat);
        $this->assertSame(['dosen', 'tim_pak'], $citra->roleList());
        $this->assertTrue(Hash::check('rahasia123', $citra->password));

        // "Tidak" mengosongkan jabatan akademik & pangkat.
        $budi = User::query()->where('nidn', '1990011220')->firstOrFail();
        $this->assertNull($budi->jabatan_fungsional);
        $this->assertNull($budi->pangkat);

        // Berkas Excel & salinan baris sementara dihapus setelah import selesai.
        $this->assertSame([], Storage::disk('local')->allFiles('import-data-dosen'));

        if (file_exists($jalur)) {
            unlink($jalur);
        }
    }

    public function test_import_page_can_be_opened(): void
    {
        $this->actingAs($this->makeAdmin())
            ->get(route('data-dosen.import.create'))
            ->assertOk()
            ->assertSee('Unduh Template')
            ->assertSee('name="berkas"', false);
    }

    public function test_import_template_can_be_downloaded(): void
    {
        $this->actingAs($this->makeAdmin())
            ->get(route('data-dosen.import.template'))
            ->assertOk()
            ->assertDownload('template-import-data-dosen.xlsx');
    }

    public function test_import_rejects_file_that_is_not_xlsx(): void
    {
        $jalur = sys_get_temp_dir().DIRECTORY_SEPARATOR.'import-data-dosen-uji-'.uniqid().'.txt';
        file_put_contents($jalur, 'bukan excel');

        $this->actingAs($this->makeAdmin())
            ->post(route('data-dosen.import.mulai'),
                ['berkas' => new UploadedFile($jalur, 'data.txt', 'text/plain', null, true)],
                ['Accept' => 'application/json'])
            ->assertStatus(422)
            ->assertJsonValidationErrors('berkas');

        if (file_exists($jalur)) {
            unlink($jalur);
        }
    }

    public function test_role_is_required_when_saving_user(): void
    {
        $this->actingAs($this->makeAdmin())
            ->post('/data-dosen', $this->payload(['roles' => []]))
            ->assertSessionHasErrors('roles');

        $this->assertDatabaseMissing('users', ['nidn' => '1985041722']);
    }

    public function test_admin_can_update_roles_and_roles_are_listed_in_table(): void
    {
        $admin = $this->makeAdmin();

        $dosen = User::create([
            'nidn' => '1987654321',
            'name' => 'Dr. Ayu Lestari, M.Kom.',
            'jabatan_fungsional' => 'Lektor',
            'role' => 'dosen',
            'roles' => ['dosen'],
            'is_active' => true,
            'password' => Hash::make('password'),
        ]);

        $this->actingAs($admin)
            ->put("/data-dosen/{$dosen->id}", $this->payload([
                'name' => $dosen->name,
                'nidn' => $dosen->nidn,
                'roles' => ['dosen', 'tim_pak'],
                'password' => '',
                'password_confirmation' => '',
            ]))
            ->assertRedirect(route('data-dosen.index'));

        $this->assertSame(['dosen', 'tim_pak'], $dosen->fresh()->roleList());

        $this->actingAs($admin)
            ->get('/data-dosen')
            ->assertOk()
            ->assertSee('TIM PAK')
            ->assertSee('Dosen')
            // Konfirmasi hapus menjelaskan dampaknya ke seluruh data terkait.
            ->assertSee('Hapus data dosen dan semua datanya?', false)
            ->assertSee('riwayat pendidikan dengan berkas ijazah &amp; transkrip', false);
    }

    public function test_user_with_tim_pak_role_can_login(): void
    {
        $user = User::create([
            'nidn' => '1985041722',
            'name' => 'Dr. Citra Maharani, M.Si.',
            'jabatan_fungsional' => 'Lektor Kepala',
            'role' => 'dosen',
            'roles' => ['dosen', 'tim_pak'],
            'is_active' => true,
            'password' => Hash::make('password'),
        ]);

        $this->post('/login', [
            'nidn' => $user->nidn,
            'password' => 'password',
        ])->assertRedirect(route('role.select'));

        $this->assertAuthenticatedAs($user);

        $this->post('/pilih-role', ['role' => 'tim_pak'])
            ->assertRedirect(route('dashboard'));

        $this->get('/dashboard')->assertOk();
    }

    public function test_form_shows_role_checkboxes_with_current_roles(): void
    {
        $admin = $this->makeAdmin();

        $dosen = User::create([
            'nidn' => '1985041722',
            'name' => 'Dr. Citra Maharani, M.Si.',
            'jabatan_fungsional' => 'Lektor Kepala',
            'role' => 'dosen',
            'roles' => ['dosen', 'tim_pak'],
            'is_active' => true,
            'password' => Hash::make('password'),
        ]);

        $this->actingAs($admin)
            ->get("/data-dosen/{$dosen->id}/edit")
            ->assertOk()
            ->assertSee('name="roles[]"', false)
            ->assertSee('value="tim_pak"', false)
            ->assertSee('TIM PAK');
    }

    public function test_admin_cannot_delete_own_account(): void
    {
        $admin = $this->makeAdmin();

        $this->actingAs($admin)
            ->delete("/data-dosen/{$admin->id}")
            ->assertForbidden();

        $this->assertDatabaseHas('users', ['id' => $admin->id]);
    }

    public function test_admin_can_bulk_delete_selected_dosen_except_own_account(): void
    {
        $admin = $this->makeAdmin();
        $satu = $this->makeDosen('1985041722', 'Dr. Citra Maharani, M.Si.');
        $dua = $this->makeDosen('1985041733', 'Dr. Budi Santoso, M.Kom.');
        $tetap = $this->makeDosen('1985041744', 'Dr. Dewi Lestari, M.Pd.');

        $this->actingAs($admin)
            ->delete('/data-dosen/hapus-terpilih', ['ids' => [$satu->id, $dua->id, $admin->id]])
            ->assertRedirect('/data-dosen')
            ->assertSessionHas('status');

        $this->assertDatabaseMissing('users', ['id' => $satu->id]);
        $this->assertDatabaseMissing('users', ['id' => $dua->id]);
        $this->assertDatabaseHas('users', ['id' => $tetap->id]);
        $this->assertDatabaseHas('users', ['id' => $admin->id]);

        // Kolom ID kini berisi checkbox yang menempel ke form bulk delete.
        $this->actingAs($admin)
            ->get('/data-dosen')
            ->assertSee('form="bulk-form"', false)
            ->assertSee('name="ids[]"', false)
            ->assertSee('data-bulk-item', false);
    }

    public function test_bulk_delete_requires_selected_dosen(): void
    {
        $this->actingAs($this->makeAdmin())
            ->delete('/data-dosen/hapus-terpilih', ['ids' => []])
            ->assertSessionHasErrors('ids');
    }

    public function test_deleting_dosen_removes_all_related_data_and_uploaded_files(): void
    {
        Storage::fake('local');

        $admin = $this->makeAdmin();

        $korban = User::create([
            'nidn' => '1985041722',
            'name' => 'Dr. Citra Maharani, M.Si.',
            'email' => 'citra@example.test',
            'role' => 'dosen',
            'roles' => ['dosen'],
            'is_active' => true,
            'password' => Hash::make('password'),
        ]);

        $dosen = User::create([
            'nidn' => '1985041733',
            'name' => 'Dr. Budi Santoso, M.Kom.',
            'email' => 'budi@example.test',
            'role' => 'dosen',
            'roles' => ['dosen'],
            'is_active' => true,
            'password' => Hash::make('password'),
        ]);

        $ijazah = "riwayat-pendidikan/{$dosen->nidn}/s1_{$dosen->nidn}_a.pdf";
        $transkrip = "riwayat-pendidikan/{$dosen->nidn}/s1_{$dosen->nidn}_b.pdf";
        $bkd = "syarat-khusus/{$dosen->nidn}/bkd_{$dosen->nidn}_a.pdf";
        $ikd = "syarat-khusus/{$dosen->nidn}/ikd_{$dosen->nidn}_b.pdf";

        foreach ([$ijazah, $transkrip, $bkd, $ikd] as $berkas) {
            Storage::disk('local')->put($berkas, '%PDF-1.4');
        }

        $dosen->profilDosen()->create(['tempat_lahir' => 'Bandung']);
        $dosen->riwayatPendidikan()->create([
            'jenjang' => 's1',
            'nama_institusi' => 'UPI',
            'tahun_masuk' => 2005,
            'ijazah_path' => $ijazah,
            'transkrip_path' => $transkrip,
        ]);
        $dosen->kegiatanDupak()->create([
            'komponen' => 'pendidikan',
            'tahun' => 2026,
            'semester' => 'genap',
            'jenis' => 'pendidikan_formal',
            'nama_kegiatan' => 'Seminar',
            'link_bukti' => 'https://example.test/bukti',
        ]);
        $dosen->pengajuanDupak()->create(['tahun' => 2026, 'semester' => 'genap']);
        $dosen->karyaTulis()->create([
            'karya_ilmiah' => 'Jurnal Nasional Terakreditasi Kemendiktisaintek (1,2,3,4,5,6)',
            'judul' => 'Machine Learning untuk Pemula',
            'link_bukti' => 'https://example.test/karya',
        ]);
        SyaratKhusus::create([
            'user_id' => $dosen->id,
            'tahun' => 2026,
            'semester' => 'genap',
            'bkd_path' => $bkd,
            'ikd_path' => $ikd,
        ]);
        Senat::create(['user_id' => $dosen->id, 'jabatan' => 'anggota']);
        TimKomite::create(['user_id' => $dosen->id, 'jabatan' => 'anggota']);

        // BAP milik dosen lain yang mencantumkan dosen terhapus sebagai peserta rapat.
        $bapKorban = SuratBapSenat::create([
            'nomor_bap' => '01/BAP/2026',
            'tanggal_bap' => '2026-10-01',
            'user_id' => $korban->id,
            'bidang_keilmuan' => 'Informatika',
            'prosentase_penelitian' => 60,
            'anggota_senat_ids' => [$korban->id, $dosen->id],
        ]);

        $bap = SuratBapSenat::create([
            'nomor_bap' => '02/BAP/2026',
            'tanggal_bap' => '2026-10-02',
            'user_id' => $dosen->id,
            'bidang_keilmuan' => 'Informatika',
            'prosentase_penelitian' => 60,
            'anggota_senat_ids' => [$dosen->id],
        ]);

        $baki = SuratBaki::create([
            'nomor_surat' => '01/BAKI/2026',
            'surat_bap_senat_id' => $bap->id,
            'tanggal_surat' => '2026-10-03',
        ]);

        DB::table('sessions')->insert([
            'id' => 'sesi-dosen',
            'user_id' => $dosen->id,
            'payload' => '',
            'last_activity' => 1,
        ]);
        DB::table('password_reset_tokens')->insert([
            'email' => $dosen->email,
            'token' => 'rahasia',
            'created_at' => now(),
        ]);

        $this->actingAs($admin)
            ->delete("/data-dosen/{$dosen->id}")
            ->assertRedirect('/data-dosen');

        $this->assertDatabaseMissing('users', ['id' => $dosen->id]);

        foreach (['profil_dosen', 'riwayat_pendidikan', 'kegiatan_dupak', 'pengajuan_dupak',
            'karya_tulis', 'syarat_khusus', 'senat', 'tim_komite', 'surat_bap_senat', 'sessions'] as $tabel) {
            $this->assertDatabaseMissing($tabel, ['user_id' => $dosen->id]);
        }

        $this->assertDatabaseMissing('surat_baki', ['id' => $baki->id]);
        $this->assertDatabaseMissing('password_reset_tokens', ['email' => $dosen->email]);

        // Data dosen lain tidak ikut terhapus, hanya rujukannya yang dibersihkan.
        $this->assertDatabaseHas('users', ['id' => $korban->id]);
        $this->assertEquals([$korban->id], $bapKorban->fresh()->anggota_senat_ids);

        Storage::disk('local')->assertDirectoryEmpty("riwayat-pendidikan/{$dosen->nidn}");
        Storage::disk('local')->assertDirectoryEmpty("syarat-khusus/{$dosen->nidn}");
    }
}
