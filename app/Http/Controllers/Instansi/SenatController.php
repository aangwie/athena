<?php

namespace App\Http\Controllers\Instansi;

use App\Http\Controllers\Controller;
use App\Models\Senat;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class SenatController extends Controller
{
    public function index(Request $request): View
    {
        $items = Senat::query()
            ->with('user')
            ->get()
            ->sortBy(fn (Senat $item) => [$item->jabatan === 'ketua' ? 0 : 1, $item->user?->name ?? ''])
            ->values();

        // Parameter ?edit= memakai ulang formulir yang sama di halaman ini.
        return view('instansi.senat.index', [
            'items' => $items,
            'editSenat' => $items->firstWhere('id', (int) $request->query('edit')),
            'jabatanList' => Senat::JABATAN,
            'dosenList' => User::query()
                ->where('role', 'dosen')
                ->where('is_active', true)
                ->orderBy('name')
                ->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        Senat::query()->create($this->validated($request));

        return redirect()
            ->route('instansi.senat.index')
            ->with('status', 'Anggota senat berhasil ditambahkan.');
    }

    public function update(Request $request, Senat $senat): RedirectResponse
    {
        $senat->update($this->validated($request, $senat));

        return redirect()
            ->route('instansi.senat.index')
            ->with('status', 'Data anggota senat berhasil diperbarui.');
    }

    public function destroy(Senat $senat): RedirectResponse
    {
        $senat->delete();

        return redirect()
            ->route('instansi.senat.index')
            ->with('status', 'Anggota senat berhasil dihapus.');
    }

    /**
     * @return array<string, mixed>
     */
    private function validated(Request $request, ?Senat $senat = null): array
    {
        return $request->validate([
            'user_id' => [
                'required',
                'integer',
                Rule::exists('users', 'id')->where(
                    fn ($query) => $query->where('role', 'dosen')->where('is_active', true),
                ),
                Rule::unique('senat', 'user_id')->ignore($senat?->id),
            ],
            'jabatan' => ['required', Rule::in(array_keys(Senat::JABATAN))],
        ], [
            'user_id.required' => 'Nama dosen wajib dipilih.',
            'user_id.exists' => 'Dosen tidak ditemukan atau sudah tidak aktif.',
            'user_id.unique' => 'Dosen tersebut sudah terdaftar pada senat.',
            'jabatan.required' => 'Jabatan wajib dipilih.',
            'jabatan.in' => 'Jabatan senat tidak valid.',
        ], [
            'user_id' => 'nama dosen',
            'jabatan' => 'jabatan',
        ]);
    }
}
