<?php

namespace App\Http\Controllers;

use App\Models\PengajuanJabatan;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(Request $request): View
    {
        $user = $request->user();

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
        ]);
    }
}
