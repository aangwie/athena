<?php

use App\Http\Middleware\EnsureRoleSelected;
use App\Http\Middleware\EnsureUserIsAdmin;
use App\Http\Middleware\EnsureUserIsDosen;
use App\Http\Middleware\EnsureUserIsDosenOrTendik;
use App\Http\Middleware\EnsureUserIsTendik;
use App\Http\Middleware\EnsureUserIsTimPak;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Session\TokenMismatchException;


return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->alias([
            'admin' => EnsureUserIsAdmin::class,
            'dosen' => EnsureUserIsDosen::class,
            'dosen_tendik' => EnsureUserIsDosenOrTendik::class,
            'tendik' => EnsureUserIsTendik::class,
            'tim_pak' => EnsureUserIsTimPak::class,
        ]);

        $middleware->web(append: [
            EnsureRoleSelected::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        $exceptions->render(function (TokenMismatchException $e, $request) {
            return redirect()->back()
                ->withInput($request->except('_token'))
                ->with('error', 'Sesi Anda telah berakhir karena tidak ada aktivitas. Silakan coba lagi.');
        });
    })->create();
