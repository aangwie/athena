<?php

namespace App\Http\Controllers;

use App\Models\Skp;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\View\View;

class SkpDosenController extends Controller
{
    /**
     * Daftar penilaian SKP milik dosen yang sedang login: hanya baris dengan dosen_id = user.
     */
    public function index(Request $request): View
    {
        $skpList = Skp::query()
            ->where('dosen_id', $request->user()->id)
            ->with(['dosen', 'pejabatPenilai', 'prodi'])
            ->orderByDesc('periode_mulai')
            ->orderByDesc('id')
            ->get();

        return view('skp-dosen.index', [
            'skpList' => $skpList,
        ]);
    }

    /**
     * Formulir pengajuan keberatan atas nilai SKP (halaman terpisah dari daftar).
     */
    public function keberatan(Request $request, Skp $skp): View
    {
        $this->pastikanMilikSendiri($request, $skp);

        // Keberatan hanya relevan setelah pejabat penilai mengisi seluruh unsur.
        abort_unless($skp->sudahDinilai(), 404, 'SKP belum dinilai oleh pejabat penilai.');
        abort_if($skp->sudahDinilai() && $skp->sudahDisetujui(), 403, 'Penilaian SKP sudah Anda setujui.');

        $skp->load(['dosen', 'pejabatPenilai', 'prodi']);

        return view('skp-dosen.keberatan', [
            'skp' => $skp,
        ]);
    }

    /**
     * Simpan ajuan keberatan beserta tanggal ajuannya.
     */
    public function simpanKeberatan(Request $request, Skp $skp): RedirectResponse
    {
        $this->pastikanMilikSendiri($request, $skp);

        abort_unless($skp->sudahDinilai(), 404, 'SKP belum dinilai oleh pejabat penilai.');
        abort_if($skp->sudahDisetujui(), 403, 'Penilaian SKP sudah Anda setujui.');

        $data = $request->validate([
            'keberatan' => ['required', 'string', 'max:2000'],
            'tanggal_keberatan' => ['required', 'date_format:d-m-Y'],
        ], [
            'keberatan.required' => 'Uraian keberatan wajib diisi.',
            'keberatan.max' => 'Uraian keberatan maksimal 2000 karakter.',
            'tanggal_keberatan.required' => 'Tanggal ajuan keberatan wajib diisi.',
            'tanggal_keberatan.date_format' => 'Tanggal ajuan keberatan harus berformat DD-MM-YYYY.',
        ], [
            'keberatan' => 'uraian keberatan',
            'tanggal_keberatan' => 'tanggal ajuan keberatan',
        ]);

        $skp->update([
            'keberatan' => $data['keberatan'],
            'tanggal_keberatan' => Carbon::createFromFormat('d-m-Y', $data['tanggal_keberatan'])->toDateString(),
        ]);

        return redirect()
            ->route('skp-dosen.index')
            ->with('status', 'Keberatan atas nilai SKP berhasil diajukan.');
    }

    /**
     * Dosen menyetujui hasil penilaian pejabat penilai.
     */
    public function setujui(Request $request, Skp $skp): RedirectResponse
    {
        $this->pastikanMilikSendiri($request, $skp);

        abort_unless($skp->sudahDinilai(), 404, 'SKP belum dinilai oleh pejabat penilai.');
        abort_if($skp->sudahDisetujui(), 403, 'Penilaian SKP sudah Anda setujui.');

        $skp->update(['disetujui_pada' => now()->toDateString()]);

        return redirect()
            ->route('skp-dosen.index')
            ->with('status', 'Penilaian SKP berhasil disetujui.');
    }

    /**
     * Dosen yang dinilai membatalkan persetujuannya, sehingga penilaian kembali
     * bisa diubah pejabat penilai. Ditolak bila atasan penilai sudah menyetujui.
     */
    public function batalSetujui(Request $request, Skp $skp): RedirectResponse
    {
        $this->pastikanMilikSendiri($request, $skp);

        abort_unless($skp->sudahDisetujui(), 404, 'Penilaian SKP belum Anda setujui.');
        abort_if($skp->sudahDisetujuiAtasan(), 403, 'Penilaian SKP sudah disetujui atasan penilai.');

        $skp->update(['disetujui_pada' => null]);

        return redirect()
            ->route('skp-dosen.index')
            ->with('status', 'Persetujuan penilaian SKP berhasil dibatalkan.');
    }

    /**
     * Dosen hanya boleh mengakses baris penilaian miliknya sendiri.
     */
    private function pastikanMilikSendiri(Request $request, Skp $skp): void
    {
        abort_unless($skp->dosen_id === $request->user()->id, 403);
    }
}
