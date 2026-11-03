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
        // Dosen yang sudah menjabat tidak ditawarkan lagi (kecuali pejabat yang sedang diubah).
        $sudahMenjabat = StrukturInstansi::query()
            ->when($struktur, fn ($query) => $query->whereKeyNot($struktur->getKey()))
            ->pluck('user_id');

        // Jabatan yang sudah terisi juga tidak ditawarkan lagi.
        $jabatanTerisi = StrukturInstansi::query()
            ->when($struktur, fn ($query) => $query->whereKeyNot($struktur->getKey()))
            ->pluck('jabatan_struktural_id');

        return view('instansi.struktur.form', [
            'struktur' => $struktur,
            'jabatanList' => JabatanStruktural::query()
                ->whereNotIn('id', $jabatanTerisi)
                ->orderBy('nama')
                ->get(),
            'dosenList' => User::query()
                ->where('role', 'dosen')
                ->where('is_active', true)
                ->whereNotIn('id', $sudahMenjabat)
                ->orderBy('name')
                ->get(),
        ]);
    }
}
