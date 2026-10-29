<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserIsTimPak
{
    /**
     * Pastikan hanya user dengan role TIM PAK yang dapat mengakses halaman.
     */
    public function handle(Request $request, Closure $next): Response
    {
        abort_unless($request->user()?->isTimPak(), 403, 'Halaman ini hanya tersedia untuk TIM PAK.');

        return $next($request);
    }
}
