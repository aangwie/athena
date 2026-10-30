<?php

namespace App\Http\Controllers;

use App\Models\PengajuanDupak;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(Request $request): View
    {
        $user = $request->user();

        // Semua role memakai tabel ajuan DUPAK (jabatan akademik) yang diusulkan dosen:
        // dosen melihat miliknya, TIM PAK melihat semua usulan, admin melihat yang menunggu diputuskan.
        $dupak = PengajuanDupak::with('user')
            ->when($user->isDosen(), fn ($query) => $query->where('user_id', $user->id)->diajukanDupak())
            ->when($user->isAdmin(), fn ($query) => $query->diajukanDupak())
            ->latest('diajukan_at')
            ->latest('id')
            ->get();

        return view('dashboard', [
            'statistics' => [
                'total' => $dupak->count(),
                // TIM PAK menunggu giliran menilai; dosen & admin menunggu keputusan verifikasi.
                'menunggu' => $dupak->whereIn('status', $user->isTimPak() ? ['diajukan', 'dinilai'] : ['diajukan_dupak'])->count(),
                'disetujui' => $dupak->where('status', 'disetujui')->count(),
                'perbaikan' => $dupak->where('status', 'perlu_perbaikan')->count(),
                'ditolak' => $dupak->where('status', 'ditolak')->count(),
            ],
            'dupak' => $dupak,
            // Total angka kredit hasil penilaian TIM PAK per periode (tabel dosen).
            'nilai' => $user->isDosen()
                ? $user->kegiatanDupak()
                    ->selectRaw('tahun, semester, SUM(nilai_baru) as total')
                    ->groupBy('tahun', 'semester')
                    ->get()
                    ->keyBy(fn ($baris) => $baris->tahun.'-'.$baris->semester)
                : collect(),
        ]);
    }
}
