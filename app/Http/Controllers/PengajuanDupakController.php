<?php

namespace App\Http\Controllers;

use App\Models\PengajuanDupak;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class PengajuanDupakController extends Controller
{
    /**
     * Daftar usulan DUPAK yang masuk dari dosen.
     */
    public function index(): View
    {
        return view('tim-pak.pengajuan.index', [
            'pengajuan' => PengajuanDupak::with('user')
                ->latest('diajukan_at')
                ->latest('id')
                ->get(),
        ]);
    }

    /**
     * Halaman verifikasi admin: rekap DUPAK read-only + keputusan setujui/perbaikan/tolak.
     */
    public function verifikasi(PengajuanDupak $pengajuan): View
    {
        $pengajuan->load('user');

        return view('admin.dupak.show', [
            'pengajuan' => $pengajuan,
            'editable' => false,
            ...$pengajuan->user->kegiatanPeriode($pengajuan->tahun, $pengajuan->semester),
        ]);
    }

    /**
     * Simpan keputusan admin atas ajuan DUPAK beserta alasannya.
     */
    public function putuskan(Request $request, PengajuanDupak $pengajuan): RedirectResponse
    {
        $data = $request->validate([
            'keputusan' => ['required', Rule::in(['disetujui', 'perlu_perbaikan', 'ditolak'])],
            'catatan' => ['nullable', 'string', 'max:1000', 'required_unless:keputusan,disetujui'],
        ], [
            'catatan.required_unless' => 'Alasan wajib diisi saat memilih Butuh Perbaikan atau Tolak.',
        ]);

        $pengajuan->update([
            'status' => $data['keputusan'],
            'catatan' => $data['catatan'] ?? null,
        ]);

        return redirect()
            ->route('verifikasi-dupak.show', $pengajuan)
            ->with('status', 'Keputusan "'.$pengajuan->statusLabel().'" berhasil disimpan.');
    }

    /**
     * Halaman penilaian: tabel rekap DUPAK dengan kolom Baru yang bisa diisi TIM PAK.
     */
    public function show(PengajuanDupak $pengajuan): View
    {
        $pengajuan->load('user');

        return view('tim-pak.pengajuan.show', [
            'pengajuan' => $pengajuan,
            'tahun' => $pengajuan->tahun,
            'semester' => $pengajuan->semester,
            'editable' => ! $pengajuan->nilaiTerkunci(),
            ...$pengajuan->user->kegiatanPeriode($pengajuan->tahun, $pengajuan->semester),
        ]);
    }

    /**
     * Simpan angka kredit hasil penilaian TIM PAK.
     */
    public function update(Request $request, PengajuanDupak $pengajuan): RedirectResponse
    {
        if ($pengajuan->nilaiTerkunci()) {
            return redirect()
                ->route('tim-pak.pengajuan.show', $pengajuan)
                ->with('error', 'DUPAK periode ini sudah disetujui admin, nilai tidak bisa diubah lagi.');
        }

        $data = $request->validate([
            'nilai' => ['array'],
            'nilai.*' => ['nullable', 'numeric', 'min:0', 'max:9999.99'],
        ]);

        $nilai = $data['nilai'] ?? [];

        $pengajuan->user->kegiatanDupak()
            ->periode($pengajuan->tahun, $pengajuan->semester)
            ->whereIn('id', array_keys($nilai))
            ->get()
            ->each(fn ($kegiatan) => $kegiatan->update(['nilai_baru' => $nilai[$kegiatan->id]]));

        $pengajuan->update(['status' => 'dinilai', 'dinilai_at' => now()]);

        return redirect()
            ->route('tim-pak.pengajuan.show', $pengajuan)
            ->with('status', 'Nilai angka kredit berhasil disimpan.');
    }
}
