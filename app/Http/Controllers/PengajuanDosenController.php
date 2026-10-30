<?php

namespace App\Http\Controllers;

use App\Http\Requests\KegiatanPelaksanaanRequest;
use App\Http\Requests\KegiatanPendidikanRequest;
use App\Http\Requests\KegiatanPenelitianRequest;
use App\Http\Requests\KegiatanPengabdianRequest;
use App\Http\Requests\KegiatanPenunjangRequest;
use App\Models\KegiatanDupak;
use App\Models\PengajuanDupak;
use App\Models\SyaratKhusus;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

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

    /**
     * Berkas syarat khusus pengajuan pertama beserta batas ukurannya (MB).
     *
     * @var array<string, array{label: string, deskripsi: string, max_mb: int}>
     */
    public const SYARAT_KHUSUS = [
        'bkd' => [
            'label' => 'BKD 2 Semester Terakhir (Status Memenuhi, Tridharma PT terpenuhi)',
            'deskripsi' => 'Unggah Bukti Kompilasi LKD 2 Semester terakhir dalam satu file pdf',
            'max_mb' => 3,
        ],
        'ikd' => [
            'label' => 'Bukti Pemenuhan Indikator Kinerja Dosen',
            'deskripsi' => 'Unggah kompilasi Bukti Klaim Indikator Kinerja Dosen sesuai target JAD (Asisten Ahli) dari laman SISTER Dosen dalam satu file pdf. Indikator Kinerja Dosen terdiri dari 2 komponen yaitu: [a] Pendidikan (Kegiatan pembelajaran, pengajaran, dan pengembangan akademik) dan [b] Penelitian & Pengmas (Kontribusi langsung kepada masyarakat dan penerapan ilmu)',
            'max_mb' => 5,
        ],
    ];

    /**
     * Halaman status jabatan akademik sebelum dosen memilih periode pengajuan.
     */
    public function mulai(Request $request): View
    {
        return view('profil.pengajuan.mulai', [
            'jabatan' => $request->user()->jabatan_fungsional ?: null,
        ]);
    }

    public function periode(): View
    {
        return view('profil.pengajuan.periode');
    }

    /**
     * Pemilihan periode untuk dosen yang belum memiliki jabatan akademik.
     */
    public function periodePertama(): View
    {
        return view('profil.pengajuan.periode', [
            'judul' => 'Pilih Periode Pengajuan Pertama',
            'kicker' => 'Pengajuan Jabatan Akademik Pertama',
            'deskripsi' => 'Anda belum memiliki jabatan akademik. Tentukan tahun akademik dan semester, lalu lengkapi syarat khusus pengajuan pertama.',
            'action' => route('profil.pengajuan.syarat-khusus'),
            'labelTombol' => 'Lanjut ke IKD',
        ]);
    }

    /**
     * Syarat khusus pengajuan pertama dosen tanpa jabatan akademik.
     */
    public function syaratKhusus(Request $request): View|RedirectResponse
    {
        $periode = $this->periodeQuery($request);

        if ($periode === null) {
            return redirect()->route('profil.pengajuan.periode-pertama');
        }

        [$tahun, $semester] = $periode;

        return view('profil.pengajuan.syarat-khusus', [
            'tahun' => $tahun,
            'semester' => $semester,
            'berkas' => self::SYARAT_KHUSUS,
            'syaratKhusus' => $this->syaratKhususFor($request, $tahun, $semester),
            'daftar' => SyaratKhusus::query()
                ->where('user_id', $request->user()->id)
                ->orderByDesc('tahun')
                ->orderByDesc('id')
                ->get(),
        ]);
    }

    /**
     * Unggah / ganti berkas syarat khusus (BKD & IKD) untuk periode terpilih.
     */
    public function simpanSyaratKhusus(Request $request): RedirectResponse
    {
        $periode = $this->periodeQuery($request);

        if ($periode === null) {
            return redirect()->route('profil.pengajuan.periode-pertama');
        }

        [$tahun, $semester] = $periode;

        $syaratKhusus = $this->syaratKhususFor($request, $tahun, $semester) ?? new SyaratKhusus([
            'user_id' => $request->user()->id,
            'tahun' => $tahun,
            'semester' => $semester,
        ]);

        if ($syaratKhusus->exists && ! $syaratKhusus->bisaDiubah()) {
            return redirect()
                ->route('profil.pengajuan.syarat-khusus', ['tahun' => $tahun, 'semester' => $semester])
                ->with('error', 'Berkas periode ini sudah diajukan dan tidak dapat diubah.');
        }

        $rules = [];
        $messages = [];

        foreach (self::SYARAT_KHUSUS as $jenis => $item) {
            $rules[$jenis] = [
                filled($syaratKhusus->{$jenis.'_path'}) ? 'nullable' : 'required',
                'file',
                'mimes:pdf',
                'max:'.($item['max_mb'] * 1024),
            ];

            $messages["{$jenis}.required"] = 'Berkas wajib diunggah.';
            $messages["{$jenis}.file"] = 'Berkas gagal diunggah.';
            $messages["{$jenis}.mimes"] = 'Berkas harus berformat PDF.';
            $messages["{$jenis}.max"] = "Ukuran berkas maksimal {$item['max_mb']} MB.";
        }

        $request->validate($rules, $messages);

        foreach (array_keys(self::SYARAT_KHUSUS) as $jenis) {
            if (! $request->hasFile($jenis)) {
                continue;
            }

            if ($syaratKhusus->{$jenis.'_path'}) {
                Storage::disk('local')->delete($syaratKhusus->{$jenis.'_path'});
            }

            $syaratKhusus->{$jenis.'_path'} = $request->file($jenis)->storeAs(
                "syarat-khusus/{$request->user()->nidn}",
                sprintf('%s_%s_%s.pdf', $jenis, $request->user()->nidn, Str::random(16)),
                'local',
            );

            // Berkas yang diunggah ulang kembali berstatus draf dan catatan lamanya dibuang.
            $syaratKhusus->{$jenis.'_status'} = 'draf';
            $syaratKhusus->{$jenis.'_catatan'} = null;
        }

        $syaratKhusus->sinkronkanStatus()->save();

        return redirect()
            ->route('profil.pengajuan.syarat-khusus', ['tahun' => $tahun, 'semester' => $semester])
            ->with('status', 'Berkas syarat khusus berhasil disimpan.');
    }

    /**
     * Kirim berkas syarat khusus periode ini ke admin untuk diverifikasi.
     */
    public function ajukanSyaratKhusus(Request $request, SyaratKhusus $syaratKhusus): RedirectResponse
    {
        abort_unless($syaratKhusus->user_id === $request->user()->id, 404);

        $kembali = redirect()->route('profil.pengajuan.syarat-khusus', [
            'tahun' => $syaratKhusus->tahun,
            'semester' => $syaratKhusus->semester,
        ]);

        if (! $syaratKhusus->ajukan()) {
            return $kembali->with('error', 'Berkas periode ini sudah diajukan dan tidak dapat diajukan ulang.');
        }

        return $kembali->with('status', 'Berkas syarat khusus berhasil diajukan ke admin.');
    }

    /**
     * Ajukan semua berkas periode yang sudah lengkap (BKD & IKD) sekaligus.
     */
    public function ajukanSemuaSyaratKhusus(Request $request): RedirectResponse
    {
        $periode = $this->periodeQuery($request);

        $kembali = redirect()->route(
            'profil.pengajuan.syarat-khusus',
            $periode === null ? [] : ['tahun' => $periode[0], 'semester' => $periode[1]],
        );

        $jumlah = SyaratKhusus::query()
            ->where('user_id', $request->user()->id)
            ->whereIn('status', SyaratKhusus::BISA_DIUBAH)
            ->whereNotNull('bkd_path')
            ->whereNotNull('ikd_path')
            ->get()
            ->filter(fn (SyaratKhusus $item) => $item->ajukan())
            ->count();

        return $jumlah === 0
            ? $kembali->with('error', 'Belum ada berkas lengkap (BKD & IKD) yang bisa diajukan.')
            : $kembali->with('status', $jumlah.' berkas berhasil diajukan ke admin.');
    }

    /**
     * Daftar berkas syarat khusus yang masuk untuk diverifikasi admin.
     */
    public function verifikasiSyaratKhusus(): View
    {
        return view('admin.verifikasi-berkas.index', [
            'daftar' => SyaratKhusus::query()
                ->with('user')
                ->where(fn ($query) => $query
                    ->where('bkd_status', '!=', 'draf')
                    ->orWhere('ikd_status', '!=', 'draf'))
                ->orderByRaw("case when bkd_status = 'diajukan' or ikd_status = 'diajukan' then 0 else 1 end")
                ->orderByDesc('updated_at')
                ->get(),
        ]);
    }

    /**
     * Keputusan admin atas satu berkas syarat khusus: diterima atau ditolak beserta catatan.
     */
    public function putuskanSyaratKhusus(Request $request, SyaratKhusus $syaratKhusus, string $jenis): RedirectResponse
    {
        abort_unless(array_key_exists($jenis, self::SYARAT_KHUSUS), 404);

        $data = $request->validate([
            'keputusan' => ['required', Rule::in(['diterima', 'ditolak'])],
            'catatan' => ['nullable', 'string', 'max:1000', 'required_if:keputusan,ditolak'],
        ], [
            'catatan.required_if' => 'Catatan wajib diisi saat menolak berkas.',
        ]);

        if ($syaratKhusus->statusJenis($jenis) !== 'diajukan') {
            return redirect()
                ->route('verifikasi-berkas.index')
                ->with('error', 'Berkas '.strtoupper($jenis).' ini belum diajukan oleh dosen.');
        }

        $syaratKhusus->putuskan($jenis, $data['keputusan'], $data['catatan'] ?? null);

        return redirect()
            ->route('verifikasi-berkas.index')
            ->with('status', 'Berkas '.strtoupper($jenis).' '.$syaratKhusus->user->name.' ditandai "'.$syaratKhusus->statusLabel($jenis).'".');
    }

    /**
     * Berkas syarat khusus untuk admin: tanpa batasan pemilik.
     */
    public function berkasVerifikasi(SyaratKhusus $syaratKhusus, string $jenis): StreamedResponse
    {
        abort_unless(array_key_exists($jenis, self::SYARAT_KHUSUS), 404);

        $path = $syaratKhusus->{$jenis.'_path'};

        abort_unless($path && Storage::disk('local')->exists($path), 404);

        return Storage::disk('local')->response($path, basename($path), [
            'Content-Type' => 'application/pdf',
        ]);
    }

    /**
     * Tampilkan berkas syarat khusus milik dosen yang sedang login.
     */
    public function berkasSyaratKhusus(Request $request, string $jenis): StreamedResponse
    {
        $periode = $this->periodeQuery($request);

        abort_if($periode === null, 404);
        abort_unless(array_key_exists($jenis, self::SYARAT_KHUSUS), 404);

        $path = $this->syaratKhususFor($request, $periode[0], $periode[1])?->{$jenis.'_path'};

        abort_unless($path && Storage::disk('local')->exists($path), 404);

        return Storage::disk('local')->response($path, basename($path), [
            'Content-Type' => 'application/pdf',
        ]);
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
     * Riwayat usulan IKD dosen: berkas yang sudah diajukan beserta keputusan admin.
     */
    public function riwayatIkd(Request $request): View
    {
        return view('profil.pengajuan.riwayat-ikd', [
            'daftar' => SyaratKhusus::query()
                ->where('user_id', $request->user()->id)
                ->where('status', '!=', 'draf')
                ->orderByDesc('updated_at')
                ->get(),
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

    /**
     * Tahun & semester dari query string, null bila tidak valid.
     *
     * @return array{0: int, 1: string}|null
     */
    private function periodeQuery(Request $request): ?array
    {
        $tahun = (int) $request->input('tahun');
        $semester = $request->input('semester');

        return $tahun >= 2000 && $tahun <= 2100
            && is_string($semester) && array_key_exists($semester, KegiatanDupak::SEMESTER)
                ? [$tahun, $semester]
                : null;
    }

    private function syaratKhususFor(Request $request, int $tahun, string $semester): ?SyaratKhusus
    {
        return SyaratKhusus::query()->firstWhere([
            'user_id' => $request->user()->id,
            'tahun' => $tahun,
            'semester' => $semester,
        ]);
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
