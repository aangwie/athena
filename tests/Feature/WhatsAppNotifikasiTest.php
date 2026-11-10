<?php

namespace Tests\Feature;

use App\Models\Pengaturan;
use App\Models\SyaratKhusus;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class WhatsAppNotifikasiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Pengaturan::simpanWhatsappGatewayUrl('http://wa.test');
    }

    /**
     * Pasang respons gateway. Registrasi per-test supaya urutan stub Http::fake
     * tidak "terkunci" oleh setUp (Http::fake bersifat menumpuk, stub pertama menang).
     *
     * @param  array<string, mixed>  $stubs  Respons pengganti, mis. wa.test/send-message.
     */
    private function fakeGateway(array $stubs = []): void
    {
        Http::fake([
            'wa.test/send-message' => Http::response(['status' => true, 'message' => 'Pesan terkirim']),
            ...$stubs,
            'wa.test/*' => Http::response('not found', 404),
        ]);
    }

    private function makeUser(string $role, string $suffix, ?string $noHp = null, array $extra = []): User
    {
        return User::create([
            'nidn' => '9'.str_pad((string) (User::max('id') + 1), 9, '0', STR_PAD_LEFT),
            'name' => ucfirst($role).' '.$suffix,
            'email' => $role.$suffix.'@example.test',
            'program_studi' => 'Pendidikan Informatika',
            'jabatan_fungsional' => 'Lektor',
            'no_hp' => $noHp,
            'role' => $role,
            'roles' => [$role],
            'is_active' => true,
            'password' => Hash::make('password'),
            ...$extra,
        ]);
    }

    private function makeKegiatan(User $dosen): void
    {
        $dosen->kegiatanDupak()->create([
            'komponen' => 'pendidikan',
            'tahun' => 2026,
            'semester' => 'ganjil',
            'jenis' => 'pendidikan_formal',
            'nama_kegiatan' => 'Ijazah Magister Teknologi Pendidikan',
            'link_bukti' => 'https://drive.google.com/file/d/ijazah/view',
        ]);
    }

    /**
     * Nomor-nomor penerima yang benar-benar dikirimi pesan gateway.
     *
     * @return list<string>
     */
    private function nomorTerkirim(): array
    {
        return Http::recorded()
            ->map(fn ($pair) => (string) $pair[0]['number'])
            ->values()
            ->all();
    }

    /** '0812-0000-0001' → '6281200000001'. */
    private function normalisasi(string $nomor): string
    {
        $angka = preg_replace('/\D+/', '', $nomor) ?? '';

        return match (true) {
            str_starts_with($angka, '0') => '62'.substr($angka, 1),
            str_starts_with($angka, '8') => '62'.$angka,
            default => $angka,
        };
    }

    public function test_usulan_pak_mengirim_whatsapp_ke_semua_tim_pak(): void
    {
        $this->fakeGateway();

        $dosen = $this->makeUser('dosen', '1');
        $this->makeKegiatan($dosen);

        $pak = $this->makeUser('tim_pak', '1', '0812-0000-0009');
        $pak2 = $this->makeUser('tim_pak', '2', '0812-0000-0010');

        $this->actingAs($dosen)
            ->post('/profil/pengajuan/ajukan', ['tahun' => 2026, 'semester' => 'ganjil'])
            ->assertSessionHas('status');

        $terkirim = $this->nomorTerkirim();

        $this->assertContains($this->normalisasi((string) $pak->no_hp), $terkirim);
        $this->assertContains($this->normalisasi((string) $pak2->no_hp), $terkirim);
        $this->assertCount(2, $terkirim);

        Http::assertSent(fn ($request) => str_contains($request['message'], 'Penilaian PAK'));
    }

    public function test_usulan_pak_mengirim_ke_akun_role_ganda_lewat_kolom_roles(): void
    {
        $this->fakeGateway();

        $dosen = $this->makeUser('dosen', '1');
        $this->makeKegiatan($dosen);

        // Akun dosen yang juga TIM PAK, seperti pada data seeder.
        $ganda = $this->makeUser('dosen', '2', '0812-0000-0011', ['roles' => ['dosen', 'tim_pak']]);

        $this->actingAs($dosen)
            ->post('/profil/pengajuan/ajukan', ['tahun' => 2026, 'semester' => 'ganjil']);

        $this->assertContains($this->normalisasi((string) $ganda->no_hp), $this->nomorTerkirim());
    }

    public function test_ajuan_dupak_mengirim_whatsapp_ke_semua_admin(): void
    {
        $this->fakeGateway();

        $dosen = $this->makeUser('dosen', '1', '0812-0000-0003');
        $kegiatan = $dosen->kegiatanDupak()->create([
            'komponen' => 'pendidikan',
            'tahun' => 2026,
            'semester' => 'ganjil',
            'jenis' => 'pendidikan_formal',
            'nama_kegiatan' => 'Ijazah Magister',
            'link_bukti' => 'https://drive.google.com/file/d/ijazah/view',
        ]);

        $admin = $this->makeUser('admin', '1', '0812-0000-0004');
        $admin2 = $this->makeUser('admin', '2', '0812-0000-0005');

        // Nilai sudah tersedia sehingga DUPAK bisa diajukan ke admin.
        $dosen->pengajuanDupak()->create([
            'tahun' => 2026,
            'semester' => 'ganjil',
            'status' => 'dinilai',
            'diajukan_at' => now()->subDay(),
            'dinilai_at' => now(),
        ]);
        $kegiatan->update(['nilai_baru' => 2]);

        $this->actingAs($dosen)
            ->post('/profil/pengajuan/ajukan-dupak', ['tahun' => 2026, 'semester' => 'ganjil'])
            ->assertSessionHas('status');

        $terkirim = $this->nomorTerkirim();

        $this->assertContains($this->normalisasi((string) $admin->no_hp), $terkirim);
        $this->assertContains($this->normalisasi((string) $admin2->no_hp), $terkirim);
        $this->assertCount(2, $terkirim);

        Http::assertSent(fn ($request) => str_contains($request['message'], 'DUPAK'));
    }

    public function test_pengajuan_berkas_bkd_ikd_mengirim_whatsapp_ke_admin(): void
    {
        $this->fakeGateway();

        $dosen = $this->makeUser('dosen', '1');
        $admin = $this->makeUser('admin', '1', '0812-0000-0006');

        $syaratKhusus = SyaratKhusus::create([
            'user_id' => $dosen->id,
            'tahun' => 2026,
            'semester' => 'ganjil',
            'bkd_status' => 'draf',
            'ikd_status' => 'draf',
            'bkd_path' => 'syarat-khusus/'.$dosen->nidn.'/bkd.pdf',
            'ikd_path' => 'syarat-khusus/'.$dosen->nidn.'/ikd.pdf',
        ]);

        $this->actingAs($dosen)
            ->post(route('profil.pengajuan.syarat-khusus.ajukan', $syaratKhusus))
            ->assertSessionHas('status');

        $this->assertContains($this->normalisasi((string) $admin->no_hp), $this->nomorTerkirim());
        Http::assertSent(fn ($request) => str_contains($request['message'], 'BKD & IKD'));
    }

    public function test_tim_pak_menyimpan_nilai_mengirim_whatsapp_ke_dosen(): void
    {
        $this->fakeGateway();

        $dosen = $this->makeUser('dosen', '1');
        $kegiatan = $dosen->kegiatanDupak()->create([
            'komponen' => 'pendidikan',
            'tahun' => 2026,
            'semester' => 'ganjil',
            'jenis' => 'pendidikan_formal',
            'nama_kegiatan' => 'Ijazah Magister',
            'link_bukti' => 'https://drive.google.com/file/d/ijazah/view',
        ]);

        // Nomor WhatsApp dosen diambil dari profil dosen.
        $dosen->profilDosen()->create(['no_hp' => '0812-0000-0007']);

        $pengajuan = $dosen->pengajuanDupak()->create([
            'tahun' => 2026,
            'semester' => 'ganjil',
            'status' => 'diajukan',
            'diajukan_at' => now()->subDay(),
        ]);

        $this->actingAs($this->makeUser('tim_pak', '1'))
            ->put(route('tim-pak.pengajuan.update', $pengajuan), ['nilai' => [$kegiatan->id => '3']])
            ->assertSessionHas('status');

        $this->assertSame([$this->normalisasi('0812-0000-0007')], $this->nomorTerkirim());
        Http::assertSent(fn ($request) => str_contains($request['message'], 'Nilai PAK Tersimpan'));
    }

    public function test_admin_memutuskan_dupak_mengirim_whatsapp_ke_dosen(): void
    {
        $this->fakeGateway();

        $dosen = $this->makeUser('dosen', '1', '0812-0000-0008');
        $pengajuan = $dosen->pengajuanDupak()->create([
            'tahun' => 2026,
            'semester' => 'ganjil',
            'status' => 'diajukan_dupak',
            'diajukan_at' => now()->subDay(),
        ]);

        $this->actingAs($this->makeUser('admin', '1'))
            ->put(route('verifikasi-dupak.update', $pengajuan), [
                'keputusan' => 'perlu_perbaikan',
                'catatan' => 'Lengkapi bukti publikasi.',
            ])
            ->assertSessionHas('status');

        $this->assertSame([$this->normalisasi('0812-0000-0008')], $this->nomorTerkirim());
        Http::assertSent(fn ($request) => str_contains($request['message'], 'Keputusan DUPAK')
            && str_contains($request['message'], 'Lengkapi bukti publikasi.'));
    }

    public function test_admin_memutuskan_berkas_mengirim_whatsapp_ke_dosen(): void
    {
        $this->fakeGateway();

        $dosen = $this->makeUser('dosen', '1', '0812-0000-0012');

        $syaratKhusus = SyaratKhusus::create([
            'user_id' => $dosen->id,
            'tahun' => 2026,
            'semester' => 'ganjil',
            'bkd_status' => 'diajukan',
            'ikd_status' => 'draf',
        ]);

        $this->actingAs($this->makeUser('admin', '1'))
            ->put(route('verifikasi-berkas.update', ['syaratKhusus' => $syaratKhusus, 'jenis' => 'bkd']), [
                'keputusan' => 'ditolak',
                'catatan' => 'Scan BKD kurang jelas.',
            ])
            ->assertSessionHas('status');

        $this->assertSame([$this->normalisasi('0812-0000-0012')], $this->nomorTerkirim());
        Http::assertSent(fn ($request) => str_contains($request['message'], 'Keputusan Berkas BKD')
            && str_contains($request['message'], 'Scan BKD kurang jelas.'));
    }

    public function test_admin_menghapus_berkas_mengirim_whatsapp_ke_dosen(): void
    {
        $this->fakeGateway();
        Storage::fake('local');

        $dosen = $this->makeUser('dosen', '1', '0812-0000-0013');

        $syaratKhusus = SyaratKhusus::create([
            'user_id' => $dosen->id,
            'tahun' => 2026,
            'semester' => 'ganjil',
            'bkd_path' => 'syarat-khusus/2026/ganjil/bkd.pdf',
            'bkd_status' => 'diajukan',
            'ikd_status' => 'draf',
        ]);

        Storage::disk('local')->put($syaratKhusus->bkd_path, 'pdf');

        $this->actingAs($this->makeUser('admin', '1'))
            ->delete(route('verifikasi-berkas.destroy', ['syaratKhusus' => $syaratKhusus, 'jenis' => 'bkd']))
            ->assertSessionHas('status');

        $this->assertSame([$this->normalisasi('0812-0000-0013')], $this->nomorTerkirim());
        Http::assertSent(fn ($request) => str_contains($request['message'], 'Berkas BKD Dihapus Admin')
            && str_contains($request['message'], 'unggah ulang'));
    }

    public function test_tidak_mengirim_bila_nomor_penerima_kosong(): void
    {
        $this->fakeGateway();

        $dosen = $this->makeUser('dosen', '1');
        $this->makeKegiatan($dosen);

        // TIM PAK tanpa nomor WhatsApp tidak dihubungi.
        $this->makeUser('tim_pak', '1');

        $this->actingAs($dosen)
            ->post('/profil/pengajuan/ajukan', ['tahun' => 2026, 'semester' => 'ganjil']);

        $this->assertSame([], $this->nomorTerkirim());
    }

    public function test_tidak_mengirim_bila_gateway_belum_diatur(): void
    {
        $this->fakeGateway();

        Pengaturan::simpanWhatsappGatewayUrl(null);

        $dosen = $this->makeUser('dosen', '1');
        $this->makeKegiatan($dosen);
        $this->makeUser('tim_pak', '1', '0812-0000-0013');

        $this->actingAs($dosen)
            ->post('/profil/pengajuan/ajukan', ['tahun' => 2026, 'semester' => 'ganjil']);

        Http::assertNothingSent();
    }

    public function test_alert_sukses_ditampilkan_saat_notifikasi_terkirim(): void
    {
        $this->fakeGateway();

        $dosen = $this->makeUser('dosen', '1');
        $this->makeKegiatan($dosen);
        $this->makeUser('tim_pak', '1', '0812-0000-0020');

        $this->actingAs($dosen)
            ->post('/profil/pengajuan/ajukan', ['tahun' => 2026, 'semester' => 'ganjil'])
            ->assertRedirect()
            ->assertSessionHas('wa_notif', fn (array $ringkasan) => $ringkasan['terkirim'] === 1
                && $ringkasan['gagal'] === 0);

        // Halaman berikutnya menampilkan elemen alert untuk diubah JavaScript.
        $this->actingAs($dosen)
            ->get('/dashboard')
            ->assertOk()
            ->assertSee('data-wa-notif-alert', false)
            ->assertSee('data-wa-icon="success"', false)
            ->assertSee('Notifikasi WhatsApp terkirim');
    }

    public function test_alert_gagal_ditampilkan_saat_gateway_menolak(): void
    {
        $this->fakeGateway([
            'wa.test/send-message' => Http::response(['status' => false, 'message' => 'Gagal'], 200),
        ]);

        $dosen = $this->makeUser('dosen', '1');
        $this->makeKegiatan($dosen);
        $this->makeUser('tim_pak', '1', '0812-0000-0021');

        $this->actingAs($dosen)
            ->post('/profil/pengajuan/ajukan', ['tahun' => 2026, 'semester' => 'ganjil'])
            ->assertSessionHas('wa_notif', fn (array $ringkasan) => $ringkasan['terkirim'] === 0
                && $ringkasan['gagal'] === 1);

        $this->actingAs($dosen)
            ->get('/dashboard')
            ->assertOk()
            ->assertSee('data-wa-icon="error"', false)
            ->assertSee('Notifikasi WhatsApp gagal');
    }

    public function test_alert_sebagian_gagal_memakai_ikon_warning(): void
    {
        // Penerima pertama berhasil, kedua gagal.
        $this->fakeGateway([
            'wa.test/send-message' => Http::sequence()
                ->push(['status' => true])
                ->push(['status' => false]),
        ]);

        $dosen = $this->makeUser('dosen', '1');
        $this->makeKegiatan($dosen);
        $this->makeUser('tim_pak', '1', '0812-0000-0022');
        $this->makeUser('tim_pak', '2', '0812-0000-0023');

        $this->actingAs($dosen)
            ->post('/profil/pengajuan/ajukan', ['tahun' => 2026, 'semester' => 'ganjil'])
            ->assertSessionHas('wa_notif', fn (array $ringkasan) => $ringkasan['terkirim'] === 1
                && $ringkasan['gagal'] === 1);

        $this->actingAs($dosen)
            ->get('/dashboard')
            ->assertOk()
            ->assertSee('data-wa-icon="warning"', false)
            ->assertSee('gagal ke 1 penerima');
    }

    public function test_tidak_ada_alert_bila_tak_ada_penerima_bernomor(): void
    {
        $this->fakeGateway();

        $dosen = $this->makeUser('dosen', '1');
        $this->makeKegiatan($dosen);
        $this->makeUser('tim_pak', '1');

        $this->actingAs($dosen)
            ->post('/profil/pengajuan/ajukan', ['tahun' => 2026, 'semester' => 'ganjil'])
            ->assertSessionMissing('wa_notif');

        $this->actingAs($dosen)
            ->get('/dashboard')
            ->assertOk()
            ->assertDontSee('data-wa-notif-alert', false);
    }
}
