<?php

use App\Http\Middleware\EnsureRoleSelected;
use App\Http\Middleware\EnsureUserIsAdmin;
use App\Http\Middleware\EnsureUserIsDosen;
use App\Http\Middleware\EnsureUserIsTimPak;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

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
            'tim_pak' => EnsureUserIsTimPak::class,
        ]);

        $middleware->web(append: [
            EnsureRoleSelected::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();
