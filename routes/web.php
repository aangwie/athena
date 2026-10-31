<?php

use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\RoleSelectionController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DataDosenController;
use App\Http\Controllers\Instansi\InstansiController;
use App\Http\Controllers\Instansi\ProdiController;
use App\Http\Controllers\Instansi\SenatController;
use App\Http\Controllers\Instansi\StrukturInstansiController;
use App\Http\Controllers\KaryaTulisController;
use App\Http\Controllers\Kepegawaian\JabatanFungsionalController;
use App\Http\Controllers\Kepegawaian\JabatanStrukturalController;
use App\Http\Controllers\Kepegawaian\KepangkatanController;
use App\Http\Controllers\PengajuanDosenController;
use App\Http\Controllers\PengajuanDupakController;
use App\Http\Controllers\PengaturanController;
use App\Http\Controllers\ProfilDosenController;
use App\Http\Controllers\RiwayatPekerjaanController;
use App\Http\Controllers\RiwayatPendidikanController;
use App\Http\Controllers\SuratController;
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
    Route::get('/pilih-role', [RoleSelectionController::class, 'create'])->name('role.select');
    Route::post('/pilih-role', [RoleSelectionController::class, 'store'])->name('role.select.store');
    Route::post('/logout', [AuthenticatedSessionController::class, 'destroy'])->name('logout');

    Route::middleware('admin')->prefix('data-dosen')->name('data-dosen.')->group(function () {
        Route::get('/', [DataDosenController::class, 'index'])->name('index');
        Route::get('/create', [DataDosenController::class, 'create'])->name('create');
        Route::post('/', [DataDosenController::class, 'store'])->name('store');
        Route::get('/{id}/edit', [DataDosenController::class, 'edit'])->name('edit');
        Route::put('/{id}', [DataDosenController::class, 'update'])->name('update');
        Route::delete('/{id}', [DataDosenController::class, 'destroy'])->name('destroy');
    });

    Route::view('/panduan-pengajuan', 'panduan.pengajuan')
        ->middleware('dosen')
        ->name('panduan.pengajuan');

    Route::middleware('dosen')->prefix('profil')->name('profil.')->group(function () {
        Route::get('/', [ProfilDosenController::class, 'show'])->name('show');
        Route::get('/data-pribadi', [ProfilDosenController::class, 'edit'])->name('data-pribadi.edit');
        Route::put('/data-pribadi', [ProfilDosenController::class, 'update'])->name('data-pribadi.update');

        Route::get('/pengajuan', [PengajuanDosenController::class, 'mulai'])->name('pengajuan.mulai');
        Route::get('/pengajuan/periode', [PengajuanDosenController::class, 'periode'])->name('pengajuan.periode');
        Route::get('/pengajuan/periode-pertama', [PengajuanDosenController::class, 'periodePertama'])->name('pengajuan.periode-pertama');
        Route::get('/pengajuan/syarat-khusus', [PengajuanDosenController::class, 'syaratKhusus'])->name('pengajuan.syarat-khusus');
        Route::post('/pengajuan/syarat-khusus', [PengajuanDosenController::class, 'simpanSyaratKhusus'])->name('pengajuan.syarat-khusus.store');
        Route::get('/pengajuan/syarat-khusus/berkas/{jenis}', [PengajuanDosenController::class, 'berkasSyaratKhusus'])->name('pengajuan.syarat-khusus.berkas');
        Route::post('/pengajuan/syarat-khusus/ajukan-semua', [PengajuanDosenController::class, 'ajukanSemuaSyaratKhusus'])->name('pengajuan.syarat-khusus.ajukan-semua');
        Route::post('/pengajuan/syarat-khusus/{syaratKhusus}/ajukan', [PengajuanDosenController::class, 'ajukanSyaratKhusus'])->name('pengajuan.syarat-khusus.ajukan');
        Route::get('/pengajuan/create', [PengajuanDosenController::class, 'create'])->name('pengajuan.create');
        Route::post('/pengajuan/ajukan', [PengajuanDosenController::class, 'ajukan'])->name('pengajuan.ajukan');
        Route::post('/pengajuan/ajukan-dupak', [PengajuanDosenController::class, 'ajukanDupak'])->name('pengajuan.ajukan-dupak');
        Route::get('/pengajuan/riwayat', [PengajuanDosenController::class, 'riwayat'])->name('pengajuan.riwayat');
        Route::get('/pengajuan/riwayat-ikd', [PengajuanDosenController::class, 'riwayatIkd'])->name('pengajuan.riwayat-ikd');

        Route::get('/riwayat-karya-tulis', [KaryaTulisController::class, 'index'])->name('karya-tulis.index');
        Route::post('/riwayat-karya-tulis', [KaryaTulisController::class, 'store'])->name('karya-tulis.store');
        Route::put('/riwayat-karya-tulis/{karyaTulis}', [KaryaTulisController::class, 'update'])->name('karya-tulis.update');
        Route::delete('/riwayat-karya-tulis/{karyaTulis}', [KaryaTulisController::class, 'destroy'])->name('karya-tulis.destroy');
        Route::post('/pengajuan/ajukan-ulang', [PengajuanDosenController::class, 'ajukanUlang'])->name('pengajuan.ajukan-ulang');
        Route::get('/pengajuan/cetak', [PengajuanDosenController::class, 'cetak'])->name('pengajuan.cetak');
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
        Route::post('/pengajuan/penunjang', [PengajuanDosenController::class, 'storePenunjang'])->name('pengajuan.penunjang.store');
        Route::put('/pengajuan/penunjang/{kegiatan}', [PengajuanDosenController::class, 'updatePenunjang'])->name('pengajuan.penunjang.update');
        Route::delete('/pengajuan/penunjang/{kegiatan}', [PengajuanDosenController::class, 'destroyPenunjang'])->name('pengajuan.penunjang.destroy');

        Route::get('/riwayat-pendidikan', [RiwayatPendidikanController::class, 'index'])->name('pendidikan.index');
        Route::get('/riwayat-pendidikan/create', [RiwayatPendidikanController::class, 'create'])->name('pendidikan.create');
        Route::post('/riwayat-pendidikan', [RiwayatPendidikanController::class, 'store'])->name('pendidikan.store');
        Route::get('/riwayat-pendidikan/{riwayatPendidikan}/edit', [RiwayatPendidikanController::class, 'edit'])->name('pendidikan.edit');
        Route::put('/riwayat-pendidikan/{riwayatPendidikan}', [RiwayatPendidikanController::class, 'update'])->name('pendidikan.update');
        Route::delete('/riwayat-pendidikan/{riwayatPendidikan}', [RiwayatPendidikanController::class, 'destroy'])->name('pendidikan.destroy');
        Route::get('/riwayat-pendidikan/{riwayatPendidikan}/berkas/{jenis}', [RiwayatPendidikanController::class, 'berkas'])
            ->whereIn('jenis', ['ijazah', 'transkrip'])
            ->name('pendidikan.berkas');

        Route::get('/riwayat-pekerjaan', [RiwayatPekerjaanController::class, 'index'])->name('pekerjaan.index');
        Route::put('/riwayat-pekerjaan', [RiwayatPekerjaanController::class, 'updateIdentitas'])->name('pekerjaan.update');

        Route::post('/riwayat-pekerjaan/kepangkatan', [RiwayatPekerjaanController::class, 'storeKepangkatan'])->name('pekerjaan.kepangkatan.store');
        Route::put('/riwayat-pekerjaan/kepangkatan/{riwayatKepangkatan}', [RiwayatPekerjaanController::class, 'updateKepangkatan'])->name('pekerjaan.kepangkatan.update');
        Route::delete('/riwayat-pekerjaan/kepangkatan/{riwayatKepangkatan}', [RiwayatPekerjaanController::class, 'destroyKepangkatan'])->name('pekerjaan.kepangkatan.destroy');

        Route::post('/riwayat-pekerjaan/jabatan', [RiwayatPekerjaanController::class, 'storeJabatan'])->name('pekerjaan.jabatan.store');
        Route::put('/riwayat-pekerjaan/jabatan/{riwayatJabatan}', [RiwayatPekerjaanController::class, 'updateJabatan'])->name('pekerjaan.jabatan.update');
        Route::delete('/riwayat-pekerjaan/jabatan/{riwayatJabatan}', [RiwayatPekerjaanController::class, 'destroyJabatan'])->name('pekerjaan.jabatan.destroy');

        Route::post('/riwayat-pekerjaan/angka-kredit', [RiwayatPekerjaanController::class, 'storeAngkaKredit'])->name('pekerjaan.angka-kredit.store');
        Route::put('/riwayat-pekerjaan/angka-kredit/{riwayatAngkaKredit}', [RiwayatPekerjaanController::class, 'updateAngkaKredit'])->name('pekerjaan.angka-kredit.update');
        Route::delete('/riwayat-pekerjaan/angka-kredit/{riwayatAngkaKredit}', [RiwayatPekerjaanController::class, 'destroyAngkaKredit'])->name('pekerjaan.angka-kredit.destroy');
    });

    Route::middleware('admin')->prefix('verifikasi-dupak')->name('verifikasi-dupak.')->group(function () {
        Route::get('/{pengajuan}', [PengajuanDupakController::class, 'verifikasi'])->name('show');
        Route::put('/{pengajuan}', [PengajuanDupakController::class, 'putuskan'])->name('update');
    });

    Route::middleware('admin')->prefix('verifikasi-berkas')->name('verifikasi-berkas.')->group(function () {
        Route::get('/', [PengajuanDosenController::class, 'verifikasiSyaratKhusus'])->name('index');
        Route::put('/{syaratKhusus}/berkas/{jenis}', [PengajuanDosenController::class, 'putuskanSyaratKhusus'])->name('update');
        Route::get('/{syaratKhusus}/berkas/{jenis}', [PengajuanDosenController::class, 'berkasVerifikasi'])->name('berkas');
    });

    Route::middleware('admin')->prefix('surat')->name('surat.')->group(function () {
        Route::get('/', [SuratController::class, 'index'])->name('index');
        Route::post('/pernyataan-pimpinan', [SuratController::class, 'storePernyataanPimpinan'])->name('pernyataan-pimpinan.store');
        Route::put('/pernyataan-pimpinan/{pernyataan}', [SuratController::class, 'updatePernyataanPimpinan'])->name('pernyataan-pimpinan.update');
        Route::delete('/pernyataan-pimpinan/{pernyataan}', [SuratController::class, 'destroyPernyataanPimpinan'])->name('pernyataan-pimpinan.destroy');
        Route::get('/pernyataan-pimpinan/{pernyataan}/cetak', [SuratController::class, 'cetakPernyataanPimpinan'])->name('pernyataan-pimpinan.cetak');

        Route::post('/pakta-integritas', [SuratController::class, 'storePaktaIntegritas'])->name('pakta-integritas.store');
        Route::put('/pakta-integritas/{pakta}', [SuratController::class, 'updatePaktaIntegritas'])->name('pakta-integritas.update');
        Route::delete('/pakta-integritas/{pakta}', [SuratController::class, 'destroyPaktaIntegritas'])->name('pakta-integritas.destroy');
        Route::get('/pakta-integritas/{pakta}/cetak', [SuratController::class, 'cetakPaktaIntegritas'])->name('pakta-integritas.cetak');
    });

    Route::middleware('tim_pak')->prefix('tim-pak')->name('tim-pak.')->group(function () {
        Route::get('/pengajuan', [PengajuanDupakController::class, 'index'])->name('pengajuan.index');
        Route::get('/pengajuan/{pengajuan}', [PengajuanDupakController::class, 'show'])->name('pengajuan.show');
        Route::put('/pengajuan/{pengajuan}', [PengajuanDupakController::class, 'update'])->name('pengajuan.update');
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

        Route::get('/senat', [SenatController::class, 'index'])->name('senat.index');
        Route::post('/senat', [SenatController::class, 'store'])->name('senat.store');
        Route::put('/senat/{senat}', [SenatController::class, 'update'])->name('senat.update');
        Route::delete('/senat/{senat}', [SenatController::class, 'destroy'])->name('senat.destroy');

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
        Route::post('/nama-web', [PengaturanController::class, 'simpanNamaWeb'])->name('nama-web');
        Route::post('/update', [PengaturanController::class, 'update'])->name('update');
        Route::post('/clear-cache', [PengaturanController::class, 'clearCache'])->name('clear-cache');
        Route::post('/migrate', [PengaturanController::class, 'migrate'])->name('migrate');
        Route::post('/storage-link', [PengaturanController::class, 'storageLink'])->name('storage-link');
        Route::post('/database/mulai', [PengaturanController::class, 'dbMulai'])->name('db.mulai');
        Route::post('/database/langkah', [PengaturanController::class, 'dbLangkah'])->name('db.langkah');
        Route::get('/database/unduh/{berkas}', [PengaturanController::class, 'dbUnduh'])->name('db.unduh');
        Route::delete('/database/kosongkan', [PengaturanController::class, 'dbKosongkan'])->name('db.kosongkan');
        Route::delete('/log', [PengaturanController::class, 'hapusLog'])->name('log.hapus');
    });
});
