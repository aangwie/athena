<?php

use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\DataDosenController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\Instansi\InstansiController;
use App\Http\Controllers\Instansi\ProdiController;
use App\Http\Controllers\Instansi\StrukturInstansiController;
use App\Http\Controllers\Kepegawaian\JabatanFungsionalController;
use App\Http\Controllers\Kepegawaian\JabatanStrukturalController;
use App\Http\Controllers\Kepegawaian\KepangkatanController;
use App\Http\Controllers\PengajuanDosenController;
use App\Http\Controllers\PengaturanController;
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

    Route::middleware('admin')->prefix('data-dosen')->name('data-dosen.')->group(function () {
        Route::get('/', [DataDosenController::class, 'index'])->name('index');
        Route::get('/create', [DataDosenController::class, 'create'])->name('create');
        Route::post('/', [DataDosenController::class, 'store'])->name('store');
        Route::get('/{id}/edit', [DataDosenController::class, 'edit'])->name('edit');
        Route::put('/{id}', [DataDosenController::class, 'update'])->name('update');
        Route::delete('/{id}', [DataDosenController::class, 'destroy'])->name('destroy');
    });

    Route::middleware('dosen')->prefix('profil')->name('profil.')->group(function () {
        Route::get('/', [ProfilDosenController::class, 'show'])->name('show');
        Route::get('/data-pribadi', [ProfilDosenController::class, 'edit'])->name('data-pribadi.edit');
        Route::put('/data-pribadi', [ProfilDosenController::class, 'update'])->name('data-pribadi.update');

        Route::get('/pengajuan/periode', [PengajuanDosenController::class, 'periode'])->name('pengajuan.periode');
        Route::get('/pengajuan/create', [PengajuanDosenController::class, 'create'])->name('pengajuan.create');
        Route::post('/pengajuan/pendidikan', [PengajuanDosenController::class, 'storePendidikan'])->name('pengajuan.pendidikan.store');
        Route::put('/pengajuan/pendidikan/{kegiatan}', [PengajuanDosenController::class, 'updatePendidikan'])->name('pengajuan.pendidikan.update');
        Route::delete('/pengajuan/pendidikan/{kegiatan}', [PengajuanDosenController::class, 'destroyPendidikan'])->name('pengajuan.pendidikan.destroy');

        Route::post('/pengajuan/pelaksanaan-pendidikan', [PengajuanDosenController::class, 'storePelaksanaan'])->name('pengajuan.pelaksanaan.store');
        Route::put('/pengajuan/pelaksanaan-pendidikan/{kegiatan}', [PengajuanDosenController::class, 'updatePelaksanaan'])->name('pengajuan.pelaksanaan.update');
        Route::delete('/pengajuan/pelaksanaan-pendidikan/{kegiatan}', [PengajuanDosenController::class, 'destroyPelaksanaan'])->name('pengajuan.pelaksanaan.destroy');

        Route::post('/pengajuan/penelitian', [PengajuanDosenController::class, 'storePenelitian'])->name('pengajuan.penelitian.store');
        Route::put('/pengajuan/penelitian/{kegiatan}', [PengajuanDosenController::class, 'updatePenelitian'])->name('pengajuan.penelitian.update');
        Route::delete('/pengajuan/penelitian/{kegiatan}', [PengajuanDosenController::class, 'destroyPenelitian'])->name('pengajuan.penelitian.destroy');
        Route::post('/pengajuan/abdimas', [PengajuanDosenController::class, 'storeAbdimas'])->name('pengajuan.abdimas.store');
        Route::put('/pengajuan/abdimas/{kegiatan}', [PengajuanDosenController::class, 'updateAbdimas'])->name('pengajuan.abdimas.update');
        Route::delete('/pengajuan/abdimas/{kegiatan}', [PengajuanDosenController::class, 'destroyAbdimas'])->name('pengajuan.abdimas.destroy');

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

        Route::get('/prodi', [ProdiController::class, 'index'])->name('prodi.index');
        Route::get('/prodi/create', [ProdiController::class, 'create'])->name('prodi.create');
        Route::post('/prodi', [ProdiController::class, 'store'])->name('prodi.store');
        Route::get('/prodi/{prodi}/edit', [ProdiController::class, 'edit'])->name('prodi.edit');
        Route::put('/prodi/{prodi}', [ProdiController::class, 'update'])->name('prodi.update');
        Route::delete('/prodi/{prodi}', [ProdiController::class, 'destroy'])->name('prodi.destroy');
    });

    Route::middleware('admin')->prefix('pengaturan')->name('pengaturan.')->group(function () {
        Route::get('/', [PengaturanController::class, 'index'])->name('index');
        Route::post('/token', [PengaturanController::class, 'simpanToken'])->name('token');
        Route::post('/update', [PengaturanController::class, 'update'])->name('update');
        Route::post('/clear-cache', [PengaturanController::class, 'clearCache'])->name('clear-cache');
        Route::post('/migrate', [PengaturanController::class, 'migrate'])->name('migrate');
        Route::post('/storage-link', [PengaturanController::class, 'storageLink'])->name('storage-link');
        Route::delete('/log', [PengaturanController::class, 'hapusLog'])->name('log.hapus');
    });
});
