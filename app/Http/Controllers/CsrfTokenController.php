<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CsrfTokenController extends Controller
{
    /**
     * Bagikan token CSRF terbaru untuk halaman yang sudah lama terbuka, supaya
     * pengiriman form berikutnya tidak ditolak dengan 419 Page Expired.
     */
    public function __invoke(Request $request): JsonResponse
    {
        return response()
            ->json(['token' => $request->session()->token()])
            ->header('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0');
    }
}
