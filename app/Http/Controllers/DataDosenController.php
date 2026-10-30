<?php

namespace App\Http\Controllers;

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
            'jabatan_fungsional' => $data['pangkat'],
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
            'jabatan_fungsional' => $data['pangkat'],
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
            ->with('status', 'Data Dosen berhasil dihapus.');
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
            'memiliki_jabatan' => ['required', Rule::in(['ya', 'tidak'])],
            'pangkat' => ['nullable', 'string', 'max:100', Rule::requiredIf($memilikiJabatan === 'ya')],
            'roles' => ['required', 'array', 'min:1'],
            'roles.*' => ['string', Rule::in(array_keys(User::ROLES))],
            'password' => [$item === null ? 'required' : 'nullable', 'string', 'min:8', 'confirmed'],
        ], [
            'name.required' => 'Nama Dosen wajib diisi.',
            'nidn.required' => 'NIDN wajib diisi.',
            'nidn.unique' => 'NIDN tersebut sudah terdaftar.',
            'memiliki_jabatan.required' => 'Pilih apakah dosen memiliki jabatan akademik.',
            'memiliki_jabatan.in' => 'Pilihan jabatan akademik tidak valid.',
            'pangkat.required' => 'Pangkat wajib diisi jika memiliki jabatan akademik.',
            'roles.required' => 'Pilih minimal satu role.',
            'roles.min' => 'Pilih minimal satu role.',
            'password.required' => 'Password wajib diisi.',
        ]);

        // Tanpa jabatan akademik, pangkat dikosongkan.
        $data['pangkat'] = $memilikiJabatan === 'ya' ? ($data['pangkat'] ?? null) : null;

        // Urutkan sesuai urutan role di form agar kolom role lama konsisten.
        $data['roles'] = array_values(array_filter(
            array_keys(User::ROLES),
            fn (string $role) => in_array($role, $data['roles'], true),
        ));

        return $data;
    }
}
