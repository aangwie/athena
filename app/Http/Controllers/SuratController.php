<?php

namespace App\Http\Controllers;

use App\Http\Requests\SuratBakiRequest;
use App\Http\Requests\SuratBapSenatRequest;
use App\Http\Requests\SuratPaktaIntegritasRequest;
use App\Http\Requests\SuratPengantarRequest;
use App\Http\Requests\SuratPernyataanPimpinanRequest;
use App\Models\Instansi;
use App\Models\JabatanFungsional;
use App\Models\KaryaTulis;
use App\Models\RiwayatPendidikan;
use App\Models\Senat;
use App\Models\StrukturInstansi;
use App\Models\SuratBaki;
use App\Models\SuratBapSenat;
use App\Models\SuratPaktaIntegritas;
use App\Models\SuratPengantar;
use App\Models\SuratPernyataanPimpinan;
use App\Models\TimKomite;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Illuminate\View\View;

class SuratController extends Controller
{
    public function index(Request $request): View
    {
        return view('surat.index', [
            'pimpinanList' => StrukturInstansi::query()
                ->with(['jabatanStruktural', 'user'])
                ->get()
                ->sortBy(fn (StrukturInstansi $item) => $item->jabatanStruktural?->nama ?? '')
                ->values(),
            'dosenList' => User::query()
                ->where('role', 'dosen')
                ->where('is_active', true)
                ->orderBy('name')
                ->get(),
            'pernyataanList' => SuratPernyataanPimpinan::query()
                ->with(['pimpinan.jabatanStruktural', 'pimpinan.user', 'dosen'])
                ->orderByDesc('tanggal_surat')
                ->orderByDesc('id')
                ->get(),
            // ?edit={id} memakai ulang formulir di atas sebagai mode ubah.
            'pernyataanEdit' => $request->filled('edit')
                ? SuratPernyataanPimpinan::query()->find($request->integer('edit'))
                : null,
            'paktaList' => SuratPaktaIntegritas::query()
                ->with('dosen')
                ->orderByDesc('tanggal_pakta')
                ->orderByDesc('id')
                ->get(),
            'paktaEdit' => $request->filled('edit_pakta')
                ? SuratPaktaIntegritas::query()->find($request->integer('edit_pakta'))
                : null,
            'bapList' => SuratBapSenat::query()
                ->with('dosen')
                ->orderByDesc('tanggal_bap')
                ->orderByDesc('id')
                ->get(),
            'bapEdit' => $request->filled('edit_bap')
                ? SuratBapSenat::query()->find($request->integer('edit_bap'))
                : null,
            'bakiList' => SuratBaki::query()
                ->with('bapSenat.dosen')
                ->orderByDesc('tanggal_surat')
                ->orderByDesc('id')
                ->get(),
            'bakiEdit' => $request->filled('edit_baki')
                ? SuratBaki::query()->find($request->integer('edit_baki'))
                : null,
            // Pilihan usulan senat (BAP) yang ditindaklanjuti BAKI.
            'usulanSenatList' => SuratBapSenat::query()
                ->with('dosen')
                ->orderByDesc('tanggal_bap')
                ->orderByDesc('id')
                ->get(),
            // Susunan tim komite untuk hitungan kuorum & daftar hadir BAKI.
            'timKomiteList' => TimKomite::query()
                ->with('user')
                ->get()
                ->sortBy(fn (TimKomite $item) => [array_search($item->jabatan, array_keys(TimKomite::JABATAN)), $item->user?->name ?? ''])
                ->values(),
            // Pilihan anggota senat pada formulir BAP (multi select).
            'senatList' => Senat::query()
                ->with('user')
                ->get()
                ->sortBy(fn (Senat $item) => [$item->urutanJabatan(), $item->user?->name ?? ''])
                ->values(),
            // Tempat pada pakta integritas mengikuti kota instansi.
            'kota' => Instansi::query()->value('kota'),
            // Pilihan jabatan akademik yang diusulkan (surat pengantar perorangan).
            'jabatanList' => JabatanFungsional::query()->orderBy('nama')->get(),
            'pengantarList' => SuratPengantar::query()
                ->orderByDesc('tanggal_surat')
                ->orderByDesc('id')
                ->get(),
            'pengantarEdit' => $request->filled('edit_pengantar')
                ? SuratPengantar::query()->find($request->integer('edit_pengantar'))
                : null,
            // Pilihan karya ilmiah per dosen (dipakai picker pada form pakta integritas).
            'karyaPerDosen' => KaryaTulis::query()
                ->orderBy('id')
                ->get(['id', 'user_id', 'karya_ilmiah', 'judul'])
                ->groupBy('user_id')
                ->map(fn ($items) => $items->map(fn ($karya) => [
                    'id' => $karya->id,
                    'jenis' => $karya->karya_ilmiah,
                    'judul' => $karya->judul,
                ])),
        ]);
    }

