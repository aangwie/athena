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

        return view('profil.data-pribadi', [
            'user' => $user,
            'profil' => $user->profilDosen,
        ]);
    }

    public function update(UpdateDataPribadiRequest $request): RedirectResponse
    {
        $user = $request->user();
        $data = $request->validated();

        DB::transaction(function () use ($user, $data) {
            $user->update(['name' => $data['nama']]);

            $profil = $user->profilDosen()->firstOrNew([]);
            $profil->fill(collect($data)->except('nama')->all());
            $profil->user_id = $user->id;
            $profil->save();
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
        return $request->user()->load('profilDosen', 'riwayatPendidikan');
    }
}
