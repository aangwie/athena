<?php

namespace App\Http\Controllers;

use App\Http\Requests\KegiatanPelaksanaanRequest;
use App\Http\Requests\KegiatanPendidikanRequest;
use App\Http\Requests\KegiatanPenelitianRequest;
use App\Http\Requests\KegiatanPengabdianRequest;
use App\Http\Requests\KegiatanPenunjangRequest;
use App\Models\KegiatanDupak;
use App\Models\PengajuanDupak;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class PengajuanDosenController extends Controller
{
    /**
     * Empat komponen kegiatan DUPAK. Urutan array menentukan urutan tab.
     *
     * @var array<string, array{label: string, deskripsi: string}>
     */
    public const KOMPONEN = [
        'pendidikan' => [
            'label' => 'Pendidikan',
            'deskripsi' => 'Ijazah, diklat prajabatan, kegiatan perkuliahan, pembimbingan, pengujian, dan pengembangan bahan ajar.',
        ],
        'penelitian' => [
            'label' => 'Penelitian',
            'deskripsi' => 'Hasil penelitian, publikasi ilmiah, buku ajar, dan kekayaan intelektual yang diakui.',
        ],
        'abdimas' => [
            'label' => 'Pengabdian kepada Masyarakat',
            'deskripsi' => 'Kegiatan pengabdian kepada masyarakat beserta hasil dan bukti pelaksanaannya.',
        ],
        'penunjang' => [
            'label' => 'Penunjang',
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

        return view('profil.pengajuan.create', [
            'komponen' => self::KOMPONEN,
            'tahun' => $tahun,
            'semester' => $semester,
            'pengajuan' => $request->user()->pengajuanDupak()
                ->where('tahun', $tahun)
                ->where('semester', $semester)
                ->first(),
            'entriEdit' => $entriEdit?->komponen === 'pendidikan' ? $entriEdit : null,
            'entriEditPelaksanaan' => $entriEdit?->komponen === 'pelaksanaan_pendidikan' ? $entriEdit : null,
            'entriEditPenelitian' => $entriEdit?->komponen === 'penelitian' ? $entriEdit : null,
            'entriEditAbdimas' => $entriEdit?->komponen === 'abdimas' ? $entriEdit : null,
            'entriEditPenunjang' => $entriEdit?->komponen === 'penunjang' ? $entriEdit : null,
            ...$request->user()->kegiatanPeriode($tahun, $semester),
        ]);
    }

    /**
     * Kirim usulan periode ini ke TIM PAK untuk dinilai.
     */
    public function ajukan(Request $request): RedirectResponse
    {
        [$tahun, $semester] = $this->periodeInput($request);

        if ($request->user()->pengajuanDupak()
            ->where('tahun', $tahun)
            ->where('semester', $semester)
            ->first()?->nilaiTerkunci()) {
            return $this->kembaliKeReview($tahun, $semester)
                ->with('error', 'DUPAK periode ini sudah disetujui admin, usulan tidak bisa diajukan lagi.');
        }

        if ($request->user()->kegiatanDupak()->periode($tahun, $semester)->doesntExist()) {
            return $this->kembaliKeReview($tahun, $semester)
                ->with('error', 'Belum ada kegiatan pada periode ini yang bisa diajukan.');
        }

        $request->user()->pengajuanDupak()->updateOrCreate(
            ['tahun' => $tahun, 'semester' => $semester],
            ['status' => 'diajukan', 'diajukan_at' => now(), 'dinilai_at' => null],
        );

        return $this->kembaliKeReview($tahun, $semester)
            ->with('status', 'Usulan dikirim ke TIM PAK. Nilai angka kredit akan muncul setelah dinilai.');
    }

    /**
     * Ajukan DUPAK final setelah nilai dari TIM PAK tersedia.
     */
    public function ajukanDupak(Request $request): RedirectResponse
    {
        [$tahun, $semester] = $this->periodeInput($request);

        $pengajuan = $request->user()->pengajuanDupak()
            ->where('tahun', $tahun)
            ->where('semester', $semester)
            ->first();

        if ($pengajuan?->status !== 'dinilai') {
            return $this->kembaliKeReview($tahun, $semester)
                ->with('error', 'DUPAK bisa diajukan setelah TIM PAK menyimpan nilai periode ini.');
        }

        $pengajuan->update(['status' => 'diajukan_dupak']);

        return $this->kembaliKeReview($tahun, $semester)
            ->with('status', 'DUPAK periode ini berhasil diajukan.');
    }

    /**
     * Riwayat pengajuan: tab nilai PAK dan tab ajuan DUPAK.
     */
    public function riwayat(Request $request): View
    {
        $pengajuan = $request->user()->pengajuanDupak()
            ->whereNotNull('diajukan_at')
            ->orderByDesc('tahun')
            ->orderByDesc('semester')
            ->get();

        // Satu query: total angka kredit hasil penilaian TIM PAK per periode.
        $nilai = $request->user()->kegiatanDupak()
            ->selectRaw('tahun, semester, SUM(nilai_baru) as total')
            ->groupBy('tahun', 'semester')
            ->get()
            ->keyBy(fn ($baris) => $baris->tahun.'-'.$baris->semester);

        return view('profil.pengajuan.riwayat', [
            'pengajuan' => $pengajuan,
            'dupak' => $pengajuan->whereIn('status', PengajuanDupak::STATUS_DUPAK)->values(),
            'nilai' => $nilai,
        ]);
    }

    /**
     * Ajukan ulang nilai PAK satu periode setelah dosen melakukan perbaikan.
     */
    public function ajukanUlang(Request $request): RedirectResponse
    {
        [$tahun, $semester] = $this->periodeInput($request);

        $pengajuan = $request->user()->pengajuanDupak()
            ->where('tahun', $tahun)
            ->where('semester', $semester)
            ->first();

        if ($pengajuan?->nilaiTerkunci()) {
            return redirect()->route('profil.pengajuan.riwayat')
                ->with('error', 'DUPAK periode ini sudah disetujui admin, nilai tidak bisa diajukan ulang.');
        }

        if ($pengajuan?->dinilai_at === null) {
            return redirect()->route('profil.pengajuan.riwayat')
                ->with('error', 'Nilai PAK periode ini belum tersedia untuk diajukan ulang.');
        }

        $pengajuan->update(['status' => 'diajukan', 'diajukan_at' => now(), 'dinilai_at' => null]);

        return redirect()->route('profil.pengajuan.riwayat')
            ->with('status', 'Nilai PAK periode '.$pengajuan->periodeLabel().' diajukan ulang ke TIM PAK.');
    }

    /**
     * Halaman cetak DUPAK (disimpan sebagai PDF lewat dialog cetak peramban).
     */
    public function cetak(Request $request): View|RedirectResponse
    {
        $tahun = (int) $request->query('tahun');
        $semester = $request->query('semester');

        if ($tahun < 2000 || $tahun > 2100
            || ! is_string($semester) || ! array_key_exists($semester, KegiatanDupak::SEMESTER)) {
            return redirect()->route('profil.pengajuan.periode');
        }

        return view('profil.pengajuan.cetak', [
            'tahun' => $tahun,
            'semester' => $semester,
            'pengajuan' => $request->user()->pengajuanDupak()
                ->where('tahun', $tahun)
                ->where('semester', $semester)
                ->first(),
            // Penanda tangan DUPAK: akun TIM PAK yang aktif.
            // ponytail: belum dicatat per penilaian (tidak ada kolom dinilai_oleh);
            // tambahkan kolom itu kalau penilai TIM PAK lebih dari satu orang.
            'penilai' => User::query()
                ->where('is_active', true)
                ->where(fn ($query) => $query->where('role', 'tim_pak')
                    ->orWhereJsonContains('roles', 'tim_pak'))
                ->orderBy('name')
                ->first(),
            ...$request->user()->kegiatanPeriode($tahun, $semester),
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

    public function storeAbdimas(KegiatanPengabdianRequest $request): RedirectResponse
    {
        $kegiatan = $request->user()->kegiatanDupak()->create([
            'komponen' => 'abdimas',
            ...$request->validated(),
        ]);

        return $this->kembaliKeTab('Entri bidang pengabdian kepada masyarakat berhasil ditambahkan.', $kegiatan, 'abdimas');
    }

    public function updateAbdimas(KegiatanPengabdianRequest $request, KegiatanDupak $kegiatan): RedirectResponse
    {
        $this->ensureKegiatan($request, $kegiatan, 'abdimas');

        $kegiatan->update($request->validated());

        return $this->kembaliKeTab('Entri bidang pengabdian kepada masyarakat berhasil diperbarui.', $kegiatan, 'abdimas');
    }

    public function destroyAbdimas(Request $request, KegiatanDupak $kegiatan): RedirectResponse
    {
        $this->ensureKegiatan($request, $kegiatan, 'abdimas');

        $kegiatan->delete();

        return $this->kembaliKeTab('Entri bidang pengabdian kepada masyarakat berhasil dihapus.', $kegiatan, 'abdimas');
    }

    public function storePenunjang(KegiatanPenunjangRequest $request): RedirectResponse
    {
        $kegiatan = $request->user()->kegiatanDupak()->create([
            'komponen' => 'penunjang',
            ...$request->validated(),
        ]);

        return $this->kembaliKeTab('Entri bidang penunjang berhasil ditambahkan.', $kegiatan, 'penunjang');
    }

    public function updatePenunjang(KegiatanPenunjangRequest $request, KegiatanDupak $kegiatan): RedirectResponse
    {
        $this->ensureKegiatan($request, $kegiatan, 'penunjang');

        $kegiatan->update($request->validated());

        return $this->kembaliKeTab('Entri bidang penunjang berhasil diperbarui.', $kegiatan, 'penunjang');
    }

    public function destroyPenunjang(Request $request, KegiatanDupak $kegiatan): RedirectResponse
    {
        $this->ensureKegiatan($request, $kegiatan, 'penunjang');

        $kegiatan->delete();

        return $this->kembaliKeTab('Entri bidang penunjang berhasil dihapus.', $kegiatan, 'penunjang');
    }

    /**
     * Periode yang dikirim tombol aksi pada tab Review.
     *
     * @return array{0: int, 1: string}
     */
    private function periodeInput(Request $request): array
    {
        $data = $request->validate([
            'tahun' => ['required', 'integer', 'between:2000,2100'],
            'semester' => ['required', Rule::in(array_keys(KegiatanDupak::SEMESTER))],
        ]);

        return [(int) $data['tahun'], (string) $data['semester']];
    }

    private function kembaliKeReview(int $tahun, string $semester): RedirectResponse
    {
        return redirect()->to(route('profil.pengajuan.create', [
            'tahun' => $tahun,
            'semester' => $semester,
        ]).'#review');
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