    public function storePernyataanPimpinan(SuratPernyataanPimpinanRequest $request): RedirectResponse
    {
        SuratPernyataanPimpinan::query()->create($this->dataSurat($request));

        return redirect()
            ->route('surat.index')
            ->withFragment('pernyataan-pimpinan')
            ->with('status', 'Pernyataan pimpinan berhasil disimpan.');
    }

    public function updatePernyataanPimpinan(SuratPernyataanPimpinanRequest $request, SuratPernyataanPimpinan $pernyataan): RedirectResponse
    {
        $pernyataan->update($this->dataSurat($request));

        return redirect()
            ->route('surat.index')
            ->withFragment('pernyataan-pimpinan')
            ->with('status', 'Pernyataan pimpinan berhasil diperbarui.');
    }

    public function destroyPernyataanPimpinan(SuratPernyataanPimpinan $pernyataan): RedirectResponse
    {
        $pernyataan->delete();

        return redirect()
            ->route('surat.index')
            ->withFragment('pernyataan-pimpinan')
            ->with('status', 'Pernyataan pimpinan berhasil dihapus.');
    }

    public function storePaktaIntegritas(SuratPaktaIntegritasRequest $request): RedirectResponse
    {
        SuratPaktaIntegritas::query()->create($this->dataPakta($request));

        return redirect()
            ->route('surat.index')
            ->withFragment('pakta-integritas')
            ->with('status', 'Pakta integritas berhasil disimpan.');
    }

    public function updatePaktaIntegritas(SuratPaktaIntegritasRequest $request, SuratPaktaIntegritas $pakta): RedirectResponse
    {
        $pakta->update($this->dataPakta($request));

        return redirect()
            ->route('surat.index')
            ->withFragment('pakta-integritas')
            ->with('status', 'Pakta integritas berhasil diperbarui.');
    }

    public function destroyPaktaIntegritas(SuratPaktaIntegritas $pakta): RedirectResponse
    {
        $pakta->delete();

        return redirect()
            ->route('surat.index')
            ->withFragment('pakta-integritas')
            ->with('status', 'Pakta integritas berhasil dihapus.');
    }

    /**
     * Pakta integritas siap cetak: halaman pratinjau A4 (HTML) yang marginnya dapat
     * disesuaikan sebelum dicetak lewat dialog cetak peramban — mengikuti formulir SKP.
     */
    public function cetakPaktaIntegritas(SuratPaktaIntegritas $pakta): View
    {
        $pakta->load(['dosen' => [
            'profilDosen',
            'riwayatKepangkatan.kepangkatan',
            'riwayatJabatanStruktural',
            'riwayatPendidikan',
            'karyaTulis',
        ]]);

        return view('surat.pakta-integritas.cetak', [
            'pakta' => $pakta,
            // Tempat pada baris tanda tangan mengikuti kota instansi.
            'kota' => Instansi::query()->value('kota'),
            'identitas' => $this->identitas($pakta->dosen),
            'karya' => $pakta->karyaTerpilih(),
        ]);
    }

    /**
     * Lembar validasi karya ilmiah siap cetak: tabel identitas karya (jurnal/prosiding)
     * yang divalidasi, dengan format surat tersendiri.
     */
    public function cetakValidasiPaktaIntegritas(SuratPaktaIntegritas $pakta): View
    {
        $pakta->load(['dosen' => [
            'profilDosen',
            'riwayatKepangkatan.kepangkatan',
            'riwayatJabatanStruktural',
            'riwayatPendidikan',
            'karyaTulis',
        ]]);

        return view('surat.pakta-integritas.validasi', [
            'pakta' => $pakta,
            'instansi' => Instansi::query()->first(),
            // Tempat pada baris tanda tangan mengikuti kota instansi.
            'kota' => Instansi::query()->value('kota'),
            'identitas' => $this->identitas($pakta->dosen),
            // Ketua instansi sebagai penanda tangan lembar validasi.
            'ketua' => $this->identitas($this->ketua()),
            'karya' => $pakta->karyaTerpilih(),
        ]);
    }

