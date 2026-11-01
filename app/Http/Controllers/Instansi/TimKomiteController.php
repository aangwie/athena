<?php

namespace App\Http\Controllers\Instansi;

use App\Http\Controllers\Controller;
use App\Models\TimKomite;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class TimKomiteController extends Controller
{
    public function index(Request $request): View
    {
        $items = TimKomite::query()
            ->with('user')
            ->get()
            ->sortBy(fn (TimKomite $item) => [array_search($item->jabatan, array_keys(TimKomite::JABATAN)), $item->user?->name ?? ''])
            ->values();

        // Parameter ?edit= memakai ulang formulir yang sama di halaman ini.
        return view('instansi.tim-komite.index', [
            'items' => $items,
            'editTimKomite' => $items->firstWhere('id', (int) $request->query('edit')),
            'jabatanList' => TimKomite::JABATAN,
            'dosenList' => User::query()
                ->where('role', 'dosen')
                ->where('is_active', true)
                ->orderBy('name')
                ->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        TimKomite::query()->create($this->validated($request));

        return redirect()
            ->route('instansi.tim-komite.index')
            ->with('status', 'Anggota tim komite berhasil ditambahkan.');
    }

    public function update(Request $request, TimKomite $timKomite): RedirectResponse
    {
        $timKomite->update($this->validated($request, $timKomite));

        return redirect()
            ->route('instansi.tim-komite.index')
            ->with('status', 'Data anggota tim komite berhasil diperbarui.');
    }

    public function destroy(TimKomite $timKomite): RedirectResponse
    {
        $timKomite->delete();

        return redirect()
            ->route('instansi.tim-komite.index')
            ->with('status', 'Anggota tim komite berhasil dihapus.');
    }

    /**
     * @return array<string, mixed>
     */
    private function validated(Request $request, ?TimKomite $timKomite = null): array
    {
        return $request->validate([
            'user_id' => [
                'required',
                'integer',
                Rule::exists('users', 'id')->where(
                    fn ($query) => $query->where('role', 'dosen')->where('is_active', true),
                ),
                Rule::unique('tim_komite', 'user_id')->ignore($timKomite?->id),
            ],
            'jabatan' => ['required', Rule::in(array_keys(TimKomite::JABATAN))],
        ], [
            'user_id.required' => 'Nama dosen wajib dipilih.',
            'user_id.exists' => 'Dosen tidak ditemukan atau sudah tidak aktif.',
            'user_id.unique' => 'Dosen tersebut sudah terdaftar pada tim komite.',
            'jabatan.required' => 'Jabatan wajib dipilih.',
            'jabatan.in' => 'Jabatan tim komite tidak valid.',
        ], [
            'user_id' => 'nama dosen',
            'jabatan' => 'jabatan',
        ]);
    }
}
