<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class NotifikasiController extends Controller
{
    /**
     * Tandai semua notifikasi lonceng sudah dilihat, sehingga badge merah hilang
     * sampai ada pengajuan baru masuk.
     */
    public function baca(Request $request): JsonResponse|RedirectResponse
    {
        $request->user()->forceFill(['notifikasi_dibaca_at' => now()])->save();

        return $request->expectsJson()
            ? response()->json(['status' => 'ok'])
            : back();
    }
}