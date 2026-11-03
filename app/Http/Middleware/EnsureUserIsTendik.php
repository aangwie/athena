<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserIsTendik
{
    /**
     * Pastikan hanya user dengan role tendik yang dapat mengakses halaman.
     */
    public function handle(Request $request, Closure $next): Response
    {
        abort_unless($request->user()?->isTendik(), 403, 'Halaman ini hanya tersedia untuk tendik.');

        return $next($request);
    }
}
