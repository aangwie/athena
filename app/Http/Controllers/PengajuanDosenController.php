<?php

namespace App\Http\Controllers;

use App\Http\Requests\KegiatanPelaksanaanRequest;
use App\Http\Requests\KegiatanPendidikanRequest;
use App\Http\Requests\KegiatanPenelitianRequest;
use App\Models\KegiatanDupak;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PengajuanDosenController extends Controller
{
    /**
     * Empat komponen kegiatan DUPAK. Urutan array menentukan urutan tab.
     *
     * @var array<string, array{label: string, singkat: string, deskripsi: string}>
     */
    public const KOMPONEN = [
        'pendidikan' => [
            'label' => 'Pendidikan',
            'singkat' => 'Pendidikan formal, diklat, perkuliahan, pembimbingan, dan pengujian.',
            'deskripsi' => 'Ijazah, diklat prajabatan, kegiatan perkuliahan, pembimbingan, pengujian, dan pengembangan bahan ajar.',
        ],
        'penelitian' => [
            'label' => 'Penelitian',
            'singkat' => 'Karya ilmiah, publikasi, buku, dan paten.',
            'deskripsi' => 'Hasil penelitian, publikasi ilmiah, buku ajar, dan kekayaan intelektual yang diakui.',
        ],
        'abdimas' => [
            'label' => 'Pengabdian kepada Masyarakat',
            'singkat' => 'Kegiatan pengabdian dan pemberdayaan masyarakat.',
            'deskripsi' => 'Kegiatan pengabdian kepada masyarakat beserta hasil dan bukti pelaksanaannya.',
        ],
        'penunjang' => [
            'label' => 'Penunjang',
            'singkat' => 'Organisasi profesi, penghargaan, dan tugas tambahan.',
            'deskripsi' => 'Kegiatan penunjang tugas dosen: organisasi profesi, penghargaan, dan tugas tambahan.',
        ],
    ];

    public function periode(): View
    {
        return view('profil.pengajuan.periode');
    }

    public function create(Request $request): View|RedirectResponse
    {
        $entriEdit = $request->filled('edit')
            ? $request->user()->kegiatanDupak()->findOrFail($request->integer('edit'))
            : null;

        $tahun = (int) $request->query('tahun') ?: (int) $entriEdit?->tahun;
        $semester = $request->query('semester');
        $semester = is_string($semester) && array_key_exists($semester, KegiatanDupak::SEMESTER)
            ? $semester
            : (string) $entriEdit?->semester;

        if ($tahun < 2000 || $tahun > 2100 || ! array_key_exists($semester, KegiatanDupak::SEMESTER)) {
            return redirect()->route('profil.pengajuan.periode');
        }

        $entri = $request->user()->kegiatanDupak()->latest();

        return view('profil.pengajuan.create', [
            'komponen' => self::KOMPONEN,
            'tahun' => $tahun,
            'semester' => $semester,
            'entriEdit' => $entriEdit?->komponen === 'pendidikan' ? $entriEdit : null,
            'entriEditPelaksanaan' => $entriEdit?->komponen === 'pelaksanaan_pendidikan' ? $entriEdit : null,
            'entriEditPenelitian' => $entriEdit?->komponen === 'penelitian' ? $entriEdit : null,
            'kegiatanPendidikan' => (clone $entri)->where('komponen', 'pendidikan')->get(),
            'kegiatanPelaksanaan' => (clone $entri)->where('komponen', 'pelaksanaan_pendidikan')->get(),
            'kegiatanPenelitian' => (clone $entri)->where('komponen', 'penelitian')->get(),
        ]);
    }

    public function storePendidikan(KegiatanPendidikanRequest $request): RedirectResponse
    {
        $kegiatan = $request->user()->kegiatanDupak()->create([
            'komponen' => 'pendidikan',
            ...$request->validated(),
        ]);

        return $this->kembaliKeTab('Entri bidang pendidikan berhasil ditambahkan.', $kegiatan);
    }

    public function updatePendidikan(KegiatanPendidikanRequest $request, KegiatanDupak $kegiatan): RedirectResponse
    {
        $this->ensureKegiatan($request, $kegiatan, 'pendidikan');

        $kegiatan->update($request->validated());

        return $this->kembaliKeTab('Entri bidang pendidikan berhasil diperbarui.', $kegiatan);
    }

    public function destroyPendidikan(Request $request, KegiatanDupak $kegiatan): RedirectResponse
    {
        $this->ensureKegiatan($request, $kegiatan, 'pendidikan');

        $kegiatan->delete();

        return $this->kembaliKeTab('Entri bidang pendidikan berhasil dihapus.', $kegiatan);
    }

    public function storePelaksanaan(KegiatanPelaksanaanRequest $request): RedirectResponse
    {
        $kegiatan = $request->user()->kegiatanDupak()->create([
            'komponen' => 'pelaksanaan_pendidikan',
            ...$request->validated(),
        ]);

        return $this->kembaliKeTab('Entri pelaksanaan pendidikan berhasil ditambahkan.', $kegiatan);
    }

    public function updatePelaksanaan(KegiatanPelaksanaanRequest $request, KegiatanDupak $kegiatan): RedirectResponse
    {
        $this->ensureKegiatan($request, $kegiatan, 'pelaksanaan_pendidikan');

        $kegiatan->update($request->validated());

        return $this->kembaliKeTab('Entri pelaksanaan pendidikan berhasil diperbarui.', $kegiatan);
    }

    public function destroyPelaksanaan(Request $request, KegiatanDupak $kegiatan): RedirectResponse
    {
        $this->ensureKegiatan($request, $kegiatan, 'pelaksanaan_pendidikan');

        $kegiatan->delete();

        return $this->kembaliKeTab('Entri pelaksanaan pendidikan berhasil dihapus.', $kegiatan);
    }

    public function storePenelitian(KegiatanPenelitianRequest $request): RedirectResponse
    {
        $kegiatan = $request->user()->kegiatanDupak()->create([
            'komponen' => 'penelitian',
            ...$request->validated(),
        ]);

        return $this->kembaliKeTab('Entri bidang penelitian berhasil ditambahkan.', $kegiatan, 'penelitian');
    }

    public function updatePenelitian(KegiatanPenelitianRequest $request, KegiatanDupak $kegiatan): RedirectResponse
    {
        $this->ensureKegiatan($request, $kegiatan, 'penelitian');

        $kegiatan->update($request->validated());

        return $this->kembaliKeTab('Entri bidang penelitian berhasil diperbarui.', $kegiatan, 'penelitian');
    }

    public function destroyPenelitian(Request $request, KegiatanDupak $kegiatan): RedirectResponse
    {
        $this->ensureKegiatan($request, $kegiatan, 'penelitian');

        $kegiatan->delete();

        return $this->kembaliKeTab('Entri bidang penelitian berhasil dihapus.', $kegiatan, 'penelitian');
    }

    private function ensureKegiatan(Request $request, KegiatanDupak $kegiatan, string $komponen): void
    {
        abort_unless(
            $kegiatan->user_id === $request->user()->id && $kegiatan->komponen === $komponen,
            404,
        );
    }

    private function kembaliKeTab(string $pesan, KegiatanDupak $kegiatan, string $tab = 'pendidikan'): RedirectResponse
    {
        return redirect()
            ->to(route('profil.pengajuan.create', [
                'tahun' => $kegiatan->tahun,
                'semester' => $kegiatan->semester,
            ]).'#'.$tab)
            ->with('status', $pesan);
    }
}
