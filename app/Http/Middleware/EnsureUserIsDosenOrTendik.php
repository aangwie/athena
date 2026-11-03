<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserIsDosenOrTendik
{
    /**
     * Halaman yang dipakai bersama dosen dan tendik (data pribadi, riwayat pendidikan).
     */
    public function handle(Request $request, Closure $next): Response
    {
        abort_unless(
            $request->user()?->isDosen() || $request->user()?->isTendik(),
            403,
            'Halaman ini hanya tersedia untuk dosen dan tendik.',
        );

        return $next($request);
    }
}
