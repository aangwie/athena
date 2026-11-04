<?php

namespace App\Http\Controllers;

use App\Models\JabatanFungsional;
use App\Models\Kepangkatan;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class DataDosenController extends Controller
{
    public function index(): View
    {
        return view('data-dosen.index', [
            'items' => User::query()
                ->orderBy('name')
                ->get(),
        ]);
    }

    public function create(): View
    {
        return view('data-dosen.form', [
            'item' => null,
            'jabatanOptions' => JabatanFungsional::query()->orderBy('nama')->get(),
            'pangkatOptions' => Kepangkatan::query()->orderBy('nama')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);

        User::create([
            'role' => $data['roles'][0],
            'roles' => $data['roles'],
            'is_active' => true,
            'name' => $data['name'],
            'nidn' => $data['nidn'],
            'jabatan_fungsional' => $data['jabatan_fungsional'],
            'pangkat' => $data['pangkat'],
            'no_hp' => $data['no_hp'] ?? null,
            'password' => $data['password'],
        ]);

        return redirect()
            ->route('data-dosen.index')
            ->with('status', 'Data Dosen berhasil ditambahkan.');
    }

    public function edit(string $id): View
    {
        return view('data-dosen.form', [
            'item' => $this->find($id),
            'jabatanOptions' => JabatanFungsional::query()->orderBy('nama')->get(),
            'pangkatOptions' => Kepangkatan::query()->orderBy('nama')->get(),
        ]);
    }

    public function update(Request $request, string $id): RedirectResponse
    {
        $item = $this->find($id);
        $data = $this->validated($request, $item);

        $update = [
            'role' => $data['roles'][0],
            'roles' => $data['roles'],
            'name' => $data['name'],
            'nidn' => $data['nidn'],
            'jabatan_fungsional' => $data['jabatan_fungsional'],
            'pangkat' => $data['pangkat'],
            'no_hp' => $data['no_hp'] ?? null,
        ];

        if (! empty($data['password'])) {
            $update['password'] = $data['password'];
        }

        $item->update($update);

        return redirect()
            ->route('data-dosen.index')
            ->with('status', 'Data Dosen berhasil diperbarui.');
    }

    public function destroy(Request $request, string $id): RedirectResponse
    {
        $item = $this->find($id);

        abort_if($item->is($request->user()), 403, 'Anda tidak dapat menghapus akun sendiri.');

        $item->delete();

        return redirect()
            ->route('data-dosen.index')
            ->with('status', 'Data Dosen beserta seluruh data terkait dan berkas unggahannya berhasil dihapus.');
    }

    public function bulkDestroy(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'ids' => ['required', 'array', 'min:1'],
            'ids.*' => ['integer', Rule::exists('users', 'id')],
        ], [
            'ids.required' => 'Pilih minimal satu data dosen yang akan dihapus.',
            'ids.min' => 'Pilih minimal satu data dosen yang akan dihapus.',
        ]);

        // Akun yang sedang login tidak boleh ikut terhapus.
        $ids = collect($data['ids'])
            ->map(fn ($id) => (int) $id)
            ->reject(fn (int $id) => $id === $request->user()->id)
            ->unique();

        $jumlah = 0;

        foreach (User::query()->whereIn('id', $ids)->get() as $item) {
            $item->delete();
            $jumlah++;
        }

        return redirect()
            ->route('data-dosen.index')
            ->with('status', $jumlah === 0
                ? 'Tidak ada data dosen yang dihapus.'
                : "{$jumlah} data dosen beserta seluruh data terkait dan berkas unggahannya berhasil dihapus.");
    }

    private function find(string $id): User
    {
        return User::query()->findOrFail($id);
    }

    private function validated(Request $request, ?User $item = null): array
    {
        $uniqueNidn = Rule::unique('users', 'nidn');
        if ($item !== null) {
            $uniqueNidn->ignore($item->id);
        }

        $memilikiJabatan = $request->input('memiliki_jabatan');

        $data = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'nidn' => ['required', 'string', 'max:20', $uniqueNidn],
            'no_hp' => ['nullable', 'string', 'max:25', 'regex:/^[0-9+\-\s()]+$/'],
            'memiliki_jabatan' => ['required', Rule::in(['ya', 'tidak'])],
            'jabatan_fungsional' => ['nullable', 'string', 'max:100', Rule::requiredIf($memilikiJabatan === 'ya')],
            'pangkat' => ['nullable', 'string', 'max:100', Rule::requiredIf($memilikiJabatan === 'ya')],
            'roles' => ['required', 'array', 'min:1'],
            'roles.*' => ['string', Rule::in(array_keys(User::ROLES))],
            'password' => [$item === null ? 'required' : 'nullable', 'string', 'min:8', 'confirmed'],
        ], [
            'name.required' => 'Nama Dosen wajib diisi.',
            'nidn.required' => 'NIDN wajib diisi.',
            'nidn.unique' => 'NIDN tersebut sudah terdaftar.',
            'no_hp.regex' => 'Format nomor WhatsApp tidak valid.',
            'no_hp.max' => 'Nomor WhatsApp maksimal 25 karakter.',
            'memiliki_jabatan.required' => 'Pilih apakah dosen memiliki jabatan akademik.',
            'memiliki_jabatan.in' => 'Pilihan jabatan akademik tidak valid.',
            'jabatan_fungsional.required' => 'Jabatan Akademik wajib dipilih jika dosen memiliki jabatan akademik.',
            'pangkat.required' => 'Pangkat wajib diisi jika memiliki jabatan akademik.',
            'roles.required' => 'Pilih minimal satu role.',
            'roles.min' => 'Pilih minimal satu role.',
            'password.required' => 'Password wajib diisi.',
        ]);

        // Tanpa jabatan akademik, jabatan akademik & pangkat dikosongkan.
        $data['jabatan_fungsional'] = $memilikiJabatan === 'ya' ? ($data['jabatan_fungsional'] ?? null) : null;
        $data['pangkat'] = $memilikiJabatan === 'ya' ? ($data['pangkat'] ?? null) : null;

        // Urutkan sesuai urutan role di form agar kolom role lama konsisten.
        $data['roles'] = array_values(array_filter(
            array_keys(User::ROLES),
            fn (string $role) => in_array($role, $data['roles'], true),
        ));

        return $data;
    }
}
