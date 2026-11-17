<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class PreventPageCaching
{
    /**
     * Halaman HTML membawa token CSRF. Bila browser (back/forward cache) atau page cache
     * hosting menyimpan versi lama, token bisa kedaluwarsa dan form ditolak 419.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        $tipe = (string) $response->headers->get('Content-Type', '');

        if (str_contains($tipe, 'text/html')) {
            $response->headers->set('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0');
            $response->headers->set('Pragma', 'no-cache');
        }

        return $response;
    }
}
