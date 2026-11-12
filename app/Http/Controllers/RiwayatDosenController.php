<?php

namespace App\Http\Controllers;

use App\Models\RiwayatPendidikan;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class RiwayatDosenController extends Controller
{
    /**
     * Halaman ringkas riwayat dosen yang sedang login.
     */
    public function show(Request $request): View
    {
        return view('riwayat-dosen.show', [
            'dosen' => $this->loadRiwayat($request->user()),
        ]);
    }

    /**
     * Daftar dosen untuk admin memilih riwayat yang ingin dilihat.
     */
    public function index(): View
    {
        return view('riwayat-dosen.index', [
            'items' => User::berRole(['dosen']),
        ]);
    }

    public function detail(User $user): View
    {
        return view('riwayat-dosen.show', [
            'dosen' => $this->loadRiwayat($user),
        ]);
    }

    public function berkas(User $user, RiwayatPendidikan $riwayatPendidikan, string $jenis): StreamedResponse
    {
        abort_unless($riwayatPendidikan->user_id === $user->id, 404);

        $path = $riwayatPendidikan->pathBerkas($jenis);

        abort_unless($path && Storage::disk('local')->exists($path), 404);

        return Storage::disk('local')->response($path, basename($path), [
            'Content-Type' => 'application/pdf',
        ]);
    }

    public function skDosenTetap(User $user): StreamedResponse
    {
        $profil = $user->profilDosen;

        abort_unless(
            $profil && $profil->sk_dosen_tetap_path && Storage::disk('local')->exists($profil->sk_dosen_tetap_path),
            404,
        );

        return Storage::disk('local')->response($profil->sk_dosen_tetap_path, basename($profil->sk_dosen_tetap_path), [
            'Content-Type' => 'application/pdf',
        ]);
    }

    private function loadRiwayat(User $user): User
    {
        return $user->load([
            'profilDosen',
            'riwayatPendidikan',
            'riwayatKepangkatan.kepangkatan',
            'riwayatJabatanStruktural.jabatanStruktural',
            'riwayatJabatanStruktural.jabatanFungsional',
            'riwayatAngkaKredit',
        ]);
    }
}