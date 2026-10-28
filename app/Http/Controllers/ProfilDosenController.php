<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateDataPribadiRequest;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class ProfilDosenController extends Controller
{
    public function show(Request $request): View
    {
        $user = $this->userWithProfile($request);

        return view('profil.show', [
            'user' => $user,
            'profil' => $user->profilDosen,
            'riwayatPendidikan' => $user->riwayatPendidikan
                ->sortBy(fn ($item) => ($item->jenjangRank() * 10000) + $item->tahun_masuk)
                ->values(),
        ]);
    }

    public function edit(Request $request): View
    {
        $user = $this->userWithProfile($request);
        $profil = $user->profilDosen;

        $anakList = old('children');

        if ($anakList === null) {
            $anakList = $profil?->anak->map(fn ($anak) => [
                'nama' => $anak->nama,
                'tempat_lahir' => $anak->tempat_lahir,
                'tanggal_lahir' => $anak->tanggal_lahir?->format('Y-m-d'),
                'jenis_kelamin' => $anak->jenis_kelamin,
                'pekerjaan' => $anak->pekerjaan,
            ])->all() ?? [];
        }

        return view('profil.data-pribadi', [
            'user' => $user,
            'profil' => $profil,
            'anakList' => $anakList,
        ]);
    }

    public function update(UpdateDataPribadiRequest $request): RedirectResponse
    {
        $user = $request->user();
        $data = $request->validated();

        DB::transaction(function () use ($user, $data) {
            $user->update(['name' => $data['nama']]);

            $profil = $user->profilDosen()->firstOrNew([]);
            $profil->fill(collect($data)->except(['nama', 'children'])->all());
            $profil->user_id = $user->id;
            $profil->save();

            $profil->anak()->delete();

            foreach ($data['children'] ?? [] as $anak) {
                $profil->anak()->create([
                    'nama' => $anak['nama'],
                    'tempat_lahir' => $anak['tempat_lahir'],
                    'tanggal_lahir' => $anak['tanggal_lahir'],
                    'jenis_kelamin' => $anak['jenis_kelamin'],
                    'pekerjaan' => $anak['pekerjaan'] ?? null,
                ]);
            }
        });

        return redirect()
            ->route('profil.show')
            ->with('status', 'Data pribadi berhasil disimpan dan ditampilkan pada portofolio Anda.');
    }

    public function pendidikan(): View
    {
        return view('profil.riwayat-pendidikan');
    }

    private function userWithProfile(Request $request): User
    {
        return $request->user()->load('profilDosen.anak', 'riwayatPendidikan');
    }
}