    public function storeBapSenat(SuratBapSenatRequest $request): RedirectResponse
    {
        SuratBapSenat::query()->create($this->dataBap($request));

        return redirect()
            ->route('surat.index')
            ->withFragment('bap-senat')
            ->with('status', 'BAP senat berhasil disimpan.');
    }

    public function updateBapSenat(SuratBapSenatRequest $request, SuratBapSenat $bap): RedirectResponse
    {
        $bap->update($this->dataBap($request));

        return redirect()
            ->route('surat.index')
            ->withFragment('bap-senat')
            ->with('status', 'BAP senat berhasil diperbarui.');
    }

    public function destroyBapSenat(SuratBapSenat $bap): RedirectResponse
    {
        $bap->delete();

        return redirect()
            ->route('surat.index')
            ->withFragment('bap-senat')
            ->with('status', 'BAP senat berhasil dihapus.');
    }

    /**
     * BAP senat siap cetak: PDF A4 (FPDF) berisi berita acara + daftar hadir rapat.
     */
    public function cetakBapSenat(SuratBapSenat $bap): View
    {
        $bap->load('dosen');

        $senat = Senat::query()->with('user')->get();

        // Halaman pratinjau A4 (HTML): berita acara (halaman 1) dan daftar hadir
        // (halaman 2) dipisah, dengan pilihan halaman & margin sebelum dicetak.
        return view('surat.bap-senat.cetak', [
            'bap' => $bap,
            'instansi' => Instansi::query()->first(),
            // Tempat pada baris tanda tangan mengikuti kota instansi.
            'kota' => Instansi::query()->value('kota'),
            'identitas' => $this->identitas($bap->dosen),
            'anggota' => $bap->anggotaSenat(),
            'ketua' => $senat->firstWhere('jabatan', 'ketua'),
        ]);
    }

    public function storePengantar(SuratPengantarRequest $request): RedirectResponse
    {
        SuratPengantar::query()->create($this->dataPengantar($request));

        return redirect()
            ->route('surat.index')
            ->withFragment('surat-pengantar')
            ->with('status', 'Surat pengantar berhasil disimpan.');
    }

    public function updatePengantar(SuratPengantarRequest $request, SuratPengantar $pengantar): RedirectResponse
    {
        $pengantar->update($this->dataPengantar($request));

        return redirect()
            ->route('surat.index')
            ->withFragment('surat-pengantar')
            ->with('status', 'Surat pengantar berhasil diperbarui.');
    }

    public function destroyPengantar(SuratPengantar $pengantar): RedirectResponse
    {
        $pengantar->delete();

        return redirect()
            ->route('surat.index')
            ->withFragment('surat-pengantar')
            ->with('status', 'Surat pengantar berhasil dihapus.');
    }

    /**
     * Surat pengantar siap cetak: halaman pratinjau A4 (HTML) bentuk pribadi maupun
     * kolektif, dengan pilihan margin sebelum dicetak.
     */
    public function cetakPengantar(SuratPengantar $pengantar): View
    {
        return view('surat.pengantar.cetak', [
            'pengantar' => $pengantar,
            'instansi' => Instansi::query()->first(),
            // Tempat pada baris tanda tangan mengikuti kota instansi.
            'kota' => Instansi::query()->value('kota'),
            'dosen' => $pengantar->dosenList()
                ->map(fn (User $dosen): array => $this->identitas($dosen))
                ->all(),
            // Penanda tangan surat pengantar: pimpinan berjabatan Ketua/Rektor.
            'pimpinan' => $this->identitas($this->ketua()),
        ]);
    }

    public function storeBaki(SuratBakiRequest $request): RedirectResponse
    {
        SuratBaki::query()->create($this->dataBaki($request));

        return redirect()
            ->route('surat.index')
            ->withFragment('baki')
            ->with('status', 'BAKI berhasil disimpan.');
    }

    public function updateBaki(SuratBakiRequest $request, SuratBaki $baki): RedirectResponse
    {
        $baki->update($this->dataBaki($request));

        return redirect()
            ->route('surat.index')
            ->withFragment('baki')
            ->with('status', 'BAKI berhasil diperbarui.');
    }

    public function destroyBaki(SuratBaki $baki): RedirectResponse
    {
        $baki->delete();

        return redirect()
            ->route('surat.index')
            ->withFragment('baki')
            ->with('status', 'BAKI berhasil dihapus.');
    }

