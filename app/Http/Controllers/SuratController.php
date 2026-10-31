<?php

namespace App\Http\Controllers;

use App\Http\Requests\SuratPaktaIntegritasRequest;
use App\Http\Requests\SuratPernyataanPimpinanRequest;
use App\Models\Instansi;
use App\Models\KaryaTulis;
use App\Models\RiwayatPendidikan;
use App\Models\StrukturInstansi;
use App\Models\SuratPaktaIntegritas;
use App\Models\SuratPernyataanPimpinan;
use App\Models\User;
use App\Support\SuratPaktaIntegritasPdf;
use App\Support\SuratPernyataanPimpinanPdf;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Carbon;
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
            // Tempat pada pakta integritas mengikuti kota instansi.
            'kota' => Instansi::query()->value('kota'),
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

        $identitas = $this->identitas($pakta->dosen);

        // Pada surat ini "Jabatan / TMT" adalah jabatan fungsional (akademik), bukan struktural.
        $fungsional = $pakta->dosen?->riwayatJabatanStruktural->sortByDesc('tmt')->first();
        $identitas['jabatan'] = $fungsional?->fungsionalLabel() ?? $identitas['jabatan'];
        $identitas['jabatan_tmt'] = $fungsional?->tmtLabel() ?? $identitas['jabatan_tmt'];

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
            'prodi' => $user?->program_studi ?: '—',
        ];
    }
}
