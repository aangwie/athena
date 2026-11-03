<?php

namespace App\Http\Controllers;

use App\Http\Requests\SuratBakiRequest;
use App\Http\Requests\SuratBapSenatRequest;
use App\Http\Requests\SuratPaktaIntegritasRequest;
use App\Http\Requests\SuratPernyataanPimpinanRequest;
use App\Http\Requests\SuratPengantarRequest;
use App\Models\Instansi;
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
use App\Support\SuratBakiPdf;
use App\Support\SuratBapSenatPdf;
use App\Support\SuratPaktaIntegritasPdf;
use App\Support\SuratPengantarPdf;
use App\Support\SuratPernyataanPimpinanPdf;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
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
                ->sortBy(fn (Senat $item) => [array_search($item->jabatan, array_keys(Senat::JABATAN)), $item->user?->name ?? ''])
                ->values(),
            // Tempat pada pakta integritas mengikuti kota instansi.
            'kota' => Instansi::query()->value('kota'),
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
     * Pakta integritas siap cetak: PDF A4 (FPDF) yang langsung tampil di peramban.
     */
    public function cetakPaktaIntegritas(SuratPaktaIntegritas $pakta): Response
    {
        $pakta->load(['dosen' => [
            'profilDosen',
            'riwayatKepangkatan.kepangkatan',
            'riwayatJabatanStruktural',
            'riwayatPendidikan',
            'karyaTulis',
        ]]);

        $identitas = $this->identitasDosen($pakta->dosen);

        $pdf = new SuratPaktaIntegritasPdf(
            Instansi::query()->first(),
            $pakta,
            $identitas,
            $pakta->karyaTerpilih(),
        );
        $pdf->buat();

        return response($pdf->isi(), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="'.$pdf->namaBerkas().'"',
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
    public function cetakBapSenat(SuratBapSenat $bap): Response
    {
        $bap->load('dosen');

        $senat = Senat::query()->with('user')->get();

        $pdf = new SuratBapSenatPdf(
            Instansi::query()->first(),
            $bap,
            $this->identitasDosen($bap->dosen),
            $bap->anggotaSenat(),
            $senat->firstWhere('jabatan', 'ketua'),
        );
        $pdf->buat();

        return response($pdf->isi(), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="'.$pdf->namaBerkas().'"',
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
     * Surat pengantar siap cetak: pribadi (blok identitas) atau kolektif (tabel dosen).
     */
    public function cetakPengantar(SuratPengantar $pengantar): Response
    {
        $pdf = new SuratPengantarPdf(
            Instansi::query()->first(),
            $pengantar,
            $pengantar->dosenList()
                ->map(function (User $dosen): array {
                    $identitas = $this->identitasDosen($dosen);

                    return [
                        'nama' => $identitas['nama'],
                        'nidn' => $identitas['nidn'],
                        'nuptk' => $identitas['nuptk'],
                        'jabatan' => $identitas['jabatan'],
                    ];
                })
                ->all(),
            $this->identitas($this->ketua()),
        );
        $pdf->buat();

        return response($pdf->isi(), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="'.$pdf->namaBerkas().'"',
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
     * BAKI siap cetak: berita acara tim komite + daftar hadir (PDF A4, FPDF).
     */
    public function cetakBaki(SuratBaki $baki): Response
    {
        $baki->load('bapSenat.dosen');

        $timKomite = TimKomite::query()
            ->with('user')
            ->get()
            ->sortBy(fn (TimKomite $item) => [array_search($item->jabatan, array_keys(TimKomite::JABATAN)), $item->user?->name ?? ''])
            ->values();

        $pdf = new SuratBakiPdf(
            Instansi::query()->first(),
            $baki,
            $this->identitasDosen($baki->bapSenat?->dosen),
            $timKomite,
            $timKomite->firstWhere('jabatan', 'ketua'),
        );
        $pdf->buat();

        return response($pdf->isi(), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="'.$pdf->namaBerkas().'"',
        ]);
    }

    /**
     * Surat siap cetak: PDF A4 (FPDF) yang langsung tampil di peramban.
     */
    public function cetakPernyataanPimpinan(SuratPernyataanPimpinan $pernyataan): Response
    {
        $pernyataan->load(['pimpinan.jabatanStruktural', 'pimpinan.user', 'dosen']);

        $pdf = new SuratPernyataanPimpinanPdf(
            Instansi::query()->first(),
            $pernyataan,
            $this->identitas($pernyataan->pimpinan?->user, $pernyataan->pimpinan?->jabatanStruktural?->nama),
            $this->identitas($pernyataan->dosen),
        );
        $pdf->buat();

        return response($pdf->isi(), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="'.$pdf->namaBerkas().'"',
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
            'karya_tulis_ids' => array_map('intval', $data['karya_tulis_ids'] ?? []),
        ];
    }

    /**
     * Identitas dosen untuk badan surat, dengan jabatan akademik sebagai "Jabatan".
     *
     * @return array<string, string>
     */
    private function identitasDosen(?User $dosen): array
    {
        $identitas = $this->identitas($dosen);

        // Pada surat, "Jabatan / TMT" adalah jabatan akademik, bukan struktural.
        $fungsional = $dosen?->riwayatJabatanStruktural->sortByDesc('tmt')->first();

        $identitas['jabatan'] = $fungsional?->fungsionalLabel() ?? $identitas['jabatan'];
        $identitas['jabatan_tmt'] = $fungsional?->tmtLabel() ?? $identitas['jabatan_tmt'];

        return $identitas;
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
            'prosentase_penelitian' => (int) $data['prosentase_penelitian'],
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
     * ponytail: belum ada kolom NIP & status dosen tetap — NIP memakai NIY/NPP,
     * status tampil "—" sampai field kepegawaian ditambahkan.
     *
     * @return array<string, string>
     */
    private function identitas(?User $user, ?string $jabatan = null): array
    {
        $user?->loadMissing([
            'profilDosen',
            'riwayatKepangkatan.kepangkatan',
            'riwayatJabatanStruktural',
            'riwayatPendidikan',
        ]);

        $profil = $user?->profilDosen;
        $pangkat = $user?->riwayatKepangkatan->sortByDesc('tmt')->first();
        $jabatanTerakhir = $user?->riwayatJabatanStruktural->sortByDesc('tmt')->first();
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
            'jabatan' => $jabatan ?: ($jabatanTerakhir?->strukturalLabel() ?? ($user?->jabatan_fungsional ?: '—')),
            'jabatan_tmt' => $jabatanTerakhir?->tmtLabel() ?? '—',
            'pendidikan' => $pendidikan?->jenjangLabel() ?? '—',
            'fakultas' => '—',
            'prodi' => $user?->program_studi ?: '—',
        ];
    }
}