    /**
     * BAKI siap cetak: halaman pratinjau A4 (HTML) berisi berita acara (halaman 1)
     * dan daftar hadir (halaman 2), dengan pilihan halaman & margin sebelum dicetak.
     */
    public function cetakBaki(SuratBaki $baki): View
    {
        $baki->load('bapSenat.dosen');

        // Susunan tim komite pada daftar hadir mengikuti urutan input admin (id menaik),
        // bukan abjad nama maupun pengelompokan jabatan.
        $timKomite = TimKomite::query()
            ->with('user')
            ->orderBy('id')
            ->get();

        return view('surat.baki.cetak', [
            'baki' => $baki,
            'instansi' => Instansi::query()->first(),
            // Tempat pada baris tanda tangan mengikuti kota instansi.
            'kota' => Instansi::query()->value('kota'),
            'identitas' => $this->identitas($baki->bapSenat?->dosen),
            'anggota' => $timKomite,
            'ketua' => $timKomite->firstWhere('jabatan', 'ketua'),
        ]);
    }

    /**
     * Surat siap cetak: PDF A4 (FPDF) yang langsung tampil di peramban.
     */
    /**
     * Surat pernyataan pimpinan siap cetak: halaman pratinjau A4 (HTML) dengan
     * pilihan margin sebelum dicetak.
     */
    public function cetakPernyataanPimpinan(SuratPernyataanPimpinan $pernyataan): View
    {
        $pernyataan->load(['pimpinan.user', 'dosen']);

        return view('surat.pernyataan-pimpinan.cetak', [
            'pernyataan' => $pernyataan,
            'instansi' => Instansi::query()->first(),
            // Tempat pada baris tanda tangan mengikuti kota instansi.
            'kota' => Instansi::query()->value('kota'),
            // Pimpinan surat ini juga dosen, jadi "Jabatan / TMT" memakai jabatan
            // akademik (Riwayat Pekerjaan → Jabatan Akademik) beserta TMT-nya,
            // sama seperti blok identitas dosen di bawahnya — bukan "Ketua".
            'pimpinan' => $this->identitas($pernyataan->pimpinan?->user),
            'dosen' => $this->identitas($pernyataan->dosen),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function dataSurat(SuratPernyataanPimpinanRequest $request): array
    {
        $data = $request->validated();
        $data['tanggal_surat'] = Carbon::createFromFormat('d-m-Y', $data['tanggal_surat'])->toDateString();

        return $data;
    }

    /**
     * @return array<string, mixed>
     */
    private function dataPakta(SuratPaktaIntegritasRequest $request): array
    {
        $data = $request->validated();

        return [
            'user_id' => $data['dosen_id'],
            'tanggal_pakta' => Carbon::createFromFormat('d-m-Y', $data['tanggal_pakta'])->toDateString(),
            'nomor_validasi' => $data['nomor_validasi'] ?? null,
            // Kosong berarti lembar validasi memakai tanggal pakta (perilaku sebelumnya).
            'tanggal_validasi' => filled($data['tanggal_validasi'] ?? null)
                ? Carbon::createFromFormat('d-m-Y', $data['tanggal_validasi'])->toDateString()
                : null,
            'karya_tulis_ids' => array_map('intval', $data['karya_tulis_ids'] ?? []),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function dataBaki(SuratBakiRequest $request): array
    {
        $data = $request->validated();

        return [
            'nomor_surat' => $data['nomor_surat'],
            'surat_bap_senat_id' => $data['surat_bap_senat_id'],
            'tanggal_surat' => Carbon::createFromFormat('d-m-Y', $data['tanggal_surat'])->toDateString(),
            'tempat_pelaksanaan' => $data['tempat_pelaksanaan'],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function dataBap(SuratBapSenatRequest $request): array
    {
        $data = $request->validated();

        return [
            'nomor_bap' => $data['nomor_bap'],
            'tanggal_bap' => Carbon::createFromFormat('d-m-Y', $data['tanggal_bap'])->toDateString(),
            'user_id' => $data['dosen_id'],
            'bidang_keilmuan' => $data['bidang_keilmuan'],
            'kenaikan_jabatan' => $data['kenaikan_jabatan'],
            'prosentase_penelitian' => isset($data['prosentase_penelitian']) ? (int) $data['prosentase_penelitian'] : null,
            'tempat_pelaksanaan' => $data['tempat_pelaksanaan'],
            'materi_rapat' => $data['materi_rapat'],
            'anggota_senat_ids' => array_map('intval', $data['anggota_senat_ids']),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function dataPengantar(SuratPengantarRequest $request): array
    {
        $data = $request->validated();

        return [
            'nomor_surat' => $data['nomor_surat'],
            'lampiran' => $data['lampiran'] ?? null,
            'perihal' => $data['perihal'],
            'tujuan' => $data['tujuan'] ?? null,
            'kota_tujuan' => $data['kota_tujuan'] ?? null,
            'jabatan_diusulkan' => $request->boolean('kolektif') ? null : ($data['jabatan_diusulkan'] ?? null),
            'tanggal_surat' => Carbon::createFromFormat('d-m-Y', $data['tanggal_surat'])->toDateString(),
            'kolektif' => $request->boolean('kolektif'),
            'dosen_ids' => array_map('intval', $data['dosen_ids']),
        ];
    }

    /**
     * Penanda tangan surat pengantar: pimpinan berjabatan Ketua/Rektor.
     *
     * ponytail: belum ada penanda tangan per surat; bila instansi tidak punya
     * jabatan Ketua/Rektor, dipakai pimpinan pertama pada struktur instansi.
     */
    private function ketua(): ?User
    {
        $pimpinan = StrukturInstansi::query()->with(['jabatanStruktural', 'user'])->get();

        $ketua = $pimpinan->first(fn (StrukturInstansi $item) => in_array(
            Str::lower($item->jabatanStruktural?->nama ?? ''),
            ['ketua', 'rektor'],
            true,
        ));

        return ($ketua ?? $pimpinan->first())?->user;
    }

    /**
     * Identitas pimpinan/dosen yang tampil pada badan surat.
     *
     * "Jabatan" memakai Jabatan Terakhir pada Profil → Riwayat Pekerjaan →
     * Riwayat Jabatan Akademik beserta TMT-nya, bukan jabatan struktural.
     *
     * ponytail: belum ada kolom NIP & status dosen tetap — NIP memakai NIY/NPP,
     * status tampil "—" sampai field kepegawaian ditambahkan.
     *
     * @return array<string, string>
     */
    private function identitas(?User $user): array
    {
        $user?->loadMissing([
            'profilDosen',
            'riwayatKepangkatan.kepangkatan',
            'riwayatJabatanStruktural.jabatanFungsional',
            'riwayatPendidikan',
        ]);

        $profil = $user?->profilDosen;
        $pangkat = $user?->riwayatKepangkatan->sortByDesc('tmt')->first();
        // Jabatan pada identitas surat mengikuti "Jabatan Terakhir" di
        // Profil → Riwayat Pekerjaan → Riwayat Jabatan Akademik: baris riwayat
        // yang memuat jabatan akademik dengan TMT paling akhir. Jabatan
        // struktural (mis. "Ketua") tidak dipakai pada baris ini.
        $jabatanTerakhir = $user?->riwayatJabatanStruktural
            ->whereNotNull('jabatan_fungsional_id')
            ->sortByDesc('tmt')
            ->first();
        $pendidikan = $user?->riwayatPendidikan
            ->sortByDesc(fn (RiwayatPendidikan $item) => $item->jenjangRank())
            ->first();

        return [
            'nama' => $user?->name ?? '—',
            'nip' => $profil?->niy_npp ?: '—',
            'nidn' => $user?->nidn ?: '—',
            'nuptk' => $profil?->nuptk ?: '',
            'status' => '—',
            'ttl' => collect([
                $profil?->tempat_lahir,
                $profil?->tanggal_lahir?->locale('id')->translatedFormat('d F Y'),
            ])->filter()->implode(', ') ?: '—',
            'pangkat' => $pangkat?->pangkatLabel() ?? '—',
            'pangkat_tmt' => $pangkat?->tmtLabel() ?? '—',
            // Belum ada riwayat jabatan akademik: pakai kolom users.jabatan_fungsional
            // (data dosen hasil impor) supaya baris jabatan tetap terisi.
            'jabatan' => $jabatanTerakhir?->jabatanFungsional?->nama ?: ($user?->jabatan_fungsional ?: '—'),
            'jabatan_tmt' => $jabatanTerakhir?->tmtLabel() ?? '—',
            'pendidikan' => $pendidikan?->jenjangLabel() ?? '—',
            'fakultas' => '—',
            'prodi' => $user?->program_studi ?: '—',
        ];
    }
}
