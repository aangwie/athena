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
            // Urutan tabel mengikuti urutan input admin (id menaik), bukan abjad nama
            // dan bukan pengelompokan jabatan, supaya susunan yang disusun admin tetap.
            ->orderBy('id')
            ->get();

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
        $rules = [
            'user_id' => [
                'required',
                'integer',
                Rule::exists('users', 'id')->where(
                    fn ($query) => $query->where('role', 'dosen')->where('is_active', true),
                ),
                Rule::unique('tim_komite', 'user_id')->ignore($timKomite?->id),
            ],
            'jabatan' => ['required', Rule::in(array_keys(TimKomite::JABATAN))],
        ];

        // Tim komite hanya boleh punya satu ketua.
        if ($request->input('jabatan') === 'ketua') {
            $rules['jabatan'][] = Rule::unique('tim_komite', 'jabatan')->ignore($timKomite?->id);
        }

        return $request->validate($rules, [
            'user_id.required' => 'Nama dosen wajib dipilih.',
            'user_id.exists' => 'Dosen tidak ditemukan atau sudah tidak aktif.',
            'user_id.unique' => 'Dosen tersebut sudah terdaftar pada tim komite.',
            'jabatan.required' => 'Jabatan wajib dipilih.',
            'jabatan.in' => 'Jabatan tim komite tidak valid.',
            'jabatan.unique' => 'Ketua tim komite sudah ada. Hapus atau ubah jabatan ketua sebelumnya terlebih dahulu.',
        ], [
            'user_id' => 'nama dosen',
            'jabatan' => 'jabatan',
        ]);
    }
}
