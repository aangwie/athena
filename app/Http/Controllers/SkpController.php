<?php

namespace App\Http\Controllers;

use App\Models\Instansi;
use App\Models\MasaPenilaian;
use App\Models\Prodi;
use App\Models\Skp;
use App\Models\StrukturInstansi;
use App\Models\User;
use App\Support\WhatsAppNotifikasi;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class SkpController extends Controller
{
    public function index(Request $request): View
    {
        // Pejabat & atasan penilai diambil dari dosen yang memiliki jabatan struktural
        // (satu dosen = satu jabatan sesuai aturan strukktur instansi).
        $penilaiList = StrukturInstansi::query()
            ->with(['user', 'jabatanStruktural'])
            ->get()
            ->sortBy(fn (StrukturInstansi $s) => $s->user?->name ?? '')
            ->values();

        return view('skp.index', [
            'items' => Skp::query()
                ->with(['dosen', 'pejabatPenilai', 'atasanPenilai', 'prodi'])
                ->orderByDesc('periode_mulai')
                ->orderByDesc('id')
                ->get(),
            'editSkp' => $request->filled('edit')
                ? Skp::query()->find($request->integer('edit'))
                : null,
            'dosenList' => User::query()
                ->where('role', 'dosen')
                ->where('is_active', true)
                ->orderBy('name')
                ->get(),
            'penilaiList' => $penilaiList,
            'prodiList' => Prodi::query()->orderBy('nama_prodi')->get(),
            'bulanList' => MasaPenilaian::BULAN,
            'tahunList' => range((int) now()->year - 1, (int) now()->year + 2),
        ]);
    }

    /**
     * Formulir SKP siap cetak: identitas tiga pihak + nilai perilaku kerja yang sudah diisi.
     */
    public function cetak(Skp $skp): View
    {
        $skp->load([
            'dosen.riwayatKepangkatan.kepangkatan',
            'pejabatPenilai.riwayatKepangkatan.kepangkatan',
            'atasanPenilai.riwayatKepangkatan.kepangkatan',
            'prodi',
        ]);

        $unit = Instansi::query()->value('nama_instansi') ?: '—';

        // Jabatan struktural penilai diambil dari struktur instansi (satu dosen = satu jabatan).
        $jabatan = StrukturInstansi::query()
            ->with('jabatanStruktural')
            ->whereIn('user_id', [$skp->pejabat_penilai_id, $skp->atasan_penilai_id])
            ->get()
            ->keyBy('user_id');

        return view('skp.cetak', [
            'skp' => $skp,
            'instansi' => Instansi::query()->first(),
            'dinilai' => $this->identitas($skp->dosen, $skp->dosen?->jabatan_fungsional ?: 'Dosen', $unit),
            'pejabat' => $this->identitas(
                $skp->pejabatPenilai,
                $jabatan->get($skp->pejabat_penilai_id)?->jabatanStruktural?->nama,
                $unit,
            ),
            'atasan' => $this->identitas(
                $skp->atasanPenilai,
                $jabatan->get($skp->atasan_penilai_id)?->jabatanStruktural?->nama,
                $unit,
            ),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        // Tanggal simpan dicatat otomatis dan tercetak pada blok 10 halaman cetak SKP.
        $skp = Skp::query()->create($this->validatedData($request) + [
            'tanggal_diterima' => now()->toDateString(),
        ]);

        WhatsAppNotifikasi::skpMasuk($skp);

        return redirect()
            ->route('skp.index')
            ->with('status', 'Data SKP berhasil ditambahkan.');
    }

    public function update(Request $request, Skp $skp): RedirectResponse
    {
        $skp->update($this->validatedData($request));

        return redirect()
            ->route('skp.index')
            ->withFragment('form-skp')
            ->with('status', 'Data SKP berhasil diperbarui.');
    }

    public function destroy(Skp $skp): RedirectResponse
    {
        $skp->delete();

        return redirect()
            ->route('skp.index')
            ->with('status', 'Data SKP berhasil dihapus.');
    }

    /**
     * Baris identitas blok 1–3 formulir cetak: nama, NIP, pangkat/golongan/TMT, jabatan, unit.
     *
     * @return array<string, string>
     */
    private function identitas(?User $user, ?string $jabatan, string $unit): array
    {
        $pangkat = $user?->riwayatKepangkatan->sortByDesc('tmt')->first();

        return [
            'nama' => $user?->name ?? '—',
            'nip' => $user?->nidn ?? '—',
            'pangkat' => collect([$pangkat?->pangkatLabel(), $pangkat?->tmtLabel()])
                ->filter(fn ($nilai) => filled($nilai) && $nilai !== '—')
                ->implode(' / ') ?: '—',
            'jabatan' => $jabatan ?: '—',
            'unit' => $unit,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function validatedData(Request $request): array
    {
        $data = $request->validate([
            'dosen_id' => [
                'required',
                'integer',
                Rule::exists('users', 'id')->where(
                    fn ($query) => $query->where('role', 'dosen')->where('is_active', true),
                ),
            ],
            'pejabat_penilai_id' => ['required', 'integer', Rule::exists('struktur_instansi', 'user_id')],
            'atasan_penilai_id' => ['required', 'integer', Rule::exists('struktur_instansi', 'user_id')],
            'prodi_id' => ['required', 'integer', Rule::exists('prodi', 'id')],
            'bulan_mulai' => ['required', 'integer', 'between:1,12'],
            'tahun_mulai' => ['required', 'integer'],
            'bulan_akhir' => ['required', 'integer', 'between:1,12'],
            'tahun_akhir' => ['required', 'integer'],
        ], [
            'dosen_id.required' => 'Dosen yang dinilai wajib dipilih.',
            'dosen_id.exists' => 'Dosen tidak ditemukan atau sudah tidak aktif.',
            'pejabat_penilai_id.required' => 'Pejabat penilai wajib dipilih.',
            'pejabat_penilai_id.exists' => 'Pejabat penilai harus dosen yang memiliki jabatan struktural.',
            'atasan_penilai_id.required' => 'Atasan penilai wajib dipilih.',
            'atasan_penilai_id.exists' => 'Atasan penilai harus dosen yang memiliki jabatan struktural.',
            'prodi_id.required' => 'Prodi wajib dipilih.',
            'prodi_id.exists' => 'Prodi tidak ditemukan.',
            'bulan_mulai.required' => 'Bulan mulai wajib dipilih.',
            'tahun_mulai.required' => 'Tahun mulai wajib dipilih.',
            'bulan_akhir.required' => 'Bulan akhir wajib dipilih.',
            'tahun_akhir.required' => 'Tahun akhir wajib dipilih.',
        ]);

        $mulai = Carbon::createFromDate((int) $data['tahun_mulai'], (int) $data['bulan_mulai'], 1);
        $akhir = Carbon::createFromDate((int) $data['tahun_akhir'], (int) $data['bulan_akhir'], 1);

        if ($akhir->lt($mulai)) {
            throw ValidationException::withMessages([
                'bulan_akhir' => 'Jangka waktu akhir tidak boleh mendahului jangka waktu mulai.',
            ]);
        }

        return [
            'dosen_id' => $data['dosen_id'],
            'pejabat_penilai_id' => $data['pejabat_penilai_id'],
            'atasan_penilai_id' => $data['atasan_penilai_id'],
            'prodi_id' => $data['prodi_id'],
            'periode_mulai' => $mulai->toDateString(),
            'periode_akhir' => $akhir->toDateString(),
        ];
    }
}