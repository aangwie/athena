<?php

namespace Tests\Feature;

use App\Models\JabatanStruktural;
use App\Models\KegiatanDupak;
use App\Models\Kepangkatan;
use App\Models\StrukturInstansi;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class PengajuanTimPakTest extends TestCase
{
    use RefreshDatabase;

    private function makeUser(string $role, string $suffix = '1'): User
    {
        return User::create([
            'nidn' => '9'.str_pad((string) (User::max('id') + 1), 9, '0', STR_PAD_LEFT),
            'name' => $role === 'tim_pak' ? 'Prof. Budi Santoso, M.T.' : 'Dr. Ayu Lestari, M.Kom.',
            'email' => $role.$suffix.'@example.test',
            'program_studi' => 'Pendidikan Informatika',
            'jabatan_fungsional' => 'Lektor',
            'role' => $role,
            'is_active' => true,
            'password' => Hash::make('password'),
        ]);
    }

    private function makeKegiatan(User $user, array $attributes = []): KegiatanDupak
    {
        return $user->kegiatanDupak()->create([
            'komponen' => 'pendidikan',
            'tahun' => 2026,
            'semester' => 'ganjil',
            'jenis' => 'pendidikan_formal',
            'nama_kegiatan' => 'Ijazah Magister Teknologi Pendidikan',
            'link_bukti' => 'https://drive.google.com/file/d/ijazah/view',
            ...$attributes,
        ]);
    }

    public function test_dosen_can_send_usulan_to_tim_pak(): void
    {
        $dosen = $this->makeUser('dosen');
        $this->makeKegiatan($dosen);

        $this->actingAs($dosen)
            ->post('/profil/pengajuan/ajukan', ['tahun' => 2026, 'semester' => 'ganjil'])
            ->assertRedirect(route('profil.pengajuan.create', ['tahun' => 2026, 'semester' => 'ganjil']).'#review')
            ->assertSessionHas('status');

        $this->assertDatabaseHas('pengajuan_dupak', [
            'user_id' => $dosen->id,
            'tahun' => 2026,
            'semester' => 'ganjil',
            'status' => 'diajukan',
        ]);

        $this->actingAs($dosen)
            ->get('/profil/pengajuan/create?tahun=2026&semester=ganjil')
            ->assertOk()
            ->assertSee('Menunggu penilaian');
    }

    public function test_usulan_ditolak_bila_periode_belum_punya_kegiatan(): void
    {
        $dosen = $this->makeUser('dosen');

        $this->actingAs($dosen)
            ->post('/profil/pengajuan/ajukan', ['tahun' => 2026, 'semester' => 'ganjil'])
            ->assertSessionHas('error');

        $this->assertDatabaseCount('pengajuan_dupak', 0);
    }

    public function test_dosen_tidak_boleh_membuka_halaman_penilaian_tim_pak(): void
    {
        $dosen = $this->makeUser('dosen');

        $this->actingAs($dosen)->get('/tim-pak/pengajuan')->assertForbidden();

        // Detail pengajuan tidak terbuka untuk dosen (404 karena tidak ada data / middleware).
        $this->actingAs($dosen)->get('/tim-pak/pengajuan/1')->assertNotFound();
    }

    public function test_tim_pak_melihat_daftar_pengajuan_masuk(): void
    {
        $dosen = $this->makeUser('dosen');
        $this->makeKegiatan($dosen);

        $this->actingAs($dosen)->post('/profil/pengajuan/ajukan', ['tahun' => 2026, 'semester' => 'ganjil']);

        $this->actingAs($this->makeUser('tim_pak'))
            ->get('/tim-pak/pengajuan')
            ->assertOk()
            ->assertSee('Pengajuan DUPAK Masuk')
            ->assertSee($dosen->name)
            ->assertSee('2026/2027 · Ganjil')
            ->assertSee('Menunggu penilaian');
    }

    public function test_tim_pak_menyimpan_nilai_dan_jumlah_terhitung_otomatis(): void
    {
        $dosen = $this->makeUser('dosen');
        $formal = $this->makeKegiatan($dosen);
        $lain = $this->makeKegiatan($dosen, ['nama_kegiatan' => 'Ijazah Doktor Teknologi Pendidikan']);

        $this->actingAs($dosen)->post('/profil/pengajuan/ajukan', ['tahun' => 2026, 'semester' => 'ganjil']);

        $pengajuan = $dosen->pengajuanDupak()->firstOrFail();

        $this->actingAs($this->makeUser('tim_pak'))
            ->put('/tim-pak/pengajuan/'.$pengajuan->id, [
                'nilai' => [$formal->id => '1.5', $lain->id => '2.5'],
            ])
            ->assertRedirect(route('tim-pak.pengajuan.show', $pengajuan))
            ->assertSessionHas('status');

        $this->assertEquals(1.5, (float) $formal->fresh()->nilai_baru);
        $this->assertEquals(2.5, (float) $lain->fresh()->nilai_baru);
        $this->assertSame('dinilai', $pengajuan->fresh()->status);

        // Halaman penilaian menampilkan input nilai dan total bidang (1,5 + 2,5 = 4).
        $this->actingAs($this->makeUser('tim_pak', '2'))
            ->get('/tim-pak/pengajuan/'.$pengajuan->id)
            ->assertOk()
            ->assertSee('name="nilai['.$formal->id.']"', false)
            ->assertSee('data-jumlah-total="g0">4<', false);
    }

    public function test_nilai_hanya_tersimpan_untuk_kegiatan_pada_periode_pengajuan(): void
    {
        $dosen = $this->makeUser('dosen');
        $periode = $this->makeKegiatan($dosen);
        $periodeLain = $this->makeKegiatan($dosen, ['semester' => 'genap']);

        $this->actingAs($dosen)->post('/profil/pengajuan/ajukan', ['tahun' => 2026, 'semester' => 'ganjil']);
        $pengajuan = $dosen->pengajuanDupak()->firstOrFail();

        $this->actingAs($this->makeUser('tim_pak'))
            ->put('/tim-pak/pengajuan/'.$pengajuan->id, [
                'nilai' => [$periode->id => '2', $periodeLain->id => '9'],
            ]);

        $this->assertEquals(2, (float) $periode->fresh()->nilai_baru);
        $this->assertNull($periodeLain->fresh()->nilai_baru);
    }

    public function test_dosen_melihat_nilai_dari_tim_pak_pada_tab_review(): void
    {
        $dosen = $this->makeUser('dosen');
        $kegiatan = $this->makeKegiatan($dosen);

        $this->actingAs($dosen)->post('/profil/pengajuan/ajukan', ['tahun' => 2026, 'semester' => 'ganjil']);
        $pengajuan = $dosen->pengajuanDupak()->firstOrFail();

        $this->actingAs($this->makeUser('tim_pak'))
            ->put('/tim-pak/pengajuan/'.$pengajuan->id, ['nilai' => [$kegiatan->id => '3']]);

        $this->actingAs($dosen)
            ->get('/profil/pengajuan/create?tahun=2026&semester=ganjil')
            ->assertOk()
            ->assertSee('Sudah dinilai')
            ->assertSee('data-jumlah-total="g0">3<', false)
            ->assertDontSee('name="nilai['.$kegiatan->id.']"', false);
    }

    public function test_jumlah_total_mengakumulasi_seluruh_bidang(): void
    {
        $dosen = $this->makeUser('dosen');

        // Bidang I (Pendidikan) bernilai 1, Bidang III (Penelitian) bernilai 3 → total 4.
        $pendidikan = $this->makeKegiatan($dosen, ['nilai_baru' => 1]);
        $penelitian = $this->makeKegiatan($dosen, [
            'komponen' => 'penelitian',
            'jenis' => 'karya_ilmiah',
            'sub_jenis' => 'dipublikasikan',
            'rincian' => 'jurnal_internasional',
            'nama_kegiatan' => 'Artikel pada Jurnal Ilmiah Internasional Bereputasi',
            'nilai_baru' => 3,
        ]);

        $this->actingAs($dosen)->post('/profil/pengajuan/ajukan', ['tahun' => 2026, 'semester' => 'ganjil']);
        $pengajuan = $dosen->pengajuanDupak()->firstOrFail();

        // Halaman penilaian TIM PAK: jumlah per bidang 1 dan 3, jumlah total 4.
        $this->actingAs($this->makeUser('tim_pak'))
            ->get('/tim-pak/pengajuan/'.$pengajuan->id)
            ->assertOk()
            ->assertSee('Jumlah Total')
            ->assertSee('data-jumlah-total="g0">1<', false)
            ->assertSee('data-jumlah-total="g2">3<', false)
            ->assertSee('data-jumlah-grand-total>4<', false);

        // Dosen melihat akumulasi yang sama pada tab Review.
        $this->actingAs($dosen)
            ->get('/profil/pengajuan/create?tahun=2026&semester=ganjil')
            ->assertOk()
            ->assertSee('data-jumlah-grand-total>4<', false);

        $this->assertEquals(4, (float) ($pendidikan->fresh()->nilai_baru + $penelitian->fresh()->nilai_baru));
    }

    public function test_ajukan_dupak_hanya_setelah_nilai_tersedia(): void
    {
        $dosen = $this->makeUser('dosen');
        $kegiatan = $this->makeKegiatan($dosen);

        // Belum diajukan ke TIM PAK.
        $this->actingAs($dosen)
            ->post('/profil/pengajuan/ajukan-dupak', ['tahun' => 2026, 'semester' => 'ganjil'])
            ->assertSessionHas('error');

        $this->actingAs($dosen)->post('/profil/pengajuan/ajukan', ['tahun' => 2026, 'semester' => 'ganjil']);
        $pengajuan = $dosen->pengajuanDupak()->firstOrFail();

        $this->actingAs($this->makeUser('tim_pak'))
            ->put('/tim-pak/pengajuan/'.$pengajuan->id, ['nilai' => [$kegiatan->id => '2']]);

        $this->actingAs($dosen)
            ->post('/profil/pengajuan/ajukan-dupak', ['tahun' => 2026, 'semester' => 'ganjil'])
            ->assertSessionHas('status');

        $this->assertSame('diajukan_dupak', $pengajuan->fresh()->status);
    }

    public function test_tim_pak_hanya_bisa_melihat_setelah_dupak_disetujui(): void
    {
        $dosen = $this->makeUser('dosen');
        $kegiatan = $this->makeKegiatan($dosen, ['nilai_baru' => 2]);

        $this->actingAs($dosen)->post('/profil/pengajuan/ajukan', ['tahun' => 2026, 'semester' => 'ganjil']);
        $pengajuan = $dosen->pengajuanDupak()->firstOrFail();
        $pengajuan->update(['status' => 'disetujui']);

        $timPak = $this->makeUser('tim_pak');

        // Daftar pengajuan: aksi berubah menjadi Lihat saja.
        $this->actingAs($timPak)
            ->get('/tim-pak/pengajuan')
            ->assertOk()
            ->assertSee('Lihat')
            ->assertDontSee('Lihat / Ubah');

        // Halaman penilaian: read-only, tanpa input nilai, tombol Update Nilai nonaktif.
        $this->actingAs($timPak)
            ->get('/tim-pak/pengajuan/'.$pengajuan->id)
            ->assertOk()
            ->assertSee('hanya untuk dilihat')
            ->assertDontSee('name="nilai[', false)
            ->assertSee('text-sm" disabled>', false);

        // Simpan nilai ditolak walau tombolnya dipaksa lewat request langsung.
        $this->actingAs($timPak)
            ->put('/tim-pak/pengajuan/'.$pengajuan->id, ['nilai' => [$kegiatan->id => '9']])
            ->assertSessionHas('error');

        $this->assertSame(2.0, (float) $kegiatan->fresh()->nilai_baru);
        $this->assertSame('disetujui', $pengajuan->fresh()->status);
    }

    public function test_dosen_dapat_mencetak_dupak_periode_terpilih(): void
    {
        $dosen = $this->makeUser('dosen');
        $kegiatan = $this->makeKegiatan($dosen, ['nilai_baru' => 2]);

        $this->actingAs($dosen)->post('/profil/pengajuan/ajukan', ['tahun' => 2026, 'semester' => 'ganjil']);

        $this->actingAs($dosen)
            ->get('/profil/pengajuan/cetak?tahun=2026&semester=ganjil')
            ->assertOk()
            // Judul mengikuti lampiran DUPAK: judul + "Jabatan Akademik Dosen".
            ->assertSee('Daftar Usul Penetapan Angka Kredit')
            ->assertSee('Jabatan Akademik Dosen')
            ->assertSee($kegiatan->nama_kegiatan)
            // Nomor SK belum diisi admin: ditampilkan sebagai titik-titik.
            ->assertSee('Nomor:')
            // Blok identitas menampilkan NIDN dosen pengusul pada tabel "Keterangan Perorangan".
            ->assertSee('NUPTK/NIDN')
            ->assertSee($dosen->nidn)
            // Blok pengesahan: pernyataan validasi, pendapat tim penilai, dan tanda tangan Ketua.
            ->assertSee('Saya menyatakan bahwa Angka kredit telah dihitung')
            ->assertSee('Pendapat Tim Penilai Jabatan Angka Kredit')
            ->assertSee('Telah memenuhi syarat angka kredit untuk kenaikan Jabatan dan Pangkat menjadi')
            ->assertSee('Tim Penilai Pusat');

        // Periode tanpa parameter valid dikembalikan ke pemilihan periode.
        $this->actingAs($dosen)
            ->get('/profil/pengajuan/cetak?tahun=2026&semester=pendek')
            ->assertRedirect(route('profil.pengajuan.periode'));
    }

    public function test_cetak_menampilkan_nomor_sk_dari_keputusan_admin(): void
    {
        $dosen = $this->makeUser('dosen');
        $this->makeKegiatan($dosen, ['nilai_baru' => 2]);

        // Admin menyetujui DUPAK beserta nomor SK; nomor itu tampil di judul cetak.
        $pengajuan = $dosen->pengajuanDupak()->create([
            'tahun' => 2026,
            'semester' => 'ganjil',
            'status' => 'disetujui',
            'diajukan_at' => now()->subDays(5),
            'dinilai_at' => now()->subDays(2),
            'nomor_sk' => '806/STKIP PGRI/KL/VIII/2026',
        ]);

        $this->actingAs($dosen)
            ->get('/profil/pengajuan/cetak?tahun=2026&semester=ganjil')
            ->assertOk()
            ->assertSee('Nomor: '.$pengajuan->nomor_sk);
    }

    public function test_cetak_memakai_tabel_layout_tetap_agar_kolom_kanan_tidak_terpotong(): void
    {
        $dosen = $this->makeUser('dosen');
        $this->makeKegiatan($dosen, ['nilai_baru' => 2]);

        $cetak = $this->actingAs($dosen)
            ->get('/profil/pengajuan/cetak?tahun=2026&semester=ganjil')
            ->assertOk()
            ->getContent();

        // Tabel rekap & pengesahan memakai kelas penanda agar bisa dikunci oleh CSS cetak.
        $this->assertStringContainsString('tabel-rekap', $cetak);
        $this->assertStringContainsString('tabel-cetak', $cetak);

        // Aturan cetak: table-layout fixed + min-width dilucuti + teks dibungkus,
        // sehingga kolom paling kanan (Lama/Baru/Jumlah) tidak terpotong saat dicetak.
        $this->assertStringContainsString('table-layout: fixed', $cetak);
        $this->assertStringContainsString('min-width: 0', $cetak);
        $this->assertStringContainsString('overflow-wrap: anywhere', $cetak);
        // Padding body dilepas saat mencetak agar lebar kertas terpakai penuh.
        $this->assertStringContainsString('body { background: #fff !important; padding: 0 !important; }', $cetak);

        // Lebar kolom dikunci lewat <colgroup>; kolom "Unsur, Sub Unsur, dan Butir
        // Kegiatan" dipersempit (27% pada formulir cetak bertim penilai).
        $this->assertStringContainsString('<colgroup>', $cetak);
        $this->assertStringContainsString('width: 27%', $cetak);
    }

    public function test_kolom_lama_baru_jumlah_instansi_pengusul_dan_tim_penilai_sama_lebar(): void
    {
        $dosen = $this->makeUser('dosen');
        $this->makeKegiatan($dosen, ['nilai_baru' => 2]);

        $cetak = $this->actingAs($dosen)
            ->get('/profil/pengajuan/cetak?tahun=2026&semester=ganjil')
            ->assertOk()
            ->getContent();

        // Ambil nilai width dari setiap <col> pada tabel rekap (formulir cetak).
        $dom = new \DOMDocument();
        libxml_use_internal_errors(true);
        $dom->loadHTML($cetak);
        libxml_clear_errors();

        $xpath = new \DOMXPath($dom);
        $cols = $xpath->query('//table[contains(@class, "tabel-rekap")]/colgroup/col');

        $lebar = [];
        foreach ($cols as $col) {
            if (preg_match('/width:\s*([\d.]+)%/', $col->getAttribute('style'), $m)) {
                $lebar[] = (float) $m[1];
            }
        }

        // 4 kolom indent + 1 kolom unsur + 6 kolom angka (3 Instansi Pengusul + 3 Tim Penilai).
        $this->assertCount(11, $lebar);

        $angka = array_slice($lebar, 5);
        $this->assertCount(6, $angka);

        // Keenam kolom Lama/Baru/Jumlah sama lebar dan total tabel tepat 100%.
        $this->assertCount(1, array_unique($angka), 'Kolom Lama/Baru/Jumlah harus sama lebar.');
        $this->assertSame(100.0, array_sum($lebar));
    }

    public function test_cetak_menambahkan_grup_kolom_tim_penilai(): void
    {
        $dosen = $this->makeUser('dosen');
        $this->makeKegiatan($dosen, ['nilai_baru' => 2]);

        $cetak = $this->actingAs($dosen)
            ->get('/profil/pengajuan/cetak?tahun=2026&semester=ganjil')
            ->assertOk()
            // Grup kolom baru sejajar "Instansi Pengusul".
            ->assertSee('Instansi Pengusul')
            ->assertSee('Tim Penilai')
            ->getContent();

        // "Lama", "Baru", "Jumlah" masing-masing muncul 2x (Instansi Pengusul + Tim Penilai).
        $this->assertSame(2, substr_count($cetak, '>Lama</th>'));
        $this->assertSame(2, substr_count($cetak, '>Baru</th>'));
        $this->assertSame(2, substr_count($cetak, '>Jumlah</th>'));

        // Grup Tim Penilai mengikuti grup Instansi Pengusul (muncul setelahnya).
        $this->assertGreaterThan(
            strpos($cetak, '>Instansi Pengusul</th>'),
            strpos($cetak, '>Tim Penilai</th>'),
        );

        // Nilai angka kredit (2) pada baris jumlah hanya ada di grup Instansi Pengusul,
        // tepatnya 2x per baris (kolom Baru + Jumlah). Grup Tim Penilai dikosongkan.
        $this->assertSame(2, substr_count($cetak, 'data-jumlah-total="g0">2<'));
        $this->assertSame(2, substr_count($cetak, 'data-jumlah-grand-total>2<'));
    }

    public function test_cetak_tanpa_kolom_link_bukti(): void
    {
        $dosen = $this->makeUser('dosen');
        $this->makeKegiatan($dosen, ['nilai_baru' => 2]);

        $cetak = $this->actingAs($dosen)
            ->get('/profil/pengajuan/cetak?tahun=2026&semester=ganjil')
            ->assertOk()
            // Kolom & judul "Link Bukti" tidak dicetak pada formulir DUPAK.
            ->assertDontSee('Link Bukti')
            ->assertDontSee('>Bukti</span>', false)
            ->getContent();

        $this->assertSame(0, substr_count($cetak, '>Link Bukti</th>'));
        // Tidak ada tautan bukti yang tercetak.
        $this->assertSame(0, substr_count($cetak, 'href="https://drive.google.com'));
    }

    public function test_cetak_memiliki_jumlah_kolom_konsisten_tanpa_link_bukti(): void
    {
        $dosen = $this->makeUser('dosen');
        $this->makeKegiatan($dosen, ['nilai_baru' => 2]);

        $cetak = $this->actingAs($dosen)
            ->get('/profil/pengajuan/cetak?tahun=2026&semester=ganjil')
            ->assertOk()
            ->getContent();

        $dom = new \DOMDocument();
        libxml_use_internal_errors(true);
        $dom->loadHTML($cetak);
        libxml_clear_errors();

        $xpath = new \DOMXPath($dom);
        // Hanya baris <tbody> (tanpa rowspan) supaya jumlah kolom tiap baris sebanding.
        $baris = $xpath->query('//table[contains(@class, "tabel-rekap")]/tbody/tr');

        $jumlahKolom = [];
        foreach ($baris as $tr) {
            $total = 0;
            foreach ($tr->childNodes as $sel) {
                if ($sel->nodeName === 'td' || $sel->nodeName === 'th') {
                    $total += (int) ($sel->getAttribute('colspan') ?: 1);
                }
            }
            if ($total > 0) {
                $jumlahKolom[] = $total;
            }
        }

        // Cetak: 11 kolom = No (1) + Unsur (5) + Instansi Pengusul (3) + Tim Penilai (3).
        // Tanpa kolom "Link Bukti". Semua baris harus sama agar tabel tidak rusak.
        $this->assertNotEmpty($jumlahKolom);
        $this->assertSame([11], array_values(array_unique($jumlahKolom)));
    }

    public function test_halaman_review_lain_tetap_menampilkan_kolom_link_bukti(): void
    {
        $dosen = $this->makeUser('dosen');
        $this->makeKegiatan($dosen, ['nilai_baru' => 2]);

        $this->actingAs($dosen)
            ->get('/profil/pengajuan/create?tahun=2026&semester=ganjil')
            ->assertOk()
            ->assertSee('>Link Bukti</th>', false)
            ->assertSee('href="https://drive.google.com', false);
    }

    public function test_pendapat_tim_penilai_menampilkan_jabatan_diajukan_dan_pangkat_golongan(): void
    {
        $dosen = $this->makeUser('dosen');
        // Target jabatan yang diisi dosen pada halaman Status Jabatan Akademik.
        $dosen->update(['jabatan_diajukan' => 'Lektor Kepala']);
        $this->makeKegiatan($dosen, ['nilai_baru' => 2]);

        // Pangkat dengan golongan ruang → dicetak "IIIa / Penata Muda".
        $kepangkatan = Kepangkatan::create(['nama' => 'Penata Muda', 'golongan' => 'IIIa']);
        $dosen->riwayatKepangkatan()->create(['kepangkatan_id' => $kepangkatan->id, 'tmt' => '2019-05-01']);

        $this->actingAs($dosen)
            ->get('/profil/pengajuan/cetak?tahun=2026&semester=ganjil')
            ->assertOk()
            // Jabatan baris pendapat = jabatan yang diajukan, bukan jabatan saat ini.
            ->assertSee('Telah memenuhi syarat angka kredit untuk kenaikan Jabatan dan Pangkat menjadi')
            ->assertSee(': Lektor Kepala')
            // Pangkat/Gol memuat golongan ruang di depan nama pangkat.
            ->assertSee(': IIIa / Penata Muda');
    }

    public function test_cetak_menampilkan_keterangan_perorangan_dosen_pengusul(): void
    {
        $dosen = $this->makeUser('dosen');
        $this->makeKegiatan($dosen, ['nilai_baru' => 2]);

        // Identitas kepegawaian yang mengisi blok "Keterangan Perorangan".
        $dosen->profilDosen()->create([
            'tempat_lahir' => 'Bandung',
            'tanggal_lahir' => '1987-05-14',
            'nuptk' => '0943761662230152',
            'bidang_ilmu' => 'Rekayasa Perangkat Lunak',
            'jenis_kelamin' => 'P',
            'masa_kerja_golongan_lama_tahun' => 0,
            'masa_kerja_golongan_lama_bulan' => 9,
            'masa_kerja_golongan_baru_tahun' => 1,
            'masa_kerja_golongan_baru_bulan' => 3,
        ]);

        $dosen->riwayatPendidikan()->create([
            'jenjang' => 's2',
            'nama_institusi' => 'Institut Teknologi Bandung',
            'jurusan' => 'Ilmu Komputer',
            'tahun_masuk' => 2010,
            'tahun_lulus' => 2012,
        ]);

        $kepangkatan = Kepangkatan::create(['nama' => 'Penata Muda Tk.1 / Golongan III/b']);
        $dosen->riwayatKepangkatan()->create(['kepangkatan_id' => $kepangkatan->id, 'tmt' => '2021-05-01']);
        $dosen->riwayatAngkaKredit()->create(['tahun' => 2020, 'angka_kredit' => 150]);
        $dosen->masaPenilaian()->create([
            'tahun' => 2026,
            'semester' => 'ganjil',
            'mulai' => '2018-09-01',
            'akhir' => '2022-12-01',
        ]);

        $this->actingAs($dosen)
            ->get('/profil/pengajuan/cetak?tahun=2026&semester=ganjil')
            ->assertOk()
            // Judul blok dan baris identitas sesuai lampiran DUPAK.
            ->assertSee('Keterangan Perorangan')
            ->assertSee('INSTANSI')
            ->assertSee('MASA PENILAIAN')
            ->assertSee('NUPTK/NIDN')
            ->assertSee('Tempat dan Tanggal Lahir')
            ->assertSee('Jenis Kelamin')
            ->assertSee('Pendidikan Terakhir')
            ->assertSee('Jabatan Akademik Dosen, Angka Kredit/TMT')
            ->assertSee('Jurusan/Program Studi')
            ->assertSee('Bidang Ilmu')
            ->assertSee('Pangkat dan Golongan Ruang/TMT')
            ->assertSee('Masa kerja golongan lama')
            ->assertSee('Masa kerja golongan baru')
            ->assertSee('Unit Kerja')
            // Nilai dari data dosen.
            ->assertSee('Dr. Ayu Lestari, M.Kom.')
            ->assertSee('0943761662230152')
            ->assertSee('Bandung, 14 Mei 1987')
            ->assertSee('Perempuan')
            ->assertSee('S2')
            ->assertSee('Ilmu Komputer')
            ->assertSee('Rekayasa Perangkat Lunak')
            ->assertSee('Penata Muda Tk.1 / Golongan III/b')
            ->assertSee('150,00')
            ->assertSee('0 Tahun 09 Bulan')
            ->assertSee('1 Tahun 03 Bulan')
            // Masa penilaian mengikuti isian dosen (September 2018 s.d. Desember 2022).
            ->assertSee('September 2018 s.d. Desember 2022');
    }

    public function test_cetak_menampilkan_blok_pengesahan_pendapat_tim_penilai(): void
    {
        $dosen = $this->makeUser('dosen');
        $this->makeKegiatan($dosen, ['nilai_baru' => 4]);

        // Pimpinan instansi (Rektor) sebagai penanda tangan pengesahan DUPAK.
        $rektor = $this->makeUser('dosen', '9');
        $jabatan = JabatanStruktural::create(['nama' => 'Rektor']);
        StrukturInstansi::create(['jabatan_struktural_id' => $jabatan->id, 'user_id' => $rektor->id]);

        $kepangkatan = Kepangkatan::create(['nama' => 'Penata / IIIc']);
        $dosen->riwayatKepangkatan()->create(['kepangkatan_id' => $kepangkatan->id, 'tmt' => '2021-05-01']);

        $this->actingAs($dosen)
            ->get('/profil/pengajuan/cetak?tahun=2026&semester=ganjil')
            ->assertOk()
            // Blok pengesahan berada setelah baris "Jumlah" (kolom bawah jumlah).
            ->assertSee('Saya menyatakan bahwa Angka kredit telah dihitung dan dokumen pendukung')
            ->assertSee('DUPAK ini telah divalidasi oleh TIM PAK Perguruan Tinggi')
            ->assertSee('Pendapat Tim Penilai Jabatan Angka Kredit')
            ->assertSee('Telah memenuhi syarat angka kredit untuk kenaikan Jabatan dan Pangkat menjadi')
            // Nama jabatan & pangkat dosen (tanpa TMT) tampil pada pendapat tim penilai.
            ->assertSee('Jabatan')
            ->assertSee('Pangkat/Gol')
            ->assertSee('Penata / IIIc')
            // Penanda tangan: pimpinan instansi + blok tanda tangan Tim Penilai Pusat.
            ->assertSee('Ketua')
            ->assertSee($rektor->name)
            ->assertSee('Tim Penilai Pusat');
    }

    public function test_halaman_review_lain_tetap_tanpa_kolom_tim_penilai(): void
    {
        $dosen = $this->makeUser('dosen');
        $this->makeKegiatan($dosen, ['nilai_baru' => 2]);

        $this->actingAs($dosen)
            ->get('/profil/pengajuan/create?tahun=2026&semester=ganjil')
            ->assertOk()
            ->assertSee('Instansi Pengusul')
            // Header grup "Tim Penilai" tidak ditambahkan pada halaman review biasa.
            ->assertDontSee('>Tim Penilai</th>', false)
            ->assertSee('>Link Bukti</th>', false);
    }
}
