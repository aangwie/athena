<?php

namespace App\Http\Controllers;

use App\Http\Requests\KegiatanPelaksanaanRequest;
use App\Http\Requests\KegiatanPendidikanRequest;
use App\Http\Requests\KegiatanPenelitianRequest;
use App\Http\Requests\KegiatanPengabdianRequest;
use App\Http\Requests\KegiatanPenunjangRequest;
use App\Models\Instansi;
use App\Models\JabatanFungsional;
use App\Models\KegiatanDupak;
use App\Models\MasaPenilaian;
use App\Models\PengajuanDupak;
use App\Models\RiwayatPendidikan;
use App\Models\StrukturInstansi;
use App\Models\SyaratKhusus;
use App\Models\User;
use App\Support\WhatsAppNotifikasi;
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
     * Memuat juga dropdown jabatan akademik yang akan diajukan (target kenaikan).
     */
    public function mulai(Request $request): View
    {
        $user = $request->user();
        $jabatan = $user->jabatan_fungsional ?: null;

        // Pilihan jabatan akademik dari data master, milik dosen yang sudah punya
        // jabatan akademik (pengajuan pertama memakai alur khusus IKD).
        $jabatanList = $jabatan
            ? JabatanFungsional::query()->orderBy('nama')->pluck('nama')
            : collect();

        // Jabatan tersimpan bisa saja sudah tidak ada di data master: tetap
        // ditampilkan agar tidak hilang dari pilihan.
        $jabatanDiajukan = $user->jabatan_diajukan ?: null;

        if ($jabatanDiajukan && $jabatanList->isNotEmpty() && ! $jabatanList->contains($jabatanDiajukan)) {
            $jabatanList = $jabatanList->push($jabatanDiajukan)->sort()->values();
        }

        return view('profil.pengajuan.mulai', [
            'jabatan' => $jabatan,
            'jabatanList' => $jabatanList,
            'jabatanDiajukan' => $jabatanDiajukan,
        ]);
    }

    /**
     * Simpan jabatan akademik yang akan diajukan pada halaman Status Jabatan Akademik.
     * Setelah tersimpan, dosen melanjutkan ke pemilihan periode.
     */
    public function simpanJabatan(Request $request): RedirectResponse
    {
        $user = $request->user();

        // Dosen tanpa jabatan akademik memakai alur pengajuan pertama (IKD),
        // jadi dropdown ini tidak berlaku untuk mereka.
        if (! $user->jabatan_fungsional) {
            return redirect()->route('profil.pengajuan.periode-pertama');
        }

        $data = $request->validate([
            'jabatan_diajukan' => ['required', 'string', 'max:100'],
        ], [
            'jabatan_diajukan.required' => 'Jabatan akademik yang akan diajukan wajib dipilih.',
            'jabatan_diajukan.max' => 'Jabatan akademik maksimal 100 karakter.',
        ]);

        $user->update(['jabatan_diajukan' => $data['jabatan_diajukan']]);

        return redirect()
            ->route('profil.pengajuan.periode')
            ->with('status', 'Jabatan akademik yang akan diajukan berhasil disimpan.');
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
     * Halaman masa penilaian: hanya untuk dosen yang sudah memiliki jabatan
     * akademik, dan wajib diisi sebelum masuk ke Pengajuan Jabatan Akademik.
     */
    public function masaPenilaian(Request $request): View|RedirectResponse
    {
        $periode = $this->periodeQuery($request);

        if ($periode === null) {
            return redirect()->route('profil.pengajuan.periode');
        }

        // Dosen tanpa jabatan akademik memakai alur pengajuan pertama (IKD).
        if (! $request->user()->jabatan_fungsional) {
            return redirect()->route('profil.pengajuan.periode-pertama');
        }

        [$tahun, $semester] = $periode;

        return view('profil.pengajuan.masa-penilaian', [
            'tahun' => $tahun,
            'semester' => $semester,
            'masaPenilaian' => $this->masaPenilaianFor($request, $tahun, $semester),
        ]);
    }

    public function simpanMasaPenilaian(Request $request): RedirectResponse
    {
        [$tahun, $semester] = $this->periodeInput($request);

        $data = $request->validate([
            'mulai_bulan' => ['required', 'integer', 'between:1,12'],
            'mulai_tahun' => ['required', 'integer', 'between:2000,2100'],
            'akhir_bulan' => ['required', 'integer', 'between:1,12'],
            'akhir_tahun' => ['required', 'integer', 'between:2000,2100'],
        ], [
            'mulai_bulan.required' => 'Bulan awal masa penilaian wajib dipilih.',
            'mulai_tahun.required' => 'Tahun awal masa penilaian wajib dipilih.',
            'akhir_bulan.required' => 'Bulan akhir masa penilaian wajib dipilih.',
            'akhir_tahun.required' => 'Tahun akhir masa penilaian wajib dipilih.',
        ]);

        // Disimpan sebagai tanggal pertama bulan; tampilannya bulan + tahun.
        $mulai = sprintf('%04d-%02d-01', $data['mulai_tahun'], $data['mulai_bulan']);
        $akhir = sprintf('%04d-%02d-01', $data['akhir_tahun'], $data['akhir_bulan']);

        if ($akhir < $mulai) {
            return back()
                ->withInput()
                ->withErrors(['akhir_bulan' => 'Batas akhir masa penilaian tidak boleh sebelum batas awal.']);
        }

        MasaPenilaian::updateOrCreate(
            [
                'user_id' => $request->user()->id,
                'tahun' => $tahun,
                'semester' => $semester,
            ],
            ['mulai' => $mulai, 'akhir' => $akhir],
        );

        return redirect()
            ->route('profil.pengajuan.create', ['tahun' => $tahun, 'semester' => $semester])
            ->with('status', 'Masa penilaian berhasil disimpan.');
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

        WhatsAppNotifikasi::berkasMasuk(
            $request->user(),
            $syaratKhusus->jenisDiajukan(),
            $syaratKhusus->tahun,
            $syaratKhusus->semester,
        );

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

        if ($jumlah > 0 && $periode !== null) {
            WhatsAppNotifikasi::berkasMasuk($request->user(), SyaratKhusus::JENIS, $periode[0], $periode[1]);
        }

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

        WhatsAppNotifikasi::berkasDiputuskan(
            $syaratKhusus,
            $jenis,
            $data['keputusan'],
            $data['catatan'] ?? null,
        );

        return redirect()
            ->route('verifikasi-berkas.index')
            ->with('status', 'Berkas '.strtoupper($jenis).' '.$syaratKhusus->user->name.' ditandai "'.$syaratKhusus->statusLabel($jenis).'".');
    }

    /**
     * Hapus satu berkas syarat khusus (BKD/IKD) yang masuk ke admin.
     *
     * Berkas fisik dihapus lalu status berkas dikembalikan ke dosen pengusul
     * sebagai "ditolak" beserta catatan, sehingga dosen dapat mengunggah ulang
     * berkasnya. Dosen juga menerima notifikasi WhatsApp atas penghapusan ini.
     *
     * Hanya berkas yang belum diputuskan admin (belum diverifikasi) yang boleh
     * dihapus; berkas yang sudah diterima/ditolak ditolak dengan pesan error.
     */
    public function hapusSyaratKhusus(SyaratKhusus $syaratKhusus, string $jenis): RedirectResponse
    {
        abort_unless(array_key_exists($jenis, self::SYARAT_KHUSUS), 404);

        $label = strtoupper($jenis);
        $path = $syaratKhusus->{$jenis.'_path'};

        if ($path === null) {
            return redirect()
                ->route('verifikasi-berkas.index')
                ->with('error', 'Berkas '.$label.' '.$syaratKhusus->user->name.' sudah tidak ada.');
        }

        // Berkas yang sudah diputuskan tidak boleh dihapus lagi oleh admin.
        if ($syaratKhusus->sudahDiverifikasi($jenis)) {
            return redirect()
                ->route('verifikasi-berkas.index')
                ->with('error', 'Berkas '.$label.' '.$syaratKhusus->user->name.' sudah diverifikasi, tidak dapat dihapus.');
        }

        Storage::disk('local')->delete($path);

        $syaratKhusus->{$jenis.'_path'} = null;
        $syaratKhusus->putuskan(
            $jenis,
            'ditolak',
            'Berkas dihapus admin. Silakan unggah ulang berkas '.$label.'.',
        );

        WhatsAppNotifikasi::berkasDihapus($syaratKhusus, $jenis);

        return redirect()
            ->route('verifikasi-berkas.index')
            ->with('status', 'Berkas '.$label.' '.$syaratKhusus->user->name.' berhasil dihapus. Dosen pengusul dapat mengunggah ulang berkas.');
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
            'masaPenilaian' => $this->masaPenilaianFor($request, $tahun, $semester),
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

        WhatsAppNotifikasi::ajuanPakMasuk($request->user(), $tahun, $semester);

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

        WhatsAppNotifikasi::dupakMasuk($request->user(), $tahun, $semester);

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
     * Riwayat usulan syarat khusus dosen (BKD & IKD): semua berkas yang sudah
     * diajukan, baik yang diterima maupun ditolak admin. Difilter per-berkas
     * (bukan status gabungan) supaya usulan yang sudah diputuskan tetap tampil.
     */
    public function riwayatIkd(Request $request): View
    {
        return view('profil.pengajuan.riwayat-ikd', [
            'daftar' => SyaratKhusus::query()
                ->where('user_id', $request->user()->id)
                ->where(function ($query) {
                    foreach (SyaratKhusus::JENIS as $jenis) {
                        $query->orWhere($jenis.'_status', '!=', 'draf');
                    }
                })
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

        WhatsAppNotifikasi::ajuanPakMasuk($request->user(), $tahun, $semester);

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

        $user = $request->user()->load([
            'profilDosen',
            'riwayatKepangkatan.kepangkatan',
            'riwayatJabatanStruktural.jabatanFungsional',
            'riwayatAngkaKredit',
            'riwayatPendidikan',
        ]);

        return view('profil.pengajuan.cetak', [
            'tahun' => $tahun,
            'semester' => $semester,
            'pengajuan' => $user->pengajuanDupak()
                ->where('tahun', $tahun)
                ->where('semester', $semester)
                ->first(),
            // Blok identitas "Keterangan Perorangan" di atas tabel unsur yang dinilai.
            'instansi' => Instansi::query()->first(),
            'masaPenilaian' => $user->masaPenilaian()
                ->where('tahun', $tahun)
                ->where('semester', $semester)
                ->first(),
            'identitas' => $this->identitasDupak($user),
            // Blok bawah: pimpinan yang mengesahkan DUPAK (satu kolom dengan identitas dosen).
            'pimpinan' => $this->pimpinanInstansi(),
            ...$user->kegiatanPeriode($tahun, $semester),
        ]);
    }

    /**
     * Pimpinan instansi yang mengesahkan DUPAK pada blok tanda tangan bawah.
     * Dipilih dari struktur instansi: jabatan Rektor/Ketua, atau pimpinan pertama.
     */
    private function pimpinanInstansi(): ?User
    {
        $struktur = StrukturInstansi::query()->with(['jabatanStruktural', 'user'])->get();

        $pimpinan = $struktur->first(fn (StrukturInstansi $item) => in_array(
            Str::lower($item->jabatanStruktural?->nama ?? ''),
            ['ketua', 'rektor'],
            true,
        ));

        return ($pimpinan ?? $struktur->first())?->user;
    }

    /**
     * Identitas dosen pengusul untuk blok "Keterangan Perorangan" pada formulir cetak.
     *
     * @return array<string, string>
     */
    private function identitasDupak(User $user): array
    {
        $profil = $user->profilDosen;
        $pangkat = $user->riwayatKepangkatan->sortByDesc('tmt')->first();
        $jabatan = $user->riwayatJabatanStruktural
            ->whereNotNull('jabatan_fungsional_id')
            ->sortByDesc('tmt')
            ->first();
        $pendidikan = $user->riwayatPendidikan
            ->sortByDesc(fn (RiwayatPendidikan $item) => $item->jenjangRank())
            ->first();
        $angkaKredit = $user->riwayatAngkaKredit->sortByDesc('tahun')->first();

        // Angka kredit terakhir + TMT jabatan akademik, mis. "150,00 / 02 Agustus 2019".
        $angkaKreditTmt = collect([
            $angkaKredit?->angkaKreditLabel(),
            $jabatan?->tmtLabel(),
        ])->filter(fn ($nilai) => filled($nilai) && $nilai !== '—')->implode(' / ');

        return [
            'nama' => $user->name ?: '—',
            'nuptk_nidn' => collect([$profil?->nuptk, $user->nidn])
                ->filter()->implode('/') ?: '—',
            'ttl' => collect([
                $profil?->tempat_lahir,
                $profil?->tanggal_lahir?->locale('id')->translatedFormat('d F Y'),
            ])->filter()->implode(', ') ?: '—',
            'jenis_kelamin' => $profil?->jenisKelaminLabel() ?? '—',
            'pendidikan_terakhir' => $pendidikan?->jenjangLabel() ?? '—',
            'jabatan_akademik' => trim(collect([
                $user->jabatan_fungsional,
                $angkaKreditTmt !== '' ? '('.$angkaKreditTmt.')' : null,
            ])->filter()->implode(' ')) ?: '—',
            // Nama jabatan & pangkat tanpa TMT, dipakai blok "Pendapat Tim Penilai".
            // Jabatan = target yang diajukan dosen; Pangkat memuat golongan ("IIIa / Penata Muda").
            'jabatan_diajukan' => $user->jabatan_diajukan ?: ($user->jabatan_fungsional ?: '—'),
            'pangkat_nama' => $pangkat?->pangkatLabel() ?? '—',
            'jurusan_prodi' => $pendidikan?->jurusan ?: ($user->program_studi ?: '—'),
            'bidang_ilmu' => $profil?->bidang_ilmu ?: '—',
            'pangkat' => trim(collect([
                $pangkat?->pangkatLabel(),
                $pangkat?->tmtLabel(),
            ])->filter(fn ($nilai) => filled($nilai) && $nilai !== '—')
                ->implode(' / ')) ?: '—',
            'masa_kerja_lama' => $profil?->masaKerjaGolonganLabel('lama') ?? '—',
            'masa_kerja_baru' => $profil?->masaKerjaGolonganLabel('baru') ?? '—',
        ];
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

    private function masaPenilaianFor(Request $request, int $tahun, string $semester): ?MasaPenilaian
    {
        return MasaPenilaian::query()->firstWhere([
            'user_id' => $request->user()->id,
            'tahun' => $tahun,
            'semester' => $semester,
        ]);
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
