<?php

namespace App\Http\Controllers\Instansi;

use App\Http\Controllers\Controller;
use App\Http\Requests\InstansiRequest;
use App\Models\Instansi;
use Illuminate\Http\RedirectResponse;
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
            $logo = $request->file('logo');

            $data['logo_base64'] = sprintf(
                'data:%s;base64,%s',
                $logo->getMimeType(),
                base64_encode($logo->get()),
            );
        }

        $instansi->fill($data)->save();

        return redirect()
            ->route('instansi.profil.edit')
            ->with('status', 'Data instansi berhasil disimpan.');
    }
}
