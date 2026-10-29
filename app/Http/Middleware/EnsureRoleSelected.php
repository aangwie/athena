<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureRoleSelected
{
    /**
     * Arahkan user multi-role ke halaman pemilihan peran sebelum masuk aplikasi.
     */
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->user()?->needsRoleSelection() && ! $request->routeIs('role.*', 'logout')) {
            return redirect()->route('role.select');
        }

        return $next($request);
    }
}
