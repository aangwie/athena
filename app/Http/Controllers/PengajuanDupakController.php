<?php

namespace App\Http\Controllers;

use App\Models\PengajuanDupak;
use App\Support\WhatsAppNotifikasi;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use App\Models\User;

class PengajuanDupakController extends Controller
{
    /**
     * Daftar usulan DUPAK yang masuk dari dosen.
     */
    public function index(): View
    {
        return view('tim-pak.pengajuan.index', [
            'pengajuan' => PengajuanDupak::with('user')
                ->where('tim_pak_id', request()->user()->id)
                // Ajuan yang sudah disetujui tetap tampil agar TIM PAK dapat melihatnya read-only.
                ->whereIn('status', ['diajukan', 'dinilai', 'disetujui'])
                ->latest('diajukan_at')
                ->latest('id')
                ->get(),
        ]);
    }

    public function ajuanPak(): View
    {
        return view('admin.ajuan-pak.index', [
            'pengajuan' => PengajuanDupak::with(['user', 'timPak'])
                ->whereIn('status', ['menunggu_penugasan', 'diajukan', 'dinilai'])
                ->latest('diajukan_at')->latest('id')->get(),
            'timPak' => User::query()->where(function ($query) {
                $query->where('role', 'tim_pak')->orWhereJsonContains('roles', 'tim_pak');
            })->where('is_active', true)->orderBy('name')->get(),
        ]);
    }

    public function tugaskan(Request $request, PengajuanDupak $pengajuan): RedirectResponse
    {
        $data = $request->validate(['tim_pak_id' => ['required', Rule::exists('users', 'id')->where(function ($query) {
            $query->where(function ($roleQuery) {
                $roleQuery->where('role', 'tim_pak')->orWhereJsonContains('roles', 'tim_pak');
            })->where('is_active', true);
        })]]);
        abort_unless($pengajuan->status === 'menunggu_penugasan', 422, 'Ajuan ini sudah ditugaskan atau tidak dapat ditugaskan.');
        $pengajuan->update(['tim_pak_id' => $data['tim_pak_id'], 'status' => 'diajukan']);
        return back()->with('status', 'Ajuan berhasil diteruskan ke TIM PAK yang dipilih.');
    }

    /**
     * Halaman verifikasi admin: rekap DUPAK read-only + keputusan setujui/perbaikan/tolak.
     */
    public function verifikasi(PengajuanDupak $pengajuan): View
    {
        $pengajuan->load('user');

        return view('admin.dupak.show', [
            'pengajuan' => $pengajuan,
            'masaPenilaian' => $pengajuan->masaPenilaian(),
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
            'nomor_sk' => ['nullable', 'string', 'max:150', 'required_if:keputusan,disetujui'],
        ], [
            'catatan.required_unless' => 'Alasan wajib diisi saat memilih Butuh Perbaikan atau Tolak.',
            'nomor_sk.required_if' => 'Nomor Surat Keputusan wajib diisi saat menyetujui DUPAK.',
        ]);

        $pengajuan->update([
            'status' => $data['keputusan'],
            'catatan' => $data['catatan'] ?? null,
            // Nomor SK hanya berlaku untuk keputusan Setujui.
            'nomor_sk' => $data['keputusan'] === 'disetujui' ? ($data['nomor_sk'] ?? null) : null,
        ]);

        WhatsAppNotifikasi::dupakDiputuskan($pengajuan);

        return redirect()
            ->route('verifikasi-dupak.show', $pengajuan)
            ->with('status', 'Keputusan "'.$pengajuan->statusLabel().'" berhasil disimpan.');
    }

    /**
     * Halaman penilaian: tabel rekap DUPAK dengan kolom Baru yang bisa diisi TIM PAK.
     */
    public function show(PengajuanDupak $pengajuan): View
    {
        abort_unless($pengajuan->tim_pak_id === request()->user()->id, 403, 'Ajuan ini tidak ditugaskan kepada Anda.');
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
        abort_unless($pengajuan->tim_pak_id === $request->user()->id, 403, 'Ajuan ini tidak ditugaskan kepada Anda.');
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

        WhatsAppNotifikasi::pakDinilai($pengajuan);

        return redirect()
            ->route('tim-pak.pengajuan.show', $pengajuan)
            ->with('status', 'Nilai angka kredit berhasil disimpan.');
    }
}
