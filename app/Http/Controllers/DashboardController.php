<?php

namespace App\Http\Controllers;

use App\Models\PengajuanDupak;
use App\Models\PengajuanJabatan;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(Request $request): View
    {
        $user = $request->user();

        // Dashboard dosen: ringkasan status dan riwayat ajuan DUPAK, tabelnya sama
        // dengan tabel di menu Riwayat Pengajuan (tanpa kolom aksi).
        if ($user->isDosen()) {
            $dupak = $user->pengajuanDupak()
                ->diajukanDupak()
                ->latest('diajukan_at')
                ->latest('id')
                ->get();

            return view('dashboard', [
                'statistics' => [
                    'total' => $dupak->count(),
                    'menunggu' => $dupak->where('status', 'diajukan_dupak')->count(),
                    'disetujui' => $dupak->where('status', 'disetujui')->count(),
                    'perbaikan' => $dupak->where('status', 'perlu_perbaikan')->count(),
                    'ditolak' => $dupak->where('status', 'ditolak')->count(),
                ],
                'pengajuan' => collect(),
                'dupak' => $dupak,
                // Total angka kredit hasil penilaian TIM PAK per periode.
                'nilai' => $user->kegiatanDupak()
                    ->selectRaw('tahun, semester, SUM(nilai_baru) as total')
                    ->groupBy('tahun', 'semester')
                    ->get()
                    ->keyBy(fn ($baris) => $baris->tahun.'-'.$baris->semester),
            ]);
        }

        // Dashboard TIM PAK: tabelnya sama dengan menu Pengajuan Masuk.
        if ($user->isTimPak()) {
            $pengajuan = PengajuanDupak::with('user')
                ->latest('diajukan_at')
                ->latest('id')
                ->get();

            return view('dashboard', [
                'statistics' => [
                    'total' => $pengajuan->count(),
                    'menunggu' => $pengajuan->whereIn('status', ['diajukan', 'dinilai'])->count(),
                    'disetujui' => $pengajuan->where('status', 'disetujui')->count(),
                    'perbaikan' => $pengajuan->where('status', 'perlu_perbaikan')->count(),
                ],
                'pengajuan' => $pengajuan,
                'dupak' => $pengajuan,
                'nilai' => collect(),
            ]);
        }

        $baseQuery = PengajuanJabatan::query()->forUser($user);

        $statistics = [
            'total' => (clone $baseQuery)->count(),
            'menunggu' => (clone $baseQuery)->whereIn('status', ['diajukan', 'diverifikasi'])->count(),
            'disetujui' => (clone $baseQuery)->where('status', 'disetujui')->count(),
            'perbaikan' => (clone $baseQuery)->where('status', 'ditolak')->count(),
        ];

        $pengajuan = (clone $baseQuery)
            ->with('user')
            ->latest('tanggal_pengajuan')
            ->latest('id')
            ->get();

        return view('dashboard', [
            'statistics' => $statistics,
            'pengajuan' => $pengajuan,
            'nilai' => collect(),
            // Admin memutuskan ajuan DUPAK yang dikirim dosen.
            'dupak' => $user->isAdmin()
                ? PengajuanDupak::with('user')->diajukanDupak()->latest('diajukan_at')->latest('id')->get()
                : collect(),
        ]);
    }
}
