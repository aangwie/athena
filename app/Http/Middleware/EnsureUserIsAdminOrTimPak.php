<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserIsAdminOrTimPak
{
    /**
     * Halaman yang dipakai bersama admin dan TIM PAK (mis. panduan penilaian).
     */
    public function handle(Request $request, Closure $next): Response
    {
        abort_unless(
            $request->user()?->canReview(),
            403,
            'Halaman ini hanya tersedia untuk admin dan TIM PAK.',
        );

        return $next($request);
    }
}
