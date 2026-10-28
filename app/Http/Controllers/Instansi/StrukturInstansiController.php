<?php

namespace App\Http\Controllers\Instansi;

use App\Http\Controllers\Controller;
use App\Http\Requests\StrukturInstansiRequest;
use App\Models\JabatanStruktural;
use App\Models\StrukturInstansi;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class StrukturInstansiController extends Controller
{
    public function index(): View
    {
        $items = StrukturInstansi::query()
            ->with(['jabatanStruktural', 'user'])
            ->get()
            ->sortBy(fn (StrukturInstansi $item) => $item->jabatanStruktural?->nama ?? '')
            ->values();

        return view('instansi.struktur.index', [
            'items' => $items,
        ]);
    }

    public function create(): View
    {
        return $this->form(null);
    }

    public function store(StrukturInstansiRequest $request): RedirectResponse
    {
        StrukturInstansi::query()->create($request->validated());

        return redirect()
            ->route('instansi.struktur.index')
            ->with('status', 'Pejabat struktural berhasil ditambahkan.');
    }

    public function edit(StrukturInstansi $struktur): View
    {
        return $this->form($struktur);
    }

    public function update(StrukturInstansiRequest $request, StrukturInstansi $struktur): RedirectResponse
    {
        $struktur->update($request->validated());

        return redirect()
            ->route('instansi.struktur.index')
            ->with('status', 'Data pejabat struktural berhasil diperbarui.');
    }

    public function destroy(StrukturInstansi $struktur): RedirectResponse
    {
        $struktur->delete();

        return redirect()
            ->route('instansi.struktur.index')
            ->with('status', 'Data pejabat struktural berhasil dihapus.');
    }

    private function form(?StrukturInstansi $struktur): View
    {
        return view('instansi.struktur.form', [
            'struktur' => $struktur,
            'jabatanList' => JabatanStruktural::query()->orderBy('nama')->get(),
            'dosenList' => User::query()
                ->where('role', 'dosen')
                ->where('is_active', true)
                ->orderBy('name')
                ->get(),
        ]);
    }
}
