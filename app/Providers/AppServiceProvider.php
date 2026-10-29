<?php

namespace App\Providers;

use App\Models\Pengaturan;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Nama web dari halaman Pengaturan dipakai sidebar, judul halaman, dan footer.
        View::composer(
            ['layouts.app', 'layouts.auth', 'auth.login', 'dashboard', 'profil.pengajuan.cetak'],
            fn ($view) => $view->with('namaWeb', Pengaturan::namaWeb()),
        );
    }
}
