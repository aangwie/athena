<?php

namespace App\Http\Controllers\Instansi;

use App\Http\Controllers\Controller;
use App\Http\Requests\ProdiRequest;
use App\Models\Prodi;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class ProdiController extends Controller
{
    public function index(): View
    {
        return view('instansi.prodi.index', [
            'items' => Prodi::query()->orderBy('nama_prodi')->get(),
        ]);
    }

    public function create(): View
    {
        return $this->form(null);
    }

    public function store(ProdiRequest $request): RedirectResponse
    {
        Prodi::query()->create($request->validated());

        return redirect()
            ->route('instansi.prodi.index')
            ->with('status', 'Data prodi berhasil ditambahkan.');
    }

    public function edit(Prodi $prodi): View
    {
        return $this->form($prodi);
    }

    public function update(ProdiRequest $request, Prodi $prodi): RedirectResponse
    {
        $prodi->update($request->validated());

        return redirect()
            ->route('instansi.prodi.index')
            ->with('status', 'Data prodi berhasil diperbarui.');
    }

    public function destroy(Prodi $prodi): RedirectResponse
    {
        $prodi->delete();

        return redirect()
            ->route('instansi.prodi.index')
            ->with('status', 'Data prodi berhasil dihapus.');
    }

    private function form(?Prodi $prodi): View
    {
        return view('instansi.prodi.form', [
            'prodi' => $prodi,
        ]);
    }
}
