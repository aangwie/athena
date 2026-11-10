<?php

namespace App\Http\Controllers;

use App\Models\Skp;
use App\Support\WhatsAppNotifikasi;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\View\View;

class SkpUsulanController extends Controller
{
    public function index(Request $request): View
    {
        // SKP tempat dosen login menjadi Pejabat Penilai (mengisi nilai) atau
        // Atasan Pejabat Penilai (keputusan keberatan & persetujuan).
        $skpList = Skp::query()
            ->where(fn ($query) => $query
                ->where('pejabat_penilai_id', $request->user()->id)
                ->orWhere('atasan_penilai_id', $request->user()->id))
            ->with(['dosen', 'pejabatPenilai', 'prodi'])
            ->orderByDesc('periode_mulai')
            ->orderByDesc('id')
            ->get();

        return view('skp-usulan.index', [
            'skpList' => $skpList,
            'unsurList' => Skp::UNSUR,
            'editSkp' => $request->filled('edit')
                // Form nilai hanya untuk baris tempat dosen login sebagai pejabat penilai
                // dan penilaiannya belum disetujui dosen.
                ? $skpList->first(fn (Skp $skp) => $skp->id === (int) $request->integer('edit')
                    && $skp->pejabat_penilai_id === $request->user()->id
                    && ! $skp->sudahDisetujui())
                : null,
        ]);
    }

    public function update(Request $request, Skp $skp): RedirectResponse
    {
        // Pejabat penilai hanya boleh mengisi nilai pada SKP yang ditugaskan kepadanya,
        // dan nilainya terkunci selama belum dibatalkan oleh dosen yang dinilai.
        abort_unless($skp->pejabat_penilai_id === $request->user()->id, 403);
        abort_if($skp->sudahDisetujui(), 403, 'Nilai SKP sudah disetujui dosen yang dinilai.');

        $skp->update($this->validated($request));

        WhatsAppNotifikasi::skpDinilai($skp);

        return redirect()
            ->route('skp-usulan.index')
            ->with('status', 'Nilai perilaku kerja berhasil disimpan.');
    }

    /**
     * Halaman baru tanggapan pejabat penilai atas keberatan dosen.
     */
    public function tanggapan(Request $request, Skp $skp): View
    {
        $this->pastikanPejabatPenilai($request, $skp);

        // Tanggapan hanya relevan bila dosen sudah mengajukan keberatan.
        abort_unless($skp->mengajukanKeberatan(), 404, 'Dosen belum mengajukan keberatan.');

        $skp->load(['dosen', 'prodi']);

        return view('skp-usulan.tanggapan', [
            'skp' => $skp,
        ]);
    }

    /**
     * Simpan tanggapan pejabat penilai beserta tanggal tanggapan.
     */
    public function simpanTanggapan(Request $request, Skp $skp): RedirectResponse
    {
        $this->pastikanPejabatPenilai($request, $skp);

        abort_unless($skp->mengajukanKeberatan(), 404, 'Dosen belum mengajukan keberatan.');

        $data = $request->validate([
            'tanggapan_keberatan' => ['required', 'string', 'max:2000'],
            'tanggal_tanggapan' => ['required', 'date_format:d-m-Y'],
        ], [
            'tanggapan_keberatan.required' => 'Tanggapan keberatan wajib diisi.',
            'tanggapan_keberatan.max' => 'Tanggapan keberatan maksimal 2000 karakter.',
            'tanggal_tanggapan.required' => 'Tanggal tanggapan wajib diisi.',
            'tanggal_tanggapan.date_format' => 'Tanggal tanggapan harus berformat DD-MM-YYYY.',
        ], [
            'tanggapan_keberatan' => 'tanggapan keberatan',
            'tanggal_tanggapan' => 'tanggal tanggapan',
        ]);

        $skp->update([
            'tanggapan_keberatan' => $data['tanggapan_keberatan'],
            'tanggal_tanggapan' => Carbon::createFromFormat('d-m-Y', $data['tanggal_tanggapan'])->toDateString(),
        ]);

        return redirect()
            ->route('skp-usulan.index')
            ->with('status', 'Tanggapan keberatan berhasil disimpan.');
    }

    /**
     * Halaman baru keputusan atasan pejabat penilai atas keberatan dosen (opsional).
     */
    public function keputusan(Request $request, Skp $skp): View
    {
        $this->pastikanAtasanPenilai($request, $skp);

        // Hanya tersedia setelah dosen mengajukan keberatan, ditinjau ulang pejabat
        // penilai (tanggapan), dan dosen menyetujui hasil peninjauan.
        abort_unless($skp->bolehKeputusanKeberatan(), 404, 'Keberatan belum selesai ditinjau atau belum disetujui dosen.');

        $skp->load(['dosen', 'pejabatPenilai', 'prodi']);

        return view('skp-usulan.keputusan', [
            'skp' => $skp,
        ]);
    }

