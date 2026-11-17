<?php

namespace App\Providers;

use App\Models\Instansi;
use App\Models\Pengaturan;
use Illuminate\Http\Middleware\TrustProxies;
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
        // Hosting di belakang reverse proxy (Cloudflare, LiteSpeed, dsb.): percayai header
        // X-Forwarded-* agar skema HTTPS dan IP klien terbaca benar, sehingga URL redirect
        // dan cookie sesi konsisten (salah satu pemicu 419 Page Expired).
        if ($proxies = config('app.trusted_proxies')) {
            TrustProxies::at($proxies);
        }

        // Nama web dari halaman Pengaturan dipakai sidebar, judul halaman, dan footer.
        View::composer(
            ['layouts.app', 'layouts.auth', 'auth.login', 'dashboard', 'profil.pengajuan.cetak', 'skp.cetak', 'surat.pakta-integritas.cetak', 'surat.pakta-integritas.validasi', 'surat.bap-senat.cetak', 'surat.baki.cetak', 'surat.pernyataan-pimpinan.cetak', 'surat.pengantar.cetak'],
            fn ($view) => $view->with('namaWeb', Pengaturan::namaWeb()),
        );

        // Tombol bantuan di sidebar menuju WhatsApp admin instansi.
        View::composer(
            'layouts.app',
            fn ($view) => $view->with('waAdmin', Instansi::whatsappAdmin()),
        );
    }
}
