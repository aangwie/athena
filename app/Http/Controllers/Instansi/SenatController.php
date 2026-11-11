<?php

namespace App\Http\Controllers\Instansi;

use App\Http\Controllers\Controller;
use App\Models\Instansi;
use App\Models\Senat;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class SenatController extends Controller
{
    public function index(Request $request): View
    {
        $items = Senat::query()
            ->with('user')
            ->get()
            ->sortBy(fn (Senat $item) => [$item->urutanJabatan(), $item->user?->name ?? ''])
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
            'instansi' => Instansi::query()->firstOrNew([]),
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
     * Simpan logo senat sebagai data URI base64 pada profil instansi.
     */
    public function simpanLogo(Request $request): RedirectResponse
    {
        $logo = $this->validatedLogo($request);

        // Baris instansi baru tetap memerlukan nama_instansi (kolom wajib isi);
        // sisanya dilengkapi admin melalui menu Data Instansi.
        $instansi = Instansi::query()->firstOrNew([], ['nama_instansi' => '']);

        $instansi->logo_senat_base64 = sprintf(
            'data:%s;base64,%s',
            $logo->getMimeType(),
            base64_encode($logo->get()),
        );

        $instansi->save();

        return redirect()
            ->route('instansi.senat.index')
            ->with('status', 'Logo senat berhasil disimpan.');
    }

    /**
     * @return array<string, mixed>
     */
    private function validated(Request $request, ?Senat $senat = null): array
    {
        $rules = [
            'user_id' => [
                'required',
                'integer',
                Rule::exists('users', 'id')->where(
                    fn ($query) => $query->where('role', 'dosen')->where('is_active', true),
                ),
                Rule::unique('senat', 'user_id')->ignore($senat?->id),
            ],
            'jabatan' => ['required', Rule::in(array_keys(Senat::JABATAN))],
        ];

        // Senat hanya boleh punya satu ketua.
        if ($request->input('jabatan') === 'ketua') {
            $rules['jabatan'][] = Rule::unique('senat', 'jabatan')->ignore($senat?->id);
        }

        return $request->validate($rules, [
            'user_id.required' => 'Nama dosen wajib dipilih.',
            'user_id.exists' => 'Dosen tidak ditemukan atau sudah tidak aktif.',
            'user_id.unique' => 'Dosen tersebut sudah terdaftar pada senat.',
            'jabatan.required' => 'Jabatan wajib dipilih.',
            'jabatan.in' => 'Jabatan senat tidak valid.',
            'jabatan.unique' => 'Ketua senat sudah ada. Hapus atau ubah jabatan ketua sebelumnya terlebih dahulu.',
        ], [
            'user_id' => 'nama dosen',
            'jabatan' => 'jabatan',
        ]);
    }

    private function validatedLogo(Request $request): UploadedFile
    {
        $data = $request->validate([
            'logo_senat' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:500'],
        ], [
            'logo_senat.required' => 'Logo senat wajib diunggah.',
            'logo_senat.image' => 'Logo senat harus berupa gambar.',
            'logo_senat.mimes' => 'Logo senat harus berformat JPG, PNG, atau WEBP.',
            'logo_senat.max' => 'Ukuran logo senat maksimal 500 KB.',
        ], [
            'logo_senat' => 'logo senat',
        ]);

        return $data['logo_senat'];
    }
}