    /**
     * Simpan keputusan atasan pejabat penilai atas keberatan beserta tanggalnya.
     */
    public function simpanKeputusan(Request $request, Skp $skp): RedirectResponse
    {
        $this->pastikanAtasanPenilai($request, $skp);

        abort_unless($skp->bolehKeputusanKeberatan(), 404, 'Keberatan belum selesai ditinjau atau belum disetujui dosen.');

        $data = $request->validate([
            'keputusan_keberatan' => ['required', 'string', 'max:2000'],
            'tanggal_keputusan' => ['required', 'date_format:d-m-Y'],
        ], [
            'keputusan_keberatan.required' => 'Keputusan atas keberatan wajib diisi.',
            'keputusan_keberatan.max' => 'Keputusan atas keberatan maksimal 2000 karakter.',
            'tanggal_keputusan.required' => 'Tanggal keputusan wajib diisi.',
            'tanggal_keputusan.date_format' => 'Tanggal keputusan harus berformat DD-MM-YYYY.',
        ], [
            'keputusan_keberatan' => 'keputusan atas keberatan',
            'tanggal_keputusan' => 'tanggal keputusan',
        ]);

        $skp->update([
            'keputusan_keberatan' => $data['keputusan_keberatan'],
            'tanggal_keputusan' => Carbon::createFromFormat('d-m-Y', $data['tanggal_keputusan'])->toDateString(),
        ]);

        return redirect()
            ->route('skp-usulan.index')
            ->with('status', 'Keputusan atas keberatan berhasil disimpan.');
    }

    /**
     * Atasan pejabat penilai menyetujui hasil penilaian beserta tanggal persetujuan
     * (tercetak pada blok 11 halaman cetak SKP).
     */
    public function setujuiAtasan(Request $request, Skp $skp): RedirectResponse
    {
        $this->pastikanAtasanPenilai($request, $skp);

        // Persetujuan atasan hanya setelah dosen yang dinilai menyetujui penilaian.
        abort_unless($skp->sudahDisetujui(), 404, 'Dosen belum menyetujui penilaian SKP.');
        abort_if($skp->sudahDisetujuiAtasan(), 403, 'Penilaian SKP sudah Anda setujui.');

        $data = $request->validate([
            'tanggal_persetujuan' => ['required', 'date_format:d-m-Y'],
        ], [
            'tanggal_persetujuan.required' => 'Tanggal persetujuan wajib diisi.',
            'tanggal_persetujuan.date_format' => 'Tanggal persetujuan harus berformat DD-MM-YYYY.',
        ], [
            'tanggal_persetujuan' => 'tanggal persetujuan',
        ]);

        $skp->update([
            'disetujui_atasan_pada' => Carbon::createFromFormat('d-m-Y', $data['tanggal_persetujuan'])->toDateString(),
        ]);

        return redirect()
            ->route('skp-usulan.index')
            ->with('status', 'Penilaian SKP berhasil Anda setujui.');
    }

    /**
     * Pejabat penilai hanya boleh mengisi nilai pada SKP yang ditugaskan kepadanya.
     */
    private function pastikanPejabatPenilai(Request $request, Skp $skp): void
    {
        abort_unless($skp->pejabat_penilai_id === $request->user()->id, 403);
    }

    /**
     * Atasan penilai hanya boleh memutuskan/menyetujui SKP yang ditugaskan kepadanya.
     */
    private function pastikanAtasanPenilai(Request $request, Skp $skp): void
    {
        abort_unless($skp->atasan_penilai_id === $request->user()->id, 403);
    }

    /**
     * @return array<string, mixed>
     */
    private function validated(Request $request): array
    {
        $aturan = [];
        foreach (array_keys(Skp::UNSUR) as $kunci) {
            $aturan['nilai_'.$kunci] = ['required', 'integer', 'between:0,100'];
        }

        // Tanggal penilaian tercetak pada blok 9 ("DIBUAT TANGGAL, ...") halaman cetak SKP.
        $aturan['tanggal_penilaian'] = ['required', 'date_format:d-m-Y'];

        $data = $request->validate($aturan, [
            'nilai_*.required' => 'Nilai wajib diisi untuk seluruh unsur perilaku kerja.',
            'nilai_*.integer' => 'Nilai angka harus berupa bilangan bulat.',
            'nilai_*.between' => 'Nilai angka harus antara 0 sampai 100.',
            'tanggal_penilaian.required' => 'Tanggal penilaian wajib diisi.',
            'tanggal_penilaian.date_format' => 'Tanggal penilaian harus berformat DD-MM-YYYY.',
        ], [
            'tanggal_penilaian' => 'tanggal penilaian',
        ]);

        // Input DD-MM-YYYY disimpan sebagai tanggal biasa.
        $data['tanggal_penilaian'] = Carbon::createFromFormat('d-m-Y', $data['tanggal_penilaian'])->toDateString();

        return $data;
    }
}