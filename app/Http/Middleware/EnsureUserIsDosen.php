<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserIsDosen
{
    /**
     * Pastikan hanya user dengan role dosen yang dapat mengakses halaman.
     */
    public function handle(Request $request, Closure $next): Response
    {
        abort_unless($request->user()?->isDosen(), 403, 'Halaman ini hanya tersedia untuk dosen.');

        return $next($request);
    }
}
