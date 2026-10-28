<?php

use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\Instansi\InstansiController;
use App\Http\Controllers\Instansi\StrukturInstansiController;
use App\Http\Controllers\Kepegawaian\JabatanFungsionalController;
use App\Http\Controllers\Kepegawaian\JabatanStrukturalController;
use App\Http\Controllers\Kepegawaian\KepangkatanController;
use App\Http\Controllers\ProfilDosenController;
use App\Http\Controllers\RiwayatPendidikanController;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return Auth::check()
        ? redirect()->route('dashboard')
        : redirect()->route('login');
});

Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthenticatedSessionController::class, 'create'])->name('login');
    Route::post('/login', [AuthenticatedSessionController::class, 'store'])->name('login.store');
});

Route::middleware('auth')->group(function () {
    Route::get('/dashboard', DashboardController::class)->name('dashboard');
    Route::post('/logout', [AuthenticatedSessionController::class, 'destroy'])->name('logout');

    Route::middleware('dosen')->prefix('profil')->name('profil.')->group(function () {
        Route::get('/', [ProfilDosenController::class, 'show'])->name('show');
        Route::get('/data-pribadi', [ProfilDosenController::class, 'edit'])->name('data-pribadi.edit');
        Route::put('/data-pribadi', [ProfilDosenController::class, 'update'])->name('data-pribadi.update');

        Route::get('/riwayat-pendidikan', [RiwayatPendidikanController::class, 'index'])->name('pendidikan.index');
        Route::get('/riwayat-pendidikan/create', [RiwayatPendidikanController::class, 'create'])->name('pendidikan.create');
        Route::post('/riwayat-pendidikan', [RiwayatPendidikanController::class, 'store'])->name('pendidikan.store');
        Route::get('/riwayat-pendidikan/{riwayatPendidikan}/edit', [RiwayatPendidikanController::class, 'edit'])->name('pendidikan.edit');
        Route::put('/riwayat-pendidikan/{riwayatPendidikan}', [RiwayatPendidikanController::class, 'update'])->name('pendidikan.update');
        Route::delete('/riwayat-pendidikan/{riwayatPendidikan}', [RiwayatPendidikanController::class, 'destroy'])->name('pendidikan.destroy');
        Route::get('/riwayat-pendidikan/{riwayatPendidikan}/berkas/{jenis}', [RiwayatPendidikanController::class, 'berkas'])
            ->whereIn('jenis', ['ijazah', 'transkrip'])
            ->name('pendidikan.berkas');
    });

    Route::middleware('admin')->prefix('kepegawaian')->name('kepegawaian.')->group(function () {
        Route::resource('jabatan-fungsional', JabatanFungsionalController::class)
            ->except('show')
            ->parameters(['jabatan-fungsional' => 'id']);

        Route::resource('jabatan-struktural', JabatanStrukturalController::class)
            ->except('show')
            ->parameters(['jabatan-struktural' => 'id']);

        Route::resource('kepangkatan', KepangkatanController::class)
            ->except('show')
            ->parameters(['kepangkatan' => 'id']);
    });

    Route::middleware('admin')->prefix('instansi')->name('instansi.')->group(function () {
        Route::get('/', [InstansiController::class, 'edit'])->name('profil.edit');
        Route::put('/', [InstansiController::class, 'update'])->name('profil.update');

        Route::get('/struktur', [StrukturInstansiController::class, 'index'])->name('struktur.index');
        Route::get('/struktur/create', [StrukturInstansiController::class, 'create'])->name('struktur.create');
        Route::post('/struktur', [StrukturInstansiController::class, 'store'])->name('struktur.store');
        Route::get('/struktur/{struktur}/edit', [StrukturInstansiController::class, 'edit'])->name('struktur.edit');
        Route::put('/struktur/{struktur}', [StrukturInstansiController::class, 'update'])->name('struktur.update');
        Route::delete('/struktur/{struktur}', [StrukturInstansiController::class, 'destroy'])->name('struktur.destroy');
    });
});
