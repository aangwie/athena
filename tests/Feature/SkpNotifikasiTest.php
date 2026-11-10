<?php

namespace Tests\Feature;

use App\Models\JabatanStruktural;
use App\Models\Pengaturan;
use App\Models\Prodi;
use App\Models\Skp;
use App\Models\StrukturInstansi;
use App\Models\User;
use App\Support\Notifikasi;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class SkpNotifikasiTest extends TestCase
{
    use RefreshDatabase;

    private static int $seq = 0;

    protected function setUp(): void
    {
        parent::setUp();
        Pengaturan::simpanWhatsappGatewayUrl('http://wa.test');
    }

    private function fakeGateway(): void
    {
        Http::fake([
            'wa.test/send-message' => Http::response(['status' => true, 'message' => 'Pesan terkirim']),
            'wa.test/*' => Http::response('not found', 404),
        ]);
    }

    private function makeUser(string $role, ?string $noHp = null): User
    {
        return User::create([
            'nidn' => '9'.str_pad((string) (++self::$seq), 9, '0', STR_PAD_LEFT),
            'name' => ucfirst($role).' '.self::$seq,
            'email' => $role.self::$seq.'@example.test',
            'program_studi' => 'Pendidikan Informatika',
            'jabatan_fungsional' => 'Lektor',
            'no_hp' => $noHp,
            'role' => $role,
            'roles' => [$role],
            'is_active' => true,
            'password' => Hash::make('password'),
        ]);
    }

    private function makeProdi(): Prodi
    {
        return Prodi::create([
            'nama_prodi' => 'Pendidikan Informatika',
            'kode_prodi' => 'PI-'.str_pad((string) (++self::$seq), 2, '0', STR_PAD_LEFT),
        ]);
    }

    private function makeStruktur(User $user, string $jabatan): StrukturInstansi
    {
        return StrukturInstansi::create([
            'jabatan_struktural_id' => JabatanStruktural::create(['nama' => $jabatan.' '.(++self::$seq)])->id,
            'user_id' => $user->id,
        ]);
    }

    private function makeSkp(User $pejabat, array $nilai = []): Skp
    {
        $atasan = $this->makeUser('dosen');
        $this->makeStruktur($atasan, 'Dekan');

        return Skp::create(array_merge([
            'dosen_id' => $this->makeUser('dosen')->id,
            'pejabat_penilai_id' => $pejabat->id,
            'atasan_penilai_id' => $atasan->id,
            'prodi_id' => $this->makeProdi()->id,
            'periode_mulai' => '2026-01-01',
            'periode_akhir' => '2026-06-01',
        ], $nilai));
    }

    private function nilaiPayload(): array
    {
        return [
            'nilai_orientasi' => 90,
            'nilai_integritas' => 80,
            'nilai_komitmen' => 85,
            'nilai_disiplin' => 95,
            'nilai_kerjasama' => 75,
            'nilai_kepemimpinan' => 88,
            'tanggal_penilaian' => '05-11-2026',
        ];
    }

    private function nomorTerkirim(): array
    {
        return Http::recorded()
            ->map(fn ($pair) => (string) $pair[0]['number'])
            ->values()
            ->all();
    }

    private function normalisasi(string $nomor): string
    {
        $angka = preg_replace('/\D+/', '', $nomor) ?? '';

        return match (true) {
            str_starts_with($angka, '0') => '62'.substr($angka, 1),
            str_starts_with($angka, '8') => '62'.$angka,
            default => $angka,
        };
    }

    public function test_lonceng_admin_menampilkan_nilai_skp_diperbarui(): void
    {
        $pejabat = $this->makeUser('dosen');
        $this->makeSkp($pejabat, $this->nilaiPayload());

        $this->actingAs($this->makeUser('admin'))
            ->get('/dashboard')
            ->assertOk()
            ->assertSee('Nilai perilaku kerja SKP diperbarui');
    }

    public function test_lonceng_pejabat_menampilkan_usulan_skp(): void
    {
        $pejabat = $this->makeUser('dosen');
        $this->makeSkp($pejabat);

        $judul = Notifikasi::untuk($pejabat->fresh())->pluck('judul')->all();

        $this->assertContains('Usulan SKP untuk dinilai', $judul);
    }

    public function test_admin_buat_skp_mengirim_whatsapp_ke_pejabat_penilai(): void
    {
        $this->fakeGateway();

        $pejabat = $this->makeUser('dosen', '0812-0000-0001');
        $this->makeStruktur($pejabat, 'Rektor');
        $atasan = $this->makeUser('dosen');
        $this->makeStruktur($atasan, 'Dekan');

        $this->actingAs($this->makeUser('admin'))
            ->post(route('skp.store'), [
                'dosen_id' => $this->makeUser('dosen')->id,
                'pejabat_penilai_id' => $pejabat->id,
                'atasan_penilai_id' => $atasan->id,
                'prodi_id' => $this->makeProdi()->id,
                'bulan_mulai' => 1,
                'tahun_mulai' => 2026,
                'bulan_akhir' => 6,
                'tahun_akhir' => 2026,
            ])
            ->assertRedirect(route('skp.index'));

        $this->assertSame([$this->normalisasi('0812-0000-0001')], $this->nomorTerkirim());
        Http::assertSent(fn ($request) => str_contains($request['message'], 'Usulan SKP'));
    }

    public function test_pejabat_update_nilai_mengirim_whatsapp_ke_semua_admin(): void
    {
        $this->fakeGateway();

        $pejabat = $this->makeUser('dosen', '0812-0000-0002');
        $admin1 = $this->makeUser('admin', '0812-0000-0003');
        $admin2 = $this->makeUser('admin', '0812-0000-0004');
        $skp = $this->makeSkp($pejabat);

        $this->actingAs($pejabat)
            ->put(route('skp-usulan.update', $skp), $this->nilaiPayload())
            ->assertRedirect(route('skp-usulan.index'));

        $terkirim = $this->nomorTerkirim();
        $this->assertContains($this->normalisasi('0812-0000-0003'), $terkirim);
        $this->assertContains($this->normalisasi('0812-0000-0004'), $terkirim);
        $this->assertCount(2, $terkirim);

        Http::assertSent(fn ($request) => str_contains($request['message'], 'Nilai Perilaku Kerja SKP'));
    }

    public function test_lonceng_pejabat_menampilkan_ajuan_keberatan_skp(): void
    {
        $pejabat = $this->makeUser('dosen');
        $skp = $this->makeSkp($pejabat);
        $skp->update(['keberatan' => 'Nilai kurang tepat.', 'tanggal_keberatan' => '2026-11-07']);

        $daftar = Notifikasi::untuk($pejabat->fresh());
        $item = $daftar->firstWhere('judul', 'Ajuan keberatan SKP masuk');

        $this->assertNotNull($item);
        $this->assertSame(
            $skp->dosen->name.' · '.$skp->periodeLabel().' · diajukan 7 November 2026',
            $item['keterangan'],
        );
        // Selama belum ditanggapi, notifikasi langsung menuju form tanggapan.
        $this->assertSame(route('skp-usulan.tanggapan.edit', $skp), $item['url']);

        // Tampil juga pada lonceng navbar (dashboard).
        $this->actingAs($pejabat)
            ->get('/dashboard')
            ->assertOk()
            ->assertSee('Ajuan keberatan SKP masuk');
    }

    public function test_lonceng_dosen_menampilkan_tanggapan_keberatan(): void
    {
        $pejabat = $this->makeUser('dosen');
        $skp = $this->makeSkp($pejabat);
        $skp->update(['keberatan' => 'Nilai kurang tepat.', 'tanggal_keberatan' => '2026-11-07']);

        // Sebelum ditanggapi: dosen yang dinilai belum menerima notifikasi tanggapan.
        $this->assertNotContains(
            'Tanggapan keberatan SKP',
            Notifikasi::untuk($skp->dosen->fresh())->pluck('judul')->all(),
        );

        $skp->update([
            'tanggapan_keberatan' => 'Nilai sudah sesuai hasil verifikasi.',
            'tanggal_tanggapan' => '2026-11-10',
        ]);

        $item = Notifikasi::untuk($skp->dosen->fresh())->firstWhere('judul', 'Tanggapan keberatan SKP');

        $this->assertNotNull($item);
        $this->assertStringContainsString('ditanggapi 10 November 2026', $item['keterangan']);
        $this->assertSame(route('skp-dosen.index'), $item['url']);
    }

    public function test_lonceng_pejabat_menampilkan_persetujuan_dosen(): void
    {
        $pejabat = $this->makeUser('dosen');
        $skp = $this->makeSkp($pejabat);

        // Belum disetujui: notifikasi persetujuan tidak muncul.
        $this->assertNotContains(
            'Penilaian SKP disetujui dosen',
            Notifikasi::untuk($pejabat->fresh())->pluck('judul')->all(),
        );

        $skp->update(['disetujui_pada' => '2026-11-10']);

        $item = Notifikasi::untuk($pejabat->fresh())->firstWhere('judul', 'Penilaian SKP disetujui dosen');

        $this->assertNotNull($item);
        $this->assertStringContainsString('disetujui 10 November 2026', $item['keterangan']);
        $this->assertSame(route('skp-usulan.index'), $item['url']);
    }

    public function test_lonceng_admin_menampilkan_keberatan_tanggapan_dan_persetujuan(): void
    {
        $admin = $this->makeUser('admin');
        $pejabat = $this->makeUser('dosen');
        $skp = $this->makeSkp($pejabat, $this->nilaiPayload());

        // Tanpa ajuan: ketiga notifikasi SKP tersebut tidak muncul.
        $judulAwal = Notifikasi::untuk($admin->fresh())->pluck('judul')->all();

        foreach (['Ajuan keberatan SKP', 'Tanggapan keberatan SKP', 'Persetujuan penilaian SKP'] as $judul) {
            $this->assertNotContains($judul, $judulAwal);
        }

        $skp->update([
            'keberatan' => 'Nilai kurang tepat.',
            'tanggal_keberatan' => '2026-11-07',
            'tanggapan_keberatan' => 'Nilai sudah sesuai.',
            'tanggal_tanggapan' => '2026-11-10',
            'disetujui_pada' => '2026-11-11',
        ]);

        $daftar = Notifikasi::untuk($admin->fresh());
        $judul = $daftar->pluck('judul')->all();

        $this->assertContains('Ajuan keberatan SKP', $judul);
        $this->assertContains('Tanggapan keberatan SKP', $judul);
        $this->assertContains('Persetujuan penilaian SKP', $judul);

        $this->assertStringContainsString(
            'diajukan 7 November 2026',
            $daftar->firstWhere('judul', 'Ajuan keberatan SKP')['keterangan'],
        );
        $this->assertStringContainsString(
            'disetujui 11 November 2026',
            $daftar->firstWhere('judul', 'Persetujuan penilaian SKP')['keterangan'],
        );

        // Semua notifikasi SKP pada admin menuju halaman daftar SKP.
        foreach (['Ajuan keberatan SKP', 'Tanggapan keberatan SKP', 'Persetujuan penilaian SKP'] as $nama) {
            $this->assertSame(route('skp.index'), $daftar->firstWhere('judul', $nama)['url']);
        }
    }
}