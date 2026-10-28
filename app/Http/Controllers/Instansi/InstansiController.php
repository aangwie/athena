<?php

namespace App\Http\Controllers\Instansi;

use App\Http\Controllers\Controller;
use App\Http\Requests\InstansiRequest;
use App\Models\Instansi;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\View\View;

class InstansiController extends Controller
{
    public function edit(): View
    {
        return view('instansi.profil', [
            'instansi' => Instansi::query()->firstOrNew([]),
        ]);
    }

    public function update(InstansiRequest $request): RedirectResponse
    {
        $instansi = Instansi::query()->firstOrNew([]);
        $data = $request->validated();

        unset($data['logo']);

        if ($request->hasFile('logo')) {
            if ($instansi->logo_path) {
                Storage::disk('public')->delete($instansi->logo_path);
            }

            $data['logo_path'] = $this->simpanLogo($request->file('logo'));
        }

        $instansi->fill($data)->save();

        return redirect()
            ->route('instansi.profil.edit')
            ->with('status', 'Data instansi berhasil disimpan.');
    }

    /**
     * Simpan logo instansi pada disk publik dengan nama acak.
     */
    private function simpanLogo(UploadedFile $logo): string
    {
        $nama = sprintf('logo_instansi_%s.%s', Str::random(16), $logo->extension());

        return $logo->storeAs('instansi/logo', $nama, 'public');
    }
}
