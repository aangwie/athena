<?php

namespace App\Http\Controllers\Kepegawaian;

use App\Http\Controllers\Controller;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

abstract class KepegawaianController extends Controller
{
    /**
     * Model master data yang dikelola.
     *
     * @return class-string<Model>
     */
    abstract protected function modelClass(): string;

    /**
     * Konfigurasi tampilan dan route untuk sub menu kepegawaian.
     *
     * @return array<string, string>
     */
    abstract protected function modul(): array;

    public function index(): View
    {
        $model = $this->modelClass();

        return view('kepegawaian.index', [
            'modul' => $this->modul(),
            'items' => $model::query()->orderBy('nama')->get(),
        ]);
    }

    public function create(): View
    {
        return view('kepegawaian.form', [
            'modul' => $this->modul(),
            'item' => null,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $model = $this->modelClass();
        $model::query()->create($this->validated($request));

        return redirect()
            ->route($this->modul()['route'].'.index')
            ->with('status', $this->modul()['label'].' berhasil ditambahkan.');
    }

    public function edit(string $id): View
    {
        return view('kepegawaian.form', [
            'modul' => $this->modul(),
            'item' => $this->find($id),
        ]);
    }

    public function update(Request $request, string $id): RedirectResponse
    {
        $item = $this->find($id);
        $item->update($this->validated($request, $item));

        return redirect()
            ->route($this->modul()['route'].'.index')
            ->with('status', $this->modul()['label'].' berhasil diperbarui.');
    }

    public function destroy(string $id): RedirectResponse
    {
        $this->find($id)->delete();

        return redirect()
            ->route($this->modul()['route'].'.index')
            ->with('status', $this->modul()['label'].' berhasil dihapus.');
    }

    private function find(string $id): Model
    {
        $model = $this->modelClass();

        return $model::query()->findOrFail($id);
    }

    /**
     * Validasi nama master data beserta aturan unik per tabel.
     *
     * @return array<string, mixed>
     */
    private function validated(Request $request, ?Model $item = null): array
    {
        $table = (new ($this->modelClass()))->getTable();

        $unique = Rule::unique($table, 'nama');

        if ($item !== null) {
            $unique->ignore($item->getKey());
        }

        return $request->validate([
            'nama' => ['required', 'string', 'max:100', $unique],
        ], [
            'nama.required' => 'Nama wajib diisi.',
            'nama.string' => 'Nama harus berupa teks.',
            'nama.max' => 'Nama maksimal 100 karakter.',
            'nama.unique' => 'Nama tersebut sudah terdaftar.',
        ]);
    }
}
