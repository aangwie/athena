<?php

use App\Http\Middleware\EnsureRoleSelected;
use App\Http\Middleware\EnsureUserIsAdmin;
use App\Http\Middleware\EnsureUserIsAdminOrTimPak;
use App\Http\Middleware\EnsureUserIsDosen;
use App\Http\Middleware\EnsureUserIsDosenOrTendik;
use App\Http\Middleware\EnsureUserIsTendik;
use App\Http\Middleware\EnsureUserIsTimPak;
use App\Http\Middleware\PreventPageCaching;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\HttpException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->alias([
            'admin' => EnsureUserIsAdmin::class,
            'admin_tim_pak' => EnsureUserIsAdminOrTimPak::class,
            'dosen' => EnsureUserIsDosen::class,
            'dosen_tendik' => EnsureUserIsDosenOrTendik::class,
            'tendik' => EnsureUserIsTendik::class,
            'tim_pak' => EnsureUserIsTimPak::class,
        ]);

        $middleware->web(append: [
            EnsureRoleSelected::class,
            PreventPageCaching::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        // 419 Page Expired: token CSRF tidak lagi cocok dengan sesi (sesi kedaluwarsa,
        // cookie terhapus, atau halaman lama dikirim ulang dari cache). Laravel mengubah
        // TokenMismatchException menjadi HttpException 419 sebelum callback ini dipanggil.
        $exceptions->render(function (HttpException $e, Request $request) {
            if ($e->getStatusCode() !== 419) {
                return null;
            }

            $pesan = 'Sesi Anda telah berakhir karena halaman dibiarkan terlalu lama. Silakan muat ulang halaman lalu coba lagi.';

            if ($request->expectsJson()) {
                return response()->json([
                    'message' => $pesan,
                    'sesi_berakhir' => true,
                ], 419);
            }

            // Kembali ke halaman form sebelumnya, tetapi jangan pernah mengarahkan ke
            // domain lain (referer bisa dipalsukan) maupun ke URL POST itu sendiri.
            $sebelumnya = url()->previous();
            $samaHost = parse_url($sebelumnya, PHP_URL_HOST) === $request->getHost();
            $bolehKembali = $samaHost && ! in_array($sebelumnya, [url('/'), url()->current()], true);
            $tujuan = $bolehKembali
                ? $sebelumnya
                : ($request->user() ? route('dashboard') : route('login'));

            return redirect()
                ->to($tujuan)
                ->withInput($request->except(['_token', '_method', 'password', 'password_confirmation']))
                ->with('error', $pesan);
        });
    })->create();
